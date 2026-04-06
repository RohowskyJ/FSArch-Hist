<?php
require_once  $path2ROOT . 'login/common/BS_CentralLog_CLS.php' ;
// Logger initialisieren (einmalig, z.B. in Bootstrap)

$logger = CentralLogger::getInstance('/login/logs');
$logger->registerErrorHandlers('default'); // Optional: Default-Modul für Fehler
$moduleId = $module."-FORM";
// Eigene Meldung mit Modulkennung loggen
$logger->log('Starte Verarbeitung des Moduls', $moduleId, basename(__FILE__));


class FormRenderer
{
    private TableColumnMetadata $meta;
    private int $phase;
    private int $errors;
    private array $data;       // Eingabedaten, z.B. aus DB oder POST
    private array $errorMsgs;  // Fehlermeldungen pro Feld
    private bool $editProtect;
    private string $readOnly;
    private string $module;
    private string $editCss;
    
    /**
     * @param TableColumnMetadata $meta Instanz der Metadatenklasse
     * @param int $phase Formularphase (0 oder 1)
     * @param int $errors Anzahl Fehler
     * @param array $data Datenarray (z.B. aus DB oder POST)
     * @param array $errorMsgs Fehlermeldungen pro Feld
     * @param bool $editProtect Wenn true, Felder sind nur lesbar
     * @param string $readOnly HTML Attribut für readonly (z.B. 'readonly')
     * @param string $module Modulname für Logging
     */
    public function __construct(
        TableColumnMetadata $meta,
        int $phase,
        int $errors,
        array $data,
        array $errorMsgs = [],
        bool $editProtect = false,
        string $readOnly = '',
        string $module = ''
        ) {
            $this->meta = $meta;
            $this->phase = $phase;
            $this->errors = $errors;
            $this->data = $data;
            $this->errorMsgs = $errorMsgs;
            $this->editProtect = $editProtect;
            $this->readOnly = $readOnly;
            $this->module = $module;
            $this->editCss = ''; // w3-input
            
          }
    
    // --- Validierungsmethoden ---
    /**
     * Validierung für Taxt- Feld
     * 
     * @param string $fieldName Feldname
     * @param bool $required true  .. Feldeingabe reqired
     * @param string $pattern  Inhalt dar nicht diesem Wert enthalten
     * @return bool
     */
    public function validateTextField(string $fieldName, bool $required = false, ?string $pattern = null): bool
    {
        $value = trim($this->data[$fieldName] ?? '');
        $maxLengths = $this->meta->getMaxLengthsMap();
        $maxLength = $maxLengths[$fieldName] ?? null;
        
        if ($required && $value === '') {
            $this->errorMsgs[$fieldName] = 'Dieses Feld ist erforderlich.';
            return false;
        }
        if ($maxLength !== null && mb_strlen($value) > $maxLength) {
            $this->errorMsgs[$fieldName] = "Maximale Länge von $maxLength Zeichen überschritten.";
            return false;
        }
        if ($pattern !== null && $value !== '' && !preg_match($pattern, $value)) {
            $this->errorMsgs[$fieldName] = 'Ungültiges Format.';
            return false;
        }
        return true;
    }
    
    /**
     * Validierung Numerisches Feld
     * 
     * @param string $fieldName
     * @param bool $required true .. Feldeingabe required
     * @param int $min Mindestwert
     * @param int $max Maximalwert
     * @return bool
     */
    public function validateNumberField(string $fieldName, bool $required = false, $min = null, $max = null): bool
    {
        $value = trim($this->data[$fieldName] ?? '');
        
        if ($required && $value === '') {
            $this->errorMsgs[$fieldName] = 'Dieses Feld ist erforderlich.';
            return false;
        }
        if ($value === '') {
            return true;
        }
        if (!is_numeric($value)) {
            $this->errorMsgs[$fieldName] = 'Bitte geben Sie eine gültige Zahl ein.';
            return false;
        }
        $num = $value + 0;
        if ($min !== null && $num < $min) {
            $this->errorMsgs[$fieldName] = "Der Wert muss mindestens $min sein.";
            return false;
        }
        if ($max !== null && $num > $max) {
            $this->errorMsgs[$fieldName] = "Der Wert darf maximal $max sein.";
            return false;
        }
        return true;
    }
    
    public function validateSelectField(string $fieldName, array $allowedValues, bool $required = false): bool
    {
        $value = $this->data[$fieldName] ?? '';
        
        if ($required && $value === '') {
            $this->errorMsgs[$fieldName] = 'Bitte wählen Sie eine Option aus.';
            return false;
        }
        if ($value !== '' && !in_array($value, $allowedValues, true)) {
            $this->errorMsgs[$fieldName] = 'Ungültige Auswahl.';
            return false;
        }
        return true;
    }
    
    public function validateCheckboxField(string $fieldName): bool
    {
        $value = $this->data[$fieldName] ?? '';
        if ($value !== '' && $value !== 'Y') {
            $this->errorMsgs[$fieldName] = 'Ungültiger Wert für Checkbox.';
            return false;
        }
        return true;
    }
    
    public function validateDateField(string $fieldName, bool $required = false, ?string $format = 'Y-m-d'): bool
    {
        $value = trim($this->data[$fieldName] ?? '');
        
        if ($required && $value === '') {
            $this->errorMsgs[$fieldName] = 'Dieses Feld ist erforderlich.';
            return false;
        }
        if ($value === '') {
            return true;
        }
        
        $d = \DateTime::createFromFormat($format, $value);
        if (!($d && $d->format($format) === $value)) {
            $this->errorMsgs[$fieldName] = 'Ungültiges Datumsformat.';
            return false;
        }
        return true;
    }
    
    public function validateFileUploadField(string $fieldName, ?array $allowedMimeTypes = null, ?int $maxFileSize = null): bool
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            if (($this->errorMsgs[$fieldName] ?? '') === 'required') {
                $this->errorMsgs[$fieldName] = 'Bitte wählen Sie eine Datei aus.';
                return false;
            }
            return true;
        }
        
        $file = $_FILES[$fieldName];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errorMsgs[$fieldName] = 'Fehler beim Datei-Upload.';
            return false;
        }
        
        if ($maxFileSize !== null && $file['size'] > $maxFileSize) {
            $this->errorMsgs[$fieldName] = 'Die Datei ist zu groß.';
            return false;
        }
        
        if ($allowedMimeTypes !== null) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                $this->errorMsgs[$fieldName] = 'Dateityp nicht erlaubt.';
                return false;
            }
        }
        
        return true;
    }
    
    public function validateAll(array $validationRules): bool
    {
        $this->errorMsgs = [];
        $valid = true;
        
        foreach ($validationRules as $field => $rules) {
            $type = $rules['type'] ?? 'text';
            $required = $rules['required'] ?? false;
            $pattern = $rules['pattern'] ?? null;
            $allowedValues = $rules['allowedValues'] ?? [];
            $min = $rules['min'] ?? null;
            $max = $rules['max'] ?? null;
            $allowedMimeTypes = $rules['allowedMimeTypes'] ?? null;
            $maxFileSize = $rules['maxFileSize'] ?? null;
            
            switch ($type) {
                case 'text':
                    if (!$this->validateTextField($field, $required, $pattern)) {
                        $valid = false;
                    }
                    break;
                case 'number':
                    if (!$this->validateNumberField($field, $required, $min, $max)) {
                        $valid = false;
                    }
                    break;
                case 'select':
                    if (!$this->validateSelectField($field, $allowedValues, $required)) {
                        $valid = false;
                    }
                    break;
                case 'checkbox':
                    if (!$this->validateCheckboxField($field)) {
                        $valid = false;
                    }
                    break;
                case 'date':
                    if (!$this->validateDateField($field, $required)) {
                        $valid = false;
                    }
                    break;
                case 'file':
                    if (!$this->validateFileUploadField($field, $allowedMimeTypes, $maxFileSize)) {
                        $valid = false;
                    }
                    break;
                default:
                    // unbekannter Typ
                    break;
            }
        }
        
        $this->errors = count($this->errorMsgs);
        if ($this->errors >= 1) {
            $logger->log($message, $moduleId, 'BS_Forms_CLS.php');
        }
        
        return $valid;
    }
    
    // --- Render-Methoden mit responsivem W3.CSS Layout ---
    
    private function wrapFieldRowDiv(string $fieldName, string $fieldHtml, string $label): string
    {
        console_log("Label  $label");
        console_log("FeldName $fieldName");
        console_log("HTML  $fieldHtml");
        return '
        <div class="w3-row w3-padding-small w3-margin-bottom">
            <div class="w3-third w3-container w3-padding-small w3-text-bold">
                <label for="' . htmlspecialchars($fieldName) . '">' . htmlspecialchars($label) . '</label>
            </div>
            <div class="w3-twothird w3-container w3-padding-small">
                ' . $fieldHtml . '
            </div>
        </div>';
    }
    
    public function renderTextField(string $fieldName, int $fieldLength = 0, string $infoText = '', string $fieldAttr = ''): string
    {

        $value = htmlspecialchars($this->data[$fieldName] ?? '', ENT_QUOTES);
        $maxLengths = $this->meta->getMaxLengthsMap();
        $maxLength = $maxLengths[$fieldName] ?? 0;
        $error = $this->errorMsgs[$fieldName] ?? '';
        $readonly = $this->readOnly;
        $disabled = ($this->phase !== 0 || $this->editProtect) ? 'disabled' : '';
        
   
        $maxlengthAttr = ($maxLength > 0) ? "maxlength='$maxLength'" : '';
        $sizeAttr = ($fieldLength > 0) ? "size='$fieldLength'" : '';
        
        $class = $error ? 'w3-border-red' : $this->editCss; // w3-input
        
        $readOnly = "";
        if ($fieldLength == 0 ){
            $readOnly = 'readonly';
        }
        
        if ($disabled) {
            $inputHtml = "<div class='w3-padding-small'>$value</div>";
        } else {
            $inputHtml = "<input type='text' id='$fieldName' name='$fieldName' value='$value' $maxlengthAttr $sizeAttr $fieldAttr $readonly $disabled class='$class'" . ($error ? ' autofocus' : '') . ">";
        }
        
        if ($error) {
            $inputHtml .= "<div class='w3-text-red w3-small w3-margin-top'>$error</div>";
        }
        if ($infoText) {
            $inputHtml .= "<div class='w3-text-gray w3-small'>$infoText</div>";
        }
        
        $label = $this->meta->getCommentsMap()[$fieldName] ?? ucfirst($fieldName);
        return $this->wrapFieldRowDiv($fieldName, $inputHtml, $label);
    }
    
    public function renderTextareaField(string $fieldName, string $infoText = '', string $fieldAttr = ''): string
    {
        $value = htmlspecialchars($this->data[$fieldName] ?? '', ENT_QUOTES);
        $maxLengths = $this->meta->getMaxLengthsMap();
        $maxLength = $maxLengths[$fieldName] ?? 0;
        $error = $this->errorMsgs[$fieldName] ?? '';
        $readonly = $this->readOnly;
        $disabled = ($this->phase !== 0 || $this->editProtect) ? 'disabled' : '';
        
        $maxlengthAttr = ($maxLength > 0) ? "maxlength='$maxLength'" : '';
        $rows = ($maxLength > 200) ? 7 : 3;
        $cols = 50;
        
        $class = $error ? 'w3-border-red' : $this->editCss;
        
        if ($disabled) {
            $inputHtml = "<div class='w3-padding-small' style='white-space: pre-wrap;'>$value</div>";
        } else {
            $inputHtml = "<textarea id='$fieldName' name='$fieldName' rows='$rows' cols='$cols' $maxlengthAttr $fieldAttr $readonly $disabled class='$class'" . ($error ? ' autofocus' : '') . ">$value</textarea>";
        }
        
        if ($error) {
            $inputHtml .= "<div class='w3-text-red w3-small w3-margin-top'>$error</div>";
        }
        if ($infoText) {
            $inputHtml .= "<div class='w3-text-gray w3-small'>$infoText</div>";
        }
        
        $label = $this->meta->getCommentsMap()[$fieldName] ?? ucfirst($fieldName);
        return $this->wrapFieldRowDiv($fieldName, $inputHtml, $label);
    }
    
    public function renderRadioField(string $fieldName, array $buttons, string $infoText = '', $anzTitel = ''): string
    {
        $valueCurrent = $this->data[$fieldName] ?? '';
        $error = $this->errorMsgs[$fieldName] ?? '';
        $readonly = $this->readOnly;
        $disabled = ($this->phase !== 0 || $this->editProtect);
        
        $html = '<div class="w3-bar">';
        foreach ($buttons as $value => $text) {
            $checked = ($valueCurrent == $value) ? 'checked' : '';
            $dis = $disabled ? 'disabled' : '';
            $html .= "<label class='w3-bar-item w3-button w3-light-grey w3-margin-right' style='white-space: nowrap;'>";
            $html .= "<input type='radio' name='$fieldName' value='" . htmlspecialchars($value) . "' $checked $dis $readonly> " . htmlspecialchars($text);
            $html .= "</label>";
        }
        $html .= '</div>';
        console_log("$html");
        if ($error) {
            $html .= "<div class='w3-text-red w3-small w3-margin-top'>$error</div>";
        }
        if ($infoText) {
            $html .= "<div class='w3-text-gray w3-small'>$infoText</div>";
        }
        
        # oti code: $label = $this->meta->getCommentsMap()[$fieldName] ?? ucfirst($fieldName);
        if (isset($commentsMap[$fieldName])) {
            $label = $commentsMap[$fieldName];
        } elseif (!empty($anzTitel)) {
            $label = $anzTitel;
        } else {
            $label = ucfirst($fieldName);
        }
        console_log("$fieldName, $html, $label");
        return $this->wrapFieldRowDiv($fieldName, $html, $label);
    }
    
    public function renderCheckboxField(string $fieldName, string $text, string $infoText = ''): string
    {
        $valueCurrent = $this->data[$fieldName] ?? '';
        $error = $this->errorMsgs[$fieldName] ?? '';
        $readonly = $this->readOnly;
        $disabled = ($this->phase !== 0 || $this->editProtect);
        
        $checked = ($valueCurrent === 'Y' || $valueCurrent === true || $valueCurrent === 1) ? 'checked' : '';
        $dis = $disabled ? 'disabled' : '';
        
        $html = "<label class='w3-check'><input type='checkbox' name='$fieldName' value='Y' $checked $dis $readonly> " . htmlspecialchars($text) . "</label>";
        
        if ($error) {
            $html .= "<div class='w3-text-red w3-small w3-margin-top'>$error</div>";
        }
        if ($infoText) {
            $html .= "<div class='w3-text-gray w3-small'>$infoText</div>";
        }
        
        $label = $this->meta->getCommentsMap()[$fieldName] ?? ucfirst($fieldName);
        return $this->wrapFieldRowDiv($fieldName, $html, $label);
    }
    
    public function renderSelectField(string $fieldName, array $options, string $infoText = '', string $fieldAttr = '', $anzTitel = ''): string
    {
        $valueCurrent = $this->data[$fieldName] ?? '';
        $error = $this->errorMsgs[$fieldName] ?? '';
        $readonly = $this->readOnly;
        $disabled = ($this->phase !== 0 || $this->editProtect) ? 'disabled' : '';
        
        $class = $error ? 'w3-border-red' : 'w3-select';
        
        $html = "<select id='$fieldName' name='$fieldName' $fieldAttr $readonly $disabled class='$class'" . ($error ? ' autofocus' : '') . ">";
        foreach ($options as $val => $text) {
            $selected = ($val == $valueCurrent) ? 'selected' : '';
            $html .= "<option value='" . htmlspecialchars($val) . "' $selected>" . htmlspecialchars($text) . "</option>";
        }
        $html .= "</select>";
        
        if ($error) {
            $html .= "<div class='w3-text-red w3-small w3-margin-top'>$error</div>";
        }
        if ($infoText) {
            $html .= "<div class='w3-text-gray w3-small'>$infoText</div>";
        }
        
        // ori $label = $this->meta->getCommentsMap()[$fieldName] ?? ucfirst($fieldName);
        if (isset($commentsMap[$fieldName])) {
            $label = $commentsMap[$fieldName];
        } elseif (!empty($anzTitel)) {
            $label = $anzTitel;
        } else {
            $label = ucfirst($fieldName);
        }
        return $this->wrapFieldRowDiv($fieldName, $html, $label);
    }
    
    public function renderDatePickerField(string $fieldName, string $infoText = '', string $fieldAttr = ''): string
    {
        $value = htmlspecialchars($this->data[$fieldName] ?? '', ENT_QUOTES);
        $error = $this->errorMsgs[$fieldName] ?? '';
        $readonly = $this->readOnly;
        $disabled = ($this->phase !== 0 || $this->editProtect) ? 'disabled' : '';
        
        $class = $error ? 'w3-border-red' : $this->editCss;
        
        $html = "<input type='date' id='$fieldName' name='$fieldName' value='$value' $fieldAttr $readonly $disabled class='$class'" . ($error ? ' autofocus' : '') . ">";
        
        if ($error) {
            $html .= "<div class='w3-text-red w3-small w3-margin-top'>$error</div>";
        }
        if ($infoText) {
            $html .= "<div class='w3-text-gray w3-small'>$infoText</div>";
        }
        
        $label = $this->meta->getCommentsMap()[$fieldName] ?? ucfirst($fieldName);
        return $this->wrapFieldRowDiv($fieldName, $html, $label);
    }
    
    public function renderFileUploadField(string $fieldName, string $infoText = '', string $fieldAttr = ''): string
    {
        $error = $this->errorMsgs[$fieldName] ?? '';
        $readonly = $this->readOnly;
        $disabled = ($this->phase !== 0 || $this->editProtect) ? 'disabled' : '';
        
        $class = $error ? 'w3-border-red' : '';
        
        $html = "<input type='file' id='$fieldName' name='$fieldName' $fieldAttr $readonly $disabled class='$class'" . ($error ? ' autofocus' : '') . ">";
        
        if ($error) {
            $html .= "<div class='w3-text-red w3-small w3-margin-top'>$error</div>";
        }
        if ($infoText) {
            $html .= "<div class='w3-text-gray w3-small'>$infoText</div>";
        }
        
        $label = $this->meta->getCommentsMap()[$fieldName] ?? ucfirst($fieldName);
        return $this->wrapFieldRowDiv($fieldName, $html, $label);
    }
    
    // Seitentitel
    public function renderHeader(string $text): string
    {
        // Stil: mittelgrauer Hintergrund, kleinere Schrift, zentriert
        $style = "background-color: #b0b0b0; color: #333; font-size: 0.9em; text-align: center; padding: 0.5em 1em; margin: 1em 0; font-weight: bold; border-radius: 4px;";
        
        $html = "<div style=\"$style\">"
        . htmlspecialchars($text, ENT_QUOTES)
        . "</div>";
        
        return $html;
    }
    
    // --- Trennzeile
    public function renderTrenner(string $text): string
    {
        // Stil: hellgrauer Hintergrund, volle Breite, ca. 3 Zeichen hoch (~1.2em)
        $style = "background-color: #e0e0e0; height: 1.2em; width: 100%; margin: 1em 0;";
        
        $html = "<div style=\"$style\">" . " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; "
                . htmlspecialchars($text, ENT_QUOTES) 
                ."</div>";
        
        return $html;
    }
    
    public function renderTitleDataBlockFlex(string $fieldName, int $fieldLength = 0, string $infoText = '', string $fieldAttr = ''): string
    {
        $value = htmlspecialchars($this->data[$fieldName] ?? '', ENT_QUOTES);
        $maxLengths = $this->meta->getMaxLengthsMap();
        $maxLength = $maxLengths[$fieldName] ?? 0;
        $error = $this->errorMsgs[$fieldName] ?? '';
        $readOnlyMode = ($this->readOnly === 'readonly');
        $disabled = ($this->phase !== 0 || $this->editProtect) ? 'disabled' : '';
        
        $maxlengthAttr = ($maxLength > 0) ? "maxlength='$maxLength'" : '';
        $sizeAttr = ($fieldLength > 0) ? "size='$fieldLength'" : '';
        
        $label = $this->meta->getCommentsMap()[$fieldName] ?? ucfirst($fieldName);
        
        if ($readOnlyMode || $disabled) {
            $control = "<div class='readonly-block'>$value</div>";
        } else {
            $class = $error ? 'input danger-border' : 'input';
            $control = "<input class='$class' type='text' id='$fieldName' name='$fieldName' value='$value' $maxlengthAttr $sizeAttr $fieldAttr>";
        }
        
        if ($error)    $control .= "<div class='error'>$error</div>";
        if ($infoText) $control .= "<div class='hint'>$infoText</div>";
        
        $labelHtml = $readOnlyMode ? "<strong>$label</strong>" : "<label for='$fieldName'><strong>$label</strong></label>";
        
        return "
          <div class='field-row'>
            <div class='field-label'>$labelHtml</div>
            <div class='field-control'>$control</div>
          </div>
        ";
    }
    
    public function renderTextFieldFlex(string $fieldName, int $fieldLength = 0, string $infoText = '', string $fieldAttr = ''): string
    {
        $value = htmlspecialchars($this->data[$fieldName] ?? '', ENT_QUOTES);
        $maxLengths = $this->meta->getMaxLengthsMap();
        $maxLength = $maxLengths[$fieldName] ?? 0;
        $error = $this->errorMsgs[$fieldName] ?? '';
        $readonly = $this->readOnly; // z.B. 'readonly' oder ''
        $disabled = ($this->phase !== 0 || $this->editProtect) ? 'disabled' : '';
        
        $maxlengthAttr = ($maxLength > 0) ? "maxlength='$maxLength'" : '';
        $sizeAttr = ($fieldLength > 0) ? "size='$fieldLength'" : '';
        
        $class = $error ? 'input danger-border' : 'input';
        
        if ($disabled) {
            $control = "<div class='readonly-block'>$value</div>";
        } else {
            $control = "<input class='$class' type='text' id='$fieldName' name='$fieldName' value='$value' $maxlengthAttr $sizeAttr $fieldAttr $readonly $disabled>";
        }
        
        if ($error)    $control .= "<div class='error'>$error</div>";
        if ($infoText) $control .= "<div class='hint'>$infoText</div>";
        
        $label = $this->meta->getCommentsMap()[$fieldName] ?? ucfirst($fieldName);
        
        return "
          <div class='field-row'>
            <div class='field-label'><label for='$fieldName'>$label</label></div>
            <div class='field-control'>$control</div>
          </div>
        ";
    }
    // --- Getter für Fehlermeldungen ---
    public function getErrorMessages(): array
    {
        return $this->errorMsgs;
    }
}