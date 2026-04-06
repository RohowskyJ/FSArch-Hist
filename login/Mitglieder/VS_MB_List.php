<! doctype html>
<?php

/**
 * Mitglieder - Verwaltung Zahlungseingabgsvermerk
 * 
 * @author Josef Rohowsky - neu 2026
 */
session_start();

$module = 'ADM-MI';
$sub_mod = "Bez";

$tabelle = 'fv_mitglieder';

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/VS_MB_List_php-error.log.txt');

// error_log('nach deb start');

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

/**
 * Includes-Liste
 * enthält alle jeweils includierten Scritpt Files
 */
$_SESSION[$module]['Inc_Arr']  = array();
$_SESSION[$module]['Inc_Arr'][] = "VF_MB_List.php"; 

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../../";

$debug = False; // Debug output Ein/Aus Schalter

# require $path2ROOT . 'login/common/BS_Funcs_lib.php';
# require $path2ROOT . 'login/common/FS_CommFuncs_lib.php';

require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';
require $path2ROOT . 'login/common/VF_Const.lib.php';

use FSArch\Login\Basis\FS_Database;

$flow_list = False;

$TABUcss = true;

# ===========================================================================================================
# Haeder ausgeben
# ===========================================================================================================

HTML_header("Mitglieds Beitrag", '', '', 'Adm', '200em');

echo "<fieldset>";

initial_debug('POST','GET');


if (isset($_POST['phase'])) {
    $phase = $_POST['phase'];
} else {
    $phase = 0;
}
if ($phase == 99) {
    header("Location: /login/FS_C_Menu.php");
}


$mitgl_nrs = "";
$mitgl_einv_n = 0;

$ber_zeitr = "Bericht per  ";
$today = date('Y-m-d');

$cur_year = date('Y');

$d_arr = explode("-",$today);
if ($d_arr[1] >= '01' && $d_arr[1] < '03' ) {
    $ber_y = date('Y') - 1;
    $ber_zeitr .= date('Y-m-d', strtotime("$ber_y-12-31"));
} elseif ($d_arr[1] >= '07' && $d_arr[1] < '09' ) {
    $ber_y = date('Y');
    $ber_zeitr .= date('Y-m-d', strtotime("$ber_y-06-30"));
}

/*
// Letzter Halbjahresstichtag: 30. Juni des aktuellen Jahres
$halbjahresstichtag = date('Y-m-d', strtotime("$year-06-30"));

// Letzter Jahresstichtag: 31. Dezember des aktuellen Jahres
$jahresstichtag = date('Y-m-d', strtotime("$year-12-31"));
*/
# ===========================================================================================
# Definition der Auswahlmöglichkeiten (mittels radio Buttons)
# ===========================================================================================
echo "<input type='hidden' id='srch_Id' value=''>";
$list_ID = 'MIB';
$lTitel = ["Alle" => "Alle aktiven Mitglieder", "offenAlle" => "aktive Mitglieder - für 20" . date('y') . " nicht bezahlt", 
    "bezahlt" => "für 20" . date('y') . " bezahlt", 'sticht' => $ber_zeitr ." bezahlt",
    "EM" => "Nicht zahlende Mitglieder (EM oder OE)"];
 
if (isset($_GET['mod_t_id'])) {
    $mod_t_id = $_GET['mod_t_id'];
}

$NeuRec = "";

# require $path2ROOT . "login/Basis/BS_ListFuncs_lib.php";
require PathHelper::fs('Basis/BS_ListFuncs_lib.php');

echo "</fieldset>";

HTML_trailer();

// Lade Payment-Handler Script
echo "<script src='javascript/MIB_Payment.js'></script>";


?>