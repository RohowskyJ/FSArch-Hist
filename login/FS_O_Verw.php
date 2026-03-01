<?php

/**
 * OEffentlichkitsarbits - Menu
 * 
 * @author Josef Rohowsky - neu 2023
 * 
 * 
 */
session_start();

const Module_Name = 'OEF';
$module = Module_Name;
# const Tabellen_Name = 'fh_dokumente';

/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../";

$debug = False; // Debug output Ein/Aus Schalter

require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';
require $path2ROOT . 'login/common/VF_Const.lib.php';
require $path2ROOT . 'login/common/BS_Funcs.lib.php';

# $flow_list = True;

# flow_add($module,"FS_O_Verw.php");

initial_debug('POST','GET');
   
    /** Tabellen für Urheber-Name einlesen */
    // VF_Urh_ini();
    
   # ===========================================================================================================
   # Haeder ausgeben
   # ===========================================================================================================
    
   HTML_header('Öffentlichkeits- Arbeit und Museen', '', 'Form', '70em'); # Parm: Titel,Subtitel,HeaderLine,Type,width
    
   echo  "<div class='Menu-Header'>Öffentlichkeitsarbeit' </div>";
   
   echo "<div class='Menu-Separator'>Archiv- und Bibliotheks- Links</div>";
   echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
   echo "Pflege der öffentlichen Links zu Bibliotkeken und Archiven.";
   echo "  </div>";  // Ende Feldname
   echo "<div class='Menu-Line'>"; // Beginn der Anzeige Feld-Name
   echo "<a href='VF_O_AR_List.php?Act=1' target='Archive'>Archiv- und Bibliotheks- Links</a>";
   echo "</div>"; // Ende der Ausgabe- Einheit Feld
   
       echo "<div class='Menu-Separator'>Marktplatz, Biete/Suche</div>";
       echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
       echo "Jedes Mitglied hier seine Wünsche und freies Material anbieten/suchen.";
       echo "  </div>";  // Ende Feldname
       echo "<div class='Menu-Line'>"; // Beginn der Anzeige Feld-Name
       echo "<a href='VF_O_AN_List.php?Act=1' target='Biete-Suche'>Biete- /Suche- Marktplatz, Adminstrativer Teil</a>";
       echo "</div>"; // Ende der Ausgabe- Einheit Feld
       
       echo "<div class='Menu-Separator'>Buch Rezensionen</div>";
       echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
       echo "Pflege der Buch Rezensionen";
       echo "  </div>";  // Ende Feldname
       echo "<div class='Menu-Line'>"; // Beginn der Anzeige Feld-Name
      
           echo "<a href='VF_O_BU_List.php?Act=1' target='Bücher'>Buch- Rezensionen, Verwalten, Redigieren, Freischalten</a>";
       
       echo "</div>"; // Ende der Ausgabe- Einheit Feld
       
       echo "<div class='Menu-Separator'>Dokumentationen zum herunterladen</div>";
       echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe</div>";
       echo "Hier werden die verschiedenen im Verein erstellten und Vorgetragenen Dokumentationen ins Netz gestellt,
          und können heruntergeladen und dürfen für Zwecke der Feuerwehrgeschichte verwendet werden.";
       echo "  </div>";  // Ende Feldname
       echo "<div class='Menu-Line'>"; // Beginn der Anzeige Feld-Name
       echo "<a href='VF_O_DO_List.php?&Act=1' target='Doku'>Dokumentationen zum herunterladen</a>";
       echo "</div>"; // Ende der Ausgabe- Einheit Feld


       echo "<div class='Menu-Separator'>Fotos, Videos (Filme), Berichte</div>";
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "Hier können die von Mitgliedern erstellten Fotos <b>einzeln oder als Masse (Verzeichnisweise</b> ins Netz gestellt,
       und  heruntergeladen und dürfen für Zwecke des Vereines mit Namensnennung des Fotografen (Urheber) verwendet werden.
       Für die Berichtserstellung werden diese Fotos direkt verwendet - kein extra Upload notwendig.";
    echo "  </div>";  // Ende Feldname
    echo "<div class='Menu-Line'>"; // Beginn der Anzeige Feld-Name
    echo "<a href='VF_FO_Ber_Verw.php?Act=1' target='Foto_Ber'>Foto, Video und Berichte- Verwaltung</a>";
    echo "</div>"; // Ende der Ausgabe- Einheit Feld
    
    echo "<div class='Menu-Separator'>Museumsdaten warten</div>";
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "Pflege der Museumsliste ";
    echo "  </div>";  // Ende Feldname
    echo "<div class='Menu-Line'>"; // Beginn der Anzeige Feld-Name
    echo "<a href='VF_O_MU_List.php?Act=1' target='Museen'>Museumsdatenwartung</a>";
    echo "</div>"; // Ende der Ausgabe- Einheit Feld
    
    echo "<div class='Menu-Separator'>Archivalien- und Inventar- Verwaltung</div>";
    echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
    echo "Verwaltung aller Dokumente, Video- Listen, Foto-(Negativ)-Listen (die Fotos und Videos selbst sind unter \"Foto,Video und Berichte\" zu finden), ...";
    echo "</div>";
    echo "<div class='Menu-Line'>"; // Beginn der Anzeige Feld-Name
   
        echo "<a href='VF_A_Archiv_Verw.php' target='A-Verwaltung'>Archivalienverwaltung und erweiterte Archivordnung </a>";

    echo "</div>"; // Ende der Ausgabe- Einheit Feld
   
        echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
        echo "<div class='Menu-Separator'>Verwaltung aller nicht unter Dokumente fallenden Gegenstände.";
        echo "  </div>";  // Ende Feldname
        echo "<div class='Menu-Line'>"; // Beginn der Anzeige Feld-Name
        echo "<a href='VF_I_Inv_Verw.php' target='Inventar'>Inventar- Verwaltung</a>";
        echo "</div>"; // Ende der Ausgabe- Einheit Feld
  
    
    

        echo "<div class='Menu-Separator'>Presse- Ausschnitte</div>";
        echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
        echo "Pflege der in der Presse veröffentlichen Artikel";
        echo "  </div>";  // Ende Feldname
        echo "<div class='Menu-Line'>"; // Beginn der Anzeige Feld-Name
        
            echo "<a href='VF_O_PR_List.php?Act=1' target='Presse'>Presse-Informationen verwalten</a>";
    
        echo "</div>"; // Ende der Ausgabe- Einheit Feld
        
        echo "<div class='Menu-Separator'>Terminplan  (Kalender)');</div>";
        echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
        echo "Hier werden die Termine in den Kalender eingepflegt ";
        echo "  </div>";  // Ende Feldname
        echo "<div class='Menu-Line'>"; // Beginn der Anzeige Feld-Name
        
            echo "<a href='VF_O_TE_List.php?Act=1' target='Terminplan'>Terminplan und Anmeldungs- Bearbeitung</a>";
      
        echo "</div>"; // Ende der Ausgabe- Einheit Feld
        
        echo "<div class='Menu-Separator'>Index von Feuerwehrzeitungen</div>";
        echo "<div class='Menu-Line'>"; // Beginn der Einheit Ausgabe
        echo "Eingabe und Pflege von Index für Feuerwehrzeitugen.";
        echo "  </div>";  // Ende Feldname
        echo "<div class='Menu-Line'>"; // Beginn der Anzeige Feld-Name
        echo "<a href='VF_O_ZT_List.php' target='ZT-Index'>Zeitungsindex</a>";
        echo "</div>"; // Ende der Ausgabe- Einheit Feld
        
 

HTML_trailer();
?>
