# WC PDF Invoice Generator

Ein minimalistisches WordPress Plugin zur Generierung von PDF-Rechnungen für WooCommerce Bestellungen.

## Features

- Generiert PDF-Rechnungen für WooCommerce-Bestellungen
- Speichert PDFs automatisch im Plugin-Ordner
- Einfaches Admin-Interface zur Eingabe der Order-ID
- Integration in WooCommerce-Bestellungen (Order Actions)
- Minimalistisch und leichtgewichtig - nur ~7 KB Code
- Keine externen Dependencies - verwendet integrierte FPDF-Bibliothek

## Installation

1. Lade den `wc-pdf-invoice-generator` Ordner nach `/wp-content/plugins/`
2. Aktiviere das Plugin unter **Plugins** > **Installierte Plugins**
3. Fertig! Das Plugin erstellt automatisch den `/pdfs/` Ordner

## Voraussetzungen

- WordPress 5.8+
- WooCommerce 5.0+
- PHP 7.4+

## Verwendung

### Option 1: Über das Admin-Menü

1. Gehe zu **WooCommerce** > **PDF Rechnungen**
2. Gib die Order-ID ein (z.B. 123)
3. Klicke auf **PDF generieren**
4. Die PDF wird im Ordner `/wp-content/plugins/wc-pdf-invoice-generator/pdfs/` gespeichert

### Option 2: Aus der Bestellansicht

1. Öffne eine WooCommerce-Bestellung im Admin
2. Scrolle zu **Bestellaktionen** (rechte Sidebar)
3. Wähle "PDF-Rechnung generieren"
4. Klicke auf den Pfeil-Button
5. Die Bestellung erhält eine Notiz mit dem Dateinamen

### Programmatische Verwendung

```php
// PDF für Bestellung generieren
$generator = new WC_PDF_IG_Generator();
$pdf_path = $generator->generate( 123 ); // Order ID

if ( $pdf_path ) {
    echo 'PDF erstellt: ' . $pdf_path;
}

// PDF-Pfad einer Bestellung abrufen
$pdf_path = WC_PDF_IG_Generator::get_pdf_path( 123 );
if ( $pdf_path ) {
    echo 'PDF existiert: ' . $pdf_path;
}
```

## Verzeichnisstruktur

```
wc-pdf-invoice-generator/
├── wc-pdf-invoice-generator.php  # Haupt-Plugin-Datei
├── includes/
│   ├── fpdf.php                  # PDF-Bibliothek
│   ├── class-pdf-generator.php   # PDF-Generator
│   └── class-admin.php           # Admin-Interface
├── pdfs/                         # Generierte PDFs (automatisch erstellt)
│   ├── .htaccess                 # Sicherheit
│   └── index.php                 # Sicherheit
└── README.md
```

## PDF-Inhalt

Die generierten PDF-Rechnungen enthalten:

- Shop-Name und Beschreibung
- Rechnungsnummer (Order Number)
- Rechnungsadresse
- Lieferadresse (falls vorhanden)
- Bestelldatum
- Zahlungsmethode
- Produkttabelle (Name, Menge, Einzelpreis, Gesamt)
- Zwischensumme
- Versandkosten
- Mehrwertsteuer
- Gesamtsumme

## Sicherheit

- PDFs werden im geschützten Plugin-Ordner gespeichert
- `.htaccess` verhindert Directory Listing
- `index.php` als zusätzlicher Schutz
- Nonce-Validierung für alle Formulare
- Capability-Checks (`manage_woocommerce`)
- Proper Input-Sanitization und Output-Escaping

## Dateinamen-Format

PDFs werden mit folgendem Format gespeichert:

```
invoice-{ORDER_NUMBER}-{DATE}.pdf
```

Beispiel: `invoice-123-2025-11-07.pdf`

## Technische Details

- **PDF-Engine:** FPDF 1.85 (Open Source, inkludiert)
- **Dateigröße:** ~40 KB pro PDF
- **Performance:** < 1 Sekunde Generierungszeit
- **Speicherbedarf:** Minimal
- **Code-Größe:** ~7 KB (ohne FPDF)

## Fehlerbehebung

### PDFs werden nicht generiert

1. Überprüfe, ob WooCommerce aktiv ist
2. Überprüfe Schreibrechte: `chmod 755 wp-content/plugins/wc-pdf-invoice-generator/pdfs`
3. Prüfe ob die Bestellung existiert
4. Prüfe das PHP Error Log

### "Class FPDF not found"

- Stelle sicher, dass `includes/fpdf.php` existiert
- Deaktiviere und reaktiviere das Plugin

### PDFs sind leer

- Überprüfe, ob die Bestellung Produkte enthält
- Teste mit einer abgeschlossenen Bestellung
- Prüfe ob WooCommerce-Daten korrekt sind

## Lizenz

GPL v2 oder höher - Open Source

## Support

Bei Fragen oder Problemen:
1. Überprüfe die Dokumentation
2. Prüfe die WordPress/WooCommerce-Versionen
3. Erstelle ein Issue auf GitHub

## Changelog

### Version 1.0.0
- Minimalistisches, funktionales Plugin
- PDF-Generierung für Order-IDs
- Admin-Interface
- WooCommerce Order Actions Integration
- FPDF-Bibliothek integriert
- Sicherheitsfeatures

## Credits

- FPDF Library: Olivier Plathey
- Development: WP Xpert
