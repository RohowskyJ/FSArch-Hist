<?php

/**
 * Mitgliederverwaltung, Wartung
 * 
 * @author Josef Rohowsky - neu 2020
 */
session_start();

$module = 'OEF';
$sub_mod = 'AR';

# $tabelle = 'fv_mitglieder';

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
HTML_header('Links zu öffentl. Archiven und Bibliotheken', $header, 'Form', '90em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

initial_debug('POST','GET'); # Wenn $debug=true - Ausgabe von Debug Informationen: $_POST, $_GET, $_FILES

// ============================================================================================================
// Eingabenerfassung und defauls
// ============================================================================================================

$DBD = new FS_Database("FV_");
#var_dump($DBD);
$pdo = $DBD->getPDO();
#var_dump($pdo);

$meta = new BS_TableColumnMetadata($pdo,'fharch_new',true);
#var_dump($meta);

$columnsByTables = $meta->getColumnsForTables(['fv_falinks' ]);
#var_dump($columnsByTables);
#var_dump($meta);
$links = new AR_LinkModule($DBD);
#var_dump($links);
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
    $fa_id = $_GET['ID'];
} else {
    $fa_id = "";
}
if (isset($_POST['fa_id'])) {
    $fa_id = $_POST['fa_id'];
}

if ($phase == 99) {
    header('Location: VS_O_AR_List.php');
}

# -------------------------------------------------------------------------------------------------------
# Überschreibe die Werte in array $neu - weitere Modifikationen in Edit_tn_check_v2.php !
# -------------------------------------------------------------------------------------------------------
if ($phase == 0) {
    if ($fa_id == 0) {
        $neu['fa_id'] = $fa_id;
        $neu['fa_link'] = $neu['fa_url_chkd'] = $neu['fa_url_obsolete'] = $neu['fa_text'] = $neu['fa_changed_id'] = $neu['fa_changed_at'] = "";
    } else {

        $neu = $links->getLinksById($fa_id);

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
    foreach ($_POST as $name => $value)
    { $neu[$name] = trim($value);  }
    
    $neu['fa_changed_id'] = $_SESSION['BS_Prim']['BE']['be_id'];
    unset($neu['phase']);
   
    if ($neu['fa_id'] == 0) { # neuengabe
        $ret = $this->createLinks($neu);
    } else { # Update
        $ret = $links->updateLinks($neu['fa_id'] , $neu);
    }
    
    header("Location:  VS_O_AR_List.php");
}

switch ($phase) {
    case 0:
        require ('VS_O_AR_Edit_ph0_inc.php');
        break;
}
HTML_trailer();
?>