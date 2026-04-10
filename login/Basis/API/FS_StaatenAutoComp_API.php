<?php
declare(strict_types=1);
require_once '../FS_Config_lib.php';  // Ihre Konfigurationsdatei
require_once '../FS_Database_CLS.php';    // Ihre VF_Database-Klasse

# $db = new VF_Database();

header('Content-Type: application/json; charset=utf-8');

// Fehleranzeige und Logging aktivieren (nur für Debug, im Produktivbetrieb aus)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/VS_StaatAutoComp_API_php-error.log.txt');

$term = $_GET['term'] ?? '';
$term = trim($term);

if ($term === '') {
    echo json_encode([]);
    exit;
}

try {
    $db = FS_Database::getInstance();
    #var_dump($db);
    $pdo = $db->getPDO();
    if (!$pdo instanceof PDO) {
        throw new Exception('PDO instance not available');
    }
    
    // Query mit LIKE für Autocomplete (st_name oder st_abkzg)
    $sql = "SELECT st_id, st_name, st_abkzg, st_vorwahl
        FROM fv_staaten
        WHERE st_name LIKE :term1 OR st_abkzg LIKE :term2
        ORDER BY st_name ASC
        LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    
    $searchTerm = $term . '%';
    $stmt->bindValue(':term1', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':term2', $searchTerm, PDO::PARAM_STR);

    $stmt->execute();
    
    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'label' => $row['st_name'] . ' (' . $row['st_abkzg'] . ')',
            'value' => $row['st_name'],
            'id' => $row['st_id'],
            'abk' => $row['st_abkzg'],
            'vorwahl' => $row['st_vorwahl'],
        ];
    }
    # var_dump($results);
    echo json_encode($results);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('DB Error: ' . $e->getMessage());
    echo json_encode(['error' => 'Fehler bei der Datenbankabfrage']);
}