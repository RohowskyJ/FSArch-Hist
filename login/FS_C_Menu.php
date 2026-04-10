<?php
/**
 * Haupt- Menu im internen Bereich
 * 2012 neu Josef Rohowsky
 */
session_start();

$module = 'VFH';
$sub_module = 'IntStart';

var_dump($_SESSION['BS_Prim']);
if (isset($_SESSION['BS_Prim']['BE']['be_id']) && $_SESSION['BS_Prim']['BE']['be_id'] >= 1 && $_SESSION['BS_Prim']['BE']['roles'] != "") {
    $_SESSION['BS_Prim']['Mod'] = ['module' => $module, 'smod' => $sub_module, 'caller' => $module];  
} else {
    header("Location: Basis/cmmon/FS_Login.php");
}
var_dump($_SESSION['BS_Prim']);
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
$_SESSION[$module]['Inc_Arr'][] = "VF_C_Menu.php"; 

$debug = False; // Debug output Ein/Aus Schalter

/**
 * Bootstrap: Composer-/Shared-Einstieg mit Pfadhelder
 */
$rootPfad = $_SERVER['DOCUMENT_ROOT'];
$caller = $_SERVER['REQUEST_URI'];
$cal_arr = explode("/",$caller);
require_once __DIR__ . '/../login/bootstrap.php';
fsarch_bootstrap_path_init('/'.$cal_arr[1]);

require PathHelper::fs('Basis/common/BS_FuncsLib.php');
require PathHelper::fs('Basis/common/FS_CommFuncsLib.php');
#require PathHelper::fs('Basis/common/FS_ConstLib.php');
#require PathHelper::fs('Basis/common/FS_ConfigLib.php');
# require $path2ROOT . 'login/Basis/BS_Funcs_lib.php';
# require $path2ROOT . 'login/Basis/FS_CommFuncs_lib.php';


require $path2ROOT .  'login/common/VF_Comm_Funcs.lib.php';

# require $path2ROOT .  'login/common/BA_Edit_Funcs.lib.php';
# require $path2ROOT .  'login/common/BS_FormsFlex_CLS.php';
# require $path2ROOT .  'login/common/BS_TabSpalt_CLS.php';

$title = "Haupt- Menu";

$jq = true;
$form_start = True;
$actor = "VS_C_Menu.php";
HTML_header($title, '', 'Form', '70em'); 

$flow_list = False;

var_dump($_SESSION);

if (isset($_POST['sessionData'])) {
    $_SESSION['BS_Prim']['BE'] = $_POST['sessionData'];
    var_dump($_SESSION['BS_Prim']);
}

initial_debug('POST', 'GET'); # Wenn $debug=true - Ausgabe von Debug Informationen: $_POST, $_GET, $_FILES

    echo  "<div class='Menu-Header'>Programmauswahl für Mitglieder</div>";
    
    echo "<div class='Menu-Separator'> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Suchen nach Suchbegriffen</div>";

    echo "<div class='Menu-Line'>"; // Kommentar
    echo "Suchen in archivalien, Inventar, Fotos und Beschreibungen von Fahrzeugen und Geräten: muskelgezogen und Motorgezogen";
    echo "</div>"; // Ende der Ausgabe- Kommentar
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe

    echo "<a href='VF_S_SU_Ausw.php' target='Suchausw'>Suchen nach Suchbegriffen</a>";

    echo "</div>"; // Ende der Ausgabe- Einheit Feld
    echo "<div class='Menu-Separator'>"; // Kommentar
    echo 'Referat 1 - Organisation ' ;
    echo "</div>";
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "<b>Datenabfrage laut DSVGO, E-Mail an andere Mitglieder, Protokolle, </b> Verwaltung der Daten von Mitgliedern, Benutzern und Zugriffen, Eigentümern, Empfängerliste autom. E-Mails, .... ";
    echo "</div>"; // Ende der Inhalt Spalte
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "<a href='FS_V_Zentral_Verw.php' target='Zentrale Verwaltung'>Zentrale Verwaltung Basisdaten</a>";
    echo "</div>"; // Ende der Ausgabe- Einheit Feld
    echo "<div class='Menu-Separator'>"; // Kommentar
    echo 'Referat 2 - Fahrzeuge und Geräte, mit Muskel oder Motor bewegt, Beschreibungen <br>';
    echo "</div>";
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "Beschreibungen von Fahrzeugen und Geräten: muskelgezogen und Motorgezogen";
    
    echo "</div>"; // Ende der Inhalt Spalte
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
   
        echo "<a href='FS_FZGer_Verw.php' target='F-Verwaltung'>Fahrzeug und Geräte- Verwaltung </a>";
 
    echo "</div>"; // Ende der Ausgabe- Einheit Feld
    echo "<div class='Menu-Separator'>"; // Kommentar
    echo 'Referat 3 - Öffentlichkeitsarbeit und Museen <br>';
    echo "</div>";
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "Links zu Bibliotheken, Marktplatz, Buch- Rezensionen, Dokumente zu herunterladen, Fotos, Videos, Museumsdaten, Presseberichte, Terminplan, Veranstaltungsberichte.";
    echo "</div>"; // Ende der Inhalt Spalte
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "<a href='FS_O_Verw.php' target='Oeffi'>Öffentlichkeitsarbeit</a>";
    echo "</div>"; // Ende der Ausgabe- Einheit Feld

    echo "<div class='Menu-Separator'>"; // Kommentar
    echo 'Persönliche Ausrüstung - Beschreibungen <br>';
    echo "</div>";
        echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
        echo "Beschreibungen für alle Gegenstände die je FF Mitglied direkt der Person zugordnet ist (Uniform, Auszeichnugen, ... )";
        echo "</div>"; // Ende der Inhalt Spalte
        echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
        
            echo "<a href='FS_PS_Info_Ausz_Abz.php' target='Info_R4'>Auszeichnungen, Ärmelabzeichen, Wappen - DA 1.5.3, Uniformen, Heraldik</a>";
  
        echo "</div>"; // Ende der Ausgabe- Einheit Feld

    echo "<div class='Menu-Separator'>"; // Beginn der Einheit Ausgabe
    echo " &nbsp &nbsp &nbsp  &nbsp &nbsp &nbsp <a href='../VFH'>LOGOFF (HomePage)</a>";
    echo "</div>"; // Ende der Inhalt Spalte
    

HTML_trailer();
?>
