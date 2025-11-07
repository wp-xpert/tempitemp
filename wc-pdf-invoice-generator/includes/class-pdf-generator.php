<?php
/**
 * PDF Generator
 *
 * @package WC_PDF_Invoice_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_PDF_IG_Generator extends FPDF {

    private $order;

    /**
     * Generate invoice PDF for order
     *
     * @param int $order_id Order ID
     * @return string|false PDF path or false on error
     */
    public function generate( $order_id ) {
        $this->order = wc_get_order( $order_id );

        if ( ! $this->order ) {
            return false;
        }

        // Create PDF
        $this->AddPage();
        $this->SetFont( 'helvetica', '', 10 );

        // Add content
        $this->add_header_section();
        $this->add_order_info();
        $this->add_items_table();
        $this->add_totals();

        // Save
        $filename = $this->get_filename();
        $filepath = WC_PDF_IG_PDF_DIR . $filename;

        $this->Output( $filepath, 'F' );

        return file_exists( $filepath ) ? $filepath : false;
    }

    /**
     * Add header section
     */
    private function add_header_section() {
        $this->SetFont( 'helvetica', 'B', 20 );
        $this->Cell( 0, 10, get_bloginfo( 'name' ), 0, 1 );

        $this->SetFont( 'helvetica', '', 10 );
        $this->Cell( 0, 5, get_bloginfo( 'description' ), 0, 1 );
        $this->Ln( 10 );

        $this->SetFont( 'helvetica', 'B', 16 );
        $this->Cell( 0, 10, 'Rechnung #' . $this->order->get_order_number(), 0, 1 );
        $this->Ln( 5 );
    }

    /**
     * Add order info
     */
    private function add_order_info() {
        $this->SetFont( 'helvetica', 'B', 11 );
        $this->Cell( 90, 6, 'Rechnungsadresse', 0, 0 );

        if ( $this->order->get_formatted_shipping_address() ) {
            $this->Cell( 90, 6, 'Lieferadresse', 0, 1 );
        } else {
            $this->Ln();
        }

        $this->SetFont( 'helvetica', '', 9 );

        // Billing address
        $billing = $this->format_address(
            $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name(),
            $this->order->get_billing_address_1(),
            $this->order->get_billing_address_2(),
            $this->order->get_billing_postcode(),
            $this->order->get_billing_city(),
            $this->order->get_billing_country()
        );

        $y_start = $this->GetY();
        $this->MultiCell( 85, 5, $billing, 0, 'L' );

        // Shipping address
        if ( $this->order->get_formatted_shipping_address() ) {
            $this->SetXY( 105, $y_start );
            $shipping = $this->format_address(
                $this->order->get_shipping_first_name() . ' ' . $this->order->get_shipping_last_name(),
                $this->order->get_shipping_address_1(),
                $this->order->get_shipping_address_2(),
                $this->order->get_shipping_postcode(),
                $this->order->get_shipping_city(),
                $this->order->get_shipping_country()
            );
            $this->MultiCell( 85, 5, $shipping, 0, 'L' );
        }

        $this->Ln( 10 );

        // Order meta
        $this->SetFont( 'helvetica', '', 9 );
        $this->Cell( 45, 5, 'Bestelldatum:', 0, 0 );
        $this->Cell( 0, 5, $this->order->get_date_created()->date( 'd.m.Y H:i' ), 0, 1 );

        $this->Cell( 45, 5, 'Zahlungsmethode:', 0, 0 );
        $this->Cell( 0, 5, $this->order->get_payment_method_title(), 0, 1 );

        $this->Ln( 10 );
    }

    /**
     * Add items table
     */
    private function add_items_table() {
        $this->SetFont( 'helvetica', 'B', 9 );
        $this->SetFillColor( 240, 240, 240 );

        // Table header
        $this->Cell( 90, 7, 'Produkt', 1, 0, 'L', true );
        $this->Cell( 25, 7, 'Menge', 1, 0, 'C', true );
        $this->Cell( 35, 7, 'Einzelpreis', 1, 0, 'R', true );
        $this->Cell( 40, 7, 'Gesamt', 1, 1, 'R', true );

        // Table items
        $this->SetFont( 'helvetica', '', 9 );
        foreach ( $this->order->get_items() as $item ) {
            $product_name = $item->get_name();
            $quantity = $item->get_quantity();
            $subtotal = $this->order->get_item_subtotal( $item, false, false );
            $total = $this->order->get_line_subtotal( $item, false, false );

            $this->Cell( 90, 6, $this->truncate( $product_name, 50 ), 1, 0, 'L' );
            $this->Cell( 25, 6, $quantity, 1, 0, 'C' );
            $this->Cell( 35, 6, $this->format_price( $subtotal ), 1, 0, 'R' );
            $this->Cell( 40, 6, $this->format_price( $total ), 1, 1, 'R' );
        }
    }

    /**
     * Add totals
     */
    private function add_totals() {
        $this->Ln( 5 );

        // Subtotal
        $this->SetFont( 'helvetica', '', 9 );
        $this->Cell( 150, 6, 'Zwischensumme:', 0, 0, 'R' );
        $this->Cell( 40, 6, $this->format_price( $this->order->get_subtotal() ), 0, 1, 'R' );

        // Shipping
        if ( $this->order->get_shipping_total() > 0 ) {
            $this->Cell( 150, 6, 'Versand:', 0, 0, 'R' );
            $this->Cell( 40, 6, $this->format_price( $this->order->get_shipping_total() ), 0, 1, 'R' );
        }

        // Tax
        if ( $this->order->get_total_tax() > 0 ) {
            $this->Cell( 150, 6, 'MwSt.:', 0, 0, 'R' );
            $this->Cell( 40, 6, $this->format_price( $this->order->get_total_tax() ), 0, 1, 'R' );
        }

        // Total
        $this->SetFont( 'helvetica', 'B', 11 );
        $this->Cell( 150, 8, 'GESAMT:', 0, 0, 'R' );
        $this->Cell( 40, 8, $this->format_price( $this->order->get_total() ), 0, 1, 'R' );
    }

    /**
     * Format address
     */
    private function format_address( $name, $addr1, $addr2, $zip, $city, $country ) {
        $parts = array_filter( array( $name, $addr1, $addr2, $zip . ' ' . $city, $country ) );
        return implode( "\n", $parts );
    }

    /**
     * Format price
     */
    private function format_price( $price ) {
        return number_format( $price, 2, ',', '.' ) . ' ' . $this->order->get_currency();
    }

    /**
     * Truncate text
     */
    private function truncate( $text, $length ) {
        return strlen( $text ) > $length ? substr( $text, 0, $length ) . '...' : $text;
    }

    /**
     * Get filename
     */
    private function get_filename() {
        return sanitize_file_name( sprintf(
            'invoice-%s-%s.pdf',
            $this->order->get_order_number(),
            $this->order->get_date_created()->date( 'Y-m-d' )
        ) );
    }

    /**
     * Get PDF path for order
     */
    public static function get_pdf_path( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }

        $filename = sanitize_file_name( sprintf(
            'invoice-%s-%s.pdf',
            $order->get_order_number(),
            $order->get_date_created()->date( 'Y-m-d' )
        ) );

        $filepath = WC_PDF_IG_PDF_DIR . $filename;

        return file_exists( $filepath ) ? $filepath : false;
    }
}
