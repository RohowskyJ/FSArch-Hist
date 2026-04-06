<?php

/**
 * Basis der Seite, einlesen Configuration, Externe Seite
 *
 * @author josef Rohowsky - neu 2014
 *        letzte Änderung 2025, Änderung der Struktur, neue Schutzmechanismen, neu einterne Einteilung, Jänner 2026
 */

session_start();
$_SESSION = [];

$module = 'VFH';
$sub_module = 'ExtStart';

$_SESSION['BS_Prim']['Mod'] = ['module' => $module, 'smod' => $sub_module, 'caller' => $module];

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../";

$debug = true; // Debug output Ein/Aus Schalter

if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $SI = "l";
} else {
    $SI = "vfh";
}

if (!is_file($path2ROOT . "login/Basis/config_d.ini") ||  !is_file($path2ROOT . "login/Basis/config_m.ini") || !is_file($path2ROOT . "login/Basis/config_s_".$SI.".ini")  ) {
    echo __LINE__ . " config- dateien sind da, will trotzdem installieren <br>";
    # header("Location: ". $path2ROOT."VFH/install/install.php");
}
/**
 * Bootstrap: Composer-/Shared-Einstieg
 */
require_once '../login/Basis/bootstrap.php';

/**
 * Zur Benutzung der neuen, gemeinsamen Bibliotheken
 * die neuen Bibs
 */
require $path2ROOT . 'login/Basis/BS_Funcs_lib.php'; // Diverse Unterprogramme
require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';

$header = "";

$header .= "<style>nav{float:left;width:320px;margin:10px;border:3px solid grey;}.cont{border:1px solid grey;}@media print{.nav{display:none;}}</style>  \n";

HTML_header('Start- Seite', $header, '1P', '75em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

initial_debug('SERV', 'PUT', 'GET'); # Wenn $debug=true - Ausgabe von Debug Informationen: $_Server, $_POST, $_GET, $_FILE

?>

<br />

<div class='w3-third'>
     <fieldset>
          <div class="nav">
               <?php 
               if (is_file('../login/Oeffi/VS_O_MU_List.php')) {
               ?>
                    <a href="../login/Oeffi/VS_O_MU_List.php"
                      target='M_Links'>Museen (Feuerwehr und andere Blaulicht
                      Organisationen)</a> <br />
               <?php   
               }
               if (is_file('../login/Oeffi/VS_O_AR_List.php')) {
               ?>
                   <a href="../login/Oeffi/VS_O_AR_List.php"
                        target='A_Links'>Links zu Archiven und Bibliotheken</a> <br />
               <?php 
               }
               if (is_file('../login/Oeffi/VS_O_TE_List.php')) {
               ?>
                    <a href="../login/Oeffi/VS_O_TE_List.php"
                        target='Veranstalt'>Veranstaltungskalender</a> <br />
               <?php 
               }
               if (is_file('../login/Oeffi/VS_O_PR_List.php')) {
               ?>
                   <a href="../login/Oeffi/VS_O_PR_List.php"
                       target='Presse'>Presse-Information, Spiegel</a> <br />
               <?php 
               }
               if (is_file('../login/Oeffi/VS_O_BU_List.php')) {
               ?>
                   <a href="../login/Oeffi/VS_O_BU_List.php"
                    target='Buch'>Buch - Besprechungen</a> <br />
               <?php 
               }
               if (is_file('../login/Oeffi/VS_O_AN_List.php')) {
               ?>
                    <a href="../login/Oeffi/VS_O_AN_List.php"
                      target='Marktpl'>Marktplatz</a> <br /> 
               <?php 
               }
               ?>
           
        <a href="scripts/VF_EM_Edit.php">Kontakt
                    (E-Mail)</a> <br />

        <a href='impress.php'>Impressum</a> <br />
            <a href='scripts/VS_M_Anmeld.php' target='MitglAnmeld'>Ich will Mitglied
                    werden</a> <br />
  
          <a href='../login/Basis/FS_login.php' >Login zum internen Bereich </a><br />  
               
          <a href="DSGVO/Datenschutz_allg_Beschreibg.php" target='DSVGO'>Verarbeitungsbeschreibung
                    nach DSGVO</a> <br />

         <a href="referate.php" target='referate'>Referate</a> <br />
         <a href="Vorstand.php" target='Vorstand'>Vorstandsmitglieder </a> <br />

          </div>
     </fieldset>
</div>
<div class='w3-twothirds'>
     <fieldset>

<font size="4em"> <b>Unsere Ziele:</b> <br /> Einladen wollen wir alle,
               die an der Geschichte des Feuerwehrwesens interessiert sind,
               besonders aber jene, die bereits historisch interessante Fahrzeuge,
               Geräte und Ausrüstungsgegenstände besitzen. <br> Das sind:
               Feuerwehren, Feuerwehrmänner und -frauen, Museen und Sammlungen,
               Privatpersonen und private Vereine. <br> Eine der Aufgaben des
               Vereines ist, Feuerwehren und Personen, die bereit sind, an einer
               gezielten Sammeltätigkeit mitzuwirken, bei dieser Tätigkeit zu
               fördern und zu unterstützen.
          </font> <br />
          <p align="center">
               <a href="ziele.php" target="Ziele">Details</a>
          </p>

     </fieldset>
</div>

<div class="w3-content ">
     <hr width="100%">
     <p class="w3-center">
    
          <font face="Verdana, Arial, Helvetica, sans-serif" size="1">Mit
               freundlicher Unterstützung: (Sponsoren)</font> <br>
     <?php 
     if (is_file("imgs/logo_versand_blauer_hintergrund.jpeg")) {
     ?>
          <!-- -->
   
          <a href="http://www.noemitte.volksbank.at/" target="_blanc"><img
               src="imgs/logo_versand_blauer_hintergrund.jpeg" border="0" width="180"></a>
     <?php 
     }
     
     if (is_file("imgs/Logo_Joechlinger.jpg"))  {
      ?>      
          &nbsp; &nbsp;
          <!-- -->
          <a href="https://www.joechlinger-gemuese.at/" target="_blanc"><img
               src="imgs/Logo_Joechlinger.jpg" border="0"
               width="180"></a> &nbsp; &nbsp;
     <?php 
     }
     ?>
     </p>

     <hr width="100%">
     <p class="w3-text-blue">


     <h3 class="w3-center">
          Aktuelles: <a href="referat7/index_act.php" target="Arch">(Archiv = weniger Aktuelles)</a>
     </h3>

     <font size="+1"> <br><a href="referat7/Wiki_auch_fuer_Feuerwehren.pdf"
          target="Regiow">RegioWiki - Regionale Wiki (Wikipedia) Seiten für
               Österreich</a> <br> <a
          href="https://mitglieder.wikimedia.at/Nachrichten/2014-09-29"
          target="RegioS">Seminar Wikipedia, Regiowiki in der
               Landesfeuerwehrschule Tulln</a> <br> <a
          href="https://regiowiki.at/wiki/Kategorie:Feuerwehr">Feuerwehren in
               Regiowiki</a> <br>
     </font> <font size="+1"> <br> <a href="referat7/Pflichtabgabe/Pflichtablieferung_BGBLA_2009_II_271.pdf">Pflichtablieferung
               von Druckwerken - Gesetzestext</a> <br>
     </font> <font size="+1"> <br> <a href="referat7/BedienerDoku_7.pdf">Bedienerhilfe:
               Dokumentation für die Benutzer der Internetseite der Historiker, <b>Version
                    20.09.2024</b><br />
     </a> <a href="referat7/archord.pdf">Archivordnung
               (pdf)<br />
     </a> <a href="referat7/archord.xls">Archivordnung
               (xls)<br />
     </a> <br /> <b>CIDOC Information über die Dokumentation in Museen </b><br />
          <a href="http://www.museumsbund.at/leitfaeden-und-standards"
          target="_blanc">CIDOC Leitfäden und Standards<br /></a>

     </font>
</div>

<?php

echo "</div>";

HTML_trailer();
?>

