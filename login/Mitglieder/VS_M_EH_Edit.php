<?php

/**
 * Liste der vom Verein verliehenen Ehrungen, Wartung
 *
 * @author Josef Rohowsky - neu 2023
 *
 *
 */
session_start(); # die SESSION am leben halten

use FSArch\Login\Basis\FS_Database;
use FSArch\Login\Basis\BS_TableColumnMetadata;

use FSArch\Login\Mitglieder\MI_MitgliederModule;

// Shutdown-Funktion direkt am Anfang registrieren
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        $message = "Shutdown error detected:\n" . print_r($error, true);
        error_log($message);
        // Optional: auch in eine separate Datei schreiben
        file_put_contents(__DIR__ . '/fatal_error.log', $message, FILE_APPEND);
    }
});
    
$module = 'ADM-MI';
$sub_mod = 'Ehrg';

$tabelle = 'fv_mi_ehrung';

const Prefix = '';
/*
// <?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
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
AppAutoloader::register();

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../../";

/**
 * Includes-Liste
 * enthält alle jeweils includierten Scritpt Files
 */
$_SESSION[$module]['Inc_Arr'][] = "VF_M_EH_Edit.php";

$debug = False; // Debug output Ein/Aus Schalter

require PathHelper::fs('Basis/BS_Funcs_lib.php');
require PathHelper::fs('Basis/FS_CommFuncs_lib.php');
#require $path2ROOT . 'login/Basis/BS_Funcs_lib.php';
#require $path2ROOT . 'login/Basis/FS_CommFuncs_lib.php';


require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';
require $path2ROOT . 'login/common/VF_Const.lib.php';

$flow_list = False;

HTML_header('Auszeichnungs - Verwaltung', '', 'Form', '90em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

initial_debug('POST','GET');
# var_dump($_POST);
// ============================================================================================================
// Eingabenerfassung und defauls
// ============================================================================================================

$DBD = new FS_Database("FV_");
# var_dump($DBD);
$pdo = $DBD->getPDO();
#var_dump($pdo);

$meta = new BS_TableColumnMetadata($pdo,'fharch_new',false);
#var_dump($meta);

$columnsByTables = $meta->getColumnsForTables(['fv_mi_ehrung']);
# var_dump($columnsByTables);

$mitgl = new MI_MitgliederModule($DBD);
# var_dump($mitgl);
#var_dump($_SERVER);
#var_dump($_POST);
#var_dump($_GET);
// ============================================================================================================
// Eingabenerfassung und defauls Teil 1 - alle POST Werte werden später in array $neu gestelltt
// ============================================================================================================
if (isset($_POST['phase'])) {
    $phase = $_POST['phase'];
} else {
    $phase = 0;
}
if (isset($_GET['ID'])) {
    $me_id = $_GET['ID'];
} else {    
    $me_id = "";
}

if ($phase == 99) {
    header('Location: VS_M_Edit.php?mi_id=' . $_SESSION[$module]['mi_id']);
}

# -------------------------------------------------------------------------------------------------------
# Überschreibe die Werte in array $neu - weitere Modifikationen in Edit_tn_check_v2.php !
# -------------------------------------------------------------------------------------------------------
if ($phase == 0) {
    if ($me_id == 0) {

        $neu['me_id'] = $me_id;
        $neu['mi_id'] = $_SESSION[$module]['mi_id'];

        $neu['me_ehrung'] = $neu['me_eh_datum'] = $neu['me_begruendg'] = $neu['me_changed_at'] = "";
        $neu['me_bild1'] = $neu['me_bild2'] = $neu['me_bild3'] = $neu['me_bild4'] = "";
        $neu['me_changed_id'] = $_SESSION['BS_Prim']['BE']['be_id'];
    } else {
        try {
            $neu = $mitgl->getMiEhrungById($me_id);
            var_dump($result);
        } catch (Exception $e) {
            echo "Exception: " . $e->getMessage();
        }

    }
}

if ($phase == 1) {

    foreach ($_POST as $name => $value) {
        $neu[$name] = $value;
    }
    
    $uploaddir = PathHelper::fs("AOrd_Verz/1/MITGL/");
   
    if (! file_exists($uploaddir)) {
        mkdir($uploaddir);
    }
    
    $target1 = "";
    if (! empty($_FILES['uploaddatei_01'])) {
        $pict1 = basename($_FILES['uploaddatei_01']['name']);
        if (! empty($pict1)) {
            $target1 = $uploaddir . basename($_FILES['uploaddatei_01']['name']);
            if (move_uploaded_file($_FILES['uploaddatei_01']['tmp_name'], $target1)) {
                echo "Datei/Bild 1 geladen!<br><br><br>";
                $neu['me_bild1'] = $pict1;
            }
        }
    }

    $target2 = "";
    if (! empty($_FILES['uploaddatei_02'])) {
        $pict2 = basename($_FILES['uploaddatei_02']['name']);
        if (! empty($pict2)) {
            $target2 = $uploaddir . basename($_FILES['uploaddatei_02']['name']);
            if (move_uploaded_file($_FILES['uploaddatei_02']['tmp_name'], $target2)) {
                echo "Datei/Bild 2 geladen!<br><br><br>";
                $neu['me_bild2'] = $pict2;
            }
        }
    }

    $target3 = "";
    if (! empty($_FILES['uploaddatei_03'])) {
        $pict3 = basename($_FILES['uploaddatei_03']['name']);
        if (! empty($pict3)) {
            $target3 = $uploaddir . basename($_FILES['uploaddatei_03']['name']);
            if (move_uploaded_file($_FILES['uploaddatei_03']['tmp_name'], $target3)) {
                echo "Datei/Bild 3 geladen!<br><br><br>";
                $neu['me_bild3'] = $pict3;
            }
        }
    }

    $target4 = "";
    if (! empty($_FILES['uploaddatei_04'])) {
        $pict4 = basename($_FILES['uploaddatei_04']['name']);
        if (! empty($pict4)) {
            $target4 = $uploaddir . basename($_FILES['uploaddatei_04']['name']);
            if (move_uploaded_file($_FILES['uploaddatei_04']['tmp_name'], $target4)) {
                echo "Datei/Bild 4 geladen!<br><br><br>";
                $neu['me_bild4'] = $pict4;
            }
        }
    }

    unset($neu['MAX_FILE_SIZE']);
    unset($neu['phase']);
    
    if ($neu['me_id'] == 0) { # neueingabe
        
        try {
            $neu['mi_insert_id'] = $insert_id = $mitgl->createMiEhrung($neu);
        } catch (Exception $e) {
            echo "Exception: " . $e->getMessage();
        }

    } else { # update
        $me_id = $neu['me_id'];
        try {
            $neu['me_insert_id'] = $insert_id = $mitgl->updateMiEhrung($me_id, $neu);
        } catch (Exception $e) {
            echo "Exception: " . $e->getMessage();
        }
  
        $mi_id = $_SESSION[$module]['mi_id'];
          
        if (isset($_SESSION[$module]['Return']) AND $_SESSION[$module]['Return']) {
            header("Location: VS_M_Ehrg_List.php");
        } else {
            if ($mi_id != "") {
                header("Location: VS_M_Edit.php?ID=$mi_id");
            } 
            header("Location: VS_M_List.php");
        }
    }

}

switch ($phase) {
    case 0:
        require 'VS_M_EH_Edit_ph0.inc.php';
        break;
}
HTML_trailer();
?>