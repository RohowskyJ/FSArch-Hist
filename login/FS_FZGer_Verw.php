<?php

/**
 * Geräte- Verwaltung, Auswahl
 *
 * @author Josef Rohowsky - neu 2023
 */
session_start(); // die SESSION aktivieren

$module  = 'F_G';
$sub_mod = 'all';

/**
 * Includes-Liste
 * enthält alle jeweils includierten Scritpt Files
 */
$_SESSION[$module]['Inc_Arr']  = array();
$_SESSION[$module]['Inc_Arr'][] = "FS_FZGer_Verw.php"; 

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../";

$debug = false; // Debug output Ein/Aus Schalter

/**
 * Bootstrap: Composer-/Shared-Einstieg
 */
require_once __DIR__ . '/../login/Basis/bootstrap.php';

require $path2ROOT . 'login/Basis/BS_Funcs.lib.php';
require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';


# $LinkDB_database  = '';
# $db = LinkDB('VFH');

# ===========================================================================================================
# Haeder ausgeben
# ===========================================================================================================
# $flow_list = false;

HTML_header('Fahrzeuge und Geräte', '', 'Form', '70em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

initial_debug('POST','GET');
/*
VF_chk_valid();

VF_set_module_p();

VF_Count_add();
*/
$sk = "";

echo  "<div class='Menu-Header'>Beschreibungen der Fahrzeuge und Geräte</div>";

echo "<div class='Menu-Separator'>Mit Muskelkraft bewegte Fahrzeuge und Geräte</div>";

echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
echo "Beschreibungen der Muskelbewegten Fahrzeuge und Geräte</div>";
echo "  </div>";  // Ende Feldname
echo "<div class='Menu-Line'>"; // Beginn Inhalt- Spalte
echo "<a href='VF_FZ_MuFG_List.php?sk=$sk&ID=NextEig' target='MuskelFzgGer'>Muskelgezogene Fahrzeuge und muskelbetriebene Geräte - Wartung </a>";
echo "</div>"; // Ende der Inhalt Spalte
echo "<div class='Menu-Line'>"; // Beginn Inhalt- Spalte
echo "<a href='VF_FZ_MuFG_Katalog_List.php?sk=$sk' target='MuskelFzgGerKat'>Katalog der Muskelgezogenen Fahrzeug- und Geräte</a>";
echo "</div>"; // Ende der Inhalt Spalte
echo "<div class='Menu-Line'>"; // Beginn Inhalt- Spalte
echo "<a href='VF_O_DO_List.php?sk=$sk&sel_thema=1' target='Dokumente'>Vereins- Dokumentation zu Muskelbewegtem</a>";
echo "</div>"; // Ende der Inhalt Spalte

echo "<div class='Menu-Separator'>Maschinenbewegte Fahrzeuge (Automobile) und motorbetriebene Geräte </div>";
echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
echo "Beschreibungen der mit Motorkraft bewegten Fahrzeuge und für Motorfahrzeuge konstruierten Anhänger, Geräte.";
echo "  </div>";  // Ende Feldname
echo "<div class='Menu-Line'>"; // Beginn Inhalt- Spalte
echo "<a href='VF_FZ_MaFG_List.php?sk=$sk&ID=NextEig' target='MotorFahrzeuge'>Motorisierte Fahrzeug- und Geräte Wartung </a>";
echo "</div>"; // Ende der Inhalt Spalte

echo "<div class='Menu-Line'>"; // Beginn Inhalt- Spalte
echo "<a href='VF_FZ_MaFG_Katalog_List.php?sk=$sk' target='Automobil-Katalog'>Automobil- Fahrzeugkatalog</a>";
echo "</div>"; // Ende der Inhalt Spalte

echo "<div class='Menu-Line'>"; // Beginn Inhalt- Spalte
echo "<a href='VF_O_DO_List.php?sk=$sk&sel_thema=2' target='Dokumente'>Vereins- Dokumentation zu Fahrzeugen </a></td></tr>";
echo "</div>"; // Ende der Inhalt Spalte


// $res = VF_Urh_ini();

HTML_trailer();
