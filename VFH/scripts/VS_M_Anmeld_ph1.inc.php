<?php

/**
 * Anmeldung eines neuen Mitgliedes, Abspeichern Datensatzsatz . 
 *
 * @author Josef Rohowsky - neu 2018
 *
 * Datensatz schreiben, Log schreiben, E-Mail an Anmelder, Aufruf zur Erstellung Eigentümer und Benutzer.
 * 
 * 
 */

if ($debug) {
    echo "<pre class=debug>VF_M_Anmeld_ph1.inc.php ist gestarted</pre>";
}

if ($neu['staat'] != '' || $neu['staat_id'] != '' ) {
    if ($neu['staat_id'] != '' ) {
        $neu['mi_staat'] = $neu['staat_id'];
    }
}
unset($neu['mi_neu_id']);
unset($neu['einverkl']);
unset($neu['staat']);
unset($neu['staat_id']);
unset($neu['phase']);

$neu['mi_changed_id'] = $_SESSION['BS_Prim']['BE']['be_id'];

$return = $mitgl->createMiAnmeldg($neu); 

/*
$_SESSION[$module]['neu_mitgl']['neu_mi_id'] = mysqli_insert_id($db);

$datum = date("d.m.Y:");
$zeit = date("H:i:s");

$dsn = $path2ROOT."login/logs/anmeldlog";

$log_rec = "**** PFLICHTFELDER **** \nAnrede: " . $neu['mi_anrede'] . "\n";
$log_rec .= "Familienname: " . $neu['mi_name'] . "\n";
$log_rec .= "Vorname: " . $neu['mi_vname'] . "\n";
$log_rec .= "E-Mail: " . $neu['mi_email'] . "\n";
$log_rec .= "Adresse: " . $neu['mi_anschr'] . "\n";
$log_rec .= "PLZ: " . $neu['mi_plz'] . "\n";
$log_rec .= "Ort: " . $neu['mi_ort'] . "\n";

// ******** Optionale Felder ********
$log_rec .= "**** Optionale Felder ****\nTitel: " . $neu['mi_titel'] . "\n";
$log_rec .= "**** Optionale Felder ****\n nachfolg. Titel: " . $neu['mi_n_titel'] . "\n";

$log_rec .= "Tel Nummmer: " . $neu['mi_tel_handy'] . "\n";
$log_rec .= "Fax: " . $neu['mi_fax'] . "\n";
$log_rec .= "Geburtsdatum: " . $neu['mi_gebtag'] . "\n";
$log_rec .= "Oganisationstyp: " . $neu['mi_org_typ'] . "\n";
$log_rec .= "Organisation: " . $neu['mi_org_name'] . "\n";
# $log_rec .= "Referatsfunktion: ".$neu['mi_ref_int']."\n";
# $log_rec .= "Referatsmitarbeit: ".$neu['mi_ref_ma']."\n";
# $log_rec .= "Referatsinormation: ".$neu['mi_ref_int']."\n";
$log_rec .= "Einverstaendniserklaerung: " . $neu['mi_einversterkl'] . " $datum $zeit " . $neu['mi_einv_art'] . "\n";
$log_rec .= "Mitgliedsnummer:  " . $_SESSION[$module]['neu_mitgl']['neu_mi_id'] . "\n";
$text = " $log_rec ***** \"LOG ENDE\" *****\n";
$text .= "Orig.TCP = " . $_SERVER['REMOTE_ADDR'] . "\n";

$fname = writelog($dsn, $text);
$tr = array(
    "\n" => "<br>"
);
$text = strtr($text, $tr);

$adr_list = VF_Mail_Set('Mitgl');

# if ($module == "0_EM") {
sendEMail($neu['mi_email'] . ", $adr_list , josef@kexi.at", "VFHNÖ Mitglieds- Neuanmeldung ", $text); # service@feuerwehrhistoriker.at, helmut-riegler@aon.at, f.blueml@gmx.at"
                                                                                                         # }
# echo " Log- Dateiname \$fname $fname <br>";

$text = "Zur Info: Soeben " . $datum . " / " . $zeit . " wurde eine Anmeldung online  dem System übergeben.\n";
$text .= "Im Formular wurden u.A. Name / Vorname / Emailadresse erfasst: " . $neu['mi_name'] . " / " . $neu['mi_vname'] . " / " . $neu['mi_email'] . ".\n";
IF (! empty($ftext)) {
    $text .= "\nAchtung fehlende Pflichtangaben: $ftext\n\n";
}
$text .= "Weitere Infos sind im Anmeldelog ersichtlich.\n";
$text .= "http://www.feuerwehrhistoriker.at/login/log/DSVGO_log/";
$text .= "\nBitte beobachten ob die Anmeldung korrekt beendet wurde\n";
$text .= "und zu einem Teilnehmer Aufnameantrag geführt hat!\n";
$text .= "Zum Ansehen des Logs folgenden Link anklicken:\n";
$text .= "http://www.feuerwehrhistoriker.at/login/log/dir.php\n";
$text .= "Mitgliedsnummer:  " . $_SESSION[$module]['neu_mitgl']['neu_mi_id'] . "\n";
$text .= "Anmeldelog  Mail Ende\n";

$ini_arr = parse_ini_file($path2ROOT.'login/common/config_s.ini',True,INI_SCANNER_NORMAL);
$c_email =$ini_arr['Config']['vema'];

sendEmail("$adr_list, josef@kexi.at", // Empänger(Liste)
"Neuanmeldung " . $neu['mi_name'] . " ", // Subject Text der EMail
$text, // Inhalt der Email in HTML format
    $c_email); // optionale 'Reply-To' E-Mail-Adresse

if ($debug) {
    echo "<pre class=debug>VF_M_Anmeld_ph1.inc.php beendet</pre>";
}
*/
?>