<?php
/**
 * DB Funktionen für Login und Brechtigungen, soll/kann ?  nach Testabschluss in FV_Database_CLS.php eingebaut werden. (oder als extension?)
 * @author josef
 *
 */
class User {
    private PDO $pdo; 
    private ?int $be_id = null;
    private ?string $be_uid = null;
    private ?int $role_id = null;
    private ?string $role_description = null;
    private array $permissions = []; // mandant_id => erlaubnis (read, update, nix)
    private ?string $role_modules = null;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Login mit Benutzer-ID (be_uid) und Passwort (fe_pw)
     * Passwort wird mit password_verify geprüft.
     */
    public function login(string $userId, string $password): bool {
        // Benutzer mit Passwort und Rolle laden
        $sql = "
            SELECT b.be_id, b.be_uid, e.fe_pw, r.fl_id, rb.fl_beschreibung, rb.fl_modules
            FROM fv_benutzer b
            JOIN fv_erlauben e ON b.be_id = e.be_id
            JOIN fv_rolle r ON b.be_id = r.be_id
            JOIN fv_rollen_beschr rb ON r.fl_id = rb.fl_id
            WHERE b.be_uid = :userId
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        $user = $stmt->fetch();
        # var_dump($user);
        if (!$user) {
            return false; // Benutzer nicht gefunden
        }
        
        // Passwort pr�fen (fe_pw ist varchar(512), angenommen gehashed)
        if (!password_verify($password, $user['fe_pw'])) {
            return false; // Passwort falsch
        }
        
        // Benutzer-Daten speichern
        $this->be_id = (int)$user['be_id'];
        $this->be_uid = $user['be_uid'];
        $this->role_id = (int)$user['fl_id'];
        $this->role_description = $user['fl_beschreibung'];
        $this->role_modules = $user['fl_modules'];
        
        // Berechtigungen laden
        $this->loadPermissions(['be_id' => $this->be_id, 'be_uid' => $this->be_uid, 'role_id' => $this->role_id, 'role_modules' => $this->role_modules]);

        return true;
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
    
    /** Getter für Benutzer-ID */
    public function getUserId(): ?int {
        return $this->be_id;
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
    /** Passwortänderung */
    public function changePassword(string $newPassword): bool {
        if ($this->be_id === null) return false;
        
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE fv_erlauben SET fe_pw = :pw, fe_pw_chgd_id = :changedId, fe_pw_chgd_at = NOW() WHERE be_id = :be_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'pw' => $hashed,
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