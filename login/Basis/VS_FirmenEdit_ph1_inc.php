<?php

/**
 * Firmen- Verwaltung, Warten, Daten schreiben
 * 
 * @author Josef Rohowsky - neu 2018
 * 
 */

/**
 * Includes-Liste
 * enthält alle jeweils includierten Scritpt Files
 */
$_SESSION[$module]['Inc_Arr'][] = "VS_FirmenEdit_ph1_inc.php";

if ($debug) {
    echo "<pre class=debug>VS_FirmenEdit_ph1_inc.php ist gestarted</pre>";
}

unset($neu['phase']);

$neu['fi_changed_id'] = $_SESSION['BS_Prim']['BE']['be_id'];
$neu['fi_changed_at'] =  date("Y-m-d H:i:s");

if ($neu['fi_id'] == "0") {

    $recno = $DBD->createFirmen($neu);
 
} else {
    
    $ret = $DBD->updateFirmen($fi_id, $neu);
    
}

header ('Location: VS_BenList.php');

if ($debug) {
    echo "<pre class=debug>VS_FirmenEdit_ph1_inc.php beendet</pre>";
}
?>