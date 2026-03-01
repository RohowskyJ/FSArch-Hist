<!DOCTYPE html>
<?php
# session_start();
/**
 * Mitglieder Verwaltung Liste
 * 
 * @author Josef Rohowsky - neu 2020 - Umstellung Klassen/PDO, Module
 * 
 * 
 */
session_start();

# $_SESSION['VF_Prim'] = ['p_uid'=>'1','all_upd'=>'1'];

#var_dump($_SESSION);

$module = 'ADM-MI';
$sub_mod = "LIST";

$tabelle = 'fv_mitglieder';// <?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/VS_M_List_php-error.log.txt');

$rootPfad = $_SERVER['DOCUMENT_ROOT'];
require_once $rootPfad . '/FHArch_Neu/login/BS_BootPfadL_CLS.php';

PathHelper::init('/FHArch_Neu');  // Basis-URL anpassen
AppAutoloader::register();

/**
 * Includes-Liste
 * enthält alle jeweils includierten Scritpt Files
 */
/*
$_SESSION[$module]['Inc_Arr']  = array();
$_SESSION[$module]['Inc_Arr'][] = "VS_M_List.php"; 
*/
/**
 * Angleichung an den Root-Path
 *
 * @var string $path2ROOT
 */
$path2ROOT = "../../";

$debug = False; // Debug output Ein/Aus Schalter

require $path2ROOT . 'login/common/BS_Funcs.lib.php';

require $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';
require $path2ROOT . 'login/common/VF_Const.lib.php';


$header =   ""; 
# ===========================================================================================================
# Haeder ausgeben
# ===========================================================================================================
$ListHead = "Mitglieder- Verwaltung - Administrator ";
$title = "Mitglieder Daten";
$TABU = true;
HTML_header('Mitglieder- Verwaltung', $header, 'Admin', '200em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

$moduleId = $module."-".$sub_mod;
// Eigene Meldung mit Modulkennung loggen
# $logger->log('Starte Verarbeitung des Moduls', $moduleId, basename(__FILE__));

// XR_Database mit bestehender PDO-Instanz initialisieren
$DBD = new FS_Database();
# var_dump($DBD);
$pdo = $DBD->getPDO();
var_dump($pdo);

$flow_list = False;
$_SESSION[$module]['Return'] = False;

#$LinkDB_database  = '';
#$db = LinkDB('VFH'); 
# var_dump($db);
if (isset($_POST['phase'])) {
    $phase = $_POST['phase'];
} else {
    $phase = 0;
}
if ($phase == 99) {
    header("Location: /login/VF_C_Menu.php");
}

# $NeuRec = "momentan ned"; #     "NeuItem" => "<a href='VF_M_Edit.php?ID=0' >Neues Mitglied eingeben</a>"

?>

<div id="control-bar">
    <select id="list-selector" title="Liste auswählen">
        <option value="Alle">Alle Mitglieder</option>
        <option value="Mitgl">Aktive Mitglieder</option>
        <option value="nMitgl">Nicht- Aktive Mitglieder</option>
        <option value="Adrlist">Adressliste</option>
    </select>

    <!-- 
    <input type="text" id="search-input" placeholder="Globale Suche..." title="Suche in der aktuellen Liste" style="flex-grow:1; min-width:200px; padding:5px;" />
     
     
    <button id="add-new-btn" title="Neuen Datensatz hinzufügen">Neu</button>

    <button id="mass-update-btn" title="Massenupdate für ausgewählte Zeilen">Massenupdate</button>
    -->

    <?php 
    if (isset($NeuRec) ) {
        echo "<span style='width: 50em;'>$NeuRec</span>";
    } else {
        echo "<span style='width: 50em;'></span>";
    }
    ?>
    
    <select id="settings-selector" title="Einstellungen">
        <option value="pagination_on">Pagination an</option>
        <option value="pagination_off">Pagination aus</option>
        <option value="debug_on">Debug an</option>
        <option value="debug_off">Debug aus</option>
        <option value="csv_export">CSV Export</option>
        <option value="print">Drucken</option>
    </select>
    
    
 <div class="hints" id="hints">
  <button class="hints__trigger"
          type="button"
          aria-haspopup="dialog"
          aria-expanded="false"
          aria-controls="hints-panel"
          title="Wähle hier die gewünschten Hinweise aus.">
    <span class="hints__label">Hinweise</span>
    <span class="hints__icon" aria-hidden="true">ℹ️</span>
  </button>

  <div class="hints__panel" id="hints-panel" role="dialog" aria-label="Hinweise zur Tabelle">
    <ul class="hints__list">
      <!-- PHP Block beibehalten -->

      <li class="hints__divider" aria-hidden="true"></li>

      <li class="tip">
        <div class="tip__title">Nach Spalteninhalt sortieren <small>(Tabulator.js)</small></div>
        <div class="tip__body">
          <p>So sortierst du die Tabelle nach dem Inhalt einer Spalte:</p>
          <ol>
            <li>Auf den <b>Spaltentitel</b> klicken.</li>
            <li>Erneut klicken, um zwischen <b>aufsteigend</b> und <b>absteigend</b> zu wechseln.</li>
          </ol>
          <p class="tip__note">
            Der Sortierstatus wird im Header durch ein Sortier-Icon/Pfeil dargestellt (je nach Tabulator-Theme/Config).
            Mehrfachsortierung ist möglich, wenn sie in Tabulator aktiviert ist (z. B. per <code>sortMode</code> bzw. Multi-Column Sorting).
          </p>
        </div>
      </li>

      <li class="tip">
        <div class="tip__title">Anzeige von Spalten unterdrücken <small>(Tabulator.js)</small></div>
        <div class="tip__body">
          <p>Spalten können über ein Spalten-Menü ein-/ausgeblendet werden:</p>
          <ol>
            <li>Das <b>Spaltenmenü</b> öffnen (je nach Umsetzung: Rechtsklick auf Header oder Menü-Icon im Header).</li>
            <li><b>Spalte ausblenden</b> auswählen.</li>
          </ol>
          <p class="tip__note">
            Zum Wiederanzeigen nutzt du das gleiche Menü oder eine separate UI (z. B. „Alle Spalten anzeigen“),
            die intern <code>column.show()</code> / <code>column.hide()</code> verwendet.
          </p>
        </div>
      </li>

      <li class="tip">
        <div class="tip__title">Pagination <small>(Tabulator.js)</small></div>
        <div class="tip__body">
          <p>Wenn Pagination aktiviert ist, wird die Tabelle in Seiten aufgeteilt:</p>
          <ul>
            <li>Zwischen Seiten über die <b>Paginierungs-Steuerung</b> wechseln (z. B. „Vor/Zurück“, Seitenzahlen).</li>
            <li>Optional kann die <b>Seitengröße</b> (Anzahl Zeilen pro Seite) über eine Auswahl geändert werden – abhängig von deiner Tabulator-Konfiguration.</li>
          </ul>
          
        </div>
      </li>
    </ul>
  </div>
</div>
</div>


<?php 
echo "<div id='member-table'></div>  ";    

HTML_trailer();

?>