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

// Error Reporting aktivieren für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prüfungen
if (!function_exists('wc_get_order')) die('WooCommerce nicht aktiv!');

// E-Mails blockieren
add_filter('woocommerce_email_enabled', '__return_false', 999);
add_filter('pre_wp_mail', '__return_false', 999);

// Zeige Interface wenn keine Order-ID und kein Jahr übergeben wurde
if (!isset($_GET['order_id']) && !isset($_GET['year'])) {
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
            <h2>Alle Rechnungen eines Jahres</h2>
            <form method="get">
                <label>Jahr wählen:</label>
                <select name="year" required style="margin-bottom: 15px;">
                    <option value="">-- Jahr wählen --</option>
                    <option value="2024">2024</option>
                    <option value="2025" selected>2025</option>
                    <option value="2026">2026</option>
                </select>

                <label style="margin-top: 20px;">
                    <input type="checkbox" name="no_tax_eu" value="1" style="width: auto; margin-right: 8px;">
                    EU-Rechnungen ohne Steuer generieren (außer Österreich)
                </label>

                <br><br>
                <button type="submit" class="button">Alle PDFs des Jahres generieren</button>
            </form>
        </div>

        <div class="box" style="background: #fff8dc; border: 2px solid #ffa500;">
            <h2>🇪🇺 Alle EU-Rechnungen neu generieren (ohne Steuer)</h2>
            <p style="font-size: 14px; color: #666;">
                <strong>Wichtig:</strong> Für EU-Länder (außer Österreich) darf keine Steuer ausgewiesen werden.<br>
                Diese Option generiert ALLE Rechnungen von EU-Kunden des gewählten Jahres ohne Steuerangabe neu.
            </p>
            <form method="get">
                <input type="hidden" name="regenerate_eu" value="1">
                <input type="hidden" name="no_tax_eu" value="1">

                <label>Jahr wählen:</label>
                <select name="year" required style="margin-bottom: 15px;">
                    <option value="">-- Jahr wählen --</option>
                    <option value="2024">2024</option>
                    <option value="2025" selected>2025</option>
                    <option value="2026">2026</option>
                </select>

                <br><br>
                <button type="submit" class="button" style="background: #ffa500;">🇪🇺 EU-Rechnungen (ganzes Jahr) ohne Steuer generieren</button>
                <p style="font-size: 12px; color: #666; margin-top: 10px;">
                    ✓ Kunden werden NICHT benachrichtigt<br>
                    ✓ Nur Bestellungen aus EU-Ländern (außer AT)<br>
                    ✓ Verarbeitet das komplette Jahr
                </p>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// EU-Länder Liste (ISO-2 Codes, ohne Österreich)
$eu_countries = array(
    'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE',
    'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL',
    'PT', 'RO', 'SK', 'SI', 'ES', 'SE'
);

// Initialisiere Variablen (werden später gesetzt wenn year Parameter vorhanden)
$regenerate_eu = false;
$no_tax_eu = false;

// Bulk-Generierung für ein Jahr
if (isset($_GET['year'])) {
    // ALLERERSTER Console Log - um zu prüfen ob wir hier ankommen
    echo '<script>console.log("🚀 BULK-BEREICH ERREICHT - Jahr Parameter gefunden!");</script>';

    $year = intval($_GET['year']);
    $regenerate_eu = isset($_GET['regenerate_eu']) && $_GET['regenerate_eu'] == '1';
    $no_tax_eu = isset($_GET['no_tax_eu']) && $_GET['no_tax_eu'] == '1';

    echo '<script>console.log("Variablen geladen - Jahr:", ' . $year . ');</script>';

    // Erstelle Start- und Enddatum für das ganze Jahr
    $start_timestamp = strtotime($year . '-01-01 00:00:00');
    $end_timestamp = strtotime($year . '-12-31 23:59:59');
    $period_label = $year;

    echo '<h1>PDF Generator - ' . $period_label . '</h1>';
    if ($regenerate_eu) {
        echo '<p style="background: #fff3cd; padding: 10px; border: 2px solid #ffa500;">🇪🇺 <strong>EU-Regenerierung:</strong> Nur EU-Länder (außer Österreich), OHNE Steuer</p>';
    }
    echo '<p>Zeitraum: ' . date('d.m.Y', $start_timestamp) . ' bis ' . date('d.m.Y', $end_timestamp) . '</p>';
    echo '<hr>';

    // JavaScript Debug für Zeitraum
    echo '<script>';
    echo 'console.log("=== BULK GENERIERUNG GESTARTET ===");';
    echo 'console.log("Jahr:", ' . $year . ');';
    echo 'console.log("Zeitraum:", "' . addslashes(date('d.m.Y', $start_timestamp)) . ' - ' . addslashes(date('d.m.Y', $end_timestamp)) . '");';
    echo 'console.log("Start Timestamp:", ' . $start_timestamp . ');';
    echo 'console.log("End Timestamp:", ' . $end_timestamp . ');';
    echo 'console.log("EU-Regenerierung:", ' . ($regenerate_eu ? 'true' : 'false') . ');';
    echo 'console.log("No Tax EU:", ' . ($no_tax_eu ? 'true' : 'false') . ');';
    echo '</script>';

    // Debug: Zeige Datum-Range
    echo '<p><small>Debug - Start: ' . date('Y-m-d H:i:s', $start_timestamp) . ' | Ende: ' . date('Y-m-d H:i:s', $end_timestamp) . '</small></p>';

    // Hole alle Bestellungen des Jahres - verwende korrekte Status-Codes
    $order_args = array(
        'limit' => -1,
        'date_created' => '>=' . $start_timestamp,
        'date_created_before' => '<=' . $end_timestamp,
        'status' => array('pending', 'on-hold', 'processing', 'completed'), // Alle Status inkl. pending
        'orderby' => 'date',
        'order' => 'ASC'
    );

    // Filtere nur EU-Länder wenn EU-Regenerierung aktiv
    if ($regenerate_eu) {
        $order_args['meta_query'] = array(
            array(
                'key' => '_billing_country',
                'value' => $eu_countries,
                'compare' => 'IN'
            )
        );
        echo '<p style="background: #e7f3ff; padding: 10px; border: 1px solid #0073aa;">🔍 Filtere nur Bestellungen aus EU-Ländern: ' . implode(', ', $eu_countries) . '</p>';
    }

    $orders = wc_get_orders($order_args);

    // JavaScript Debug für gefundene Orders
    echo '<script>';
    echo 'console.log("Query ausgeführt. Gefundene Bestellungen:", ' . count($orders) . ');';
    echo 'console.log("Query Args:", ' . json_encode($order_args) . ');';
    if (count($orders) > 0) {
        $order_infos = array();
        foreach ($orders as $o) {
            $order_infos[] = array(
                'id' => $o->get_id(),
                'date' => $o->get_date_created()->date('Y-m-d'),
                'status' => $o->get_status(),
                'country' => $o->get_billing_country()
            );
        }
        echo 'console.log("Bestellungen Details:", ' . json_encode($order_infos) . ');';
    } else {
        echo 'console.error("⚠️ KEINE BESTELLUNGEN GEFUNDEN!");';
        echo 'console.log("Prüfe ob Bestellungen im System existieren...");';
    }
    echo '</script>';

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
$generated_files = array(); // Speichere alle generierten Dateinamen für ZIP

// Loop durch alle Order IDs
foreach ($order_ids as $current_order_id) {
    $order = wc_get_order($current_order_id);
    if (!$order) {
        echo '<p style="color: red;">❌ Order #' . $current_order_id . ' nicht gefunden!</p>';
        $error_count++;
        continue;
    }

    // Prüfe ob dieser Order EU ist (für Tax-Behandlung)
    $billing_country = $order->get_billing_country();
    $is_eu_order = in_array($billing_country, $eu_countries);
    $hide_tax = ($no_tax_eu && $is_eu_order);

    // Debug für Tax-Handling
    echo '<script>';
    echo 'console.log("Order #' . $current_order_id . ' - Land:", "' . $billing_country . '");';
    echo 'console.log("  no_tax_eu:", ' . ($no_tax_eu ? 'true' : 'false') . ');';
    echo 'console.log("  is_eu_order:", ' . ($is_eu_order ? 'true' : 'false') . ');';
    echo 'console.log("  hide_tax:", ' . ($hide_tax ? 'true' : 'false') . ');';
    echo '</script>';

    echo '<div style="border: 1px solid #ddd; padding: 15px; margin: 15px 0; background: #fff;">';
    echo '<h3>Order #' . $current_order_id . ' - ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</h3>';

    // Zeige Land-Info bei EU-Regenerierung
    if ($regenerate_eu || $no_tax_eu) {
        $country_flag = $is_eu_order ? '🇪🇺' : '🏳️';
        $country_style = $is_eu_order ? 'background: #fff3cd; color: #856404;' : 'background: #e7f3ff; color: #004085;';
        echo '<p style="' . $country_style . ' padding: 5px 10px; display: inline-block; border-radius: 3px; font-size: 12px;">' . $country_flag . ' Land: <strong>' . $billing_country . '</strong>' . ($hide_tax ? ' → Steuer wird ausgeblendet' : ($is_eu_order ? ' (EU-Land)' : ' (Kein EU-Land - AT bleibt)')) . '</p>';
    }

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
    $replacements['[[PDFINVOICENUMHEADING]]'] = 'Rechnungsnummer';
    $replacements['[[PDFINVOICENUM]]'] = '<strong>' . esc_html($invoice_number) . '</strong>';

    // Order Number
    $replacements['[[PDFORDERENUMHEADING]]'] = 'Bestellnummer';
    $replacements['[[PDFORDERENUM]]'] = esc_html($order->get_order_number());

    // Dates
    $invoice_date = $order->get_meta('_invoice_date', true);
    if (empty($invoice_date)) {
        $date_format = isset($settings['pdf_date_format']) ? $settings['pdf_date_format'] : 'd.m.Y';
        $invoice_date = $order->get_date_created()->date($date_format);
    }
    $replacements['[[PDFINVOICEDATEHEADING]]'] = 'Rechnungsdatum';
    $replacements['[[PDFINVOICEDATE]]'] = esc_html($invoice_date);
    $replacements['[[PDFORDERDATEHEADING]]'] = 'Bestelldatum';
    $replacements['[[PDFORDERDATE]]'] = $order->get_date_created()->date('d.m.Y');

    // Payment & Shipping Method
    $replacements['[[PDFINVOICE_PAYMETHOD_HEADING]]'] = 'Zahlungsmethode';
    $replacements['[[PDFINVOICEPAYMENTMETHOD]]'] = esc_html($order->get_payment_method_title());
    $replacements['[[PDFINVOICE_SHIPMETHOD_HEADING]]'] = 'Versandart';
    $replacements['[[PDFSHIPPINGMETHOD]]'] = esc_html($order->get_shipping_method());
    $replacements['[[PDFSHIPMENTTRACKING]]'] = '';

    // Billing Details - Behalte <br> Tags für Adressen
    $replacements['[[PDFINVOICE_BILLINGDETAILS_HEADING]]'] = 'Rechnungsadresse';
    $billing_address = $order->get_formatted_billing_address();
    $replacements['[[PDFBILLINGADDRESS]]'] = wp_kses($billing_address, $allowed_html);
    $replacements['[[PDFBILLINGTEL]]'] = $order->get_billing_phone() ? 'Tel: ' . esc_html($order->get_billing_phone()) : '';
    $replacements['[[PDFBILLINGEMAIL]]'] = esc_html($order->get_billing_email());
    $replacements['[[PDFBILLINGVATNUMBER]]'] = '';

    // Shipping Details - Behalte <br> Tags für Adressen
    $replacements['[[PDFINVOICE_SHIPPINGDETAILS_HEADING]]'] = 'Lieferadresse';
    $shipping_address = $order->get_formatted_shipping_address();
    $replacements['[[PDFSHIPPINGADDRESS]]'] = $shipping_address ? wp_kses($shipping_address, $allowed_html) : 'Wie Rechnungsadresse';

    // Footer - Company Registration
    $registered_name = isset($settings['pdf_registered_name']) ? $settings['pdf_registered_name'] : '';
    $registered_address = isset($settings['pdf_registered_address']) ? $settings['pdf_registered_address'] : '';
    $company_number = isset($settings['pdf_company_number']) ? $settings['pdf_company_number'] : '';
    $tax_number = isset($settings['pdf_tax_number']) ? $settings['pdf_tax_number'] : '';

    $replacements['[[PDFREGISTEREDNAME_SECTION]]'] = $registered_name ? esc_html($registered_name) : '';
    $replacements['[[PDFREGISTEREDADDRESS_SECTION]]'] = $registered_address ? esc_html($registered_address) : '';
    $replacements['[[PDFCOMPANYNUMBER_SECTION]]'] = $company_number ? 'Firmennummer: ' . esc_html($company_number) : '';
    $replacements['[[PDFTAXNUMBER_SECTION]]'] = $tax_number ? 'Steuernummer: ' . esc_html($tax_number) : '';

    // Order Items
    $orderinfo_html = '<table width="100%" cellpadding="5" cellspacing="0" style="border: 1px solid #ddd;">
        <thead>
            <tr style="background: #0073aa; color: white;">
                <th style="text-align: left; padding: 10px;">Produkt</th>
                <th style="text-align: center; padding: 10px; width: 10%;">Menge</th>
                <th style="text-align: right; padding: 10px; width: 15%;">Preis</th>
                <th style="text-align: right; padding: 10px; width: 15%;">Gesamt</th>
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
        <td style="text-align: right; padding: 5px 0;"><strong>Zwischensumme:</strong></td>
        <td style="text-align: right; padding: 5px 0;">' . wc_price($order->get_subtotal(), array('currency' => $order->get_currency())) . '</td>
    </tr>';

    if ($order->get_shipping_total() > 0) {
        $totals_html .= '<tr>
            <td style="text-align: right; padding: 5px 0;"><strong>Versand:</strong></td>
            <td style="text-align: right; padding: 5px 0;">' . wc_price($order->get_shipping_total(), array('currency' => $order->get_currency())) . '</td>
        </tr>';
    }

    // Zeige Steuer nur wenn NICHT EU ohne Steuer
    if ($order->get_total_tax() > 0 && !$hide_tax) {
        $totals_html .= '<tr>
            <td style="text-align: right; padding: 5px 0;"><strong>MwSt.:</strong></td>
            <td style="text-align: right; padding: 5px 0;">' . wc_price($order->get_total_tax(), array('currency' => $order->get_currency())) . '</td>
        </tr>';
    }

    // Hinweis wenn Steuer ausgeblendet wird
    if ($hide_tax && $order->get_total_tax() > 0) {
        $totals_html .= '<tr>
            <td colspan="2" style="text-align: right; padding: 5px 0; color: #666; font-size: 10px;"><em>EU-Regelung: Steuer gem. §19 UStG ausgeblendet</em></td>
        </tr>';
    }

    $totals_html .= '<tr style="background: #0073aa; color: white;">
        <td style="text-align: right; padding: 10px;"><strong>Gesamt:</strong></td>
        <td style="text-align: right; padding: 10px;"><strong>' . wc_price($order->get_total(), array('currency' => $order->get_currency())) . '</strong></td>
    </tr>';

    $replacements['[[PDFORDERTOTALS]]'] = $totals_html;
    $replacements['[[PDFORDERNOTES]]'] = $order->get_customer_note() ? '<p><strong>Bestellnotizen:</strong><br>' . nl2br(esc_html($order->get_customer_note())) . '</p>' : '';

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
        $generated_files[] = $filepath; // Für ZIP-Archiv

        echo '<p style="color: green; font-weight: bold;">✅ PDF erfolgreich generiert!</p>';
        echo '<p><strong>Datei:</strong> ' . htmlspecialchars($filename) . ' (' . $filesize . ' KB)</p>';

        // JavaScript Debug
        echo '<script>console.log("PDF generiert: ' . addslashes($filename) . '", "Pfad: ' . addslashes($filepath) . '", "Zu Array hinzugefügt");</script>';

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

// JavaScript Debug für Zusammenfassung
echo '<script>';
echo 'console.log("=== ZUSAMMENFASSUNG ===");';
echo 'console.log("Erfolgreiche PDFs:", ' . $success_count . ');';
echo 'console.log("generated_files Array Länge:", ' . count($generated_files) . ');';
echo 'console.log("isset year:", ' . (isset($_GET['year']) ? 'true' : 'false') . ');';
echo 'console.log("Bedingung success_count > 1:", ' . ($success_count > 1 ? 'true' : 'false') . ');';
echo 'console.log("ZIP-Bereich wird angezeigt:", ' . (($success_count > 1 && isset($_GET['year'])) ? 'true' : 'false') . ');';
if (count($generated_files) > 0) {
    echo 'console.log("Dateien im Array:", ' . json_encode(array_map('basename', $generated_files)) . ');';
}
echo 'console.log("ZipArchive verfügbar:", ' . (class_exists('ZipArchive') ? 'true' : 'false') . ');';
echo '</script>';

// ZIP-Download für Bulk-Generierung
if ($success_count > 1 && isset($_GET['year'])) {
    echo '<div style="background: #e7f3ff; border: 2px solid #0073aa; padding: 20px; margin: 20px 0; text-align: center;">';
    echo '<h3 style="margin-top: 0;">📦 Alle PDFs herunterladen</h3>';

    // JavaScript Console Debug
    echo '<script>';
    echo 'console.log("=== ZIP DOWNLOAD BEREICH ===");';
    echo 'console.log("Erfolgreiche PDFs:", ' . $success_count . ');';
    echo 'console.log("generated_files Array:", ' . json_encode($generated_files) . ');';
    echo 'console.log("Array Länge:", ' . count($generated_files) . ');';
    echo 'console.log("PDF Dir:", "' . addslashes($pdf_dir) . '");';
    echo 'console.log("Dir beschreibbar:", ' . (is_writable($pdf_dir) ? 'true' : 'false') . ');';

    // Prüfe jede Datei
    if (count($generated_files) > 0) {
        foreach ($generated_files as $file) {
            $exists = file_exists($file);
            $size = $exists ? filesize($file) : 0;
            echo 'console.log("Datei: ' . addslashes(basename($file)) . '", "Existiert:", ' . ($exists ? 'true' : 'false') . ', "Größe:", ' . $size . ');';
        }
    } else {
        echo 'console.error("⚠️ PROBLEM: generated_files Array ist LEER!");';
    }
    echo '</script>';

    try {
        // Erstelle ZIP-Datei
        $zip_filename = 'rechnungen-' . $year . ($regenerate_eu ? '-EU' : '') . '.zip';
        $zip_filepath = $pdf_dir . '/' . $zip_filename;

        echo '<p style="font-size: 12px;"><strong>ZIP-Datei:</strong> ' . htmlspecialchars($zip_filename) . '</p>';
        echo '<script>console.log("ZIP-Dateiname:", "' . addslashes($zip_filename) . '");</script>';
        echo '<script>console.log("ZIP-Pfad:", "' . addslashes($zip_filepath) . '");</script>';

        if (count($generated_files) === 0) {
            echo '<div style="background: #fff3cd; border: 2px solid #ffa500; padding: 15px; margin: 15px 0;">';
            echo '<p style="color: #856404; margin: 0;"><strong>⚠️ FEHLER:</strong> Keine Dateien zum Archivieren vorhanden!</p>';
            echo '<p style="color: #856404; margin: 10px 0 0 0;">Das generated_files Array ist leer. PDFs wurden möglicherweise nicht korrekt gespeichert.</p>';
            echo '</div>';
            echo '<script>console.error("Keine Dateien im Array!");</script>';
        } else {
            // Prüfe ob ZipArchive verfügbar ist
            if (!class_exists('ZipArchive')) {
                echo '<div style="background: #f8d7da; border: 2px solid #dc3545; padding: 15px; margin: 15px 0;">';
                echo '<p style="color: #721c24; margin: 0;"><strong>❌ FEHLER:</strong> ZipArchive Klasse nicht verfügbar!</p>';
                echo '<p style="color: #721c24; margin: 10px 0 0 0;">PHP ZIP Extension ist nicht installiert.</p>';
                echo '</div>';
                echo '<script>console.error("ZipArchive Klasse nicht verfügbar! ZIP Extension fehlt.");</script>';
            } else {
                $zip = new ZipArchive();
                $zip_open_result = $zip->open($zip_filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                echo '<script>console.log("ZIP->open() Ergebnis:", ' . ($zip_open_result === TRUE ? 'true' : '"Fehler-Code: ' . $zip_open_result . '"') . ');</script>';
                echo '<p style="font-size: 12px;"><strong>ZIP open() Ergebnis:</strong> ' . ($zip_open_result === TRUE ? '✓ Erfolgreich' : '❌ Fehler-Code: ' . $zip_open_result) . '</p>';

                if ($zip_open_result === TRUE) {

                    // Füge alle in dieser Session generierten PDFs zum ZIP hinzu
                    $added_count = 0;
                    $skipped_files = array();

                    echo '<script>console.log("Füge Dateien zum ZIP hinzu...");</script>';

                    foreach ($generated_files as $file) {
                        if (file_exists($file)) {
                            $filename = basename($file);
                            $add_result = $zip->addFile($file, $filename);
                            echo '<script>console.log("addFile(' . addslashes($filename) . '):", ' . ($add_result ? 'true' : 'false') . ');</script>';
                            if ($add_result) {
                                $added_count++;
                            } else {
                                $skipped_files[] = $filename . ' (addFile fehlgeschlagen)';
                            }
                        } else {
                            $skipped_files[] = basename($file) . ' (Datei nicht gefunden)';
                            echo '<script>console.error("Datei nicht gefunden:", "' . addslashes(basename($file)) . '");</script>';
                        }
                    }

                    $zip->close();

                    echo '<script>console.log("ZIP geschlossen. Hinzugefügt:", ' . $added_count . ', "Übersprungen:", ' . count($skipped_files) . ');</script>';

                    // DEBUG: Zeige Details
                    echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; font-size: 12px;">';
                    echo '<p><strong>✓ ZIP erstellt:</strong> ' . $added_count . ' Dateien hinzugefügt</p>';
                    if (count($skipped_files) > 0) {
                        echo '<p style="color: #856404;"><strong>Übersprungen:</strong> ' . implode(', ', $skipped_files) . '</p>';
                    }
                    echo '</div>';

                    if ($added_count > 0) {
                        $zip_url = $pdf_url . '/' . $zip_filename;
                        $zip_size = round(filesize($zip_filepath) / 1024, 2);

                        echo '<script>console.log("✓ ZIP erfolgreich erstellt!");</script>';
                        echo '<script>console.log("ZIP URL:", "' . addslashes($zip_url) . '");</script>';
                        echo '<script>console.log("ZIP Größe:", ' . $zip_size . ', "KB");</script>';

                        echo '<p><strong>' . $added_count . ' PDFs im ZIP-Archiv</strong></p>';
                        echo '<p>Dateigröße: ' . $zip_size . ' KB</p>';
                        echo '<p style="margin-top: 20px;"><a href="' . esc_url($zip_url) . '" style="background: #0073aa; color: white; padding: 15px 40px; text-decoration: none; display: inline-block; border-radius: 5px; font-size: 18px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.2);">📥 ZIP herunterladen (' . $added_count . ' PDFs)</a></p>';
                    } else {
                        echo '<script>console.error("Keine PDFs konnten zum ZIP hinzugefügt werden!");</script>';
                        echo '<p style="color: orange;">⚠️ Keine PDFs konnten zum ZIP hinzugefügt werden.</p>';
                    }

                } else {
                    echo '<script>console.error("ZIP->open() fehlgeschlagen! Fehler-Code:", ' . $zip_open_result . ');</script>';
                    echo '<p style="color: red;">❌ ZIP konnte nicht erstellt werden. Fehler-Code: ' . $zip_open_result . '</p>';
                }
            }
        }

    } catch (Exception $e) {
        echo '<script>console.error("Exception beim ZIP erstellen:", "' . addslashes($e->getMessage()) . '");</script>';
        echo '<script>console.error("Stack trace:", "' . addslashes($e->getTraceAsString()) . '");</script>';
        echo '<p style="color: red;">❌ Fehler beim Erstellen des ZIP: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre style="background: #f5f5f5; padding: 10px; text-align: left; font-size: 11px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }

    echo '</div>';
} else {
    // Debug warum ZIP-Bereich nicht angezeigt wird
    echo '<script>console.log("ZIP-Bereich NICHT angezeigt. Grund:");</script>';
    if ($success_count <= 1) {
        echo '<script>console.log("- success_count zu niedrig:", ' . $success_count . ');</script>';
    }
    if (!isset($_GET['year'])) {
        echo '<script>console.log("- year Parameter fehlt");</script>';
    }
}

echo '<p style="margin-top: 30px;"><a href="?" style="background: #666; color: white; padding: 12px 30px; text-decoration: none; display: inline-block; border-radius: 5px;">← Zurück zum Generator</a></p>';

echo '<p style="color: #999; font-size: 12px; margin-top: 30px;">Generiert am: ' . date('d.m.Y H:i:s') . '</p>';
