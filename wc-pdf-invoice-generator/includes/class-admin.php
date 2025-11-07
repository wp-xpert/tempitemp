<?php
/**
 * Admin Interface
 *
 * @package WC_PDF_Invoice_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_PDF_IG_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_filter( 'woocommerce_order_actions', array( $this, 'add_order_action' ) );
        add_action( 'woocommerce_order_action_wc_pdf_ig_generate', array( $this, 'process_order_action' ) );
    }

    /**
     * Add admin menu
     */
    public function add_menu() {
        add_submenu_page(
            'woocommerce',
            'PDF Rechnungen',
            'PDF Rechnungen',
            'manage_woocommerce',
            'wc-pdf-invoice-generator',
            array( $this, 'render_page' )
        );
    }

    /**
     * Add order action
     */
    public function add_order_action( $actions ) {
        $actions['wc_pdf_ig_generate'] = 'PDF-Rechnung generieren';
        return $actions;
    }

    /**
     * Process order action
     */
    public function process_order_action( $order ) {
        $generator = new WC_PDF_IG_Generator();
        $result = $generator->generate( $order->get_id() );

        if ( $result ) {
            $order->add_order_note( 'PDF-Rechnung wurde generiert: ' . basename( $result ) );
        }
    }

    /**
     * Render admin page
     */
    public function render_page() {
        $message = '';
        $error = '';

        // Handle form submission
        if ( isset( $_POST['generate_pdf'] ) && check_admin_referer( 'wc_pdf_ig_generate', 'wc_pdf_ig_nonce' ) ) {
            $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

            if ( $order_id > 0 ) {
                $generator = new WC_PDF_IG_Generator();
                $result = $generator->generate( $order_id );

                if ( $result ) {
                    $message = 'PDF erfolgreich generiert: ' . basename( $result );
                } else {
                    $error = 'Fehler beim Generieren. Bestellung existiert nicht oder ist ungültig.';
                }
            } else {
                $error = 'Bitte geben Sie eine gültige Bestellnummer ein.';
            }
        }

        ?>
        <div class="wrap">
            <h1>WC PDF Rechnung Generator</h1>

            <?php if ( $message ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html( $message ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( $error ) : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html( $error ); ?></p>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 600px;">
                <h2>PDF für Bestellung generieren</h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'wc_pdf_ig_generate', 'wc_pdf_ig_nonce' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="order_id">Bestellnummer (Order ID)</label>
                            </th>
                            <td>
                                <input
                                    type="number"
                                    name="order_id"
                                    id="order_id"
                                    class="regular-text"
                                    min="1"
                                    required
                                    placeholder="z.B. 123"
                                >
                                <p class="description">
                                    Geben Sie die ID der Bestellung ein.
                                </p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button( 'PDF generieren', 'primary', 'generate_pdf' ); ?>
                </form>
            </div>

            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>Generierte PDFs</h2>
                <?php $this->render_pdf_list(); ?>
            </div>

            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>Information</h2>
                <table class="widefat">
                    <tr>
                        <td><strong>PDF-Speicherort:</strong></td>
                        <td><code><?php echo esc_html( WC_PDF_IG_PDF_DIR ); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Anzahl PDFs:</strong></td>
                        <td><?php echo absint( $this->count_pdfs() ); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Plugin-Version:</strong></td>
                        <td><?php echo esc_html( WC_PDF_IG_VERSION ); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render PDF list
     */
    private function render_pdf_list() {
        $pdf_files = glob( WC_PDF_IG_PDF_DIR . '*.pdf' );

        if ( empty( $pdf_files ) ) {
            echo '<p>Keine PDFs gefunden.</p>';
            return;
        }

        // Sort by modification time (newest first)
        usort( $pdf_files, function( $a, $b ) {
            return filemtime( $b ) - filemtime( $a );
        } );

        // Limit to 10 most recent
        $pdf_files = array_slice( $pdf_files, 0, 10 );

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Dateiname</th>';
        echo '<th>Größe</th>';
        echo '<th>Erstellt</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ( $pdf_files as $pdf_file ) {
            $filename = basename( $pdf_file );
            $filesize = size_format( filesize( $pdf_file ) );
            $filetime = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( $pdf_file ) );

            echo '<tr>';
            echo '<td><code>' . esc_html( $filename ) . '</code></td>';
            echo '<td>' . esc_html( $filesize ) . '</td>';
            echo '<td>' . esc_html( $filetime ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        if ( count( glob( WC_PDF_IG_PDF_DIR . '*.pdf' ) ) > 10 ) {
            echo '<p class="description">Zeige nur die 10 neuesten PDFs.</p>';
        }
    }

    /**
     * Count PDFs
     */
    private function count_pdfs() {
        $pdf_files = glob( WC_PDF_IG_PDF_DIR . '*.pdf' );
        return is_array( $pdf_files ) ? count( $pdf_files ) : 0;
    }
}
