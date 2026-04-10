<?php 
/** 
 * Neu aufbau von Funktionen J. Rohowsky ab 2026
 */



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
}

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
}
