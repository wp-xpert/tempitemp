<?php
/**
 * PDF Generator für Order #17357
 *
 * Minimalistisch - nutzt woocommerce-pdf-invoice Plugin
 * Aufruf: /wp-content/plugins/pdf-generator-17357/generate.php
 */

// WordPress laden
require_once(dirname(__FILE__) . '/../../../wp-load.php');

// Prüfungen
if (!function_exists('wc_get_order')) die('WooCommerce nicht aktiv!');

// Order ID
$order_id = 17357;
$order = wc_get_order($order_id);
if (!$order) die('Bestellung #' . $order_id . ' nicht gefunden!');

// E-Mails blockieren
add_filter('woocommerce_email_enabled', '__return_false', 999);
add_filter('pre_wp_mail', '__return_false', 999);

echo '<h1>PDF Generator - Order #' . $order_id . '</h1>';
echo '<p>Kunde: ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</p>';
echo '<p>E-Mail: ' . $order->get_billing_email() . '</p>';
echo '<hr>';

// Lade WooCommerce PDF Invoice Klassen
$wc_pdf_plugin = WP_PLUGIN_DIR . '/woocommerce-pdf-invoice';

if (!is_dir($wc_pdf_plugin)) {
    die('❌ WooCommerce PDF Invoice Plugin nicht gefunden!');
}

// Lade benötigte Klassen
require_once($wc_pdf_plugin . '/classes/class-pdf-functions-class.php');
require_once($wc_pdf_plugin . '/classes/helper-functions-class.php');

// Setze Rechnungsnummer
if (!$order->get_meta('_invoice_number_display', true)) {
    WC_pdf_functions::set_invoice_number($order_id);
    WC_pdf_functions::set_invoice_date($order_id);
    echo '✓ Rechnungsnummer gesetzt<br>';
}

$invoice_number = $order->get_meta('_invoice_number_display', true);
echo '✓ Rechnungsnummer: <strong>' . ($invoice_number ?: 'N/A') . '</strong><br>';
echo '<hr>';

// PDF generieren
try {
    // Finde die originale WC_send_pdf Klasse im Backup oder im Original-Plugin
    $original_send_pdf = null;

    // Suche in allen möglichen Orten
    $possible_locations = [
        $wc_pdf_plugin . '/classes/class-pdf-send-pdf-class.php.backup',
        $wc_pdf_plugin . '/classes/class-send-pdf.php',
    ];

    // Lies die originale Datei, falls vorhanden
    foreach ($possible_locations as $loc) {
        if (file_exists($loc)) {
            $original_send_pdf = $loc;
            break;
        }
    }

    // Fallback: Lade Template-basierte Generierung
    if (!class_exists('WC_send_pdf')) {
        // Nutze Dompdf direkt
        $dompdf_autoload = $wc_pdf_plugin . '/lib/dompdf/autoload.inc.php';

        if (file_exists($dompdf_autoload)) {
            require_once($dompdf_autoload);

            echo '✓ Dompdf geladen<br>';

            // Generiere HTML
            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12pt; }
        h1 { color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f5f5f5; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .total { font-weight: bold; font-size: 14pt; }
    </style>
</head>
<body>
    <h1>Rechnung ' . ($invoice_number ?: $order->get_order_number()) . '</h1>

    <p><strong>Bestellnummer:</strong> ' . $order->get_order_number() . '<br>
    <strong>Datum:</strong> ' . $order->get_date_created()->date('d.m.Y') . '</p>

    <h2>Kunde</h2>
    <p>' . nl2br($order->get_formatted_billing_address()) . '<br>
    E-Mail: ' . $order->get_billing_email() . '</p>

    <h2>Bestellte Artikel</h2>
    <table>
        <thead>
            <tr>
                <th>Produkt</th>
                <th style="text-align:center">Menge</th>
                <th style="text-align:right">Einzelpreis</th>
                <th style="text-align:right">Gesamt</th>
            </tr>
        </thead>
        <tbody>';

            foreach ($order->get_items() as $item) {
                $html .= '<tr>
                    <td>' . $item->get_name() . '</td>
                    <td style="text-align:center">' . $item->get_quantity() . '</td>
                    <td style="text-align:right">' . wc_price($order->get_item_subtotal($item, false, false)) . '</td>
                    <td style="text-align:right">' . wc_price($order->get_line_subtotal($item, false, false)) . '</td>
                </tr>';
            }

            $html .= '</tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right"><strong>Zwischensumme:</strong></td>
                <td style="text-align:right">' . wc_price($order->get_subtotal()) . '</td>
            </tr>';

            if ($order->get_shipping_total() > 0) {
                $html .= '<tr>
                    <td colspan="3" style="text-align:right"><strong>Versand:</strong></td>
                    <td style="text-align:right">' . wc_price($order->get_shipping_total()) . '</td>
                </tr>';
            }

            if ($order->get_total_tax() > 0) {
                $html .= '<tr>
                    <td colspan="3" style="text-align:right"><strong>MwSt.:</strong></td>
                    <td style="text-align:right">' . wc_price($order->get_total_tax()) . '</td>
                </tr>';
            }

            $html .= '<tr>
                <td colspan="3" style="text-align:right" class="total">GESAMT:</td>
                <td style="text-align:right" class="total">' . wc_price($order->get_total()) . '</td>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top:40px;"><small>Vielen Dank für Ihren Einkauf!</small></p>
</body>
</html>';

            // PDF erstellen
            $dompdf = new Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Dateiname
            $filename = 'invoice-' . sanitize_file_name($invoice_number ?: $order_id) . '.pdf';
            $filepath = __DIR__ . '/' . $filename;

            // Speichern
            file_put_contents($filepath, $dompdf->output());

            if (file_exists($filepath)) {
                $filesize = round(filesize($filepath) / 1024, 2);
                echo '<p style="color:green;font-size:16pt;"><strong>✅ PDF erfolgreich generiert!</strong></p>';
                echo '<p><strong>Datei:</strong> ' . $filename . '<br>';
                echo '<strong>Größe:</strong> ' . $filesize . ' KB<br>';
                echo '<strong>Pfad:</strong> ' . $filepath . '</p>';

                // Download-Link
                $download_url = plugins_url($filename, __FILE__);
                echo '<p><a href="' . $download_url . '" target="_blank" style="background:#0073aa;color:white;padding:15px 30px;text-decoration:none;display:inline-block;margin-top:20px;border-radius:5px;font-size:14pt;">📄 PDF herunterladen</a></p>';

                // Direkter Download starten
                echo '<script>window.open("' . $download_url . '", "_blank");</script>';
            } else {
                echo '<p style="color:red;">❌ Fehler beim Speichern der PDF!</p>';
            }

        } else {
            echo '<p style="color:red;">❌ Dompdf nicht gefunden!</p>';
            echo '<p>Pfad: ' . $dompdf_autoload . '</p>';
        }
    }

} catch (Exception $e) {
    echo '<p style="color:red;">❌ Fehler: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '<hr>';
echo '<p><small>Generiert am: ' . date('d.m.Y H:i:s') . '</small></p>';
