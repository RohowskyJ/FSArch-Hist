<!DOCTYPE html>
<?php
session_start();

$path2ROOT = "../";

$debug = false;
/**
 * Zur Benutzung der neuen, gemeinsamen Bibliotheken
 * die neuen Bibs
 */
require_once $path2ROOT . 'login/common/BS_Funcs_lib.php'; // Diverse Unterprogramme
require_once $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';

require_once 'FS_Benutzer_CLS.php';

// PDO-Instanz (DB-Klasse kommt von dir)
$pdo = new PDO('mysql:host=localhost;dbname=fharch_comm;charset=utf8mb4', 'root', 'b1teller');

$user = new User($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($user->login($userId, $password)) {
        // Berechtigungen Session Var
        $_SESSION['BS_Prim']['BE'] = $user->getNutzungsParms();
        
        $nutzerDaten = $user->getNutzungsParms();
        $jsonDaten = json_encode($nutzerDaten, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        
        // PHP-Redirect
        header('Location: FS_C_Menu.php');
        exit;
    } else {
        echo "Login fehlgeschlagen. Bitte prüfen Sie Ihre Zugangsdaten.";
    }
}
$header = "";

$header .= "<style>nav{float:left;width:320px;margin:10px;border:3px solid grey;}.cont{border:1px solid grey;}@media print{.nav{display:none;}}</style>  \n";

HTML_header('Login zum internen Teil', $header, 'Form', '50em'); # Parm: Titel,Subtitel,HeaderLine,Type,width

initial_debug('SERV', 'PUT', 'GET'); # Wenn $debug=true - Ausgabe von Debug Informationen: $_Server, $_POST, $_GET, $_FILE


?>

<form method="post">
   <div style='text-align: center;'>
    <b>Zum Einloggen den Benutzer- ID und das Passwort eingeben: </b><br> <br>
    <input type="text" name="userId" placeholder="Benutzer-ID" required><br><br>
    <input type="password" name="password" placeholder="Passwort" required><br><br>
    <button type="submit">Login</button><br>
  </div> 
</form>