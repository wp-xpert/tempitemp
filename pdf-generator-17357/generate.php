<?php
/**
 * PDF Generator für Order #17357
 *
 * Minimalistisch - nutzt woocommerce-pdf-invoice Plugin
 * Speichert PDFs im WordPress uploads Ordner
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
if (empty($invoice_number)) {
    $invoice_number = $order->get_order_number();
}
echo '✓ Rechnungsnummer: <strong>' . $invoice_number . '</strong><br>';
echo '<hr>';

// Upload-Ordner erstellen
$upload_dir = wp_upload_dir();
$pdf_dir = $upload_dir['basedir'] . '/invoices';
$pdf_url = $upload_dir['baseurl'] . '/invoices';

if (!file_exists($pdf_dir)) {
    wp_mkdir_p($pdf_dir);
    // .htaccess für Sicherheit
    file_put_contents($pdf_dir . '/.htaccess', 'Options -Indexes');
}

echo '✓ PDF-Ordner: ' . $pdf_dir . '<br>';
echo '✓ Beschreibbar: ' . (is_writable($pdf_dir) ? '<strong style="color:green">Ja</strong>' : '<strong style="color:red">Nein</strong>') . '<br>';
echo '<hr>';

// PDF generieren
try {
    // Nutze Dompdf direkt
    $dompdf_autoload = $wc_pdf_plugin . '/lib/dompdf/autoload.inc.php';

    if (!file_exists($dompdf_autoload)) {
        die('❌ Dompdf nicht gefunden: ' . $dompdf_autoload);
    }

    require_once($dompdf_autoload);
    echo '✓ Dompdf geladen<br>';

    // Generiere HTML
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; color: #333; }
        h1 { color: #0073aa; border-bottom: 3px solid #0073aa; padding-bottom: 10px; margin-bottom: 20px; }
        h2 { color: #555; font-size: 14pt; margin-top: 20px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #0073aa; color: white; padding: 12px 8px; text-align: left; }
        td { padding: 10px 8px; border-bottom: 1px solid #ddd; }
        tbody tr:nth-child(even) { background: #f9f9f9; }
        .grand-total { background: #0073aa; color: white; font-size: 14pt; }
    </style>
</head>
<body>
    <h1>RECHNUNG ' . htmlspecialchars($invoice_number) . '</h1>

    <p><strong>Bestellnummer:</strong> ' . htmlspecialchars($order->get_order_number()) . '<br>
    <strong>Datum:</strong> ' . $order->get_date_created()->date('d.m.Y H:i') . '<br>
    <strong>Zahlungsmethode:</strong> ' . htmlspecialchars($order->get_payment_method_title()) . '</p>

    <h2>Rechnungsadresse</h2>
    <p>' . nl2br(htmlspecialchars($order->get_formatted_billing_address())) . '<br>
    <strong>E-Mail:</strong> ' . htmlspecialchars($order->get_billing_email()) . '</p>';

    if ($order->get_formatted_shipping_address()) {
        $html .= '<h2>Lieferadresse</h2>
        <p>' . nl2br(htmlspecialchars($order->get_formatted_shipping_address())) . '</p>';
    }

    $html .= '<h2>Bestellte Artikel</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 50%">Produkt</th>
                <th style="width: 15%; text-align: center">Menge</th>
                <th style="width: 17.5%; text-align: right">Einzelpreis</th>
                <th style="width: 17.5%; text-align: right">Gesamt</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($order->get_items() as $item) {
        $product_name = $item->get_name();
        $quantity = $item->get_quantity();
        $subtotal = $order->get_item_subtotal($item, false, false);
        $total = $order->get_line_subtotal($item, false, false);

        $html .= '<tr>
            <td>' . htmlspecialchars($product_name) . '</td>
            <td style="text-align: center">' . $quantity . '</td>
            <td style="text-align: right">' . number_format($subtotal, 2, ',', '.') . ' ' . $order->get_currency() . '</td>
            <td style="text-align: right">' . number_format($total, 2, ',', '.') . ' ' . $order->get_currency() . '</td>
        </tr>';
    }

    $html .= '</tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right"><strong>Zwischensumme:</strong></td>
                <td style="text-align: right">' . number_format($order->get_subtotal(), 2, ',', '.') . ' ' . $order->get_currency() . '</td>
            </tr>';

    if ($order->get_shipping_total() > 0) {
        $html .= '<tr>
            <td colspan="3" style="text-align: right"><strong>Versand:</strong></td>
            <td style="text-align: right">' . number_format($order->get_shipping_total(), 2, ',', '.') . ' ' . $order->get_currency() . '</td>
        </tr>';
    }

    if ($order->get_total_tax() > 0) {
        $html .= '<tr>
            <td colspan="3" style="text-align: right"><strong>MwSt.:</strong></td>
            <td style="text-align: right">' . number_format($order->get_total_tax(), 2, ',', '.') . ' ' . $order->get_currency() . '</td>
        </tr>';
    }

    $html .= '<tr class="grand-total">
            <td colspan="3" style="text-align: right; padding: 15px 8px"><strong>GESAMT:</strong></td>
            <td style="text-align: right; padding: 15px 8px"><strong>' . number_format($order->get_total(), 2, ',', '.') . ' ' . $order->get_currency() . '</strong></td>
        </tr>
        </tfoot>
    </table>

    <p style="margin-top: 40px; color: #666;"><em>Vielen Dank für Ihren Einkauf!</em></p>
</body>
</html>';

    echo '✓ HTML generiert (' . strlen($html) . ' Zeichen)<br>';

    // PDF erstellen
    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdf_output = $dompdf->output();
    echo '✓ PDF gerendert (' . strlen($pdf_output) . ' Bytes)<br>';

    // Dateiname
    $filename = 'invoice-' . sanitize_file_name($invoice_number) . '.pdf';
    $filepath = $pdf_dir . '/' . $filename;

    echo '✓ Ziel: ' . $filepath . '<br>';

    // Speichern
    $bytes_written = file_put_contents($filepath, $pdf_output);

    if ($bytes_written !== false && file_exists($filepath)) {
        $filesize = round(filesize($filepath) / 1024, 2);
        echo '<hr>';
        echo '<p style="color: green; font-size: 20pt; font-weight: bold;">✅ PDF ERFOLGREICH GENERIERT!</p>';
        echo '<table style="background: #f0f0f0; padding: 20px; border: 3px solid #0073aa; margin: 20px 0;">
            <tr><td><strong>Dateiname:</strong></td><td>' . htmlspecialchars($filename) . '</td></tr>
            <tr><td><strong>Größe:</strong></td><td>' . $filesize . ' KB</td></tr>
            <tr><td><strong>Bytes:</strong></td><td>' . number_format($bytes_written) . '</td></tr>
            <tr><td><strong>Speicherort:</strong></td><td><code>' . htmlspecialchars($filepath) . '</code></td></tr>
        </table>';

        // Download-Link
        $download_url = $pdf_url . '/' . $filename;
        echo '<p><a href="' . esc_url($download_url) . '" target="_blank" style="background: #0073aa; color: white; padding: 20px 40px; text-decoration: none; display: inline-block; margin-top: 20px; border-radius: 5px; font-size: 16pt; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">📄 PDF JETZT HERUNTERLADEN</a></p>';

        // Auto-Download nach 1 Sekunde
        echo '<script>setTimeout(function(){ window.open("' . esc_url($download_url) . '", "_blank"); }, 1000);</script>';

        echo '<p style="color: #666; margin-top: 20px;"><em>Die PDF wird automatisch in 1 Sekunde geöffnet...</em></p>';
    } else {
        echo '<p style="color: red; font-size: 16pt;">❌ Fehler beim Speichern!</p>';
        echo '<p><strong>Bytes geschrieben:</strong> ' . var_export($bytes_written, true) . '</p>';
        echo '<p><strong>Datei existiert:</strong> ' . (file_exists($filepath) ? 'Ja' : 'Nein') . '</p>';
        echo '<p><strong>Ordner beschreibbar:</strong> ' . (is_writable($pdf_dir) ? 'Ja' : 'Nein') . '</p>';

        $last_error = error_get_last();
        if ($last_error) {
            echo '<p><strong>PHP Error:</strong> ' . htmlspecialchars($last_error['message']) . '</p>';
        }
    }

} catch (Exception $e) {
    echo '<p style="color: red; font-size: 16pt;">❌ Exception: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre style="background: #f5f5f5; padding: 10px; border-left: 3px solid red;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '<hr>';
echo '<p><small>Generiert am: ' . date('d.m.Y H:i:s') . ' | Order ID: ' . $order_id . '</small></p>';
