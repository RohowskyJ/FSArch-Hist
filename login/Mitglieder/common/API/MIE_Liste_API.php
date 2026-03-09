<?php
// Fehleranzeige und Logging aktivieren (nur für Debug, im Produktivbetrieb aus)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/MIE_Liste_API_php-error.log.txt');

// Shutdown-Funktion direkt am Anfang registrieren
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        $message = "Shutdown error detected:\n" . print_r($error, true);
        error_log($message);
        // Optional: auch in eine separate Datei schreiben
        file_put_contents(__DIR__ . '/fatal_error.log', $message, FILE_APPEND);
    }
});
    
// Output Buffering starten, um unerwünschte Ausgabe zu kontrollieren
ob_start();

$rootPfad = $_SERVER['DOCUMENT_ROOT'];
$caller = $_SERVER['REQUEST_URI'];
$cal_arr = explode("/",$caller);
require_once $rootPfad . '/'.$cal_arr[1].'/login/BS_BootPfadL_CLS.php';
PathHelper::init('/'.$cal_arr[1]);  // Basis-URL anpassen
AppAutoloader::register();

// Optional: prüfen, ob PathHelper geladen ist
if (!class_exists('PathHelper')) {
    error_log("Class PathHelper not found after require_once!");
} else {
    error_log("Class PathHelper loaded successfully.");
}

// Optional: prüfen, ob PathHelper geladen ist
if (!class_exists('PathHelper')) {
    error_log("Class PathHelper not found after require_once!");
} else {
    error_log("Class PathHelper loaded successfully.");
}

header('Content-Type: application/json; charset=utf-8');

try {
    $vfDatabase = FS_Database::getInstance();
    $pdo = $vfDatabase->getPDO();
    $repo = new MIE_EhrungRepository($pdo);
    
    // Parameter aus GET oder POST
    $listType = $_GET['T_List'] ?? 'Alle';
    $search = $_GET['search'] ?? null;
    
    $data = $repo->getEhrungen($listType, $search);
    $columns = MIE_EhrungTableConfig::getColumns($listType, $pdo);
    
    $response = [
        'columns' => $columns,
        'data' => $data,
    ];
    
    $json = json_encode($response);
    
    if ($json === false) {
        $jsonError = json_last_error_msg();
        error_log("JSON encode error: $jsonError");
        http_response_code(500);
        echo json_encode(['error' => "JSON encode error: $jsonError"]);
        ob_end_flush();
        exit;
    }
    
    // Vor dem Ausgeben den Output Buffer leeren, um unerwünschte Ausgabe zu vermeiden
    ob_end_clean();
    
    echo $json;
    
} catch (Exception $e) {
    http_response_code(500);
    $errorMsg = $e->getMessage();
    error_log("Exception: $errorMsg");
    echo json_encode(['error' => "Exception: $errorMsg"]);
}

ob_end_flush();