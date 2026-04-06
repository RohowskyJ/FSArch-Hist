<!DOCTYPE html>
<?php
session_start();

$path2ROOT = "../";

/**
 * Bootstrap: Composer-/Shared-Einstieg
 */
require_once __DIR__ . '/../login/Basis/bootstrap.php';

$debug = false;
var_dump($_SERVER);
require_once $path2ROOT . 'login/Basis/BS_Funcs_lib.php'; // Diverse Unterprogramme
require_once $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';

require_once 'FS_Benutzer_CLS.php';
require_once $path2ROOT . "login/Basis/FS_Config_lib.php";

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);

$user = new User($pdo);
$error = "";
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['userId'] ?? '';
    
    if ($userId) {
        
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
 
    if ($new_password &&  $confirm_password) {
        // Passwort-Reset-Logik hier einfügen (z.B. Mail senden)
        $message = "Passwort-Reset angefordert. Bitte prüfen Sie Ihre E-Mails für weitere Anweisungen.";
        $return = $user->requestPasswordReset($userId);
    } else {
        $error = "Passwort- Änderung hat nich funktioniert. Bitte prüfen Sie Ihre Zugangsdaten.";
    }
}

$header = "<style>nav{float:left;width:320px;margin:10px;border:3px solid grey;}.cont{border:1px solid grey;}@media print{.nav{display:none;}}</style>\n";

HTML_header('Login zum internen Teil', $header, 'Form', '50em');

initial_debug('SERV', 'PUT', 'GET');
?>

<form method="post" id="pwChangeForm">
   <div style='text-align: center;'>
    <?php if ($error): ?>
    <!-- 
        <p class="error"><?=htmlspecialchars($error)?></p>
        <p><label>Passwort-Reset: </label><input type='checkbox' name='pw_reset' id="pw_reset" value='reset'></label></p>
         -->
    <?php else: ?>
        <?php if ($message): ?>
            <p class="message"><?=htmlspecialchars($message)?></p>
        <?php endif; ?>
    <?php endif; ?>
    <label>Benutzer- ID :  <input type='text' name='userId' value='<?php echo $userId?>' readonly ></label><br>
    <b>Zum Ändern des Passwortes das Passwort zweimal eingeben eingeben:</b><br><br>
    <input type="password" name="new_password" placeholder="Neues Passwort" required autofocus><br>
    <input type="password" name="confirm_password" placeholder="Passwort bestätigen" required><br>

 
    <button type="submit">Ändern</button><br>
  </div> 
</form>

