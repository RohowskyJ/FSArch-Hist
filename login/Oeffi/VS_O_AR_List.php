<!DOCTYPE html>
<?php
# session_start();
/**
 * Mitglieder Verwaltung Liste
 * 
 * @author Josef Rohowsky - neu 2020 - Umstellung Klassen/PDO, Module 2026
 * 
 * 
 */
session_start();

$module = 'OEF-AR';
$sub_mod = "LIST";

$tabelle = 'fv_falink';// <?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/VS_M_List_php-error.log.txt');
# var_dump($_SERVER);

$rootPfad = $_SERVER['DOCUMENT_ROOT'];
$caller = $_SERVER['REQUEST_URI'];
$cal_arr = explode("/",$caller);
# var_dump($cal_arr);
require_once $rootPfad . '/'.$cal_arr[1].'/login/BS_BootPfadL_CLS.php';

PathHelper::init('/'.$cal_arr[1]);  // Basis-URL anpassen
AppAutoloader::register();

/**
 * Includes-Liste
 * enthält alle jeweils includierten Scritpt Files
 */

$_SESSION[$module]['Inc_Arr']  = array();
$_SESSION[$module]['Inc_Arr'][] = "VS_O_AR_List.php"; 

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../../";

$debug = False; // Debug output Ein/Aus Schalter

require $path2ROOT . 'login/common/BS_Funcs_lib.php';

require $path2ROOT . 'login/common/FS_CommFuncs_lib.php';

require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';
require $path2ROOT . 'login/common/VF_Const.lib.php';


$header =   ""; 
# ===========================================================================================================
# Haeder ausgeben
# ===========================================================================================================
$ListHead = "Links zu Öffentl. Bibliotheken und Archiven";
$title = "Mitglieder Daten";
# $TABU = true;
$TABUcss = true;
HTML_header('Biblitheks- und Archiv- Links', $header, 'Admin', '80em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

$moduleId = $module."-".$sub_mod;
// Eigene Meldung mit Modulkennung loggen
# $logger->log('Starte Verarbeitung des Moduls', $moduleId, basename(__FILE__));

// XR_Database mit bestehender PDO-Instanz initialisieren
$DBD = new FS_Database();
# var_dump($DBD);
$pdo = $DBD->getPDO();
# var_dump($pdo);

$flow_list = False;
$_SESSION[$module]['Return'] = False;

if (isset($_POST['phase'])) {
    $phase = $_POST['phase'];
} else {
    $phase = 0;
}
if ($phase == 99) {
    header("Location: /login/FS_C_Menu.php");
}

# ===========================================================================================
# Definition der Auswahlmöglichkeiten (mittels radio Buttons)
# ===========================================================================================
echo "<input type='hidden' id='srch_Id' value=''>";
$list_ID = 'AR';
$lTitel = ["Alle" => "Alle verfügbaren LINKS "];
if ($_SESSION['BS_Prim']['Mod']['smod'] == 'ExtStart') {
    $lTitel = ["Extern" => "Alle verfügbaren LINKS "];
}

$NeuRec = "";
if ($_SESSION['BS_Prim']['Mod'] == 'IntStart') {
    $NeuRec = " &nbsp; &nbsp; &nbsp; <a href='Vs_O_AR_Edit.php?ID=0' > Neuen Datensatz anlegen </a>";
}

require $path2ROOT . "login/common/BS_ListFuncs_lib.php";

HTML_trailer();

?>