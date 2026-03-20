<?php

/**
 * Rollen- für Benutzer  zuordnen, verwaltung, Wartung
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

$rootPfad = $_SERVER['DOCUMENT_ROOT'];
$caller = $_SERVER['REQUEST_URI'];
$cal_arr = explode("/",$caller);
# var_dump($cal_arr);
require_once $rootPfad . '/'.$cal_arr[1].'/login/BS_BootPfadL_CLS.php';

PathHelper::init('/'.$cal_arr[1]);  // Basis-URL anpassen
AppAutoloader::register();

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

$TABUcss = true;
$header = "";
HTML_header('Rollen- Zuordnung  für Benutzer- Verwaltung', $header, 'Form', '90em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

initial_debug('POST','GET'); # Wenn $debug=true - Ausgabe von Debug Informationen: $_POST, $_GET, $_FILES

// ============================================================================================================
// Eingabenerfassung und defauls
// ============================================================================================================

$DBD = new FS_Database("FV_");

$pdo = $DBD->getPDO();

$meta = new BS_TableColumnMetadata($pdo,'fharch_new',false);

$columnsByTables = $meta->getColumnsForTables(['fv_rolle', 'fv_rollen_beschr']);

// ============================================================================================================
// Eingabenerfassung und defauls Teil 1 - alle POST Werte werden später in array $neu gestelltt
// ============================================================================================================
if (isset($_POST['phase'])) {
    $phase = $_POST['phase'];
} else {
    $phase = 0;
}
if (isset($_GET['ID'])) {
    $fr_id = (intval($_GET['ID']));
} else {
    $fr_id = 0;
}
$benName = $_GET['benu'] ?? '';
if (isset($_POST['benu'] )) {
    $benName = $_POST['benu'] ;
}


if (isset($_POST['fr_id'])) {
    $fr_id = (intval($_POST['fr_id']));
}
if (isset($_GET['beId'])) {
    $be_id = (intval($_GET['beId']));
} elseif (isset($_POST['be_id'])) {
    $be_id = (intval($_POST['be_id'])); 
}
    
if ($phase == 99) {
    header('Location: VS_BenEdit.php?id=be_id');
}

# -------------------------------------------------------------------------------------------------------
# Überschreibe die Werte in array $neu - weitere Modifikationen in Edit_tn_check_v2.php !
# -------------------------------------------------------------------------------------------------------
if ($phase == 0) {
    if ($fr_id == 0) {
        $neu['fr_id'] = $fr_id;
        $neu['be_id'] = $be_id;
        $neu['fl_id'] = $neu['fr_aktiv'] = "";
        $neu['fr_created_id'] = $neu['fr_changed_id'] = 0;
        $neu['fr_created_at'] = $neu['fr_changed_at'] = "0000-00-00 00:00:00";
        $neu['descript'] = "";
        $neu['Rolle'] = "";
    } else {
        
        echo __LINE__ . " fr_id $fr_id be_id $be_id <br>";
        $neu = $DBD->getRoleById($fr_id);
        $neu['descript'] =  "";
        $neu['Rolle'] = "";
        # var_dump($neu);
        
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
        require 'VS_RollenEdit_ph0_inc.php';
        break;
    case 1:
        require "VS_RollenEdit_ph1_inc.php";
        break;
}
HTML_trailer();
?>
