<?php

/**
 * Automatische Benachrichtigung für ADMINS bei Änderungen
 * 
 * @author Josef Rohowsky - neu 2023 reo 2026
 * 
 */
session_start(); # die SESSION aktivieren

$module  = 'ADM-ALL';
$sub_mod = 'EMail';

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../../";

$debug = False; // Debug output Ein/Aus Schalter
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/VS_EMail_List_php-error.log.txt');
# var_dump($_SERVER);

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

$debug = False; // Debug output Ein/Aus Schalter

require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';
require $path2ROOT . 'login/common/VF_Const.lib.php';

use FSArch\Login\Basis\FS_Database;

$flow_list = False;

$title = "E-Mail- Empfänger für automatische E-Mails ";

$ListHead = "Admin- E_Mail Verwaltung - Administrator ";
$title = "BAdmin- E_Mail- Daten";

$TABUcss = true;
HTML_header($title, '', 'Admin', '90em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

initial_debug('GET','POST');

// XR_Database mit bestehender PDO-Instanz initialisieren
$DBD = new FS_Database();
# var_dump($DBD);
$pdo = $DBD->getPDO();
# var_dump($pdo);

# ===========================================================================================
# Definition der Auswahlmöglichkeiten (mittels radio Buttons)
# ===========================================================================================
$T_list_texte = array(
    "Alle" => "Alle E-Mail- Ziele "
);
$NeuRec = "<a href='VS_AdmEmailEdit.php?ID=0' >Neues E-Mail Ziel eingeben</a>";

# ===========================================================================================
# Definition der Auswahlmöglichkeiten (mittels radio Buttons)
# ===========================================================================================
echo "<input type='hidden' id='srch_Id' value=''>";
$list_ID = 'AM';
$lTitel = ["Alle" => "Alle E-Mail- Ziele "];

# require $path2ROOT . "login/Basis/BS_ListFuncs_lib.php";
require PathHelper::fs('Basis/BS_ListFuncs_lib.php');

HTML_trailer();

?>