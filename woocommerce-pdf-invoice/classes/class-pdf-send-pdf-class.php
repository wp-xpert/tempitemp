<?php
/**
 * Plugin Name: PDF Batch Generator
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function() {
    add_submenu_page( 'tools.php', 'PDF Generator', 'PDF Generator', 'manage_options', 'pdf-gen', 'pdf_gen_page' );
});

function pdf_gen_page() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Keine Berechtigung!' );
    
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . '/pdfs';
    
    if ( ! file_exists( $target_dir ) ) {
        wp_mkdir_p( $target_dir );
    }
    
    if ( isset( $_POST['generate'] ) && check_admin_referer( 'gen_pdf' ) ) {
        
        // BLOCKIERE E-MAILS
        add_filter( 'woocommerce_email_enabled', '__return_false', 999 );
        add_filter( 'pre_wp_mail', '__return_false', 999 );
        
        // Lade WC_send_pdf Klasse falls nicht vorhanden
        if ( ! class_exists( 'WC_send_pdf' ) ) {
            $pdf_class_file = WP_PLUGIN_DIR . '/woocommerce-pdf-invoice/classes/class-pdf-send-pdf-class.php';
            
            if ( file_exists( $pdf_class_file ) ) {
                $content = file_get_contents( $pdf_class_file );
                $content = preg_replace( '/^.*wp-load\.php.*$/m', '// removed', $content );
                $content = preg_replace( '/<\?php\s*/i', '', $content );
                $content = str_replace( '?>', '', $content );
                
                $temp = sys_get_temp_dir() . '/wc-pdf-' . md5( $pdf_class_file ) . '.php';
                file_put_contents( $temp, '<?php ' . $content );
                include_once( $temp );
                @unlink( $temp );
            }
        }
        
        echo '<div style="background:white;padding:20px;margin:20px 0;border:1px solid #ccc;">';
        
        // Hole ALLE Bestellungen
        $all_orders = wc_get_orders( array( 'limit' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
        
        // Filtere nur die mit gültiger E-Mail
        $orders = array();
        foreach ( $all_orders as $order ) {
            $email = $order->get_billing_email();
            if ( ! empty( $email ) && is_email( $email ) ) {
                $orders[] = $order;
                if ( count( $orders ) >= 10 ) break; // Nur erste 10
            }
        }
        
        echo '<p>Bestellungen mit gültiger E-Mail gefunden: ' . count( $orders ) . '</p>';
        
        foreach ( $orders as $order ) {
            $order_id = $order->get_id();
            $order_number = $order->get_order_number();
            
            echo '<p><strong>#' . $order_number . '</strong> - ';
            
            try {
                
                if ( ! $order->get_meta( '_invoice_number_display', true ) ) {
                    WC_pdf_functions::set_invoice_number( $order_id );
                }
                
                $invoice_number = $order->get_meta( '_invoice_number_display', true ) ?: $order_number;
                
                $pdf_path = WC_send_pdf::get_woocommerce_pdf_invoice( $order, 'customer_completed_order', false );
                
                if ( $pdf_path && file_exists( $pdf_path ) ) {
                    $filename = 'invoice-' . sanitize_file_name( $invoice_number ) . '.pdf';
                    $target = $target_dir . '/' . $filename;
                    
                    if ( copy( $pdf_path, $target ) ) {
                        echo '<span style="color:green">✓ ' . $filename . '</span>';
                        @unlink( $pdf_path );
                    } else {
                        echo '<span style="color:red">✗ Kopieren fehlgeschlagen</span>';
                    }
                } else {
                    echo '<span style="color:red">✗ Kein PDF</span>';
                }
                
            } catch ( Exception $e ) {
                echo '<span style="color:red">✗ ' . $e->getMessage() . '</span>';
            }
            
            echo '</p>';
            flush();
        }
        
        echo '<p style="margin-top:20px;"><strong>✅ Fertig!</strong></p>';
        echo '</div>';
    }
    
    ?>
    <div class="wrap">
        <h1>PDF Generator</h1>
        <form method="post">
            <?php wp_nonce_field( 'gen_pdf' ); ?>
            <button type="submit" name="generate" class="button button-primary">PDFs generieren</button>
        </form>
    </div>
    <?php
}