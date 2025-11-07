<?php
/**
 * Plugin Name: WC PDF Invoice Generator
 * Plugin URI: https://github.com/wp-xpert/tempitemp
 * Description: Generiert PDF-Rechnungen für WooCommerce Bestellungen und speichert sie im Plugin-Ordner.
 * Version: 1.0.0
 * Author: WP Xpert
 * Author URI: https://github.com/wp-xpert
 * Text Domain: wc-pdf-invoice-generator
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 *
 * @package WC_PDF_Invoice_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define( 'WC_PDF_INVOICE_GENERATOR_VERSION', '1.0.0' );
define( 'WC_PDF_INVOICE_GENERATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_PDF_INVOICE_GENERATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WC_PDF_INVOICE_GENERATOR_PDF_DIR', WC_PDF_INVOICE_GENERATOR_PLUGIN_DIR . 'pdfs/' );

/**
 * Check if WooCommerce is active
 */
if ( ! function_exists( 'is_plugin_active' ) ) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && ! function_exists( 'WC' ) ) {
    add_action( 'admin_notices', 'wc_pdf_invoice_generator_wc_missing_notice' );
    return;
}

/**
 * Admin notice if WooCommerce is not active
 */
function wc_pdf_invoice_generator_wc_missing_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e( 'WC PDF Invoice Generator benötigt WooCommerce, um zu funktionieren. Bitte installieren und aktivieren Sie WooCommerce.', 'wc-pdf-invoice-generator' ); ?></p>
    </div>
    <?php
}

/**
 * Main Plugin Class
 */
class WC_PDF_Invoice_Generator {

    /**
     * Plugin instance
     *
     * @var WC_PDF_Invoice_Generator
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return WC_PDF_Invoice_Generator
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once WC_PDF_INVOICE_GENERATOR_PLUGIN_DIR . 'includes/class-pdf-generator.php';
        require_once WC_PDF_INVOICE_GENERATOR_PLUGIN_DIR . 'includes/class-admin-menu.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add admin menu
        add_action( 'admin_menu', array( 'WC_PDF_Invoice_Admin_Menu', 'add_menu' ) );

        // Add order action button
        add_filter( 'woocommerce_order_actions', array( $this, 'add_order_action' ) );
        add_action( 'woocommerce_order_action_generate_pdf_invoice', array( $this, 'process_order_action' ) );

        // Create PDF directory on activation
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        if ( ! file_exists( WC_PDF_INVOICE_GENERATOR_PDF_DIR ) ) {
            wp_mkdir_p( WC_PDF_INVOICE_GENERATOR_PDF_DIR );

            // Add .htaccess for security
            $htaccess_content = "Options -Indexes\n<Files *.pdf>\n    Order Deny,Allow\n    Allow from all\n</Files>";
            file_put_contents( WC_PDF_INVOICE_GENERATOR_PDF_DIR . '.htaccess', $htaccess_content );

            // Add index.php for additional security
            file_put_contents( WC_PDF_INVOICE_GENERATOR_PDF_DIR . 'index.php', '<?php // Silence is golden' );
        }
    }

    /**
     * Add PDF generation action to order actions
     *
     * @param array $actions Order actions.
     * @return array
     */
    public function add_order_action( $actions ) {
        $actions['generate_pdf_invoice'] = __( 'PDF-Rechnung generieren', 'wc-pdf-invoice-generator' );
        return $actions;
    }

    /**
     * Process order action
     *
     * @param WC_Order $order Order object.
     */
    public function process_order_action( $order ) {
        $generator = new WC_PDF_Invoice_PDF_Generator();
        $pdf_path = $generator->generate_invoice( $order->get_id() );

        if ( $pdf_path ) {
            $order->add_order_note( sprintf( __( 'PDF-Rechnung wurde generiert: %s', 'wc-pdf-invoice-generator' ), basename( $pdf_path ) ) );
        }
    }
}

/**
 * Initialize the plugin
 */
function wc_pdf_invoice_generator_init() {
    return WC_PDF_Invoice_Generator::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'wc_pdf_invoice_generator_init' );
