<?php
/**
 * PDF Generator für Order #17357
 *
 * Nutzt das Original-Template von woocommerce-pdf-invoice Plugin
 * Speichert PDFs im WordPress uploads Ordner
 * Aufruf: /wp-content/plugins/pdf-generator-17357/generate.php
 */

// WordPress laden
require_once(dirname(__FILE__) . '/../../../wp-load.php');

// Prüfungen
if (!function_exists('wc_get_order')) die('WooCommerce nicht aktiv!');

// E-Mails blockieren
add_filter('woocommerce_email_enabled', '__return_false', 999);
add_filter('pre_wp_mail', '__return_false', 999);

// Zeige Interface wenn keine Order-ID übergeben wurde
if (!isset($_GET['order_id']) && !isset($_GET['month'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>PDF Generator</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            h1 { color: #0073aa; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
            .box { background: #f9f9f9; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px; }
            .button { background: #0073aa; color: white; padding: 12px 30px; text-decoration: none; display: inline-block; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; }
            .button:hover { background: #005a87; }
            select, input { padding: 10px; font-size: 16px; margin: 10px 0; width: 100%; max-width: 300px; }
            label { display: block; margin-top: 15px; font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>PDF Rechnungsgenerator</h1>

        <div class="box">
            <h2>Einzelne Rechnung generieren</h2>
            <form method="get">
                <label>Order ID:</label>
                <input type="number" name="order_id" value="17357" required>
                <br><br>
                <button type="submit" class="button">PDF generieren</button>
            </form>
        </div>

        <div class="box">
            <h2>Alle Rechnungen eines Monats (2025)</h2>
            <form method="get">
                <label>Monat wählen:</label>
                <select name="month" required>
                    <option value="">-- Monat wählen --</option>
                    <option value="2025-01">Januar 2025</option>
                    <option value="2025-02">Februar 2025</option>
                    <option value="2025-03">März 2025</option>
                    <option value="2025-04">April 2025</option>
                    <option value="2025-05">Mai 2025</option>
                    <option value="2025-06">Juni 2025</option>
                    <option value="2025-07">Juli 2025</option>
                    <option value="2025-08">August 2025</option>
                    <option value="2025-09">September 2025</option>
                    <option value="2025-10">Oktober 2025</option>
                    <option value="2025-11">November 2025</option>
                    <option value="2025-12">Dezember 2025</option>
                </select>
                <br><br>
                <button type="submit" class="button">Alle PDFs des Monats generieren</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Bulk-Generierung für einen Monat
if (isset($_GET['month'])) {
    $month = sanitize_text_field($_GET['month']);
    list($year, $month_num) = explode('-', $month);

    // Erstelle Start- und Enddatum
    $start_timestamp = strtotime($year . '-' . $month_num . '-01 00:00:00');
    $end_timestamp = strtotime(date('Y-m-t 23:59:59', $start_timestamp));

    echo '<h1>PDF Generator - ' . date('F Y', $start_timestamp) . '</h1>';
    echo '<p>Zeitraum: ' . date('d.m.Y', $start_timestamp) . ' bis ' . date('d.m.Y', $end_timestamp) . '</p>';
    echo '<hr>';

    // Debug: Zeige Datum-Range
    echo '<p><small>Debug - Start: ' . date('Y-m-d H:i:s', $start_timestamp) . ' | Ende: ' . date('Y-m-d H:i:s', $end_timestamp) . '</small></p>';

    // Hole alle Bestellungen des Monats - verwende korrekte Status-Codes
    $orders = wc_get_orders(array(
        'limit' => -1,
        'date_created' => '>=' . $start_timestamp,
        'date_created_before' => '<=' . $end_timestamp,
        'status' => array('pending', 'on-hold', 'processing', 'completed'), // Alle Status inkl. pending
        'orderby' => 'date',
        'order' => 'ASC'
    ));

    echo '<p><strong>' . count($orders) . ' Bestellungen gefunden</strong></p>';

    // Debug: Zeige alle verfügbaren Bestellungen
    if (count($orders) === 0) {
        echo '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 15px 0;">';
        echo '<p><strong>Debug-Info: Keine Bestellungen gefunden. Prüfe alle Bestellungen...</strong></p>';

        // Hole ALLE Bestellungen ohne Filter
        $all_orders = wc_get_orders(array(
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        echo '<p>Letzte 10 Bestellungen im System:</p><ul>';
        foreach ($all_orders as $test_order) {
            $order_date = $test_order->get_date_created();
            echo '<li>Order #' . $test_order->get_id() . ' - Status: ' . $test_order->get_status() . ' - Datum: ' . $order_date->date('d.m.Y H:i:s') . '</li>';
        }
        echo '</ul></div>';
    }

    echo '<hr>';

    $order_ids = array();
    foreach ($orders as $order) {
        $order_ids[] = $order->get_id();
    }
} else {
    // Einzelne Order-ID
    $order_id = intval($_GET['order_id']);
    $order = wc_get_order($order_id);
    if (!$order) die('Bestellung #' . $order_id . ' nicht gefunden!');

    echo '<h1>PDF Generator - Order #' . $order_id . '</h1>';
    echo '<p>Kunde: ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</p>';
    echo '<p>E-Mail: ' . $order->get_billing_email() . '</p>';
    echo '<hr>';

    $order_ids = array($order_id);
}

// Lade WooCommerce PDF Invoice Klassen
$wc_pdf_plugin = WP_PLUGIN_DIR . '/woocommerce-pdf-invoice';

if (!is_dir($wc_pdf_plugin)) {
    die('❌ WooCommerce PDF Invoice Plugin nicht gefunden!');
}

// Lade benötigte Klassen
require_once($wc_pdf_plugin . '/classes/class-pdf-functions-class.php');
require_once($wc_pdf_plugin . '/classes/helper-functions-class.php');

// Lade Plugin-Einstellungen
$settings = get_option('woocommerce_pdf_invoice_settings');
if (empty($settings)) {
    die('❌ Plugin-Einstellungen nicht gefunden!');
}
echo '✓ Plugin-Einstellungen geladen<br>';

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

// Zähler
$success_count = 0;
$error_count = 0;

// Loop durch alle Order IDs
foreach ($order_ids as $current_order_id) {
    $order = wc_get_order($current_order_id);
    if (!$order) {
        echo '<p style="color: red;">❌ Order #' . $current_order_id . ' nicht gefunden!</p>';
        $error_count++;
        continue;
    }

    echo '<div style="border: 1px solid #ddd; padding: 15px; margin: 15px 0; background: #fff;">';
    echo '<h3>Order #' . $current_order_id . ' - ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</h3>';

    // Setze Rechnungsnummer
    if (!$order->get_meta('_invoice_number_display', true)) {
        WC_pdf_functions::set_invoice_number($current_order_id);
        WC_pdf_functions::set_invoice_date($current_order_id);
        echo '✓ Rechnungsnummer gesetzt<br>';
    }

    $invoice_number = $order->get_meta('_invoice_number_display', true);
    if (empty($invoice_number)) {
        $invoice_number = $order->get_order_number();
    }
    echo '✓ Rechnungsnummer: <strong>' . $invoice_number . '</strong><br>';

// PDF generieren
try {
    // Lade Template
    $template_file = $wc_pdf_plugin . '/templates/template.php';
    if (!file_exists($template_file)) {
        die('❌ Template nicht gefunden: ' . $template_file);
    }

    $template = file_get_contents($template_file);
    echo '✓ Template geladen<br>';

    // Baue Platzhalter-Ersetzungen
    $replacements = array();

    // PDF Font Family
    $pdf_font_family = isset($settings['pdf_font_family']) ? $settings['pdf_font_family'] : 'dejavusanscondensed';
    $replacements['[[PDFFONTFAMILY]]'] = $pdf_font_family;

    // Logo
    $logo_html = '';
    if (!empty($settings['logo_file'])) {
        $logo_html = '<img src="' . esc_url($settings['logo_file']) . '" alt="Logo" />';
    }
    $replacements['[[PDFLOGO]]'] = $logo_html;

    // Company Info - Verwende wp_kses statt esc_html um <br> zu erlauben
    $allowed_html = array('br' => array());
    $replacements['[[PDFCOMPANYNAME]]'] = isset($settings['pdf_company_name']) ? wp_kses($settings['pdf_company_name'], $allowed_html) : '';
    $replacements['[[PDFCOMPANYDETAILS]]'] = isset($settings['pdf_company_details']) ? wp_kses($settings['pdf_company_details'], $allowed_html) : '';

    // Invoice Number
    $replacements['[[PDFINVOICENUMHEADING]]'] = __('Invoice Number', 'woocommerce-pdf-invoice');
    $replacements['[[PDFINVOICENUM]]'] = '<strong>' . esc_html($invoice_number) . '</strong>';

    // Order Number
    $replacements['[[PDFORDERENUMHEADING]]'] = __('Order Number', 'woocommerce-pdf-invoice');
    $replacements['[[PDFORDERENUM]]'] = esc_html($order->get_order_number());

    // Dates
    $invoice_date = $order->get_meta('_invoice_date', true);
    if (empty($invoice_date)) {
        $date_format = isset($settings['pdf_date_format']) ? $settings['pdf_date_format'] : 'd.m.Y';
        $invoice_date = $order->get_date_created()->date($date_format);
    }
    $replacements['[[PDFINVOICEDATEHEADING]]'] = __('Invoice Date', 'woocommerce-pdf-invoice');
    $replacements['[[PDFINVOICEDATE]]'] = esc_html($invoice_date);
    $replacements['[[PDFORDERDATEHEADING]]'] = __('Order Date', 'woocommerce-pdf-invoice');
    $replacements['[[PDFORDERDATE]]'] = $order->get_date_created()->date('d.m.Y');

    // Payment & Shipping Method
    $replacements['[[PDFINVOICE_PAYMETHOD_HEADING]]'] = __('Payment Method', 'woocommerce-pdf-invoice');
    $replacements['[[PDFINVOICEPAYMENTMETHOD]]'] = esc_html($order->get_payment_method_title());
    $replacements['[[PDFINVOICE_SHIPMETHOD_HEADING]]'] = __('Shipping Method', 'woocommerce-pdf-invoice');
    $replacements['[[PDFSHIPPINGMETHOD]]'] = esc_html($order->get_shipping_method());
    $replacements['[[PDFSHIPMENTTRACKING]]'] = '';

    // Billing Details - Behalte <br> Tags für Adressen
    $replacements['[[PDFINVOICE_BILLINGDETAILS_HEADING]]'] = __('Billing Address', 'woocommerce-pdf-invoice');
    $billing_address = $order->get_formatted_billing_address();
    $replacements['[[PDFBILLINGADDRESS]]'] = wp_kses($billing_address, $allowed_html);
    $replacements['[[PDFBILLINGTEL]]'] = $order->get_billing_phone() ? 'Tel: ' . esc_html($order->get_billing_phone()) : '';
    $replacements['[[PDFBILLINGEMAIL]]'] = esc_html($order->get_billing_email());
    $replacements['[[PDFBILLINGVATNUMBER]]'] = '';

    // Shipping Details - Behalte <br> Tags für Adressen
    $replacements['[[PDFINVOICE_SHIPPINGDETAILS_HEADING]]'] = __('Shipping Address', 'woocommerce-pdf-invoice');
    $shipping_address = $order->get_formatted_shipping_address();
    $replacements['[[PDFSHIPPINGADDRESS]]'] = $shipping_address ? wp_kses($shipping_address, $allowed_html) : __('Same as billing', 'woocommerce-pdf-invoice');

    // Footer - Company Registration
    $registered_name = isset($settings['pdf_registered_name']) ? $settings['pdf_registered_name'] : '';
    $registered_address = isset($settings['pdf_registered_address']) ? $settings['pdf_registered_address'] : '';
    $company_number = isset($settings['pdf_company_number']) ? $settings['pdf_company_number'] : '';
    $tax_number = isset($settings['pdf_tax_number']) ? $settings['pdf_tax_number'] : '';

    $replacements['[[PDFREGISTEREDNAME_SECTION]]'] = $registered_name ? esc_html($registered_name) : '';
    $replacements['[[PDFREGISTEREDADDRESS_SECTION]]'] = $registered_address ? esc_html($registered_address) : '';
    $replacements['[[PDFCOMPANYNUMBER_SECTION]]'] = $company_number ? __('Company Number:', 'woocommerce-pdf-invoice') . ' ' . esc_html($company_number) : '';
    $replacements['[[PDFTAXNUMBER_SECTION]]'] = $tax_number ? __('Tax Number:', 'woocommerce-pdf-invoice') . ' ' . esc_html($tax_number) : '';

    // Order Items
    $orderinfo_html = '<table width="100%" cellpadding="5" cellspacing="0" style="border: 1px solid #ddd;">
        <thead>
            <tr style="background: #0073aa; color: white;">
                <th style="text-align: left; padding: 10px;">' . __('Product', 'woocommerce-pdf-invoice') . '</th>
                <th style="text-align: center; padding: 10px; width: 10%;">' . __('Qty', 'woocommerce-pdf-invoice') . '</th>
                <th style="text-align: right; padding: 10px; width: 15%;">' . __('Price', 'woocommerce-pdf-invoice') . '</th>
                <th style="text-align: right; padding: 10px; width: 15%;">' . __('Total', 'woocommerce-pdf-invoice') . '</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($order->get_items() as $item) {
        $product_name = $item->get_name();
        $quantity = $item->get_quantity();
        $subtotal = $order->get_item_subtotal($item, true, false);
        $total = $order->get_line_subtotal($item, true, false);

        $orderinfo_html .= '<tr>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . esc_html($product_name) . '</td>
            <td style="text-align: center; padding: 8px; border-bottom: 1px solid #ddd;">' . $quantity . '</td>
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">' . wc_price($subtotal, array('currency' => $order->get_currency())) . '</td>
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">' . wc_price($total, array('currency' => $order->get_currency())) . '</td>
        </tr>';
    }

    $orderinfo_html .= '</tbody>
    </table>';

    $replacements['[[ORDERINFOHEADER]]'] = '';
    $replacements['[[ORDERINFO]]'] = $orderinfo_html;
    $replacements['[[PDFBARCODES]]'] = '';

    // Order Totals
    $totals_html = '<tr>
        <td style="text-align: right; padding: 5px 0;"><strong>' . __('Subtotal:', 'woocommerce-pdf-invoice') . '</strong></td>
        <td style="text-align: right; padding: 5px 0;">' . wc_price($order->get_subtotal(), array('currency' => $order->get_currency())) . '</td>
    </tr>';

    if ($order->get_shipping_total() > 0) {
        $totals_html .= '<tr>
            <td style="text-align: right; padding: 5px 0;"><strong>' . __('Shipping:', 'woocommerce-pdf-invoice') . '</strong></td>
            <td style="text-align: right; padding: 5px 0;">' . wc_price($order->get_shipping_total(), array('currency' => $order->get_currency())) . '</td>
        </tr>';
    }

    if ($order->get_total_tax() > 0) {
        $totals_html .= '<tr>
            <td style="text-align: right; padding: 5px 0;"><strong>' . __('Tax:', 'woocommerce-pdf-invoice') . '</strong></td>
            <td style="text-align: right; padding: 5px 0;">' . wc_price($order->get_total_tax(), array('currency' => $order->get_currency())) . '</td>
        </tr>';
    }

    $totals_html .= '<tr style="background: #0073aa; color: white;">
        <td style="text-align: right; padding: 10px;"><strong>' . __('Total:', 'woocommerce-pdf-invoice') . '</strong></td>
        <td style="text-align: right; padding: 10px;"><strong>' . wc_price($order->get_total(), array('currency' => $order->get_currency())) . '</strong></td>
    </tr>';

    $replacements['[[PDFORDERTOTALS]]'] = $totals_html;
    $replacements['[[PDFORDERNOTES]]'] = $order->get_customer_note() ? '<p><strong>' . __('Order Notes:', 'woocommerce-pdf-invoice') . '</strong><br>' . nl2br(esc_html($order->get_customer_note())) . '</p>' : '';

    // CSS Placeholders
    $replacements['[[PDFPAIDINFULLOVERLAY]]'] = '';
    $replacements['[[PDFCURRENCYSYMBOLFONT]]'] = '';
    $replacements['[[PDFINVOICEADDITIONALCSS]]'] = '';
    $replacements['[[PDFRTL]]'] = '';

    // Ersetze alle Platzhalter
    $html = str_replace(array_keys($replacements), array_values($replacements), $template);

    echo '✓ HTML generiert (' . strlen($html) . ' Zeichen)<br>';

    // PDF Generator ermitteln
    $pdf_generator = isset($settings['pdf_generator']) ? $settings['pdf_generator'] : 'dompdf';
    echo '✓ PDF Generator: ' . $pdf_generator . '<br>';

    // PDF erstellen mit Dompdf (MPDF nicht verfügbar)
    $dompdf_autoload = $wc_pdf_plugin . '/lib/dompdf/autoload.inc.php';

    if (!file_exists($dompdf_autoload)) {
        die('❌ Dompdf nicht gefunden: ' . $dompdf_autoload);
    }

    require_once($dompdf_autoload);
    echo '✓ Dompdf geladen<br>';

    $dompdf = new Dompdf\Dompdf(array('enable_remote' => true));
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
        $success_count++;

        echo '<p style="color: green; font-weight: bold;">✅ PDF erfolgreich generiert!</p>';
        echo '<p><strong>Datei:</strong> ' . htmlspecialchars($filename) . ' (' . $filesize . ' KB)</p>';

        // Download-Link
        $download_url = $pdf_url . '/' . $filename;
        echo '<p><a href="' . esc_url($download_url) . '" target="_blank" style="background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; display: inline-block; border-radius: 3px;">📄 PDF herunterladen</a></p>';

        // Auto-Download nur bei einzelner Bestellung
        if (count($order_ids) === 1) {
            echo '<script>setTimeout(function(){ window.open("' . esc_url($download_url) . '", "_blank"); }, 1000);</script>';
            echo '<p style="color: #666;"><em>Die PDF wird automatisch in 1 Sekunde geöffnet...</em></p>';
        }
    } else {
        $error_count++;
        echo '<p style="color: red; font-weight: bold;">❌ Fehler beim Speichern!</p>';
        echo '<p><strong>Bytes geschrieben:</strong> ' . var_export($bytes_written, true) . '</p>';
        echo '<p><strong>Ordner beschreibbar:</strong> ' . (is_writable($pdf_dir) ? 'Ja' : 'Nein') . '</p>';

        $last_error = error_get_last();
        if ($last_error) {
            echo '<p><strong>PHP Error:</strong> ' . htmlspecialchars($last_error['message']) . '</p>';
        }
    }

} catch (Exception $e) {
    $error_count++;
    echo '<p style="color: red; font-weight: bold;">❌ Exception: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre style="background: #f5f5f5; padding: 10px; border-left: 3px solid red; font-size: 11px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

    echo '</div>'; // Schließe order div
    flush(); // Output sofort anzeigen
}

// Zusammenfassung
echo '<hr>';
echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px 0; border: 3px solid #0073aa;">';
echo '<h2>Zusammenfassung</h2>';
echo '<p><strong>Gesamt:</strong> ' . count($order_ids) . ' Bestellungen</p>';
echo '<p style="color: green;"><strong>Erfolgreich:</strong> ' . $success_count . '</p>';
echo '<p style="color: red;"><strong>Fehler:</strong> ' . $error_count . '</p>';
echo '<p><strong>Speicherort:</strong> <code>' . $pdf_dir . '</code></p>';
echo '</div>';

echo '<p style="margin-top: 30px;"><a href="?" style="background: #666; color: white; padding: 12px 30px; text-decoration: none; display: inline-block; border-radius: 5px;">← Zurück zum Generator</a></p>';

echo '<p style="color: #999; font-size: 12px; margin-top: 30px;">Generiert am: ' . date('d.m.Y H:i:s') . '</p>';
