<!DOCTYPE html>
<?php
# session_start();
/**
 * Benutzer Verwaltung Liste
 * 
 * @author Josef Rohowsky - neu 2020 - Umstellung Klassen/PDO, Module 2026
 * 
 * 
 */
session_start();

$module = 'ADM-ALL';
$sub_mod = "LIST";

$tabelle = 'fv_benutzer';// <?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/VS_Ben_List_php-error.log.txt');
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
$_SESSION[$module]['Inc_Arr'][] = "VS_BenList.php"; 

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../../";

$debug = False; // Debug output Ein/Aus Schalter

/**
 * Bootstrap: Composer-/Shared-Einstieg mit Pfadhelder
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
require $path2ROOT . 'login/common/VF_Const.lib.php';

use FSArch\Login\Basis\FS_Database;

$header =   ""; 
# ===========================================================================================================
# Haeder ausgeben
# ===========================================================================================================
$ListHead = "Benutzer- Verwaltung - Administrator ";
$title = "Benutzer- Daten";

$TABUcss = true;
HTML_header('Benutzer- Verwaltung', $header, 'Admin', '200em'); # Parm: Titel,Subtitel,HeaderLine,Type,width



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
    header("Location: /login/VF_C_Menu.php");
}

# $NeuRec = "momentan ned"; #     "NeuItem" => "<a href='VF_M_Edit.php?ID=0' >Neues Mitglied eingeben</a>"
$NeuRec = "<a href='VS_BenEdit.php?ID=0'>Neuen Benutzer anlegen</a>";
# ===========================================================================================
# Definition der Auswahlmöglichkeiten (mittels radio Buttons)
# ===========================================================================================
echo "<input type='hidden' id='srch_Id' value=''>";
$list_ID = 'BE';
$lTitel = ["Alle" => "Alle Benutzer", "Aktiv" => "Aktive Benutzer",
    "InAktiv" => "Nicht- Aktive Mitgliedert"];

# require $path2ROOT . "login/common/BS_ListFuncs_lib.php";
require PathHelper::fs('Basis/BS_ListFuncs_lib.php');

HTML_trailer();

?>
