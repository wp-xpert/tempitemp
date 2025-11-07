<?php
/**
 * PDF Generator für Order #17357
 *
 * Aufruf per URL: /wp-content/plugins/pdf-generator-17357/generate.php
 */

// WordPress laden
$wp_load = dirname(__FILE__) . '/../../../wp-load.php';
if (!file_exists($wp_load)) {
    die('WordPress nicht gefunden!');
}
require_once($wp_load);

// Prüfe ob WooCommerce aktiv ist
if (!function_exists('wc_get_order')) {
    die('WooCommerce nicht aktiv!');
}

// Order ID
$order_id = 17357;

// Hole Bestellung
$order = wc_get_order($order_id);
if (!$order) {
    die('Bestellung #' . $order_id . ' nicht gefunden!');
}

echo '<h1>PDF Generator für Order #' . $order_id . '</h1>';
echo '<p>Bestellnummer: ' . $order->get_order_number() . '</p>';
echo '<p>Kunde: ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</p>';
echo '<hr>';

// E-Mails blockieren
add_filter('woocommerce_email_enabled', '__return_false', 999);
add_filter('pre_wp_mail', '__return_false', 999);

// Setze Rechnungsnummer falls nicht vorhanden
if (!class_exists('WC_pdf_functions')) {
    $functions_file = WP_PLUGIN_DIR . '/woocommerce-pdf-invoice/classes/class-pdf-functions-class.php';
    if (file_exists($functions_file)) {
        require_once($functions_file);
    }
}

if (class_exists('WC_pdf_functions')) {
    if (!$order->get_meta('_invoice_number_display', true)) {
        WC_pdf_functions::set_invoice_number($order_id);
        echo '<p>✓ Rechnungsnummer gesetzt</p>';
    }
    $invoice_number = $order->get_meta('_invoice_number_display', true);
    echo '<p>Rechnungsnummer: ' . $invoice_number . '</p>';
} else {
    echo '<p style="color:orange;">⚠ WC_pdf_functions Klasse nicht gefunden - nutze Order-Nummer</p>';
    $invoice_number = $order->get_order_number();
}

// Lade WC_send_pdf Klasse
if (!class_exists('WC_send_pdf')) {
    $send_pdf_file = WP_PLUGIN_DIR . '/woocommerce-pdf-invoice/classes/class-pdf-send-pdf-class.php';

    // Die Datei wurde überschrieben, also nutzen wir die helper Funktionen
    $helper_file = WP_PLUGIN_DIR . '/woocommerce-pdf-invoice/classes/helper-functions-class.php';
    if (file_exists($helper_file)) {
        require_once($helper_file);
    }
}

// PDF generieren mit eigener Methode (da WC_send_pdf überschrieben wurde)
try {
    // Prüfe ob PDF-Klassen verfügbar sind
    $pdf_created = false;
    $pdf_path = '';

    // Versuche 1: Über WooCommerce PDF Invoice Plugin
    if (class_exists('WC_send_pdf') && method_exists('WC_send_pdf', 'get_woocommerce_pdf_invoice')) {
        $pdf_path = WC_send_pdf::get_woocommerce_pdf_invoice($order, 'customer_completed_order', false);
        if ($pdf_path && file_exists($pdf_path)) {
            $pdf_created = true;
            echo '<p>✓ PDF generiert über WC_send_pdf</p>';
        }
    }

    // Versuche 2: Über direkte Template-Generierung
    if (!$pdf_created) {
        $template_file = WP_PLUGIN_DIR . '/woocommerce-pdf-invoice/lib/dompdf/autoload.inc.php';
        if (file_exists($template_file)) {
            require_once($template_file);

            // Generiere HTML für PDF
            $html = '<h1>Rechnung ' . $invoice_number . '</h1>';
            $html .= '<p>Bestellung: ' . $order->get_order_number() . '</p>';
            $html .= '<p>Datum: ' . $order->get_date_created()->date('d.m.Y') . '</p>';
            $html .= '<h2>Kunde</h2>';
            $html .= '<p>' . $order->get_formatted_billing_address() . '</p>';

            $html .= '<h2>Bestellte Artikel</h2>';
            $html .= '<table border="1" cellpadding="5" style="width:100%;border-collapse:collapse;">';
            $html .= '<tr><th>Produkt</th><th>Menge</th><th>Preis</th></tr>';

            foreach ($order->get_items() as $item) {
                $html .= '<tr>';
                $html .= '<td>' . $item->get_name() . '</td>';
                $html .= '<td>' . $item->get_quantity() . '</td>';
                $html .= '<td>' . wc_price($order->get_line_total($item)) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';
            $html .= '<p><strong>Gesamt: ' . wc_price($order->get_total()) . '</strong></p>';

            // PDF erstellen
            $dompdf = new Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // PDF speichern
            $filename = 'invoice-' . sanitize_file_name($invoice_number) . '.pdf';
            $pdf_path = __DIR__ . '/' . $filename;
            file_put_contents($pdf_path, $dompdf->output());

            if (file_exists($pdf_path)) {
                $pdf_created = true;
                echo '<p>✓ PDF generiert mit Dompdf</p>';
            }
        }
    }

    // Speichere PDF im Plugin-Ordner
    if ($pdf_created && $pdf_path && file_exists($pdf_path)) {
        $target_filename = 'invoice-' . sanitize_file_name($invoice_number) . '-' . $order_id . '.pdf';
        $target_path = __DIR__ . '/' . $target_filename;

        // Wenn es ein temp-Pfad ist, kopiere es, sonst verschiebe es
        if (strpos($pdf_path, sys_get_temp_dir()) !== false || strpos($pdf_path, '/tmp/') !== false) {
            if (copy($pdf_path, $target_path)) {
                @unlink($pdf_path);
                echo '<p style="color:green;"><strong>✅ PDF erfolgreich gespeichert!</strong></p>';
                echo '<p>Datei: ' . $target_filename . '</p>';
                echo '<p>Pfad: ' . $target_path . '</p>';
                echo '<p>Größe: ' . round(filesize($target_path) / 1024, 2) . ' KB</p>';

                // Download-Link
                $plugin_url = plugins_url('/' . $target_filename, __FILE__);
                echo '<p><a href="' . $plugin_url . '" target="_blank" style="background:#0073aa;color:white;padding:10px 20px;text-decoration:none;display:inline-block;margin-top:10px;">📄 PDF herunterladen</a></p>';
            } else {
                echo '<p style="color:red;">✗ Fehler beim Kopieren der PDF</p>';
            }
        } else {
            // Ist bereits im richtigen Ordner oder anderer Pfad
            if (rename($pdf_path, $target_path)) {
                echo '<p style="color:green;"><strong>✅ PDF erfolgreich gespeichert!</strong></p>';
                echo '<p>Datei: ' . $target_filename . '</p>';
                echo '<p>Pfad: ' . $target_path . '</p>';
                echo '<p>Größe: ' . round(filesize($target_path) / 1024, 2) . ' KB</p>';

                $plugin_url = plugins_url('/' . $target_filename, __FILE__);
                echo '<p><a href="' . $plugin_url . '" target="_blank" style="background:#0073aa;color:white;padding:10px 20px;text-decoration:none;display:inline-block;margin-top:10px;">📄 PDF herunterladen</a></p>';
            } else {
                echo '<p style="color:red;">✗ Fehler beim Verschieben der PDF</p>';
            }
        }
    } else {
        echo '<p style="color:red;"><strong>✗ PDF konnte nicht generiert werden!</strong></p>';
        echo '<p>Bitte stelle sicher, dass das WooCommerce PDF Invoice Plugin korrekt installiert ist.</p>';
    }

} catch (Exception $e) {
    echo '<p style="color:red;">Fehler: ' . $e->getMessage() . '</p>';
}

echo '<hr>';
echo '<p><small>Generiert am: ' . date('d.m.Y H:i:s') . '</small></p>';
