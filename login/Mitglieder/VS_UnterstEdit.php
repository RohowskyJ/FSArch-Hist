<?php

/**
 * Unterstützer-  Wartung
 * 
 * @author Josef Rohowsky - neu 2020
 */
session_start();

use FSArch\Login\Basis\FS_Database;
use FSArch\Login\Basis\BS_TableColumnMetadata;
use FSArch\Login\Basis\BS_FormRendererFlex;
use FSArch\Login\Mitglieder\MI_MitgliederModule;

$module = 'MVW';
$sub_mod = 'US';
/*
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/bootstrap_php-error.log.txt');
*/

/**
 * Bootstrap: Composer-/Shared-Einstieg
 */
$rootPfad = $_SERVER['DOCUMENT_ROOT'];
$caller = $_SERVER['REQUEST_URI'];
$cal_arr = explode("/",$caller);
require_once __DIR__ . '/../Basis/bootstrap.php';
fsarch_bootstrap_path_init('/'.$cal_arr[1]);
AppAutoloader::register(); // Für Klassen, die Composer nicht laden kann

// Stelle sicher, dass die Klassen geladen sind
#require_once __DIR__ . '/../Basis/BS_FormRendererFlex_CLS.php';
#require_once __DIR__ . '/../Basis/BS_TableColumnMetadata_CLS.php';

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../../";

$debug = False; // Debug output Ein/Aus Schalter

require PathHelper::fs('Basis/BS_Funcs_lib.php');
require PathHelper::fs('Basis/FS_CommFuncs_lib.php');
#require $path2ROOT . 'login/Basis/BS_Funcs_lib.php';
#require $path2ROOT . 'login/Basis/FS_CommFuncs_lib.php';

require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';
require $path2ROOT . 'login/common/VF_Const.lib.php';

$TABUcss = true;
$header = "";
HTML_header('Unterstützer- Verwaltung', $header, 'Form', '90em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

initial_debug('POST','GET'); # Wenn $debug=true - Ausgabe von Debug Informationen: $_POST, $_GET, $_FILES

// ============================================================================================================
// Eingabenerfassung und defauls
// ============================================================================================================

$DBD = new FS_Database("FV_");
#var_dump($DBD);
$pdo = $DBD->getPDO();
#var_dump($pdo);

$meta = new BS_TableColumnMetadata($pdo,'fharch_new',false);
#var_dump($meta);

$columnsByTables = $meta->getColumnsForTables(['fv_unterst' ]);
#var_dump($columnsByTables);
# var_dump($meta);
$mitgl = new MI_MitgliederModule($DBD);
#var_dump($mitgl);
#var_dump($_SERVER);
// ============================================================================================================
// Eingabenerfassung und defauls Teil 1 - alle POST Werte werden später in array $neu gestelltt
// ============================================================================================================
if (isset($_POST['phase'])) {
    $phase = $_POST['phase'];
} else {
    $phase = 0;
}
if (isset($_GET['ID'])) {
    $fu_id = $_GET['ID'];
} else {
    $fu_id = "";
}
if (isset($_POST['fu_id'])) {
    $fu_id = intval($_POST['fu_id']);
}

if ($phase == 99) {
    header('Location: VS_UnterstList.php');
}

# -------------------------------------------------------------------------------------------------------
# Überschreibe die Werte in array $neu - weitere Modifikationen in Edit_tn_check_v2.php !
# -------------------------------------------------------------------------------------------------------
if ($phase == 0) {
    if ($fu_id == 0) {
        $neu['fu_id'] = $fu_id;
        $neu['fu_aktiv'] = "J";
        $neu['fu_kateg'] = "FF";
        $neu['fu_weihn_post'] = "J";
        $neu['fu_zugr'] = "N";
        $neu['fu_tit_vor'] = "";
        $neu['fu_tit_nach'] = "";
        $neu['fu_anrede'] = "Hr.";
        $neu['fu_name'] = $neu['fu_vname'] = "";
        $neu['fu_dgr'] = $neu['fu_plz'] = $neu['fu_ort'] = $neu['fu_adresse'] = "";
        $neu['fu_tel'] = $neu['fu_email'] = $neu['fu_orgname'] = "";
        $neu['fu_changed_id'] = $_SESSION['BS_Prim']['BE']['be_id'];
        $neu['fu_changed_at'] = date('Y-m-d H:m:s');
    } else {

        $neu = $mitgl->getUnterstById($fu_id);f
        
        var_dump($neu);
        
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
    #var_dump($neu);
}

switch ($phase) {
    case 0:
        require 'VS_UnterstEdit_ph0.inc.php';
        break;
    case 1:
        require "VS_UnterstEdit_ph1.inc.php";
        break;
}
HTML_trailer();
?>