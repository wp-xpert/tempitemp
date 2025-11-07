<?php
/**
 * Invoice Template
 *
 * This template can be overridden by copying it to yourtheme/wc-pdf-invoice-generator/invoice-template.php
 *
 * @package WC_PDF_Invoice_Generator
 * @var WC_Order $order
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<style>
    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        font-size: 10pt;
        color: #333;
    }
    h1 {
        color: #2c3e50;
        font-size: 24px;
        margin-bottom: 20px;
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
    }
    h2 {
        color: #34495e;
        font-size: 16px;
        margin-top: 20px;
        margin-bottom: 10px;
    }
    .header {
        margin-bottom: 40px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 20px;
    }
    .company-name {
        font-size: 20px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }
    .invoice-number {
        font-size: 18px;
        color: #3498db;
        margin: 20px 0;
    }
    .info-section {
        margin-bottom: 20px;
    }
    .info-label {
        font-weight: bold;
        color: #555;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    thead th {
        background-color: #3498db;
        color: white;
        padding: 12px 8px;
        text-align: left;
        font-weight: bold;
    }
    tbody td {
        padding: 10px 8px;
        border-bottom: 1px solid #e0e0e0;
    }
    tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .text-right {
        text-align: right;
    }
    .text-center {
        text-align: center;
    }
    tfoot td {
        padding: 8px;
        font-weight: bold;
    }
    .total-row {
        background-color: #ecf0f1;
        font-size: 12pt;
    }
    .subtotal-row {
        border-top: 1px solid #bdc3c7;
    }
    .footer {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        font-size: 9pt;
        color: #7f8c8d;
        text-align: center;
    }
    .address-box {
        background-color: #f8f9fa;
        padding: 15px;
        margin: 10px 0;
        border-left: 3px solid #3498db;
    }
</style>

<div class="header">
    <div class="company-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
    <div><?php echo esc_html( get_bloginfo( 'description' ) ); ?></div>
</div>

<div class="invoice-number">
    <strong>RECHNUNG #<?php echo esc_html( $order->get_order_number() ); ?></strong>
</div>

<div class="info-section">
    <table style="border: none;">
        <tr>
            <td style="width: 50%; border: none; vertical-align: top;">
                <h2>Rechnungsadresse</h2>
                <div class="address-box">
                    <?php echo wp_kses_post( nl2br( $order->get_formatted_billing_address() ) ); ?>
                    <?php if ( $order->get_billing_email() ) : ?>
                        <br><strong>E-Mail:</strong> <?php echo esc_html( $order->get_billing_email() ); ?>
                    <?php endif; ?>
                    <?php if ( $order->get_billing_phone() ) : ?>
                        <br><strong>Telefon:</strong> <?php echo esc_html( $order->get_billing_phone() ); ?>
                    <?php endif; ?>
                </div>
            </td>
            <?php if ( $order->get_formatted_shipping_address() ) : ?>
            <td style="width: 50%; border: none; vertical-align: top;">
                <h2>Lieferadresse</h2>
                <div class="address-box">
                    <?php echo wp_kses_post( nl2br( $order->get_formatted_shipping_address() ) ); ?>
                </div>
            </td>
            <?php endif; ?>
        </tr>
    </table>
</div>

<div class="info-section">
    <p><span class="info-label">Bestelldatum:</span> <?php echo esc_html( $order->get_date_created()->date_i18n( 'd.m.Y H:i' ) ); ?></p>
    <p><span class="info-label">Zahlungsmethode:</span> <?php echo esc_html( $order->get_payment_method_title() ); ?></p>
    <?php if ( $order->get_customer_note() ) : ?>
    <p><span class="info-label">Kundennotiz:</span> <?php echo esc_html( $order->get_customer_note() ); ?></p>
    <?php endif; ?>
</div>

<h2>Bestellte Artikel</h2>
<table>
    <thead>
        <tr>
            <th style="width: 50%;">Produkt</th>
            <th class="text-center" style="width: 15%;">Menge</th>
            <th class="text-right" style="width: 17.5%;">Einzelpreis</th>
            <th class="text-right" style="width: 17.5%;">Gesamt</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $order->get_items() as $item_id => $item ) : ?>
        <tr>
            <td>
                <strong><?php echo esc_html( $item->get_name() ); ?></strong>
                <?php
                $product = $item->get_product();
                if ( $product && $product->get_sku() ) {
                    echo '<br><small>SKU: ' . esc_html( $product->get_sku() ) . '</small>';
                }
                ?>
            </td>
            <td class="text-center"><?php echo esc_html( $item->get_quantity() ); ?></td>
            <td class="text-right"><?php echo wc_price( $order->get_item_subtotal( $item, false, false ), array( 'currency' => $order->get_currency() ) ); ?></td>
            <td class="text-right"><?php echo wc_price( $order->get_line_subtotal( $item, false, false ), array( 'currency' => $order->get_currency() ) ); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="subtotal-row">
            <td colspan="3" class="text-right">Zwischensumme:</td>
            <td class="text-right"><?php echo wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ); ?></td>
        </tr>

        <?php if ( $order->get_shipping_total() > 0 ) : ?>
        <tr>
            <td colspan="3" class="text-right">Versand (<?php echo esc_html( $order->get_shipping_method() ); ?>):</td>
            <td class="text-right"><?php echo wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ); ?></td>
        </tr>
        <?php endif; ?>

        <?php if ( $order->get_total_discount() > 0 ) : ?>
        <tr>
            <td colspan="3" class="text-right">Rabatt:</td>
            <td class="text-right">-<?php echo wc_price( $order->get_total_discount(), array( 'currency' => $order->get_currency() ) ); ?></td>
        </tr>
        <?php endif; ?>

        <?php if ( $order->get_total_tax() > 0 ) : ?>
        <tr>
            <td colspan="3" class="text-right">MwSt. (<?php echo esc_html( number_format( ( $order->get_total_tax() / $order->get_subtotal() ) * 100, 2 ) ); ?>%):</td>
            <td class="text-right"><?php echo wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ); ?></td>
        </tr>
        <?php endif; ?>

        <tr class="total-row">
            <td colspan="3" class="text-right" style="font-size: 14pt;">GESAMT:</td>
            <td class="text-right" style="font-size: 14pt;"><?php echo wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ); ?></td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    <p>Vielen Dank für Ihren Einkauf!</p>
    <p><?php echo esc_html( get_bloginfo( 'name' ) ); ?> | <?php echo esc_html( get_bloginfo( 'url' ) ); ?></p>
</div>
