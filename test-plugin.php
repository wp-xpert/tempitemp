<?php
/**
 * Test Script - Plugin lokal testen
 *
 * Dieses Script testet die PDF-Generierung ohne WordPress
 */

// Simuliere WordPress-Funktionen
define('ABSPATH', __DIR__ . '/');

function get_bloginfo($what) {
    $info = [
        'name' => 'Test Shop GmbH',
        'description' => 'Ihr Online-Shop für alles'
    ];
    return $info[$what] ?? '';
}

function sanitize_file_name($name) {
    return preg_replace('/[^a-z0-9._-]/i', '-', $name);
}

// Mock WooCommerce Order
class WC_Order {
    public function get_id() { return 123; }
    public function get_order_number() { return '123'; }
    public function get_billing_first_name() { return 'Max'; }
    public function get_billing_last_name() { return 'Mustermann'; }
    public function get_billing_address_1() { return 'Musterstraße 1'; }
    public function get_billing_address_2() { return ''; }
    public function get_billing_postcode() { return '12345'; }
    public function get_billing_city() { return 'Musterstadt'; }
    public function get_billing_country() { return 'Deutschland'; }
    public function get_shipping_first_name() { return ''; }
    public function get_shipping_last_name() { return ''; }
    public function get_shipping_address_1() { return ''; }
    public function get_shipping_address_2() { return ''; }
    public function get_shipping_postcode() { return ''; }
    public function get_shipping_city() { return ''; }
    public function get_shipping_country() { return ''; }
    public function get_formatted_shipping_address() { return ''; }
    public function get_date_created() {
        return new class {
            public function date($format) {
                return date($format);
            }
        };
    }
    public function get_payment_method_title() { return 'Vorkasse'; }
    public function get_currency() { return 'EUR'; }
    public function get_items() {
        return [
            new class {
                public function get_name() { return 'Test Produkt 1'; }
                public function get_quantity() { return 2; }
            },
            new class {
                public function get_name() { return 'Test Produkt 2'; }
                public function get_quantity() { return 1; }
            }
        ];
    }
    public function get_item_subtotal($item, $x, $y) { return 49.99; }
    public function get_line_subtotal($item, $x, $y) { return 49.99 * $item->get_quantity(); }
    public function get_subtotal() { return 149.97; }
    public function get_shipping_total() { return 5.99; }
    public function get_total_tax() { return 29.63; }
    public function get_total() { return 185.59; }
}

function wc_get_order($id) {
    return new WC_Order();
}

// Lade Plugin
define('WC_PDF_IG_VERSION', '1.0.0');
define('WC_PDF_IG_DIR', __DIR__ . '/wc-pdf-invoice-generator/');
define('WC_PDF_IG_URL', 'http://localhost/');
define('WC_PDF_IG_PDF_DIR', WC_PDF_IG_DIR . 'pdfs/');

require_once WC_PDF_IG_DIR . 'includes/fpdf.php';
require_once WC_PDF_IG_DIR . 'includes/class-pdf-generator.php';

// Erstelle PDF-Verzeichnis
if (!file_exists(WC_PDF_IG_PDF_DIR)) {
    mkdir(WC_PDF_IG_PDF_DIR, 0755, true);
}

// Generiere Test-PDF
echo "Generiere Test-PDF...\n";
$generator = new WC_PDF_IG_Generator();
$result = $generator->generate(123);

if ($result) {
    echo "✓ PDF erfolgreich generiert!\n";
    echo "Speicherort: $result\n";
    echo "Dateigröße: " . round(filesize($result) / 1024, 2) . " KB\n";
} else {
    echo "✗ Fehler beim Generieren der PDF\n";
}
