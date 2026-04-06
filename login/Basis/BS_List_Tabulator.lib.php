<?php
 
function List_Action_Bar($Tabellen_Name, $Heading, $T_List_Texte, $T_List, $Hinweise = '', $Zus_Ausw = '', $addit_act = '')
{
    global $debug, $path2ROOT, $List_parm, $List_Parameter, $module, $sub_mod, $NeuRec, $csv_DSN, $TabObject;
    
    echo __LINE__ ."path2Root $path2ROOT <br>";
    
    if (! isset($List_Parameter)) {
        $List_Parameter = '';
    }
    
    $DD_List = $DD_aktiv = '';
    foreach ($T_List_Texte as $key => $text) { # für alle
        if ($T_List == $key) { # Aktiv
            $DD_aktiv = $text;
            $DD_List .= "<a class='w3-bar-item'><span style='color:green'>$text</span></a>";
        } else {
            $DD_List .= "<a class='w3-bar-item w3-button' href='$_SERVER[PHP_SELF]?T_List=$key$List_Parameter'>$text</a>";
        }
    }
    
    $grosklein = "Die Auswahl erfolgt unabhängig von Groß/Kleinschreibung.
                  <br>Ausgenommen Umlaute - welche exakt angegeben sein müssen.";
    if ($Tabellen_Name == 'tfb3_users') {
        $grosklein = "Die Auswahl muss exakt angegeben sein.";
    }
    $OrgName = "";
    ?>
<!--  
    /**
     * Seiten- Kopf neu
     */
-->	
<div class="List-grid-container"
     style="grid-template-columns: 100px auto">

     <div>
     <?php

    if (is_file($path2ROOT . 'login/common/config_s.ini')) {
        $ini_arr = parse_ini_file($path2ROOT . 'login/common/config_s.ini', true, INI_SCANNER_NORMAL);
    }
    if (isset($ini_arr['Config'])) {
        $logo = $ini_arr['Config']['sign'];
        echo "<img src='" . $path2ROOT . "login/common/imgs/$logo' alt='Signet Verein Feuerwehrhistoriker' style='border: 3px solid lightblue;  display: block; margin-left: auto; margin-right: auto; margin-top:6px;  width: 80%;'>";
        $OrgName = $ini_arr['Config']['inst'];
    }

    ?>
     </div>

     <div>
       <?php echo "<span style='float: left;'> <label>".$OrgName."</label></span>"; ?>
       <p class='w3-xlarge'><?php echo $Heading;?></p>
     
       <input type='hidden' name='tabelle' value='<?php echo $Tabellen_Name;?>'>
         
        <b><?php echo $DD_aktiv;?></b> 
         
        <div class="container w3-border w3-light-grey " style="max-width: 65em;">
        <!-- =================================================================================================
        # Tabellen-Ansicht Dropdown
        ================================================================================================== -->
            <div class='w3-dropdown-hover'
                    style='padding-left: 5px; padding-top: 5px; padding-bottom: 5px; z-index: 61000;'>
                    
                 <b style='color: blue; text-decoration: underline; text-decoration-style: dotted;'>Tabellen- Auswahl</b>:
                    
                 <div class='w3-dropdown-content w3-bar-block w3-card-4' style='width:40em;'><?php echo $DD_List;?>
                 </div>
                    <!-- w3-dropdown-content -->
            </div>
            <!-- w3-dropdown-hover -->
            
            <?php 
            if ($NeuRec != '') {
                echo $NeuRec;
            }
            
            ?>
           <!--  <b><?php echo $DD_aktiv;?></b>  --> 
            
            <!-- =================================================================================================
            # Optionales Auswahl Feld
            ================================================================================================= -->
            <?php if (!strpos($DD_aktiv, 'Auswahl-') == false) { ?>
            <div class='w3-dropdown-hover w3-light-grey '
                    style='padding-left: 5px; padding-right: 5px; z-index: 11000'>
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Suche <input type='text' name='select_string'
                         value='<?php echo $List_parm['select_string'];?>' maxlength=40
                         size=10> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
            </div>
 
            <!-- =================================================================================================
            # Refresh Button
            ================================================================================================= -->
            <div class='w3-dropdown-hover w3-light-grey'
                    style='padding-left: 5px; padding-right: 5px; z-index: 11000'>
                    <button type='submit' style='font-size: 18px'>Daten neu einlesen</button>
            </div>
      <?php } ?>
            <!-- =================================================================================================
            # Hinweise Dropdown
            ================================================================================================= -->
            <div class='w3-dropdown-hover w3-right'
                    style='padding-left: 5px; padding-right: 5px; padding-top: 5px; padding-bottom: 5px; z-index: 11000'>
                    <b style='color: blue; text-decoration: underline; text-decoration-style: dotted;'>Hinweise</b>
               <div class='w3-dropdown-content w3-bar-block w3-card-4'
                         style='width: 50em; right: 0'>
                   <ul style='margin: 0 1em 0em 1em; padding: 0;'>
                   <?php echo $Hinweise;?>

                   <!-- ------------------------------------------------------- -->
                       <li><div class=tooltip>
                              Nach Spalteninhalten Sortieren
                         <!-- ------------------------------------------------------- -->
                         <div class='tooltiptext'
                                             style='bottom: 100%; left: 0; width: 38em;'>
                                             Um die Liste nach dem Inhalt einen Spalte zu sortiern:
                                             <ol>
                                                  <li>Den Cursor auf den <b>Spalten-Titel-Text</b>
                                                       positionieren.
                                                  </li>
                                                  <li>     <!--  -->Rechts vom Text wird ein kleiner Pfeil sichtbar - dann ist diese Spalte sortierbar. -->
                                                  Auf den Pfeil klicken, dann wird diese Spalte sortiert</li>
                                             </ol>
                                             <!--  -->
                                             <b>Die aktive Sortierung</b> wird in der jeweiligen Spalte mit einem Pfeil <i>nach oben (</i><b>▴</b><i> - Aufsteigend), 
                                             nach unten (</i><b>▾</b></i> - Absteigend) oder nach rechts (</i><b>▸</b><i> - nicht sortiert)</i> angezeigt.
                                              -->
                         </div>
               </div></li>

                     <!-- ------------------------------------------------------- -->
                     <li><div class=tooltip>
                                Anzeige von Spalten Unterdrücken
                                <!-- ------------------------------------------------------- -->
                                <div class='tooltiptext'
                                             style='bottom: 100%; left: 0; width: 40em;'>
                                             Um die Anzeige einer eine Spalte zu unterdrücken:
                                    <ol>
                                                  <li>Den Cursor auf den <b>blauen Spalten-Titel-Text</b>
                                                       positionieren.
                                                  </li>
                                                  <li>In der Pull-Down-Liste - <q>Spalte nicht anzeigen</q>'
                                                       Kicken.
                                                  </li>
                                    </ol>
                                             <p>Dieser Vorgang kann für mehrere Spalte wiederholt werden.</p>
                                             Wenn mit dieser Funktion Spalten nicht angezeigt werden - wird
                                             die Liste der nicht angezeigten Spalten angezeigt - Und ein
                                             Button <q>Alle anzeigen</q>.
                                </div>
                        </div></li>

                              <!-- ------------------------------------------------------- -->
                              <li><div class=tooltip>
                                        Größe der Tabelle Ändern
                                        <!-- ------------------------------------------------------- -->
                                        <div class='tooltiptext'
                                             style='bottom: 100%; left: 0; width: 40em;'>
                                             Um die Tabellengröße zu verändern
                                             <ul>
                                                  <li>Das winzig kleine punktierte Dreieck - rechts unten in der
                                                       Tabelle - mit dem Cursor <b>anklicken und ziehen</b>.
                                                  </li>
                                             </ul>
                                             <br> <b>Achtung</b>: Wenn Sie die Tabellengröße auf diese Art
                                             verändern - ist die Tabellengröße fixiert und passt sich nicht
                                             mehr automatisch der Fensterbreite an.
                                        </div>
                                   </div></li>
                              <!-- ------------------------------------------------------- -->
                              <li>Auswahl Feld: Bei mancher <q><i>Tabellen-Ansicht</i></q> ist
                                   es möglich die Angezeigten Zeilen einzuschränken. Für diese wird
                                   ein zusätzliches Feld <q><i>Auswahl</i>:</q> angezeigt. <!-- ------------------------------------------------------- -->
                                   Es gelten
                                   <div class=tooltip>
                                        diese Regeln
                                        <div class='tooltiptext'
                                             style='top: 100%; left: 0; width: 40em;'>
                                             Geben sie im Feld <q><i>Auswahl</i>:</q> die Zeichenkette an
                                             nach welcher ausgewählt werden soll (Name oder E-Mail_Adresse
                                             oder ..) <br> <b>Groß/Kleinschreibung:</b><br><?php echo $grosklein;?>
                                             <br> <b>Wild-Cards:</b>
                                             <ul style='list-style-type: none; margin: 0;'>
                                                  <li>% steht für beliebig viele Zeichen</li>
                                                  <li>_ entpricht genau einem unbekannten Zeichen</li>
                                             </ul>
                                        </div>
                                   </div>

                         </ul>
                         <br>
                    </div>
                    <!-- w3-dropdown-content -->

               </div>
               <!-- w3-dropdown-hover -->
	
    <!-- =================================================================================================
    # Options Dropdown
    ================================================================================================== -->
    <div class='w3-dropdown-hover w3-right'
                    style='padding-left: 5px; padding-top: 5px; padding-bottom: 5px; z-index: 11000'>
       <b style='color: blue; text-decoration: underline; text-decoration-style: dotted;'>Optionen</b>
       <div class='w3-dropdown-content w3-bar-block w3-card-4'
                         style='width: 25em; right: 0;'>
          <?php
          if ($_SESSION['VF_LISTE']['DropdownAnzeige'] == 'Ein') {
              $EinAus = "Aus$List_Parameter'>Dropdown & Eingabefelder nicht anzeigen";
          } else {
              $EinAus = "Ein$List_Parameter'>Dropdown & Eingabefelder anzeigen";
          }
          echo "<a class='w3-bar-item w3-button' href='" . $_SERVER['PHP_SELF'] . "?DropdownAnzeige=$EinAus</a>";
          
          if ($_SESSION['VF_LISTE']['LangListe'] == 'Ein') {
              $EinAus = "Aus$List_Parameter'>Liste im Kurzformat (für Eingabe)";
          } else {
              $EinAus = "Ein$List_Parameter'>Liste im Langformat (für Druck)";
          }
          echo "<a class='w3-bar-item w3-button' href='" . $_SERVER['PHP_SELF'] . "?LangListe=$EinAus</a>";
          
          if ($_SESSION['VF_LISTE']['VarTableHight'] == 'Ein') {
              $EinAus = "Aus$List_Parameter'>Listenhöhe abhängig von Anzahl der Datensätze";
          } else {
              $EinAus = "Ein$List_Parameter'>Listenhöhe Groß";
          }
          echo "<a class='w3-bar-item w3-button' href='" . $_SERVER['PHP_SELF'] . "?VarTableHight=$EinAus</a>";
          
          if ($_SESSION['VF_LISTE']['CSVDatei'] == 'Ein') {
              $EinAus = "Aus$List_Parameter'>CSV Datei nicht erzeugen";
          } else {
              $EinAus = "Ein$List_Parameter'>CSV Datei erzeugen";
          }
          echo "<a class='w3-bar-item w3-button' href='" . $_SERVER['PHP_SELF'] . "?CSVDatei=$EinAus</a>";
          
          ?>

      </div>

   
                    <!-- w3-dropdown-content -->
     </div>
     <!-- w3-dropdown-hover -->

    <!-- =================================================================================================
    # Nicht angezeigte Spalten
    ================================================================================================== -->
<?php
/*
    if (! $List_parm['hide'] == '') { # -------------------------- Anzeige von Spalten --------------------------
        echo "<div class='w3-dropdown-hover w3-light-grey w3-right' style='padding-left:5px;padding-right:5px;padding-top:5px;padding-bottom:5px;'>";
        echo "Nicht angezeigte Spalten: <q>" . $List_parm['hide'] . "</q> ";
        echo "<a class=button href='$_SERVER[PHP_SELF]?unhide=Y$List_Parameter' style='font-size:100%;'>alle anzeigen</a>";
        echo "</div>";
    } # Anzeige von Spalten
    */
    ?>

  </div>
  
  <div class="container" style="max-width: 65em; background-color: #FFFAF0;"> <!-- 2. Bar -->
   <!--  <div class="w3-bar w3-border  " style="max-width: 65em; background-color: #FFFAF0;">--> <!-- 2. Bar --> 
           <?php 
           # var_dump($_SESSION);
           $allow = erlaubnis();
           # echo $allow;
           if ($allow >= 4) { // ($_SESSION['BE']['all_upd'] == '1') {
        echo $NeuRec;
        /**
         *  debug switch beginn
         */
       
        
        
        if ( $allow >= 2)  { // isset($_SESSION['VF_Prim']['p_uid']) && $_SESSION['VF_Prim']['p_uid'] == '1' ) {
            $Hinweise = "<li>Blau unterstrichene Daten sind Klickbar <ul style='margin:0 1em 0em 1em;padding:0;'>  <li>Fahrzeug - Daten ändern: Auf die Zahl in Spalte <q>fz_id</q> Klicken.</li> ";
            $adm_cont = "
                <ul style='margin: 0 1em 0em 1em; padding: 0;'>
                $Hinweise
               </ul>
                ";
                
                ?>
           <!-- opPopOver -->
         
           <div class="dropup w3-center">
                <b class='dropupstrg' style='color:lightgrey; background-color:white;font-size: 10px; float: right;'>Dbg</b>
               <div class="dropup-content" style='bottom: -100px; right: -300px;'>
                   <b>Entwanzungs-Optionen</b> <br>
                   <i>Script-Module</i><br>
                   <?php
             
                   if (isset($_SESSION[$module]['Inc_Arr']) && count($_SESSION[$module]['Inc_Arr']) > 0) {
                       echo '<ul style="margin: 8px 0; padding-left: 20px; list-style: disc;">';
                       foreach ($_SESSION[$module]['Inc_Arr'] as $key) {
                           echo '<li style="margin: 4px 0; font-size: 0.9em;">' . htmlspecialchars($key) . '</li>';
                       }
                       echo '</ul>';
                   } else {
                       echo '<p style="color: #999; font-size: 0.9em; margin: 8px 0;">Keine Script Information enthalten</p>';
                   }
                   ?>
                   <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #ddd;">
                       SQL Befehl anzeigen <button id="toggleButt-sD" class='button-sm'>Einschalten</button><br>
                   </div>
         
             </div>
          </div>

        
            <!-- opPopOver ende -->
            <?php 
       
        # echo "</div>"; // ende kurzer Teil
        /**
         *  debug switch ende
         */
        }
        echo "<span class='toggle-csvDisp'>";   
        echo " <a href='$csv_DSN'> &nbsp; &nbsp; CSV Version ansehen</a>";
        echo "</span>";
    }
    ?>
 
  </div>  <!-- 2. Bar -->

  <!-- w3-bar -->


   </div>
  </div>
  
 

<?php

    if ($addit_act != "") { # # Funct- Aufruf oder code ? Berater- Auswahl oder ?
        echo "<div class='w3-center'><br>";
        echo $addit_act;
        echo "</div>"; // w3-noprint
    }
}

# End of Function VF_List_Action_Bar
function List_Create($meta, $tabelle, $showColumns = [], $altTitel = [], $sortAble = [], $hideAble = [], $editAble = [], $tableId = 'default')
{
    global $debug, $path2ROOT, $module,  $sub_mod, $logger, $T_List_Texte, $Hinweise , $NeuRec,
    
    $List_parm,$Div_Parm, $List_Parameter, $DropdownAnzeige, $TabButton,$Kateg_Name, $zus_text, $CSV_Spalten, $csv_DSN 
    ;
    
    echo __LINE__ ."path2Root $path2ROOT <br>";
    # $NeuRec;
    # var_dump($data);
    # $logger->log('List_Create: ', $module, basename(__FILE__));
    
    
    $colComments = $meta->getCommentsMap();
    $colTypes    = $meta->getTypesMap();
    $colStyles    = $meta->getStylesMap();
    $colMaxLength = $meta->getMaxLengthMap;
    $colKeyMap    = $meta->getKeysMap;
    
    $columns = [];
    foreach ($showColumns as $col) {
        $titelText = $altTitel[$col] ?? $colComments[$col] ?? ucfirst($col);
        $sorter = '';
        if (isset($colTypes[$col])) {
            if ($colTypes[$col] == 'text') {
                $sorter = 'string';
            } elseif ($colTypes[$col] == 'num') {
                $sorter = 'number';
            }
        }
        if (isset($colStyles[$col])) {
            var_dump($colStyles[$col]);
        }
        $columns[] = [
            'title' => $titelText,
            'field' => $col,
            'sorter' => $sorter,
            // Style oder andere Optionen hier hinzufügen, z.B. 'hozAlign' => 'center'
        ];
    }

    var_dump($columns);
    
    
    
}

