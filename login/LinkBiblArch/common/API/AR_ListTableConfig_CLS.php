<?php

/** 
 * Erstellen der Header- Ttiteln für Mitglieder- Listen
 * 
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', "MI_TableConfig_php-error.log.txt");

class AR_ListTableConfig {
    /**
     * Liefert die Spalten-Konfiguration für tabulator.js basierend auf dem Listentyp
     * @param string $listType
     * @return array
     */

    private static string $logFile = "AR_TableConfig_debug.log.txt";
    
    public static function getColumns(string $listType, PDO $pdo): array {
        /*
        $json = json_encode($pdo);
        self::log(__LINE__ . " listType $listType");
        self::log(__LINE__ . " PDO $json");
        # self::log(__LINE__ . " VF_Database instance: " . var_export($vfDatabase, true));
        # self::log(__LINE__ . " PDO object: " . var_export($pdo, true));
        
        $json = json_encode($pdo);
        # self::log(__LINE__ . " PDO $json");
        */
        $sortNo = []; // nicht zu sortierende Spalten
        $hideNo = []; // nicht versteckbare Spalten
        $editable = []; // editierbare Spalten
    
        $meta = new BS_TableColumnMetadata($pdo, 'fharch_new', false);
        $colsByTable = $meta->getColumnsForTables(["fv_falinks"]);
       // $json = json_encode($meta);
        //self::log(__LINE__ . " Meta $json");
        
        $TabTitles =  [];
        $altTitel = [];
        $showCols = []; // anzuzeigende Spalten
        $altTitel = []; // alternative Titel zu den Feld- Kommentaren
          
        $altTitel = []; /* "fa_id" => "Mitgl. Nr.", "mi_m_typ" => "Mitgl. Typ","mi_org_typ" => "Organis. Typ", "mi_org_Name" => "Organisationn", 
            "mi_name" => "Name", "mi_titel" => "Titel", "mi_n_titel" => "Nachgest. Titel", "mi_dgr" => "FW Dienstgrad", "mi_anrede" => "Anrede",  
            "mi_tel_handy" => "Telefon", "mi_gebtag" => "Geburtstag - Alter", "mi_staat" => "Staat", "mi_plz" => "PlZ", "mi_ort" => "Ort", "mi_anschr" => "Anschrift",    
            "mi_fax" => "FAX Nr.","mi_email"=> "E-Mail", "mi_email_status" => "Status  E-Mail",
            "mi_vorst_funct" => "Vorstandsfunktion", "mi_ref_leit" => "Referatsleiter", "mi_ref_int_2" => "R2", "mi_ref_int_3"=> "R3", "mi_ref_int_4"=> "R4",
            "mi_sterbdat" => "Verstorben", "mi_beitritt" => "Beigetreten", "mi_austrdat" => "Ausgetreten",    
            "mi_beitr_bez_bis" => "MB nez. bis",  "mi_abo_bez_bis" => "ABO bez bis", "mi_abo_ausg" => "ABO ausgegeben",
            "mi_einv_art" => "Einverst. Erkl.", "vom:" => "Am:", "mi_ehrg" => "Verliehen", "mi_special_1" => "SonderNutzung", "mi_changed_id_v" => "str - change", "mi_changed_id" => "int Geändert von", "mi_changed_at" => "Geändert am"
        ];       
        */
        switch ($listType) {     
            case "Alle":
                $showCols = ["fa_id", "fa_text", "fa_link"];
                break;
            case "Extern": 
                $showCols = ["fa_text", "fa_link"];
                break;
            default: 
                $showCols = ["fa_text", "fa_link"];
        }
    
        
        /** erstellen der Titel Header */
        $colComment = $meta->getCommentsMap();
        $colStyles  = $meta->getStylesMap();
        $colTypes = $meta->getTypesMap();
        $colLength = $meta->getMaxLengthsMap();
        /*
        $json = json_encode($colComment);
        self::log( __LINE__ . " Kommentare $json  ");
        */
        if ($listType != 'Extern') {
            $TabTitles[] = ["title" => "Aktion", "field" => "action", "width" =>  6 , "hozAlign" => "center",  "headerSort" => false ,  "formatter" => "html" ];
        }
        
        foreach ($showCols as $fldName ) { 
           $titel = "";
            if (isset($altTitel[$fldName]) AND $altTitel[$fldName] !=  "" ) {
                $titel = $altTitel[$fldName];
            } elseif (isset($colComment[$fldName]) and $colComment[$fldName] !=  "" ) {
                $titel = $colComment[$fldName];
            } else {
                $titel = ucfirst($fldName);
            }
            $format = "plaintext";
            if ($fldName == 'fa_link') {
                $format =   "html" ;
            }
   
            $TabTitles[] = ["title" => $titel, "field" => $fldName,  "headerFilter" => "input", "formatter" => $format ];
           
        }
        $json = json_encode($TabTitles);
        # self::log("Tabtitles $json");
        
        return $TabTitles;
    }
    
    protected static function log(string $message): void
    {
        $timestamp = date("Y-m-d H:i:s");
        $entry = "[$timestamp] $message" . PHP_EOL;
        file_put_contents(self::$logFile, $entry, FILE_APPEND);
    }
}
 ?>