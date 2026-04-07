<?php

/**
 * Firmen- verwaltung, Wartung
 * 
 * @author Josef Rohowsky - neu 2020
 */
session_start();

$module = 'ADM-ALL';
$sub_mod = 'Edit';

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/bootstrap_php-error.log.txt');

$debug = False; // Debug output Ein/Aus Schalter

/**
* Bootstrap: Composer-/Shared-Einstieg
*/
$rootPfad = $_SERVER['DOCUMENT_ROOT'];
$caller = $_SERVER['REQUEST_URI'];
$cal_arr = explode("/",$caller);
require_once __DIR__ . '/../Basis/bootstrap.php';
fsarch_bootstrap_path_init('/'.$cal_arr[1]);
AppAutoloader::register(); // Für Klassen, die Composer nicht laden kann

require PathHelper::fs('Basis/BS_Funcs_lib.php');
require PathHelper::fs('Basis/FS_CommFuncs_lib.php');
#require $path2ROOT . 'login/Basis/BS_Funcs_lib.php';
#require $path2ROOT . 'login/Basis/FS_CommFuncs_lib.php';

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../../";



require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';
require $path2ROOT . 'login/common/VF_Const.lib.php';

$TABUcss = true;
$header = "";
HTML_header('Firmen- Verwaltung', $header, 'Form', '90em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

initial_debug('POST','GET'); # Wenn $debug=true - Ausgabe von Debug Informationen: $_POST, $_GET, $_FILES

// ============================================================================================================
// Eingabenerfassung und defauls
// ============================================================================================================
use FSArch\Login\Basis\FS_Database;
use FSArch\Login\Basis\BS_TableColumnMetadata;
use FSArch\Login\Basis\BS_FormRendererFlex;
use FSArch\Login\Mitglieder\MI_MitgliederModule;

$DBD = new FS_Database("FV_");

$pdo = $DBD->getPDO();

$meta = new BS_TableColumnMetadata($pdo,'fharch_new',true);

$columnsByTables = $meta->getColumnsForTables(['fv_firmen']);

// ============================================================================================================
// Eingabenerfassung und defauls Teil 1 - alle POST Werte werden später in array $neu gestelltt
// ============================================================================================================
if (isset($_POST['phase'])) {
    $phase = $_POST['phase'];
} else {
    $phase = 0;
}
if (isset($_GET['ID'])) {
    $fi_id = $_GET['ID'];
} else {
    $fi_id = "0";
}
if (isset($_POST['fi_id'])) {
    $fi_id = $_POST['fi_id'];
}

if ($phase == 99) {
    header('Location: VS_FirmenList.php');
}
# -------------------------------------------------------------------------------------------------------
# Überschreibe die Werte in array $neu - weitere Modifikationen in Edit_tn_check_v2.php !
# -------------------------------------------------------------------------------------------------------
if ($phase == 0) {
    if ($fi_id == 0) {
        $neu['fi_id'] = $fi_id;
        $neu['fi_abk'] = $neu['fi_name'] = $neu['fi_ort'] = $neu['fi_vorgaenger'] = "";
        $neu['fi_funkt'] = $neu['fi_inet'] = "";
        $neu['fi_changed_id_s'] = "";
        $neu['fi_changed_id'] = $_SESSION['BS_Prim']['BE']['be_id'];
        $neu['fi_chanded_at'] = date('Y-m-d H:m:s');
    } else {

        $neu = $DBD->getFirmenById($fi_id);
  
        #var_dump($neu);
        
        if ($debug) {
            echo '<pre class=debug>';
            echo '<hr>$neu: ';
            var_dump($neu);
            echo '</pre>';
        }
    }
}

if ($phase == 1) {

    foreach ($_POST as $name => $value) {
        $neu[$name] = trim($value);
    }
}

switch ($phase) {
    case 0:
        require 'VS_FirmenEdit_ph0_inc.php';
        break;
    case 1:
        require "VS_FirmenEdit_ph1_inc.php";
        break;
}
HTML_trailer();
?>