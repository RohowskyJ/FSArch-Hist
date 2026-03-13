<?php 

/**
 * Wartung der Archive und Bibliotheken, Formular
 *
 * @author Josef Rohowsky - neu 2018
 *
 */

/**
 * Includes-Liste
 * enthält alle jeweils includierten Scritpt Files
 */
$_SESSION[$module]['Inc_Arr'][] = "VS_O_AR_Edit_ph0_inc.php"; 

if ($debug) {echo "<pre class=debug>VS_O_AR_Edit_ph0_inc.php ist gestarted</pre>";}

$editProtect = false;  // mit true: keine Eingabe möglich für die ganze Seite
$readonly = "";

if (isset($_SESSION['BS_Prim']['Mod']) && $_SESSION['BS_Prim']['Mod']['smod'] != 'IntStart' ) {
    $editProtect = true;
    # $readonly = false;
}
$forms = new BS_FormRendererFlex($meta, $phase,  $neu, [], $editProtect, $module );

# =========================================================================================================
echo $forms->renderHeader('Bibliotheks- und Archiv- Links');
# =========================================================================================================

echo "<input type='hidden' name='fa_id' value='".$neu['fa_id']."' >";

echo $forms->renderTextLikeFieldFlex('fa_id','0','','','readonly');

# =========================================================================================================
echo $forms->renderTrenner('Link- Daten');
# =========================================================================================================
    
echo $forms->renderTextLikeFieldFlex('fa_link',50);
echo $forms->renderTextLikeFieldFlex('fa_text',60);

# =========================================================================================================

if (isset($_SESSION['BS_Prim']['Mod'])  && ($_SESSION['BS_Prim']['BE']['roles'] == 'ADM-ALLE' || $_SESSION['BS_Prim']['BE']['roles'] == 'ADM-OEF' ) ) {
       
          echo "<p>Nach Eingabe aller Daten oder Änderungen  drücken Sie ";
          echo "<button type='submit' name='phase' value='1' class=green>Daten abspeichern</button></p>";
}
      
      echo "<p><a href='VS_O_AR_List.php'>Zurück zur Liste</a></p>";
# =========================================================================================================
 
if ($debug) {echo "<pre class=debug>VS_O_AR_Edit_ph0_inc.php beendet</pre>";}
?>