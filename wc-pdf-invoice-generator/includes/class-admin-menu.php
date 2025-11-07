<?php
/**
 * Admin Menu Class
 *
 * @package WC_PDF_Invoice_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Menu Class
 */
class WC_PDF_Invoice_Admin_Menu {

    /**
     * Add admin menu
     */
    public static function add_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'PDF Rechnungen', 'wc-pdf-invoice-generator' ),
            __( 'PDF Rechnungen', 'wc-pdf-invoice-generator' ),
            'manage_woocommerce',
            'wc-pdf-invoice-generator',
            array( __CLASS__, 'render_page' )
        );
    }

    /**
     * Render admin page
     */
    public static function render_page() {
        // Handle form submission
        $message = '';
        $error = '';

        if ( isset( $_POST['generate_pdf'] ) && check_admin_referer( 'wc_pdf_generate', 'wc_pdf_nonce' ) ) {
            $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

            if ( $order_id > 0 ) {
                $generator = new WC_PDF_Invoice_PDF_Generator();
                $pdf_path = $generator->generate_invoice( $order_id );

                if ( $pdf_path ) {
                    $message = sprintf(
                        __( 'PDF-Rechnung wurde erfolgreich generiert: %s', 'wc-pdf-invoice-generator' ),
                        '<strong>' . basename( $pdf_path ) . '</strong>'
                    );
                } else {
                    $error = __( 'Fehler beim Generieren der PDF-Rechnung. Überprüfen Sie, ob die Bestellung existiert.', 'wc-pdf-invoice-generator' );
                }
            } else {
                $error = __( 'Bitte geben Sie eine gültige Bestellnummer ein.', 'wc-pdf-invoice-generator' );
            }
        }

        // Handle bulk generation
        if ( isset( $_POST['generate_bulk_pdf'] ) && check_admin_referer( 'wc_pdf_generate_bulk', 'wc_pdf_bulk_nonce' ) ) {
            $status = isset( $_POST['order_status'] ) ? sanitize_text_field( $_POST['order_status'] ) : 'completed';
            $limit = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 10;

            $generated_count = self::generate_bulk_pdfs( $status, $limit );

            if ( $generated_count > 0 ) {
                $message = sprintf(
                    __( '%d PDF-Rechnungen wurden erfolgreich generiert.', 'wc-pdf-invoice-generator' ),
                    $generated_count
                );
            } else {
                $error = __( 'Keine Bestellungen gefunden oder Fehler beim Generieren.', 'wc-pdf-invoice-generator' );
            }
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WC PDF Rechnung Generator', 'wc-pdf-invoice-generator' ); ?></h1>

            <?php if ( $message ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo wp_kses_post( $message ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( $error ) : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html( $error ); ?></p>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 800px;">
                <h2><?php esc_html_e( 'PDF für einzelne Bestellung generieren', 'wc-pdf-invoice-generator' ); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'wc_pdf_generate', 'wc_pdf_nonce' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="order_id"><?php esc_html_e( 'Bestellnummer / Order ID', 'wc-pdf-invoice-generator' ); ?></label>
                            </th>
                            <td>
                                <input type="number" name="order_id" id="order_id" class="regular-text" min="1" required>
                                <p class="description">
                                    <?php esc_html_e( 'Geben Sie die ID der Bestellung ein, für die Sie eine PDF-Rechnung generieren möchten.', 'wc-pdf-invoice-generator' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button( __( 'PDF generieren', 'wc-pdf-invoice-generator' ), 'primary', 'generate_pdf' ); ?>
                </form>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php esc_html_e( 'Bulk PDF-Generierung', 'wc-pdf-invoice-generator' ); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'wc_pdf_generate_bulk', 'wc_pdf_bulk_nonce' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="order_status"><?php esc_html_e( 'Bestellstatus', 'wc-pdf-invoice-generator' ); ?></label>
                            </th>
                            <td>
                                <select name="order_status" id="order_status">
                                    <option value="completed"><?php esc_html_e( 'Abgeschlossen', 'wc-pdf-invoice-generator' ); ?></option>
                                    <option value="processing"><?php esc_html_e( 'In Bearbeitung', 'wc-pdf-invoice-generator' ); ?></option>
                                    <option value="on-hold"><?php esc_html_e( 'Wartend', 'wc-pdf-invoice-generator' ); ?></option>
                                    <option value="any"><?php esc_html_e( 'Alle', 'wc-pdf-invoice-generator' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="limit"><?php esc_html_e( 'Anzahl', 'wc-pdf-invoice-generator' ); ?></label>
                            </th>
                            <td>
                                <input type="number" name="limit" id="limit" class="small-text" value="10" min="1" max="100">
                                <p class="description">
                                    <?php esc_html_e( 'Maximale Anzahl der zu generierenden PDFs (1-100).', 'wc-pdf-invoice-generator' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button( __( 'Bulk PDFs generieren', 'wc-pdf-invoice-generator' ), 'secondary', 'generate_bulk_pdf' ); ?>
                </form>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php esc_html_e( 'Generierte PDFs', 'wc-pdf-invoice-generator' ); ?></h2>
                <?php self::render_pdf_list(); ?>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php esc_html_e( 'Informationen', 'wc-pdf-invoice-generator' ); ?></h2>
                <p>
                    <strong><?php esc_html_e( 'PDF-Speicherort:', 'wc-pdf-invoice-generator' ); ?></strong><br>
                    <code><?php echo esc_html( WC_PDF_INVOICE_GENERATOR_PDF_DIR ); ?></code>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Anzahl gespeicherter PDFs:', 'wc-pdf-invoice-generator' ); ?></strong>
                    <?php echo absint( self::count_pdfs() ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Version:', 'wc-pdf-invoice-generator' ); ?></strong>
                    <?php echo esc_html( WC_PDF_INVOICE_GENERATOR_VERSION ); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Generate PDFs for multiple orders
     *
     * @param string $status Order status.
     * @param int    $limit  Number of orders to process.
     * @return int Number of PDFs generated.
     */
    private static function generate_bulk_pdfs( $status, $limit ) {
        $args = array(
            'limit'  => $limit,
            'return' => 'ids',
        );

        if ( 'any' !== $status ) {
            $args['status'] = $status;
        }

        $order_ids = wc_get_orders( $args );
        $generator = new WC_PDF_Invoice_PDF_Generator();
        $count = 0;

        foreach ( $order_ids as $order_id ) {
            $pdf_path = $generator->generate_invoice( $order_id );
            if ( $pdf_path ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Render list of generated PDFs
     */
    private static function render_pdf_list() {
        $pdf_dir = WC_PDF_INVOICE_GENERATOR_PDF_DIR;
        $pdf_files = glob( $pdf_dir . '*.pdf' );

        if ( empty( $pdf_files ) ) {
            echo '<p>' . esc_html__( 'Keine PDFs gefunden.', 'wc-pdf-invoice-generator' ) . '</p>';
            return;
        }

        // Sort by modification time (newest first)
        usort( $pdf_files, function( $a, $b ) {
            return filemtime( $b ) - filemtime( $a );
        });

        // Limit to 20 most recent files
        $pdf_files = array_slice( $pdf_files, 0, 20 );

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__( 'Dateiname', 'wc-pdf-invoice-generator' ) . '</th>';
        echo '<th>' . esc_html__( 'Größe', 'wc-pdf-invoice-generator' ) . '</th>';
        echo '<th>' . esc_html__( 'Erstellt', 'wc-pdf-invoice-generator' ) . '</th>';
        echo '<th>' . esc_html__( 'Aktionen', 'wc-pdf-invoice-generator' ) . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ( $pdf_files as $pdf_file ) {
            $filename = basename( $pdf_file );
            $filesize = size_format( filesize( $pdf_file ) );
            $filetime = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( $pdf_file ) );
            $file_url = WC_PDF_INVOICE_GENERATOR_PLUGIN_URL . 'pdfs/' . $filename;

            echo '<tr>';
            echo '<td><code>' . esc_html( $filename ) . '</code></td>';
            echo '<td>' . esc_html( $filesize ) . '</td>';
            echo '<td>' . esc_html( $filetime ) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url( $file_url ) . '" class="button button-small" target="_blank">' . esc_html__( 'Ansehen', 'wc-pdf-invoice-generator' ) . '</a> ';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        if ( count( glob( $pdf_dir . '*.pdf' ) ) > 20 ) {
            echo '<p class="description">' . esc_html__( 'Es werden nur die 20 neuesten PDFs angezeigt.', 'wc-pdf-invoice-generator' ) . '</p>';
        }
    }

    /**
     * Count PDFs in directory
     *
     * @return int
     */
    private static function count_pdfs() {
        $pdf_files = glob( WC_PDF_INVOICE_GENERATOR_PDF_DIR . '*.pdf' );
        return is_array( $pdf_files ) ? count( $pdf_files ) : 0;
    }
}
