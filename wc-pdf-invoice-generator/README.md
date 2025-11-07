# WC PDF Invoice Generator

Ein WordPress Plugin für WooCommerce, das PDF-Rechnungen für Bestellungen generiert und im Plugin-Verzeichnis speichert.

## Funktionen

- **Einzelne PDF-Generierung**: Generiere PDF-Rechnungen für spezifische Order-IDs
- **Bulk-Generierung**: Generiere PDFs für mehrere Bestellungen basierend auf Status
- **Automatische Speicherung**: PDFs werden im Plugin-Ordner `/pdfs/` gespeichert
- **Admin-Interface**: Benutzerfreundliche Oberfläche im WordPress-Admin
- **WooCommerce Integration**: Nahtlose Integration in WooCommerce-Bestellungen
- **Anpassbare Templates**: Vollständig anpassbare PDF-Templates
- **Sicherheit**: `.htaccess` und `index.php` Schutz für PDF-Ordner

## Voraussetzungen

- WordPress 5.8 oder höher
- WooCommerce 5.0 oder höher
- PHP 7.4 oder höher
- TCPDF-Bibliothek (siehe Installation)

## Installation

### 1. Plugin-Dateien hochladen

Lade den `wc-pdf-invoice-generator` Ordner in das `/wp-content/plugins/` Verzeichnis deiner WordPress-Installation.

### 2. TCPDF-Bibliothek installieren

Es gibt zwei Möglichkeiten, TCPDF zu installieren:

#### Option A: Mit Composer (empfohlen)

```bash
cd wp-content/plugins/wc-pdf-invoice-generator
composer require tecnickcom/tcpdf
```

#### Option B: Manuelle Installation

1. Lade TCPDF von [https://github.com/tecnickcom/TCPDF/releases](https://github.com/tecnickcom/TCPDF/releases) herunter
2. Entpacke die Dateien in `/wp-content/plugins/wc-pdf-invoice-generator/includes/tcpdf/`
3. Stelle sicher, dass die Datei `tcpdf.php` unter `/includes/tcpdf/tcpdf.php` liegt

#### Option C: Alternative mit FPDF (leichtgewichtig)

Falls TCPDF Probleme macht, kannst du auch FPDF verwenden:

```bash
cd wp-content/plugins/wc-pdf-invoice-generator
composer require setasign/fpdf
```

Dann passe die `class-pdf-generator.php` entsprechend an.

### 3. Plugin aktivieren

1. Gehe zu **Plugins** > **Installierte Plugins**
2. Suche nach "WC PDF Invoice Generator"
3. Klicke auf **Aktivieren**

## Verwendung

### PDF für einzelne Bestellung generieren

1. Gehe zu **WooCommerce** > **PDF Rechnungen**
2. Gib die Order-ID ein
3. Klicke auf **PDF generieren**
4. Die PDF wird im `/pdfs/` Ordner gespeichert

### Aus der Bestellansicht generieren

1. Öffne eine WooCommerce-Bestellung
2. Scrolle zu **Bestellaktionen**
3. Wähle **PDF-Rechnung generieren**
4. Klicke auf den Pfeil-Button

### Bulk-Generierung

1. Gehe zu **WooCommerce** > **PDF Rechnungen**
2. Wähle den Bestellstatus
3. Gib die Anzahl ein (max. 100)
4. Klicke auf **Bulk PDFs generieren**

## Verzeichnisstruktur

```
wc-pdf-invoice-generator/
├── includes/
│   ├── class-pdf-generator.php    # PDF-Generierungs-Logik
│   ├── class-admin-menu.php       # Admin-Interface
│   └── tcpdf/                     # TCPDF-Bibliothek (manuell hinzufügen)
├── templates/
│   └── invoice-template.php       # PDF-Template
├── pdfs/                          # Generierte PDFs (automatisch erstellt)
│   ├── .htaccess                  # Sicherheit
│   └── index.php                  # Sicherheit
├── wc-pdf-invoice-generator.php   # Haupt-Plugin-Datei
├── composer.json                  # Composer-Abhängigkeiten
└── README.md                      # Diese Datei
```

## Template-Anpassung

Um das PDF-Template anzupassen:

1. Kopiere `templates/invoice-template.php`
2. Füge es in dein Theme ein: `dein-theme/wc-pdf-invoice-generator/invoice-template.php`
3. Passe das Template nach deinen Wünschen an
4. Das Plugin verwendet automatisch dein Theme-Template

## API-Verwendung

### Programmatisch PDF generieren

```php
// PDF für Order-ID 123 generieren
$generator = new WC_PDF_Invoice_PDF_Generator();
$pdf_path = $generator->generate_invoice( 123 );

if ( $pdf_path ) {
    echo 'PDF generiert: ' . $pdf_path;
}
```

### PDF-Pfad abrufen

```php
$generator = new WC_PDF_Invoice_PDF_Generator();
$pdf_path = $generator->get_pdf_path( 123 );

if ( $pdf_path && file_exists( $pdf_path ) ) {
    echo 'PDF existiert: ' . $pdf_path;
}
```

### PDF löschen

```php
$generator = new WC_PDF_Invoice_PDF_Generator();
$deleted = $generator->delete_pdf( 123 );
```

## Hooks & Filter

### Actions

```php
// Nach erfolgreicher PDF-Generierung
do_action( 'wc_pdf_invoice_generator_after_generate', $order_id, $pdf_path );

// Vor PDF-Generierung
do_action( 'wc_pdf_invoice_generator_before_generate', $order_id );
```

### Filter

```php
// PDF-Template-Pfad ändern
add_filter( 'wc_pdf_invoice_generator_template_path', function( $path, $order ) {
    return '/custom/path/to/template.php';
}, 10, 2 );

// PDF-Dateinamen ändern
add_filter( 'wc_pdf_invoice_generator_filename', function( $filename, $order ) {
    return 'custom-invoice-' . $order->get_id() . '.pdf';
}, 10, 2 );
```

## Sicherheit

- PDFs werden im geschützten Plugin-Ordner gespeichert
- `.htaccess` verhindert Verzeichnisauflistung
- Nur authentifizierte Admins können PDFs generieren
- Nonce-Validierung für alle Formular-Aktionen

## Fehlerbehebung

### PDFs werden nicht generiert

1. Überprüfe, ob TCPDF installiert ist
2. Überprüfe Schreibrechte für `/pdfs/` Ordner: `chmod 755 pdfs`
3. Prüfe PHP Error Log auf Fehlermeldungen
4. Stelle sicher, dass WooCommerce aktiv ist

### "Class TCPDF not found"

- TCPDF wurde nicht korrekt installiert
- Folge den Installationsanweisungen oben

### PDFs sind leer oder fehlerhaft

- Überprüfe, ob die Bestellung gültige Daten enthält
- Teste mit einer abgeschlossenen Bestellung
- Prüfe das Template auf Syntax-Fehler

## Systemanforderungen

- **PHP**: 7.4 oder höher
- **WordPress**: 5.8 oder höher
- **WooCommerce**: 5.0 oder höher
- **PHP Extensions**:
  - GD oder Imagick (für TCPDF)
  - mbstring
  - zip

## Lizenz

Dieses Plugin ist Open Source und unter der GPL v2 oder höher lizenziert.

## Support

Bei Fragen oder Problemen:

1. Überprüfe die Dokumentation
2. Suche in den WordPress-Foren
3. Erstelle ein Issue auf GitHub

## Changelog

### Version 1.0.0
- Initiales Release
- Einzelne PDF-Generierung
- Bulk-Generierung
- Admin-Interface
- Template-System
- WooCommerce-Integration

## Mitwirken

Contributions sind willkommen! Bitte erstelle einen Pull Request auf GitHub.

## Autor

WP Xpert - [https://github.com/wp-xpert](https://github.com/wp-xpert)
