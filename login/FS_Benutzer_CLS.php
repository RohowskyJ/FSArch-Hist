<?php
/**
 * DB Funktionen für Login und Berechtigungen, soll/kann ?  nach Testabschluss in FV_Database_CLS.php eingebaut werden. (oder als extension?)
 * @author josef
 *
 */

require_once "common/PHP_Mail_Funcs_lib.php";
class User {
    private PDO $pdo; 
    private ?int $be_id = null;
    private ?string $be_uid = null;
    private ?int $role_id = null;
    private ?string $role_description = null;
    private array $permissions = []; // mandant_id => erlaubnis (read, update, nix)
    private ?string $role_modules = null;
    
    /**
     * Konstruktor mit PDO-Instanz
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Gibt die Benutzer-ID zurück
     */
    public function getUserId(): ?int {
        return $this->be_id;
    }
    
    /**
     * Login mit Benutzer-ID (be_uid) und Passwort (fe_pw)
     * Prüft Passwort mit password_verify
     * Lädt Benutzer- und Rolleninformationen
     * Setzt Session-Flag für Passwortänderung falls nötig
     */
    public function login(string $userId, string $password): bool {
        // Benutzer mit Passwort und Rolle laden
        $sql = "
            SELECT b.be_id, b.be_uid, e.fe_pw, e.fe_pw_chgd_at, e.fe_created_at,
                   r.fl_id, rb.fl_beschreibung, rb.fl_modules
            FROM fv_benutzer b
            JOIN fv_erlauben e ON b.be_id = e.be_id  
            LEFT JOIN fv_rolle r ON b.be_id = r.be_id    
            LEFT JOIN fv_rollen_beschr rb ON r.fl_id = rb.fl_id
            WHERE b.be_uid = :userId
            LIMIT 1
        "; 
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false; // Benutzer nicht gefunden
        }
        
        if (!password_verify($password, $user['fe_pw'])) {
            return false; // Passwort falsch
        }
        
        // Benutzer-Daten speichern
        $this->be_id = (int)$user['be_id'];
        $this->be_uid = $user['be_uid'];
        $this->role_id = $user['fl_id'] !== null ? (int)$user['fl_id'] : null;
        $this->role_description = $user['fl_beschreibung'] ?? null;
        $this->role_modules = $user['fl_modules'] ?? null;
        
        // Berechtigungen laden
        $this->loadPermissions();
        
        // Passwortänderungsstatus in Session speichern
        $_SESSION['password_change_required'] = $this->needsPasswordChange($user['fe_pw_chgd_at'], $user['fe_created_at']);
        
        return true;
    }
    
    
    /**
     * Prüft, ob eine Passwortänderung erforderlich ist
     * @param string $pwChangedAt Timestamp des letzten Passwortwechsels
     * @param string $createdAt Timestamp der Erstellung
     * @return bool true wenn Passwort geändert werden muss
     */
    private function needsPasswordChange(string $pwChangedAt, string $createdAt): bool {
        return ($pwChangedAt === '0000-00-00 00:00:00' || $pwChangedAt === $createdAt);
    }
    
    
   
     /**
     * Ändert das Passwort des aktuellen Benutzers
     * @param string $newPassword Neues Passwort (klartext)
     * @param int $changedByUserId ID des Benutzers, der die Änderung durchführt
     * @return bool true bei Erfolg
     * @throws InvalidArgumentException bei ungültigem Passwort
     */    
     public function changePassword(string $newPassword, int $changedByUserId): bool {
        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException("Passwort muss mindestens 8 Zeichen lang sein.");
        }
        
        $hashedPw = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE fv_erlauben SET
                fe_pw = :fe_pw,
                fe_pw_chgd_id = :fe_pw_chgd_id,
                fe_pw_chgd_at = CURRENT_TIMESTAMP,
                fe_changed_id = :fe_changed_id,
                fe_changed_at = CURRENT_TIMESTAMP
            WHERE be_id = :be_id";
        
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'fe_pw' => $hashedPw,
            'fe_pw_chgd_id' => $changedByUserId,
            'fe_changed_id' => $changedByUserId,
            'be_id' => $this->be_id
        ]);
        
        if ($success) {
            $_SESSION['password_change_required'] = false;
        }
        
        return $success;
    }
    // --- Passwort vergessen / Reset ---
    
    public function requestPasswordReset(string $userIdOrEmail): bool {
        $sql = "SELECT be_id, be_uid FROM fv_benutzer WHERE be_uid = :uid LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userIdOrEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return false;
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
        
        $sqlUpdate = "UPDATE fv_benutzer SET be_pw_reset_token = :token, be_pw_reset_expires = :expires WHERE be_id = :be_id";
        $stmtUpdate = $this->pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            'token' => $token,
            'expires' => $expires,
            'be_id' => $user['be_id']
        ]);
        
        // Aufbauen des Links
        $srv= $_SERVER['HTTP_HOST'];

        $caller = $_SERVER['REQUEST_URI'];
        $cal_arr = explode("/",$caller);
        # var_dump($cal_arr);
        $link = $srv . '/'.$cal_arr[1];
        
        $resetLink = "https://$link/login/common/FS_ResetPW.php?token=$token";
        $subject = "Passwort zurücksetzen";
        $message = "Hallo,<br>bitte klicke auf <a href='". $resetLink . "'>den folgenden Link, um dein Passwort zurückzusetzen:</a><br>Der Link ist 1 Stunde gültig.";
        $headers = ""; // "From: no_reply@$dom\r\n";
        
        return sendEmail($user['be_uid'], $subject, $message, $headers);
    }
    
    /** Passwort mit token resetten */
    public function resetPasswordByToken(string $token, string $newPassword): bool {
        $sql = "SELECT be_id, pw_reset_expires FROM fv_benutzer WHERE pw_reset_token = :token LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            throw new Exception("Ungültiger oder abgelaufener Token.");
        }
        
        $expires = new DateTime($row['pw_reset_expires']);
        $now = new DateTime();
        
        if ($now > $expires) {
            throw new Exception("Der Link ist abgelaufen.");
        }
        
        $this->setUserId((int)$row['be_id']);
        $success = $this->changePassword($newPassword, $row['be_id']);
        
        if ($success) {
            $sqlClear = "UPDATE fv_benutzer SET pw_reset_token = NULL, pw_reset_expires = NULL WHERE be_id = :be_id";
            $stmtClear = $this->pdo->prepare($sqlClear);
            $stmtClear->execute(['be_id' => $row['be_id']]);
        }
        
        return $success;
    }
    
    // --- Passwort Policy ---
    
    public function validatePasswordPolicy(string $password): bool {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/\d/', $password)) return false;
        if (!preg_match('/[\W]/', $password)) return false;
        return true;
    }
    
    // --- E-Mail-Bestätigung ---
    
    public function sendEmailConfirmation(int $be_id, string $email): bool {
        $token = bin2hex(random_bytes(32));
        $sql = "UPDATE fv_benutzer SET email_confirm_token = :token, email_confirmed = 0 WHERE be_id = :be_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token, 'be_id' => $be_id]);
        
        // Aufbauen des Links
        $srv= $_SERVER['HTTP_HOST'];
        $dom = "";
        if ($srv == "localhost") {
            $dom = 'feuerwehrhistoriker.at';
        }
        $caller = $_SERVER['REQUEST_URI'];
        $cal_arr = explode("/",$caller);
        # var_dump($cal_arr);
        $link = $srv . '/'.$cal_arr[1];
        $confirmLink = "https://$link/confirm_email.php?token=$token";
        $subject = "Bitte bestätigen Sie Ihre E-Mail-Adresse";
        $message = "Hallo,\n\nbitte bestätigen Sie Ihre E-Mail-Adresse durch Klick auf den folgenden Link:\n$confirmLink\n\nVielen Dank!";
        $headers = "From: no_reply@d".$dom."\r\n";
        
        return sendEmail($email, $subject, $message, $headers);
    }
    
    /** E_Mail- Bestätigung */
    public function confirmEmail(string $token): bool {
        $sql = "SELECT be_id FROM fv_benutzer WHERE email_confirm_token = :token LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            throw new Exception("Ungültiger oder bereits verwendeter Link.");
        }
        
        $sqlUpdate = "UPDATE fv_benutzer SET email_confirmed = 1, email_confirm_token = NULL WHERE be_id = :be_id";
        $stmtUpdate = $this->pdo->prepare($sqlUpdate);
        return $stmtUpdate->execute(['be_id' => $row['be_id']]);
    }
    
    /**
     * Berechtigungen aus fv_mand_erl laden - [MandNr => Erl{read | update}
     * Daten werden mit getNutzungsParm mitgegeben
     */
    private function loadPermissions(): void {
        $sql = "
            SELECT ei_id, fu_erlauben
            FROM fv_mand_erl
            WHERE be_id = :be_id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['be_id' => $this->be_id]);
        $rows = $stmt->fetchAll();
        
        $this->permissions = [];
        foreach ($rows as $row) {
            // fu_erlauben ist SET('read','update','nix'), wir nehmen den String direkt
            $this->permissions[$row['ei_id']] = $row['fu_erlauben'];
        }
    }
    
 
    /** Getter für Benutzer-UID */
    public function getUserUid(): ?string {
        return $this->be_uid;
    }
    
    /** Getter für Rollen-ID */
    public function getRoleId(): ?int {
        return $this->role_id;
    }
    
    /** Getter für Rollenbeschreibung */
    public function getRoleDescription(): ?string {
        return $this->role_description;
    }
    
    /** Getter für Berechtigungen (Array ei_id => erlaubnis) */
    public function getPermissions(): array {
        return $this->permissions;
    }
    
    /** Getter für Module (Array ei_id => erlaubnis) */
    public function getRoleModules(): array {
        return $this->role_modules;
    }
    
    /** Getter für Nutzungs- Erlaubnisse , Speicherung als Session- Variable */
    public function getNutzungsParms() {
        # $nutzung['be_id'] = [$this->be_id => ['fl_Id' => $this->role_id, 'fl_modules' => $this->role_modules ]];
        # array_push($nutzung , $this->permissions);
        $nutzung['be_id'] = $this->be_id;
        $nutzung['roles'] = $this->role_modules ;
        $nutzung['mand_perm'] = $this->permissions;
        # var_dump($nutzung);
        return $nutzung; 
    }
    
    /** Rolle ändern */
    public function changeRole(int $newRoleId): bool {
        if ($this->be_id === null) return false;
        
        $sql = "UPDATE fv_rolle SET fl_id = :roleId, fr_changed_id = :changedId, fr_changed_at = NOW() WHERE be_id = :be_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'roleId' => $newRoleId,
            'changedId' => $this->be_id,
            'be_id' => $this->be_id
        ]);
    }
     
    /** Mandantenberechtigung ändern */
    public function setMandantenPermission(int $mandantId, string $permission): bool {
        if ($this->be_id === null) return false;
        // Prüfen ob Eintrag existiert
        $sqlCheck = "SELECT fu_id FROM fv_mand_erl WHERE be_id = :be_id AND ei_id = :mandantId";
        $stmtCheck = $this->pdo->prepare($sqlCheck);
        $stmtCheck->execute(['be_id' => $this->be_id, 'mandantId' => $mandantId]);
        $exists = $stmtCheck->fetchColumn();
        
        if ($exists) {
            $sqlUpdate = "UPDATE fv_mand_erl SET fu_erlauben = :permission, fu_changed_id = :changedId, fu_changed_at = NOW() WHERE fu_id = :fu_id";
            $stmtUpdate = $this->pdo->prepare($sqlUpdate);
            return $stmtUpdate->execute([
                'permission' => $permission,
                'changedId' => $this->be_id,
                'fu_id' => $exists
            ]);
        } else {
            $sqlInsert = "INSERT INTO fv_mand_erl (be_id, ei_id, fu_erlauben, fu_new_uid, fu_new_at, fu_changed_id, fu_changed_at) VALUES (:be_id, :mandantId, :permission, :newId, NOW(), :changedId, NOW())";
            $stmtInsert = $this->pdo->prepare($sqlInsert);
            return $stmtInsert->execute([
                'be_id' => $this->be_id,
                'mandantId' => $mandantId,
                'permission' => $permission,
                'newId' => $this->be_id,
                'changedId' => $this->be_id
            ]);
        }
        
    }
}
?>