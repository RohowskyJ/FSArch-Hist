<?php

/**
 * Menu Mitgliederverwaltung
 * 
 * @author Josef Rohowsky - neu 2023
 */
session_start();

$module = 'MVW';
$sub_mod = 'all';

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../../";


$debug = False; // Debug output Ein/Aus Schalter

/**
 * Bootstrap: Composer-/Shared-Einstieg
 */
/**
 * Bootstrap: Composer-/Shared-Einstieg
 */
$rootPfad = $_SERVER['DOCUMENT_ROOT'];
$caller = $_SERVER['REQUEST_URI'];
$cal_arr = explode("/",$caller);
require_once __DIR__ . '/../Basis/bootstrap.php';
fsarch_bootstrap_path_init('/'.$cal_arr[1]);

require PathHelper::fs('Basis/BS_Funcs_lib.php');
require PathHelper::fs('Basis/FS_CommFuncs_lib.php');

# require $path2ROOT . 'login/Basis/BS_Funcs_lib.php';
# require $path2ROOT . 'login/Basis/FS_CommFuncs_lib.php';

require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';

$flow_list = False;

initial_debug('POST', 'GET');

# ===========================================================================================================
# Haeder ausgeben
# ===========================================================================================================

HTML_header('Mitglieder- Verwaltung', '', 'Form', '70em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

if (userHasRole('ADM-MI')) {  // Ist benutzer berechtigt?

    echo "<div class='Menu-Separator'>Mitglieder- Verwaltung</div>";
    
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "<tr><td><a href='VS_M_List.php' target='M-Verwaltung'>Mitgliederverwaltung</a></td></tr>";
    echo "  </div>";  // Ende Feldname
    
    echo "<div class='Menu-Separator'>Ehrungen- Verwaltung</div>";
    
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "<tr><td><a href='VF_M_Ehrg_List.php?' target='M-Verwaltung'>Ehrungen</a></td></tr>";
    echo "  </div>";  // Ende Feldname

    echo "<div class='Menu-Separator'>Unterstützer- Verwaltung</div>";
    
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "<tr><td><a href='VS_UnterstList.php?' target='M-Verwaltung'>Unterstützer</a></td></tr>";
    echo "  </div>";  // Ende Feldname
    
}

if (userHasRole('ADM-MB')) {  // Ist Benutzer berechtigt?
    
    echo "<div class='Menu-Separator'>Mitglieder- Zahlungseingangs- Verwaltung</div>";
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "Hier werden die Zahlungseingänge (Mitgliedsbeitrag und ABO- Gebühr verwaltet).";
    echo "  </div>";  // Ende Feldname
    
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "<a href='VS_MB_List.php' target='M Bez.-Verwaltung'>Beitrags- Eingang</a>";
    echo "  </div>";  // Ende Feldname
}

echo "<div class='Menu-Separator'>Mitglieder- E-Mail an</div>";

echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
echo "Mitglieder können E-Mails an andere Mitglieder senden, ohne das Sie die E-Mail Adresse kennen.</a>";
echo "  </div>";  // Ende Feldname

echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
echo "<a href='VF_M_Mail.php' target='M-Mail'>Mail an andere Mitglieder senden </a>";
echo "  </div>";  // Ende Feldname

echo "<div class='Menu-Separator'>Mitglieder- Auskuft laut DSVGO</div>";

echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
echo "<tr><td>Jedes Mitglied kann sich die im System gespeicherten persönliche Daten entsprechend der DSVGO selbst anfordern und bekommt sie sofort per E-Mail zugeschickt.</td></tr>";
echo "  </div>";  // Ende Feldname

echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
echo "<tr><td><a href='VF_M_yellow.php' target='M-Datenabfrage'>Mitglieder-Daten Auskunft laut DSGVO</a></td></tr>";
echo "  </div>";  // Ende Feldname

HTML_trailer();
?>
