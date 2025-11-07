<?php
/**
 * PDF Generator Class
 *
 * @package WC_PDF_Invoice_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include TCPDF library if not already loaded
if ( ! class_exists( 'TCPDF' ) ) {
    require_once WC_PDF_INVOICE_GENERATOR_PLUGIN_DIR . 'includes/tcpdf/tcpdf.php';
}

/**
 * PDF Generator Class
 */
class WC_PDF_Invoice_PDF_Generator {

    /**
     * Generate PDF invoice for an order
     *
     * @param int $order_id Order ID.
     * @return string|false PDF file path on success, false on failure.
     */
    public function generate_invoice( $order_id ) {
        try {
            $order = wc_get_order( $order_id );

            if ( ! $order ) {
                error_log( 'WC PDF Invoice Generator: Order not found - ID: ' . $order_id );
                return false;
            }

            // Create PDF
            $pdf = $this->create_pdf_instance();

            // Set document information
            $pdf->SetCreator( 'WC PDF Invoice Generator' );
            $pdf->SetAuthor( get_bloginfo( 'name' ) );
            $pdf->SetTitle( sprintf( __( 'Rechnung #%s', 'wc-pdf-invoice-generator' ), $order->get_order_number() ) );

            // Add a page
            $pdf->AddPage();

            // Get HTML content
            $html = $this->get_invoice_html( $order );

            // Write HTML
            $pdf->writeHTML( $html, true, false, true, false, '' );

            // Generate filename
            $filename = $this->get_filename( $order );
            $filepath = WC_PDF_INVOICE_GENERATOR_PDF_DIR . $filename;

            // Save PDF
            $pdf->Output( $filepath, 'F' );

            return $filepath;

        } catch ( Exception $e ) {
            error_log( 'WC PDF Invoice Generator Error: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Create TCPDF instance
     *
     * @return TCPDF
     */
    private function create_pdf_instance() {
        // Create new PDF document
        $pdf = new TCPDF( 'P', 'mm', 'A4', true, 'UTF-8', false );

        // Remove default header/footer
        $pdf->setPrintHeader( false );
        $pdf->setPrintFooter( false );

        // Set margins
        $pdf->SetMargins( 15, 15, 15 );
        $pdf->SetAutoPageBreak( true, 15 );

        // Set font
        $pdf->SetFont( 'helvetica', '', 10 );

        return $pdf;
    }

    /**
     * Get invoice HTML content
     *
     * @param WC_Order $order Order object.
     * @return string
     */
    private function get_invoice_html( $order ) {
        $template_path = WC_PDF_INVOICE_GENERATOR_PLUGIN_DIR . 'templates/invoice-template.php';

        if ( file_exists( $template_path ) ) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        // Fallback: Generate HTML programmatically
        return $this->get_default_invoice_html( $order );
    }

    /**
     * Get default invoice HTML
     *
     * @param WC_Order $order Order object.
     * @return string
     */
    private function get_default_invoice_html( $order ) {
        $html = '<style>
            h1 { color: #333; font-size: 24px; margin-bottom: 20px; }
            h2 { color: #666; font-size: 18px; margin-top: 20px; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th { background-color: #f5f5f5; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }
            td { padding: 8px; border-bottom: 1px solid #eee; }
            .total-row { font-weight: bold; background-color: #f9f9f9; }
            .company-info { margin-bottom: 30px; }
            .customer-info { margin-bottom: 20px; }
            .info-section { display: inline-block; width: 48%; vertical-align: top; }
        </style>';

        $html .= '<div class="company-info">';
        $html .= '<h1>' . esc_html( get_bloginfo( 'name' ) ) . '</h1>';
        $html .= '<p>' . esc_html( get_bloginfo( 'description' ) ) . '</p>';
        $html .= '</div>';

        $html .= '<h1>Rechnung #' . esc_html( $order->get_order_number() ) . '</h1>';

        $html .= '<div class="customer-info">';
        $html .= '<div class="info-section">';
        $html .= '<h2>Rechnungsadresse</h2>';
        $html .= '<p>' . wp_kses_post( $order->get_formatted_billing_address() ) . '</p>';
        $html .= '</div>';

        if ( $order->get_formatted_shipping_address() ) {
            $html .= '<div class="info-section">';
            $html .= '<h2>Lieferadresse</h2>';
            $html .= '<p>' . wp_kses_post( $order->get_formatted_shipping_address() ) . '</p>';
            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= '<p><strong>Bestelldatum:</strong> ' . esc_html( $order->get_date_created()->date( 'd.m.Y H:i' ) ) . '</p>';
        $html .= '<p><strong>Zahlungsmethode:</strong> ' . esc_html( $order->get_payment_method_title() ) . '</p>';

        // Order items table
        $html .= '<h2>Bestellte Artikel</h2>';
        $html .= '<table>';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Produkt</th>';
        $html .= '<th style="text-align: center;">Menge</th>';
        $html .= '<th style="text-align: right;">Preis</th>';
        $html .= '<th style="text-align: right;">Gesamt</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            $html .= '<tr>';
            $html .= '<td>' . esc_html( $item->get_name() ) . '</td>';
            $html .= '<td style="text-align: center;">' . esc_html( $item->get_quantity() ) . '</td>';
            $html .= '<td style="text-align: right;">' . wc_price( $order->get_item_subtotal( $item, false, false ) ) . '</td>';
            $html .= '<td style="text-align: right;">' . wc_price( $order->get_line_subtotal( $item, false, false ) ) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '<tfoot>';

        // Subtotal
        $html .= '<tr>';
        $html .= '<td colspan="3" style="text-align: right;"><strong>Zwischensumme:</strong></td>';
        $html .= '<td style="text-align: right;">' . wc_price( $order->get_subtotal() ) . '</td>';
        $html .= '</tr>';

        // Shipping
        if ( $order->get_shipping_total() > 0 ) {
            $html .= '<tr>';
            $html .= '<td colspan="3" style="text-align: right;"><strong>Versand:</strong></td>';
            $html .= '<td style="text-align: right;">' . wc_price( $order->get_shipping_total() ) . '</td>';
            $html .= '</tr>';
        }

        // Tax
        if ( $order->get_total_tax() > 0 ) {
            $html .= '<tr>';
            $html .= '<td colspan="3" style="text-align: right;"><strong>MwSt.:</strong></td>';
            $html .= '<td style="text-align: right;">' . wc_price( $order->get_total_tax() ) . '</td>';
            $html .= '</tr>';
        }

        // Total
        $html .= '<tr class="total-row">';
        $html .= '<td colspan="3" style="text-align: right; font-size: 14px;"><strong>Gesamt:</strong></td>';
        $html .= '<td style="text-align: right; font-size: 14px;"><strong>' . wc_price( $order->get_total() ) . '</strong></td>';
        $html .= '</tr>';

        $html .= '</tfoot>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Get filename for PDF
     *
     * @param WC_Order $order Order object.
     * @return string
     */
    private function get_filename( $order ) {
        $filename = sprintf(
            'invoice-%s-%s.pdf',
            $order->get_order_number(),
            $order->get_date_created()->date( 'Y-m-d' )
        );

        return sanitize_file_name( $filename );
    }

    /**
     * Get PDF path by order ID
     *
     * @param int $order_id Order ID.
     * @return string|false
     */
    public function get_pdf_path( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }

        $filename = $this->get_filename( $order );
        $filepath = WC_PDF_INVOICE_GENERATOR_PDF_DIR . $filename;

        if ( file_exists( $filepath ) ) {
            return $filepath;
        }

        return false;
    }

    /**
     * Delete PDF by order ID
     *
     * @param int $order_id Order ID.
     * @return bool
     */
    public function delete_pdf( $order_id ) {
        $filepath = $this->get_pdf_path( $order_id );

        if ( $filepath && file_exists( $filepath ) ) {
            return unlink( $filepath );
        }

        return false;
    }
}
