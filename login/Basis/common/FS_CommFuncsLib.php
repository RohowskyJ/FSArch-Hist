<?php 
/** 
 * Neu aufbau von Funktionen J. Rohowsky ab 2026
 *     @author  Josef Rohowsky josef@kexi.at start 24.12.2025
 * 
 * Enthält und Unterprogramme für die Auwahl von Namen und Begriffen,
 * 
 *  
 *  AutoCompForm_Benutzer    Autocomplete Form fur Benutzer- Auswahl
 *  
 *  
 *  AutoCompForm_Staat       Autocomplete Form fur Staaten- Auswahl
 *  
 *  erlaubnisMand            Welche Berechtigung hat dieser UID für diesen Mandanten
 *  
 *  
 *  userHasRole              Ist dieser UID für dieses Script berechtigt
 */

/**
 * Autocomplete Abfrage für Benutzer- Kurzzeichen
 * Teil der Form
 */
function AutoCompForm_Benutzer () {
    
    ?>
    <div class='field-row'>
    <div class='field-label'><label for="benutzer">Benutzer auswählen (um zu Ändern):</label></div>
    <div class='field-control'><input type="text" id="benutzer" name="benutzer" /></div>
    <!-- Optional: verstecktes Feld für ID -->
    <input type="hidden" id="ben_id" name="ben_id" />
    </div>
    <?php 
} // ende AuzoCompForm_Benutzer

/**
 * Autocomplete Abfrage für Staats- Kurzzeichen
 * Teil der Form
 */
function AutoCompForm_Staat () {
    
    ?>
    <div class='field-row'>
    <div class='field-label'><label for="staat">Staat auswählen (um zu Ändern):</label></div>
    <div class='field-control'><input type="text" id="staat" name="staat" /></div>
    <!-- Optional: verstecktes Feld für ID -->
    <input type="hidden" id="staat_id" name="staat_id" />
    </div>
    <?php 
} // ende AutoCompForm_Staat

/**
 * Feststellen ob der Benutzer genügend Berechtigung hat
 * Berechtigung 0 -- darf nix -> abbruch
 *              1 -- darf fast alles lesen
 *              2 -- darf alles Lesen
 *              3 -- darf alles updaten
 *
 *  liest die Daten aus $_SESSION['VF_Prime'] aus.
 *
 *
 * @return number
 */
function erlaubnisMand() {
    global $module, $sub_mod ;
    $erlaub = 0;
    
    if (isset($_SESSION['BE']) ) {
        if (str_contains($_SESSION['BS_Prim']['BE']['roles'],'ALLE')) {
            $erlaub = 4 ;
        } else {
            if ($module != '' && str_contains($_SESSION['BS_Prim']['BE']['roles'], $module)) {
                $erlaub = 2;
                if (str_contains(MANDANTEN_MODS, $module)) {
                    foreach ($_SESSION['BS_Prim']['BE']['mand_perm'] as $mand_nr => $mand_erl ) {
                        if ($mand_nr == $_SESSION['mand_nr']) {
                            $erlaub = 3;
                        } else {
                            $erlaub = 2;
                        }
                    }
                }
            }
        }
    }
    
    return $erlaub;
} // Ende erlaubnisMand

/**
 * Abfrage für die Berechtigung eines eingeloggten Benutzers
 * 
 * @param string Rollen- Bezeichnung
 */
function userBerechtigtOK ($berechtigung) {
    global $path2ROOT;
    # var_dump($_SESSION['BS_Prim']);
    if (isset($_SESSION['BS_Prim']['BE']['be_id']) && $_SESSION['BS_Prim']['BE']['be_id'] >= 1 && $_SESSION['BS_Prim']['BE']['roles'] != "") {
        if (!userHasRole($berechtigung) ) {
            echo "Für diesen Programmteil besteht keine Berechtgung.<br>";
            echo "<a href='" . $path2ROOT . "../FS_C_Menu.php' > Zurück zum Anfang </a>";
        }
        return true;
    } else {
        echo "Für diesen Programmteil besteht keine Berechtgung.<br>";
        echo "<a href='/VFH/index.php' > Zurück zum Anfang </a>";
    }
    return false;
} // ende userBerechtigtOK

/**
 * Prüft, ob der Benutzer eine bestimmte Rolle hat.
 * Berücksichtigt dabei die Super-User-Rolle 'ADM-ALLE'.
 *
 * @param string $requiredRole Die Rolle, die geprüft werden soll.
 * @return bool True, wenn der Benutzer die Rolle oder 'ADM-ALLE' hat, sonst False.
 */
function userHasRole(string $requiredRole): bool {
    // Rollen-String aus der Session holen
    $rolesString = isset($_SESSION['BS_Prim']['BE']['roles']) ? $_SESSION['BS_Prim']['BE']['roles'] : '';
    
    // String in Array umwandeln, Leerzeichen entfernen
    $rolesArray = array_map('trim', explode(',', $rolesString));
    
    // Super-User-Rolle prüfen
    if (in_array('ADM-ALLE', $rolesArray)) {
        return true;
    }
    
    // Gesuchte Rolle prüfen
    return in_array($requiredRole, $rolesArray);
} // ende userHasRole
