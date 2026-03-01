# VS_MB_List.php - Detaillierte Beschreibung

## 📋 Übersicht des Scripts

Dieses Script ist Teil der **Mitgliederverwaltung** und dient zur **Anzeige und Verwaltung von Mitgliederbeitragszahlungen**. Es wurde von Josef Rohowsky im Jahr 2020 entwickelt.

---

## 🎯 Hauptzweck

Das Script erstellt eine Listendarstellung aller Mitglieder mit ihren **Zahlungsstati** für:
- Mitgliedsbeiträge der letzten 2 Jahre, des aktuellen Jahres und des nächsten Jahres
- Abonnementgebühren
- Die Möglichkeit, unbezahlte Zahlungen direkt zu verbuchen

---

## 🔧 Funktionen

### 1. Initialisierung und Konfiguration

- **Session-Start**: Startet die PHP-Session für Benutzerverwaltung
- **Error Handling**: Konfiguriert Fehlerausgabe und schreibt Fehler in Log-Datei (`VS_MB_List_php-error.log.txt`)
- **Module**: `'ADM-MI'` (Admin-Mitgliederverwaltung), Sub-Modul `"Bez"` (Bezahlung)
- **Datenbankverbindung**: Nutzt Tabelle `fv_mitglieder`

### 2. Pfad- und Include-Management

- Lädt automatisch benötigte Bootstrap-Klassen über `PathHelper` und `AppAutoloader`
- Includes wichtige Bibliotheken:
  - `BS_Funcs.lib.php` - Basis-Funktionen
  - `VF_Comm_Funcs.lib.php` - Allgemeine Kommunikationsfunktionen
  - `VF_Const.lib.php` - Konstanten
  - `FS_CommFuncs_lib.php` - Datenbankfunktionen
  - `BS_ListFuncs_lib.php` - Listen-Darstellungsfunktionen

### 3. Header und UI-Grundgerüst

- Ruft `HTML_header()` auf zum Generieren von HTML-Header
- Erstellt ein Fieldset-Container für die Formularelemente
- Lädt CSS und JavaScript-Dateien (TABU-Theme)

### 4. Phasen-Management

```php
if ($phase == 99) {
    header("Location: /login/FS_C_Menu.php");
}
```

- Definiert verschiedene Verarbeitungs-Phasen
- Phase 99 = Abbruch/Rückkehr zum Hauptmenü

### 5. Auswahlfunktionen (Filteroptionen)

Bietet unterschiedliche Ansichten über Radio-Buttons:

- **"Alle"** - Alle aktiven Mitglieder
- **"offenAlle"** - Aktive Mitglieder mit ausstehenden Zahlungen für das aktuelle Jahr
- **"bezahlt"** - Mitglieder mit bezahlten Beiträgen des aktuellen Jahres
- **"sticht"** - Nach Stichtag sortierte Zahler (30.06. oder 31.12.)
- **"EM"** - Nicht zahlende Mitglieder (EM oder OE-Typen)

### 6. Berichtszeitraum-Logik

```php
if ($d_arr[1] >= '01' && $d_arr[1] < '03') {
    $ber_y = date('Y') - 1;
    $ber_zeitr .= date('Y-m-d', strtotime("$ber_y-12-31"));
}
```

- Ermittelt automatisch relevante Stichtage basierend auf aktuellem Monat
- Januar-März: Stichtag 31.12. des Vorjahres
- Juli-September: Stichtag 30.06. des aktuellen Jahres

### 7. SQL-Query-Generierung

Das Script erstellt dynamisch SQL-Queries basierend auf dem ausgewählten Filter:

**Beispiel für "Alle"-Filter:**
```sql
SELECT * FROM fv_mitglieder 
WHERE mi_mtyp<>'OE' 
AND (mi_austrdat IS NULL OR mi_austrdat = '') 
AND (mi_sterbdat IS NULL OR mi_sterbdat = '')
ORDER BY mi_name, mi_vname ASC
```

**Berücksichtigte Filter:**
- Mitgliedertyp (ausgeschlossene OE-Einträge)
- Austrittsdatum und Sterbedatum (nur aktive Mitglieder)
- Suchstring für Namensfiltierung

### 8. Haupt-Funktion: `modifyRow()` ⭐

Dies ist die zentrale Funktion für die Darstellung:

**Parameter:**
- `$row` (array) - Datensatz eines Mitglieds
- `$tabelle` (string) - Tabellenname

**Funktionalität:**

#### a) Grundlegende Datenaufbereitung

- Konvertiert Organisationsnamen zu Mitgliedsnamen wenn nötig
- Extrahiert Beitrittsjahr aus Beitrittsdatum

#### b) Status-Symbole

- 🟢 **Checkmark** (`✓`) - Zahlung gebucht/bezahlt
- 🔴 **Cross** (`✗`) - Zahlung nicht möglich
- 🟡 **Euro** (`€`) - Zahlung ausstehend, zum Klicken

#### c) Zahlungsstatus-Berechnung

Für **Mitgliedsbeiträge vor 2 Jahren** (`$BM_m2`):
```php
if ($mi_beitr_jahr <= $curjahr_m2 && $row['mi_m_beitr_bez_bis'] >= $curjahr_m2)
```

Ähnlich für: `$BM_m1` (vor 1 Jahr), `$BM` (heuer), `$BM_1` (nächstes Jahr)

#### d) Zahlungs-Links generieren

Wenn Zahlung ausstehend, wird interaktiver Link als Datenelement erstellt:
```php
<a href='#' class='pay-link' 
   data-mi_id='...' 
   data-field='M_0'      // M=Mitglied, 0=aktuelles Jahr
   data-year='...'
   data-user_id='...'>€</a>
```

**Zahlungstypen:**
- `M_0`, `M_p` - Mitgliedsbeitrag (aktuelles/nächstes Jahr)
- `A_0`, `A_p` - Abo (aktuelles/nächstes Jahr)
- `MA_0`, `MA_p` - Mitgliedsbeitrag + Abo kombiniert

#### e) Farbkodierung der Zahlungsdaten

- **Grün** = Zahlung gebucht
- **Rot** = Zahlung ausstehend

#### f) Korrekturfunktion

Wenn eine Zahlung für das aktuelle Jahr gebucht ist, wird ein **"Korr"**-Link angezeigt:
```php
if ($row['mi_m_beitr_bez'] >= $curjahr || $row['mi_m_abo_bez'] >= $curjahr)
```
Ermöglicht Rücksetzen von fehlerhaft gebuchten Zahlungen.

---

## 📊 Datenbankfelder (verwendet)

- `mi_id` - Mitglieds-ID
- `mi_name`, `mi_vname` - Name und Vorname
- `mi_mtyp` - Mitgliedstyp (OE, EM, regulär)
- `mi_beitritt` - Beitrittsdatum
- `mi_m_beitr_bez_bis` - Mitgliedsbeitrag bezahlt bis (Datum)
- `mi_m_abo_bez_bis` - Abo bezahlt bis (Datum)
- `mi_m_beitr_bez` - Letztes Zahlungsdatum Mitgliedsbeitrag
- `mi_m_abo_bez` - Letztes Zahlungsdatum Abo
- `mi_austrdat` - Austrittsdatum
- `mi_sterbdat` - Sterbedatum
- `mi_org_name` - Organisationsname (für juristische Personen)

---

## 🎨 Frontend-Integration

- Lädt externes **JavaScript** (`MIB_Payment.js`) für AJAX-Zahlungsabwicklung
- Nutzt **CSS-Styling** aus `TABU_css_lib.php` und `TABU_js_lib.php`
- Responsive Tabellendarstellung mit Tooltips

---

## 🔄 Workflow

1. **Benutzer wählt** Filter-Option (Alle/Offene/Bezahlte/EM)
2. **Script erstellt** SQL-Query basierend auf Auswahl
3. **Daten werden geladen** und in **`modifyRow()`** aufbereitet
4. **Tabelle wird gerendert** mit Zahlungs-Status-Symbolen
5. **Benutzer klickt** auf Euro-Symbol zum Zahlung verbuchen
6. **JavaScript-Handler** (`MIB_Payment.js`) verarbeitet die Zahlung

---

## 📌 Zusammenfassung

Dieses Script ist ein **Mitgliederbeitrag-Buchungsverwaltungstool**, das:
- Zahlungsstati visualisiert und farblich kennzeichnet
- Flexible Filterungsmöglichkeiten bietet
- Die direkte Eingabe neuer Zahlungen ermöglicht
- Korrekturmechanismen für Fehlbuchungen bereitstellt
- Automatische Berichtszeiträume basierend auf aktuellem Datum berechnet
