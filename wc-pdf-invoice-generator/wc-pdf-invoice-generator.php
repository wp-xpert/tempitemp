<?php
/**
 * Plugin Name: WC PDF Invoice Generator
 * Plugin URI: https://github.com/wp-xpert/tempitemp
 * Description: Generiert PDF-Rechnungen für WooCommerce Bestellungen und speichert sie im Plugin-Ordner.
 * Version: 1.0.0
 * Author: WP Xpert
 * Author URI: https://github.com/wp-xpert
 * Text Domain: wc-pdf-invoice-generator
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 *
 * @package WC_PDF_Invoice_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'WC_PDF_IG_VERSION', '1.0.0' );
define( 'WC_PDF_IG_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_PDF_IG_URL', plugin_dir_url( __FILE__ ) );
define( 'WC_PDF_IG_PDF_DIR', WC_PDF_IG_DIR . 'pdfs/' );

/**
 * Plugin activation
 */
function wc_pdf_ig_activate() {
    // Create PDF directory
    if ( ! file_exists( WC_PDF_IG_PDF_DIR ) ) {
        wp_mkdir_p( WC_PDF_IG_PDF_DIR );

        // Security: .htaccess
        file_put_contents(
            WC_PDF_IG_PDF_DIR . '.htaccess',
            "Options -Indexes\n<Files *.pdf>\n    Require all granted\n</Files>"
        );

        // Security: index.php
        file_put_contents( WC_PDF_IG_PDF_DIR . 'index.php', '<?php // Silence is golden' );
    }
}
register_activation_hook( __FILE__, 'wc_pdf_ig_activate' );

/**
 * Check if WooCommerce is active
 */
function wc_pdf_ig_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>';
            echo esc_html__( 'WC PDF Invoice Generator benötigt WooCommerce.', 'wc-pdf-invoice-generator' );
            echo '</p></div>';
        } );
        return false;
    }
    return true;
}

/**
 * Initialize plugin
 */
function wc_pdf_ig_init() {
    if ( ! wc_pdf_ig_check_woocommerce() ) {
        return;
    }

    // Load PDF library
    require_once WC_PDF_IG_DIR . 'includes/fpdf.php';
    require_once WC_PDF_IG_DIR . 'includes/class-pdf-generator.php';
    require_once WC_PDF_IG_DIR . 'includes/class-admin.php';

    // Initialize admin
    new WC_PDF_IG_Admin();
}
add_action( 'plugins_loaded', 'wc_pdf_ig_init' );
