<!DOCTYPE html>
<?php
session_start();

$path2ROOT = "../../../";

$debug = true;
# var_dump($_SERVER);

/**
 * Bootstrap: Composer-/Shared-Einstieg mit Pfadhelfer
 */
$rootPfad = $_SERVER['DOCUMENT_ROOT'];
$caller = $_SERVER['REQUEST_URI'];
$cal_arr = explode("/",$caller);
require_once __DIR__ . '/../../../login/bootstrap.php';
fsarch_bootstrap_path_init('/'.$cal_arr[1]);

require PathHelper::fs('Basis/common/BS_FuncsLib.php');
require PathHelper::fs('Basis/common/FS_CommFuncsLib.php');
require PathHelper::fs('Basis/common/FS_ConstLib.php');
require PathHelper::fs('Basis/common/FS_ConfigLib.php');

require_once $path2ROOT . 'login/common/VF_Comm_Funcs.lib.php';

require_once $path2ROOT . 'login/FS_Benutzer_CLS.php';

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);

$user = new User($pdo);

$cMessage = $_GET['cMessage'] ?? "";
echo __LINE__ . " caller $caller; cMessage $cMessage";

$error = "";
$message = "";
var_dump($_POST);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'] ?? '';
    $password = $_POST['password'] ?? '';
    $pwResetRequested = isset($_POST['pw_reset']) && $_POST['pw_reset'] === 'reset';
    $pwChangeRequested = isset($_POST['pw_change']) && $_POST['pw_change'] === 'change';
    if (!empty($_SESSION['password-change_required'])) {$pwChangeRequested = 1;}
    var_dump($_SESSION);
    if ($pwResetRequested) {
        // Passwort-Reset-Logik hier einfügen (z.B. Mail senden)
        $message = "Passwort-Reset angefordert. Bitte prüfen Sie Ihre E-Mails für weitere Anweisungen.";
        $return = $user->requestPasswordReset($userId);
    } else {
        // Normale Login-Logik
        if ($user->login($userId, $password)) {
            $_SESSION['BS_Prim']['BE'] = $user->getNutzungsParms();
            if ($pwChangeRequested) {
                header("Location: FS_PWChange.php?userId=$userId");
            }
            header('Location: ../../FS_C_Menu.php');
            exit;
        } else {
            $error = "Login fehlgeschlagen. Bitte prüfen Sie Ihre Zugangsdaten.";
        }
    }
}

$header = "<style>nav{float:left;width:320px;margin:10px;border:3px solid grey;}.cont{border:1px solid grey;}@media print{.nav{display:none;}}</style>\n";

HTML_header('Login zum internen Teil', $header, 'Form', '50em');

initial_debug('SERV', 'PUT', 'GET');
?>

<form method="post" id="loginForm">
    <div style='text-align: center;'>
    <?php if ($cMessage):?>
        <p class="error"><?=htmlspecialchars($cMessage)?></p>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <p class="error"><?=htmlspecialchars($error)?></p>
        <p><label>Passwort-Reset: </label><input type='checkbox' name='pw_reset' id="pw_reset" value='reset'></label></p>
    <?php else: ?>
        <?php if ($message): ?>
            <p class="message"><?=htmlspecialchars($message)?></p>
        <?php endif; ?>
    <?php endif; ?>

    <b>Zum Einloggen den Benutzer-ID und das Passwort eingeben:</b><br><br>
    <input type="text" name="userId" placeholder="Benutzer-ID" required><br><br>
    <input type="password" name="password" id="password" placeholder="Passwort" required><br><br>

    <?php if (!$error): ?>
         <p><label>Passwort-ändern: </label><input type='checkbox' name='pw_change' value='change'></label></p>
    <?php endif; ?>

    <button type="submit">Login</button><br>
  </div> 
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pwResetCheckbox = document.getElementById('pw_reset');
    const passwordInput = document.getElementById('password');

    if (pwResetCheckbox) {
        // Initiale Kontrolle, falls Formular mit Fehler neu geladen wurde und pw_reset schon angehakt ist
        togglePasswordField();

        pwResetCheckbox.addEventListener('change', togglePasswordField);
    }

    function togglePasswordField() {
        if (pwResetCheckbox.checked) {
            passwordInput.required = false;
            passwordInput.disabled = true;
            passwordInput.value = ''; // Passwortfeld leeren, falls etwas drin war
        } else {
            passwordInput.required = true;
            passwordInput.disabled = false;
        }
    }
});
</script>