<?php 




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
