<?php

/**
 * PSA, Bilder- Beschreibungen
 *
 * @author Josef Rohowsky - neu 2018
 */
session_start(); # die SESSION aktivieren  

const Module_Name   = 'PSA';
$module             = Module_Name;
# const Tabellen_Name = 'fh_dokumente'; 

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT          = "../";

$debug = False; // Debug output Ein/Aus Schalter

require $path2ROOT . 'login/common/BS_Funcs.lib.php';

# require $path2ROOT . 'login/common/VF_Comm_Funcs.inc' ;

initial_debug('POST', 'GET');

# ========================================================================================================
#                                            Header ausgeben
# ===========================================================================================================
echo "<div class='Menu-Line'>";
 HTML_header('Information zu Bildern für Auszeichnungen, Ärmelabzeichen, Wappen ','','','Form','70em'); # Parm: Titel,Subtitel,HeaderLine,Type,width 
 
 
  echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgab
  
  echo "<div class='Menu-Separator'>Bildformate zum Hochladen</div>";
  
  echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgab
  echo "Die maximale Größe von Einzelbildern für die Darstellung von Fotos darf 20 x 20 cm, Auflösung 96 DPI nicht überschreiten.
       <br>";
  echo "Die empfohlenen Größen für Auszeichnungen und Abzeichen:";
  echo "<dl compact>";
  
  echo "<dt><b>Große Bilder</b></dt>";
  echo "<dd><b><i>20 x 20 cm 96 DPI</i></b>, z.B.: Ärmelabzeichen, Auszeichnungen mit Band oder größer </dd>";
  
  echo "<dt><b>Mittlere Bilder</b></dt>";
  echo "<dd><b><i>10 x 10 cm 96 DPI</b></i>, z.B.: Leistungsabzeichen, Medaillen ohne Band, ... </dd>";
  
  echo "<dt><b>Kleine Bilder</b></dt>";
  echo "<dd><b><i>8 x 8 cm 96 DPI</i></b>, z.B.: Miniaturen, Jugenabzeichen (alt)</dd>";
 
  echo "</dl>";
  
  echo "</div>";


HTML_trailer();
?>
