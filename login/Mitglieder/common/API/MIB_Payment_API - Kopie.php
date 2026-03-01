<?php
// Fehleranzeige und Logging aktivieren (nur für Debug, im Produktivbetrieb aus)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/MIB_Bezahl_API_php-error.log.txt');

// Shutdown-Funktion direkt am Anfang registrieren
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        $message = "Shutdown error detected:\n" . print_r($error, true);
        error_log($message);
        // Optional: auch in eine separate Datei schreiben
        file_put_contents(__DIR__ . '/MIB_bez_fatal_error.log', $message, FILE_APPEND);
    }
});
    $debug_log_file = 'MIB_Payment_debug.log.txt';
    file_put_contents($debug_log_file, "\n==== API CALL ====\n".date('Y-m-d H:i:s')."\nMETHOD: ".@$_SERVER['REQUEST_METHOD']."\n", FILE_APPEND);
    
    // Output Buffering starten, um unerwünschte Ausgabe zu kontrollieren
    ob_start();
    
    $rootPfad = $_SERVER['DOCUMENT_ROOT'];
    require_once $rootPfad . '/FHArch_Neu/login/BS_BootPfadL_CLS.php';
    
    // Jetzt PathHelper initialisieren
    PathHelper::init('/FHArch_Neu');
    
    // Autoloader registrieren (benötigt initialisierten PathHelper)
    AppAutoloader::register();
    
    // Optional: prüfen, ob PathHelper geladen ist
    if (!class_exists('PathHelper')) {
        error_log("Class PathHelper not found after require_once!");
    } else {
        error_log("Class PathHelper loaded successfully.");
    }
    # error_log(__LINE__);
    
    header('Content-Type: application/json; charset=utf-8');
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $file_cont = file_get_contents('php://input');
    file_put_contents($debug_log_file, __LINE__ . " data $file_cont \n", FILE_APPEND);
    
    if (!$data || !isset($data['mi_id']) || !isset($data['action']) || !isset($data['field']) || !isset($data['changed_id'])) {
        echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
        exit;
    }
    
    $mi_id = intval($data['mi_id']);
    $action = $data['action'];
    $field = $data['field'];
    $changed_id = intval($data['changed_id']);
    $year = isset($data['year']) ? intval($data['year']) : null;
    
    file_put_contents($debug_log_file, __LINE__ . " mi_id $mi_id action $action field $field uid $changed_id  year $year\n", FILE_APPEND);
    
    $allowedActions = ['pay', 'cancel'];
    if (!in_array($action, $allowedActions)) {
        echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
        exit;
    }
    
    // Feldnamen dürfen M_, A_ oder MA_ beginnen (Mitglieds-, Abo- oder beides gleichzeitig)
    if (!preg_match('/^(M|A|MA)_/', $field)) {
        echo json_encode(['success' => false, 'message' => 'Ungültiges Feld']);
        exit;
    }
    
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=fharch_new;charset=utf8mb4', 'root', 'b1teller');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Erwartete Eingaben (z.B. aus POST)
        $mi_id = intval($data['mi_id']);
        $action = $data['action']; // 'pay' oder 'cancel'
        $changed_id = intval($data['changed_id']);
        $year = isset($data['year']) ? intval($data['year']) : null;
        $field = $data['field'] ?? null; // z.B. 'M_0', 'A_0', 'M_p', 'A_p'
        
        $currentDate = date('Y-m-d');
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;
        
        file_put_contents($debug_log_file, __LINE__ . " vor Gruppe if action == 'pay' \n", FILE_APPEND);
        
        if ($action === 'pay') {
            if (!$year || !$field) {
                echo json_encode(['success' => false, 'message' => 'Jahr oder Feld fehlt']);
                exit;
            }
            
            // Prüfen, ob bereits ein Eintrag für mi_id, Jahr und Feld existiert
            // Wir nehmen an, dass mb_bez_mb_bis für M_* und mb_bez_abo_bis für A_* steht
            // Feldzuordnung: M = Mitgliedsbeitrag, A = Abo, MA = beide
            if (strpos($field, 'MA_') === 0) {
                $isM = true;
                $isA = true;
            } else {
                $isM = strpos($field, 'M') === 0;
                $isA = strpos($field, 'A') === 0;
            }
            
            // Suche nach bestehendem Eintrag für dieses Jahr und Mitglied
            $sqlCheck = "SELECT * FROM fv_mi_bez WHERE mi_id = ? AND mb_cancel IS NULL ORDER BY mb_changed_at DESC";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([$mi_id]);
            $entries = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);
            
            $foundEntry = null;
            foreach ($entries as $entry) {
                if (($isM && $entry['mb_bez_mb_bis'] == $year) || ($isA && $entry['mb_bez_abo_bis'] == $year)) {
                    $foundEntry = $entry;
                    break;
                }
            }
            
            // Neue Werte vorbereiten
            $new_mb_bis = $isM ? $year : null;
            $new_abo_bis = $isA ? $year : null;
            $new_mb_datum = $isM ? $currentDate : null;
            $new_abo_datum = $isA ? $currentDate : null;
            
            if ($foundEntry) {
                
                file_put_contents($debug_log_file, date('Y-m-d H:i:s') . " - Action: $action, Insert oder Update? " . __LINE__ , FILE_APPEND);
                              
                // Update vorhandenen Eintrag, vorherige Werte sichern falls noch nicht gesetzt
                $prev_mb_bis = $foundEntry['mb_prev_mb_bis'] ?? $foundEntry['mb_bez_mb_bis'];
                $prev_abo_bis = $foundEntry['mb_prev_abo_bis'] ?? $foundEntry['mb_bez_abo_bis'];
                $prev_mb_datum = $foundEntry['mb_prev_mb_datum'] ?? $foundEntry['mb_bez_mb_datum'];
                $prev_abo_datum = $foundEntry['mb_prev_abo_datum'] ?? $foundEntry['mb_bez_abo_datum'];
                
                $sqlUpdate = "UPDATE fv_mi_bez SET
                mb_bez_mb_bis = IF(? IS NOT NULL, ?, mb_bez_mb_bis),
                mb_bez_abo_bis = IF(? IS NOT NULL, ?, mb_bez_abo_bis),
                mb_bez_mb_datum = IF(? IS NOT NULL, ?, mb_bez_mb_datum),
                mb_bez_abo_datum = IF(? IS NOT NULL, ?, mb_bez_abo_datum),
                mb_prev_mb_bis = ?,
                mb_prev_abo_bis = ?,
                mb_prev_mb_datum = ?,
                mb_prev_abo_datum = ?,
                mb_cancel = NULL,
                mb_changed_id = ?,
                mb_changed_at = NOW()
                WHERE mb_id = ?";
                
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    $new_mb_bis, $new_mb_bis,
                    $new_abo_bis, $new_abo_bis,
                    $new_mb_datum, $new_mb_datum,
                    $new_abo_datum, $new_abo_datum,
                    $prev_mb_bis,
                    $prev_abo_bis,
                    $prev_mb_datum,
                    $prev_abo_datum,
                    $changed_id,
                    $foundEntry['mb_id']
                ]);
            } else {
                // Insert neuen Eintrag
                $stmtInsert = $pdo->prepare("
                INSERT INTO fv_mi_bez (
                    mi_id, mb_bez_mb_bis, mb_bez_abo_bis, mb_bez_mb_datum, mb_bez_abo_datum,
                    mb_prev_mb_bis, mb_prev_abo_bis, mb_prev_mb_datum, mb_prev_abo_datum,
                    mb_cancel, mb_changed_id, mb_changed_at
                    ) VALUES (?, ?, ?, ?, ?, NULL, NULL, NULL, NULL, NULL, ?, NOW())
                ");

                $success = $stmtInsert->execute([
                    $mi_id,
                    $new_mb_bis,
                    $new_abo_bis,
                    $new_mb_datum,
                    $new_abo_datum,
                    $changed_id
                ]);
                
                file_put_contents($debug_log_file, "170 Insert success: " . ($success ? "true" : "false") . "\n", FILE_APPEND);
                
                if (!$success) {
                    $errorInfo = $stmtInsert->errorInfo();
                    file_put_contents('debug.log', "Insert error: " . json_encode($errorInfo) . "\n", FILE_APPEND);
                }
            }
            
            # echo json_encode(['success' => true, 'message' => 'Zahlung verbucht']);
            /*
            $stmt = $pdo->prepare("SELECT * FROM fv_mitglieder WHERE mi_id = ?");
            $stmt->execute([$mi_id]);
            $updatedRow = $stmt->fetch(PDO::FETCH_ASSOC);
            */
            $updatedRow['mi_id'] = $mi_id;
            $updatedRow['M_1'] = $updatedRow['A_1'] = $updatedRow['M_0'] = $updatedRow['A_0'] = $updatedRow['MA_0'] = $updatedRow['M_p'] = $updatedRow['A_p'] = $updatedRow['MA_p'] = "";
            
            // Bereite SQL vor, um alle relevanten Bezahldaten für das Mitglied abzufragen
            $sqlBez = "SELECT mb_bez_mb_bis, mb_bez_abo_bis, mb_bez_abo_datum, mb_bez_mb_datum
               FROM fv_mi_bez
               WHERE mi_id = :mi_id AND (mb_bez_mb_bis >= :jahr1 OR mb_bez_abo_bis >= :jahr2)";
            
            $stmtBez = $pdo->prepare($sqlBez);
            $stmtBez->execute([
                ':mi_id' => $updatedRow['mi_id'],
                ':jahr1' => $year,
                ':jahr2' => $year
            ]);
            
            $miBez_arr = $stmtBez->fetchAll(PDO::FETCH_ASSOC);
            
            $updatedRow['M_1'] = $updatedRow['A_1'] = $updatedRow['M_0'] = $updatedRow['A_0'] = $updatedRow['MA_0'] = $updatedRow['M_p'] = $updatedRow['A_p'] = $updatedRow['MA_p'] = "";
            $updatedRow['mi_m_beitr_bez_bis'] = $updatedRow['mi_m_abo_bez_bis'] = $updatedRow['mi_m_beitr_bez'] = $updatedRow['mi_m_abo_bez'] = '';
            
            $lJahr = $year -1;
            // Werte aus fv_mi_bez in $row eintragen
            foreach ($miBez_arr as $rbez) {
                
                $json = json_encode($rbez);
                //$this->log(__LINE__ . " bez_Daten, row $json");
                /*
                if ($rbez['mb_bez_mb_bis'] == $lJahr) {
                    $updatedRow['M_1'] = $rbez['mb_bez_mb_bis'];
                    $updatedRow['A_1'] = $rbez['mb_bez_abo_bis'];
                }
                if ($rbez['mb_bez_mb_bis'] == $year) {
                    $updatedRow['M_0'] = $rbez['mb_bez_mb_bis'];
                    $updatedRow['A_0'] = $rbez['mb_bez_abo_bis'];
                    $updatedRow['mi_m_beitr_bez_bis'] =  $rbez['mb_bez_mb_bis'];
                    $updatedRow['mi_m_abo_bez_bis'] =  $rbez['mb_bez_abo_bis'];
                    $updatedRow['mi_m_beitr_bez'] = $rbez['mb_bez_mb_datum'];
                    $updatedRow['mi_m_abo_bez'] = $rbez['mb_bez_abo_datum'];
                }
                if ($rbez['mb_bez_mb_bis'] > $year) {
                    $updatedRow['M_p'] = $rbez['mb_bez_mb_bis'];
                    $updatedRow['A_p'] = $rbez['mb_bez_abo_bis'];
                }
                */
                if ($rbez['mb_bez_mb_bis'] == $lJahr) {
                    $updatedRow['M_1'] = $rbez['mb_bez_mb_bis'];
                }
                if ($rbez['mb_bez_abo_bis'] == $lJahr) {
                    $updatedRow['A_1'] = $rbez['mb_bez_abo_bis'];
                }
                if ($rbez['mb_bez_mb_bis'] == $year) {
                    $updatedRow['M_0'] = $rbez['mb_bez_mb_bis'];
                    $updatedRow['mi_m_beitr_bez_bis'] =  $rbez['mb_bez_mb_bis'];
                    $updatedRow['mi_m_beitr_bez'] = $rbez['mb_bez_mb_datum'];
                }
                if ($rbez['mb_bez_abo_bis'] == $year) {
                    $updatedRow['A_0'] = $rbez['mb_bez_abo_bis'];
                    $updatedRow['mi_m_abo_bez_bis'] =  $rbez['mb_bez_abo_bis'];
                    $updatedRow['mi_m_abo_bez'] = $rbez['mb_bez_abo_datum'];
                }
                if ($rbez['mb_bez_mb_bis'] > $year) {
                    $updatedRow['M_p'] = $rbez['mb_bez_mb_bis'];
                    $updatedRow['mi_m_beitr_bez_bis'] =  $rbez['mb_bez_mb_bis'];
                    $updatedRow['mi_m_beitr_bez'] = $rbez['mb_bez_mb_datum'];
                }
                if ($rbez['mb_bez_abo_bis'] > $year) {
                    $updatedRow['A_p'] = $rbez['mb_bez_abo_bis'];
                    $updatedRow['mi_m_abo_bez_bis'] =  $rbez['mb_bez_abo_bis'];
                    $updatedRow['mi_m_abo_bez'] = $rbez['mb_bez_abo_datum'];
                }
            }
            
            $updatedRow["Korrektur"] = "Korrektur";
            
            $data = json_encode([
                'success' => true,
                'updatedRow' => $updatedRow
            ]);
            file_put_contents($debug_log_file, __LINE__ . " data $data \n", FILE_APPEND);
            
            // Erfolgreiche Antwort mit aktualisierten Daten
            echo json_encode([
                'success' => true,
                'updatedRow' => $updatedRow
            ]);
            exit;
        }
        
        if ($action === 'cancel') {
            // Storno: Letzten nicht stornierten Eintrag für mi_id und Jahr (M_, A_ oder MA_) finden
            if (!$year || !$field) {
                echo json_encode(['success' => false, 'message' => 'Jahr oder Feld fehlt']);
                exit;
            }
            
            // direktes Erkennen ob Kombinationsfeld (MA_)
            if (strpos($field, 'MA_') === 0) {
                $isM = true;
                $isA = true;
            } else {
                $isM = strpos($field, 'M') === 0;
                $isA = strpos($field, 'A') === 0;
            }
            
            $sqlCancel = "SELECT * FROM fv_mi_bez WHERE mi_id = ? AND mb_cancel IS NULL ORDER BY mb_changed_at DESC";
            $stmtCancel = $pdo->prepare($sqlCancel);
            $stmtCancel->execute([$mi_id]);
            $entries = $stmtCancel->fetchAll(PDO::FETCH_ASSOC);
            
            $entryToCancel = null;
            foreach ($entries as $entry) {
                if (($isM && $entry['mb_bez_mb_bis'] == $year) || ($isA && $entry['mb_bez_abo_bis'] == $year)) {
                    $entryToCancel = $entry;
                    break;
                }
            }
            
            if (!$entryToCancel) {
                echo json_encode(['success' => false, 'message' => 'Keine Zahlung zum Stornieren gefunden']);
                exit;
            }
            
            // Alte Werte aus mb_prev_* Feldern holen
            $restore_mb_bis = $entryToCancel['mb_prev_mb_bis'];
            $restore_abo_bis = $entryToCancel['mb_prev_abo_bis'];
            $restore_mb_datum = $entryToCancel['mb_prev_mb_datum'];
            $restore_abo_datum = $entryToCancel['mb_prev_abo_datum'];
            
            // Update mit Wiederherstellung der alten Werte und Storno markieren
            $sqlUpdateCancel = "UPDATE fv_mi_bez SET
            mb_bez_mb_bis = ?,
            mb_bez_abo_bis = ?,
            mb_bez_mb_datum = ?,
            mb_bez_abo_datum = ?,
            mb_cancel = NOW(),
            mb_changed_id = ?,
            mb_changed_at = NOW()
            WHERE mb_id = ?";
            
            $stmtUpdateCancel = $pdo->prepare($sqlUpdateCancel);
            $stmtUpdateCancel->execute([
                $restore_mb_bis,
                $restore_abo_bis,
                $restore_mb_datum,
                $restore_abo_datum,
                $changed_id,
                $entryToCancel['mb_id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Zahlung storniert und alte Werte wiederhergestellt']);
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
        exit;
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
        exit;
    }
