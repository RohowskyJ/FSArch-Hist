<?php
/**
 * Zentrale Verwaltung
 * 
 * @author Josef Rohowsky - neu 2020
 */
session_start();

$module = 'ADM';
$sub_adm = 'all';

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../";

/**
 * Includes-Liste
 * enthält alle jeweils includierten Scritpt Files
 */
$_SESSION[$module]['Inc_Arr']  = array();
$_SESSION[$module]['Inc_Arr'][] = "FS_V_Zentral_Verw.php"; 

$debug = False; // Debug output Ein/Aus Schalter

require $path2ROOT . 'login/common/BS_Funcs_lib.php';
require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';

$flow_list = False;

initial_debug('POST','GET');

# ===========================================================================================================
# Haeder ausgeben
# ===========================================================================================================

HTML_header('Administration', '', 'Form', '70em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

echo "<div class='Menu-Separator'>Mitglieder- Verwaltung</div>";
echo "<div class='w3-row' >"; // Beginn der Einheit Ausgabe
echo "Verwaltung der Mitglieder, Zahlungeingang und Kontrolle, Mitteilung der gespeicherten Daten nach DSGVO, E-Mail an andere Mitglieder ohne Kenntnis deren Adresse.<br>";
echo "<a href='Mitglieder/VS_MitglVerw.php' target='M-Verwaltung'>Mitgliederverwaltung</a>"; # neu OK
echo "</div>";

if (userHasRole('ADM-MA')) {  // Ist benutzer berechtigt?
    echo "<div class='w3-row' >"; // Beginn der Einheit Ausgabe
    echo "<div class='Menu-Separator'>Eigentümerverwaltung </div>";
    echo "Da hier auch Daten von Nicht-Mitgliedern aufgenommen werden können, ist eine eigene Verwaltung ohne Mitglieder-Bezug notwendig.<br>";
    echo "<a href='VF_Z_E_List.php' target='Eigentm'>Eigentümerverwaltung </a>"; # neu OK
    echo "</div>";

    echo "<div class='Menu-Separator'>Liste der Empfänger von administrativen E-Mails (Mitglieds- Neuanmeldung, Bezahlung, ... </div>";;
    echo "<div class='w3-row' >"; // Beginn der Einheit Ausgabe
    echo "<a href='VF_Z_EM_List.php' target='Mail_List'>Empfänger der automatischen E-Mails</a>"; # neu ok
    echo "</div>";
}
 
if (userHasRole('ADM-MI')) {  // Ist benutzer berechtigt?
    echo "<div class='Menu-Separator'>Benutzer- und Zugriffsverwaltung</div>";;
    echo "<div class='w3-row' >"; // Beginn der Einheit Ausgabe
    echo "Pflege der berechtigten Benutzer, Passworte und Berechtigungen.</d><br>";
    echo "<a href='VF_Z_B_List.php' target='Benutz'>Benutzer- und Zugriffs- Verwaltung </a>"; # neu
    echo "</div>";
}

    echo "<div class='Menu-Separator'>Firmen (Fzg/Gerät - Hersteller/Aufbauer) </div>";
    echo "<div class='w3-row' >"; // Beginn der Einheit Ausgabe
    echo "<tr><TD>Liste Fahrzeug- und Geräte- Hersteller und Aufbauer </d><br>";
    echo "<a href='VF_Z_FI_List.php' target='Config'>Firmen</a>"; # neu OK
    echo "</div>";
    
    echo "<div class='Menu-Separator'>Abkürzungen </div>";
    echo "<div class='w3-row' >"; // Beginn der Einheit Ausgabe
    echo "<tr><TD>Abkürzungen im Fahrzeug- Gerätebereich  </d><br>";
    echo "<a href='VF_Z_AB_List.php' target='Config'>Abkürzungen</a>"; # neu OK
    echo "</div>";
if (userHasRole('ADM-ALLE')) {  // Ist benutzer berechtigt?
    echo "<div class='Menu-Separator'>Konfiguration der Seite </div>";
    echo "<div class='w3-row' >"; // Beginn der Einheit Ausgabe
    echo "<tr><TD>Betreiber der Seite, Vereinsregister, E-Mail-Adresse,  </d><br>";

    echo "<a href='common/Proj_Conf_Edit.php' target='Config'>Konfigurations- Parameter der URL</a>"; # neu OK
    echo "</div>";

    
        echo "<div class='Menu-Separator'>Prozesse, die zu Analysen und Korrekturen dienen, aber unter Umständen vorher geändert/angepasst werden müssen.</div>";
        echo "<div class='w3-row' >"; // Beginn der Einheit Ausgabe
        echo "Pflege verschiedener Daten </br>";
        echo "<a href='VF_Z_Suchb_Gen.php' target='suchbegr'>Suchbegriffe (Findbücher) regenerieren </a><br>";
        echo "<a href='VF_Z_Pict_Valid.php' target='Bilder Prüfg'>Bilder- Prüfung (Tabellen - Dirs / vorhanden - nicht vorhanden)</a><br>";
        
        echo "<a href='VF_Z_AR_Renum_AN.php?ei_id=1' target='ArchNr-Renum'>Archiv- Nummern Renum Eig=1 (Verein)</a><br>";
        echo "<a href='VF_Z_AR_Renum_AN.php?ei_id=21' target='ArchNr-Renum'>Archiv- Nummern Renum Eig=21 (FF WrNdf)</a><br>";
        echo "</div>";
        echo "<div>";
        echo "<div class='Menu-Separator'>Daten von CSV-Datei in Tabellen einlesen:</div>";;
        echo "Dateiformat:<br>";
        echo "1. Zeile: Tabellen- Name, z.B.: Test_tab<br>";
        echo "2. Zeile: fld_nam1|fld-nam2| ....<br>";
        echo "ab der 3. Zeile: Inhalte, z.B.: inh1|inh2| ....<br>";
        echo "<a href='VF_Z_DS_2_Table.php' target='Flat-File Imp'>FlatFile Import in eine Tabelle</a><br>";
        echo "</div>";
        
        echo "<div class='Menu-Separator'>Datenbank- Tabellen Exportieren und Importieren:</div>";
        echo "<a href='VF_Z_DB_backup.php' target='DB_BU'>Datenbank Sichern und wieder Herstellen</a><br>";
        echo "</div>";
    
    
    echo "<div class='Menu-Separator'>Sitzungs- Protokolle'</div>";
    echo "<div class='w3-row' >"; // Beginn der Einheit Ausgabe
    echo "Protokolle, .... .<br>";
    echo "<a href='VF_P_RO_List.php' target='P-Verwaltung'>Liste der Protokolle</a>";
    echo "</div>";
}


echo "<div class='Menu-Separator'>Mitglieder- E-Mail an </div>";
echo "<div class='w3-row' >"; // Beginn der Einheit Ausgabe
echo "Mitglieder können E-Mails an andere Mitglieder senden, ohne das Sie die E-Mail Adresse kennen.</a><br>";
echo "<a href='VF_M_Mail.php' target='M-Mail'>Mail an andere Mitglieder senden </a>";
echo "</div>";

echo "<div class='Menu-Separator'>Mitglieder- Auskuft laut DSVGO</div>";;
echo "<div class='w3-row' >"; // Beginn der Einheit Ausgabe
echo "<Jedes Mitglied kann sich die im System gespeicherten persönlichne Daten entsprechend der DSVGO selbst anfordern und bekommt sie sofort per E-Mail zugeschickt.<br>";
echo "<a href='VF_M_yellow.php' target='M-Datenabfrage'>Mitglieder-Daten Auskunft laut DSGVO</a></td></tr>";
echo "</div>";

HTML_trailer();
?>
