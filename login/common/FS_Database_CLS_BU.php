<?php
declare(strict_types=1);

$moduleId = 'ADM-DB';

require_once 'FS_Config_lib.php';

/** 
 * FS_Database
 * - PDO Wrapper + sichere Query-Helper
 * - Tabellenpräfix (z.B. "fv_") für Stamm-Tabellen
 * - CRUD Helfer (insert/update/delete/select)
 * - Login/ACL Helfer (User, Passwort, Rollen, Mandantenrechte)
 *
 * Hinweis:
 * - Tabellen/Spaltennamen können NICHT als PDO-Parameter gebunden werden.
 *   Daher: strikte Whitelists/Validierung für Identifiers (prefix, table, columns).
 */
class FS_Database
{
    private \PDO $pdo;
    private string $prefix;
    private static ?FS_Database $instance = null;
    
    /** Erlaubte Tabellen im "Stammteil" (anpassen/erweitern) */
    private array $allowedTables = [
        'benutzer',
        'erlauben',
        'rolle',
        'rollen_beschr',
        'mand_erl',
        'mandant', // wird als FK referenziert
        'adm_mail',
        'ben_dat',
        'module',
        'mitglieder',
        'mi_bez',
        'mi_ehrung',
        'mi_anmeld',
        'staaten',
    ];
    
    /** Erlaubte Rechtewerte für Mandant */
    private const MAND_RIGHTS = ['read', 'update', 'nix'];
    
    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix ?? (defined('DB_PREFIX') ? DB_PREFIX : 'fv_');
        $this->validatePrefix($this->prefix);
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $this->pdo = new \PDO($dsn, DB_USER, DB_PASS, $options);
    }
    
    /** PDO auslesen */
    public function getPDO(): \PDO
    {
        return $this->pdo;
    }
    
    /** eine Instanz beim Aufrufer erzeugen - MI_Members_API*/
    public static function getInstance(): FS_Database
    {
        if (self::$instance === null) {
            self::$instance = new FS_Database();
        }
        return self::$instance;
    }
    
    /** Prefix setzen (z.B. pro "Stammteil" / Umgebung) */
    public function setPrefix(string $prefix): void
    {
        $this->validatePrefix($prefix);
        $this->prefix = $prefix;
    }
    
    public function getPrefix(): string
    {
        return $this->prefix;
    }
    
    /**
     * Validierung Prefix auf gültige Zeichen
     */
    private function validatePrefix(string $prefix): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
            throw new \InvalidArgumentException("Ungültiges Tabellenpräfix: $prefix");
        }
    }
    
    /** Identifier validieren (Spaltenname/Tabellen-Suffix) */
    private function validateIdentifier(string $name): void
    {
        // erlaubt: a-z A-Z 0-9 _
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException("Ungültiger Identifier: $name");
        }
    }
    
    /** Stamm-Tabellenname aus Suffix zusammensetzen: prefix + suffix */
    private function table(string $suffix): string
    {
        $this->validateIdentifier($suffix);
        if (!in_array($suffix, $this->allowedTables, true)) {
            throw new \InvalidArgumentException("Tabelle nicht erlaubt (Whitelist): {$suffix}");
        }
        return $this->prefix . $suffix;
    }
    
    /** Spaltenliste validieren und quoten */
    private function quoteColumns(array $columns): array
    {
        $out = [];
        foreach ($columns as $col) {
            $this->validateIdentifier($col);
            $out[] = "`{$col}`";
        }
        return $out;
    }
    
    /** WHERE Builder (gleichheitsbasiert) */
    private function buildWhere(array $where, array &$params): string
    {
        if (!$where) return '';
        $parts = [];
        foreach ($where as $col => $val) {
            $this->validateIdentifier((string)$col);
            $ph = ':w_' . $col;
            $parts[] = "`{$col}` = {$ph}";
            $params[$ph] = $val;
        }
        return ' WHERE ' . implode(' AND ', $parts);
    }
    
    /** ORDER BY Builder mit Whitelist */
    private function buildOrderBy(array $orderBy): string
    {
        if (!$orderBy) return '';
        $parts = [];
        foreach ($orderBy as $col => $dir) {
            $this->validateIdentifier((string)$col);
            $dir = strtoupper((string)$dir);
            if (!in_array($dir, ['ASC', 'DESC'], true)) {
                throw new \InvalidArgumentException("Ungültige ORDER Richtung: $dir");
            }
            $parts[] = "`{$col}` {$dir}";
        }
        return ' ORDER BY ' . implode(', ', $parts);
    }
    
    /** Basis-Query Helper */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            // PDO bindValue akzeptiert sowohl :name als auch name; wir normalisieren:
            $param = is_string($k) && $k[0] !== ':' ? ':' . $k : $k;
            $stmt->bindValue($param, $v);
        }
        $stmt->execute();
        return $stmt;
    }
    
    /** Transaction Helper */
    public function transaction(callable $fn)
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    // ---------------------------------------------------------------------
    // CRUD (generisch)
    // ---------------------------------------------------------------------
    
    /**
     * SELECT (generisch)
     * @param string $tableSuffix z.B. 'benutzer'
     * @param array $where ['be_id'=>1]
     * @param array $columns ['be_id','be_uid'] oder ['*']
     * @param array $orderBy ['be_id'=>'DESC']
     * @param int|null $limit
     * @param int|null $offset
     */
    public function select(
        string $tableSuffix,
        array $where = [],
        array $columns = ['*'],
        array $orderBy = [],
        ?int $limit = null,
        ?int $offset = null
        ): array {
            $tbl = $this->table($tableSuffix);
            
            $params = [];
            $colsSql = ($columns === ['*']) ? '*' : implode(', ', $this->quoteColumns($columns));
            $sql = "SELECT {$colsSql} FROM `{$tbl}`";
            
            $sql .= $this->buildWhere($where, $params);
            $sql .= $this->buildOrderBy($orderBy);
            
            if ($limit !== null) {
                if ($limit < 1) throw new \InvalidArgumentException("LIMIT muss >= 1 sein");
                $sql .= " LIMIT " . (int)$limit;
                if ($offset !== null) {
                    if ($offset < 0) throw new \InvalidArgumentException("OFFSET muss >= 0 sein");
                    $sql .= " OFFSET " . (int)$offset;
                }
            }
            
            return $this->query($sql, $params)->fetchAll();
    }
    
    /** SELECT genau 1 Zeile */
    public function selectOne(
        string $tableSuffix,
        array $where,
        array $columns = ['*']
        ): ?array {
            $rows = $this->select($tableSuffix, $where, $columns, [], 1);
            return $rows[0] ?? null;
    }
    
    /**
     * INSERT (generisch)
     * @return int lastInsertId
     */
    public function insert(string $tableSuffix, array $data): int
    {
        if (!$data) throw new \InvalidArgumentException("INSERT benötigt Daten");
        
        $tbl = $this->table($tableSuffix);
        
        $cols = array_keys($data);
        foreach ($cols as $c) $this->validateIdentifier((string)$c);
        
        $colSql = implode(', ', array_map(fn($c) => "`{$c}`", $cols));
        $phSql  = implode(', ', array_map(fn($c) => ":{$c}", $cols));
        
        $sql = "INSERT INTO `{$tbl}` ({$colSql}) VALUES ({$phSql})";
        $this->query($sql, $data);
        
        return (int)$this->pdo->lastInsertId();
    }
    
    /**
     * UPDATE (generisch)
     * @return int affected rows
     */
    public function update(string $tableSuffix, array $data, array $where): int
    {
        if (!$data) throw new \InvalidArgumentException("UPDATE benötigt Daten");
        if (!$where) throw new \InvalidArgumentException("UPDATE benötigt WHERE (Sicherheitsmaßnahme)");
        
        $tbl = $this->table($tableSuffix);
        
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $this->validateIdentifier((string)$col);
            $ph = ':u_' . $col;
            $sets[] = "`{$col}` = {$ph}";
            $params[$ph] = $val;
        }
        
        $sql = "UPDATE `{$tbl}` SET " . implode(', ', $sets);
        $sql .= $this->buildWhere($where, $params);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * DELETE (generisch)
     * @return int affected rows
     */
    public function delete(string $tableSuffix, array $where): int
    {
        if (!$where) throw new \InvalidArgumentException("DELETE benötigt WHERE (Sicherheitsmaßnahme)");
        $tbl = $this->table($tableSuffix);
        
        $params = [];
        $sql = "DELETE FROM `{$tbl}`";
        $sql .= $this->buildWhere($where, $params);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    // ---------------------------------------------------------------------
    // Benutzer / Login / Rollen / Mandantenrechte (Stammteil)
    // ---------------------------------------------------------------------
    
    /**
     * Benutzer anlegen (fv_benutzer)
     * Erwartete Felder gemäß Schema: be_uid, be_erst_von, be_erstell, ...
     * Tipp: timestamps besser als CURRENT_TIMESTAMP in DB defaulten.
     */
    public function createUser(array $userData): int
    {
        return $this->insert('benutzer', $userData);
    }
    
    public function getUserByUid(string $uid): ?array
    {
        return $this->selectOne('benutzer', ['be_uid' => $uid]);
    }
    
    /**
     * Passwort setzen/ändern in fv_erlauben
     * - hash muss von außen als password_hash(...) kommen
     * - optional: alte Einträge historisieren (hier: simple INSERT)
     */
    public function setUserPassword(int $beId, string $passwordHash, array $audit = []): int
    {
        $data = array_merge([
            'be_id' => $beId,
            'fe_pw' => $passwordHash,
        ], $audit);
        
        return $this->insert('erlauben', $data);
    }
    
    /**
     * Login prüfen: holt letzten Passwort-Hash aus fv_erlauben und verifiziert.
     * Annahme: "letzter" Eintrag = höchste fe_id (oder fe_pw_chgd_at).
     */
    public function verifyLogin(string $uid, string $passwordPlain): array
    {
        $user = $this->getUserByUid($uid);
        if (!$user) {
            return ['ok' => false, 'reason' => 'user_not_found', 'user' => null];
        }
        
        $tblErlauben = $this->table('erlauben');
        $sql = "SELECT fe_pw
                FROM `{$tblErlauben}`
                WHERE be_id = :be_id
                ORDER BY fe_id DESC
                LIMIT 1";
        $row = $this->query($sql, ['be_id' => (int)$user['be_id']])->fetch();
        
        if (!$row) {
            return ['ok' => false, 'reason' => 'no_password_set', 'user' => $user];
        }
        
        $ok = password_verify($passwordPlain, (string)$row['fe_pw']);
        return ['ok' => $ok, 'reason' => $ok ? null : 'invalid_password', 'user' => $user];
    }
    
    /**
     * Rolle zuweisen (fv_rolle)
     */
    public function assignRole(int $beId, int $flId, array $audit = []): int
    {
        $data = array_merge([
            'be_id' => $beId,
            'fl_id' => $flId,
        ], $audit);
        
        return $this->insert('rolle', $data);
    }
    
    /**
     * Rollen eines Users (inkl. Beschreibung aus fv_rollen_beschr)
     */
    public function getUserRoles(int $beId): array
    {
        $tblRolle = $this->table('rolle');
        $tblBeschr = $this->table('rollen_beschr');
        
        $sql = "SELECT r.fr_id, r.fl_id, b.fl_Beschreibung, b.fl_module, b.fl_eigner
                FROM `{$tblRolle}` r
                INNER JOIN `{$tblBeschr}` b ON b.fl_id = r.fl_id
                WHERE r.be_id = :be_id
                ORDER BY r.fr_id DESC";
        return $this->query($sql, ['be_id' => $beId])->fetchAll();
    }
    
    /**
     * Mandantenrecht setzen (fv_mand_erl)
     * fu_erlauben: read|update|nix (set)
     */
    public function setTenantRight(int $beId, int $eiId, string $right, array $audit = []): int
    {
        if (!in_array($right, self::MAND_RIGHTS, true)) {
            throw new \InvalidArgumentException("Ungültiges Mandantenrecht: {$right}");
        }
        
        // Upsert-Strategie: wenn bereits vorhanden -> update, sonst insert.
        $existing = $this->selectOne('mand_erl', ['be_id' => $beId, 'ei_id' => $eiId], ['fu_id']);
        if ($existing) {
            $data = array_merge(['fu_erlauben' => $right], $audit);
            $this->update('mand_erl', $data, ['fu_id' => (int)$existing['fu_id']]);
            return (int)$existing['fu_id'];
        }
        
        $data = array_merge([
            'be_id' => $beId,
            'ei_id' => $eiId,
            'fu_erlauben' => $right,
        ], $audit);
        
        return $this->insert('mand_erl', $data);
    }
    
    /**
     * Mandantenrecht abfragen
     */
    public function getTenantRight(int $beId, int $eiId): string
    {
        $row = $this->selectOne('mand_erl', ['be_id' => $beId, 'ei_id' => $eiId], ['fu_erlauben']);
        return $row['fu_erlauben'] ?? 'nix';
    }
    
    /**
     * Prüfen, ob User Zugriff für Mandant hat (read/update)
     */
    public function canTenant(int $beId, int $eiId, string $needed = 'read'): bool
    {
        if (!in_array($needed, ['read', 'update'], true)) {
            throw new \InvalidArgumentException("needed muss read oder update sein");
        }
        $right = $this->getTenantRight($beId, $eiId);
        if ($right === 'update') return true;
        if ($right === 'read' && $needed === 'read') return true;
        return false;
    }
    
    // ---------------------------------------------------------------------
    // OPTIONAL: Prefix-basierte Mandanten-Tabellen (wenn ihr Mandantentabellen
    // pro Tenant via Prefix trennt, z.B. m123_kunden, m123_rechnung, ...)
    // ---------------------------------------------------------------------
    
    /**
     * Erzeugt einen "Tenant-Prefix" sicher (z.B. "m{$eiId}_")
     * Wenn ihr tatsächlich Tabellen pro Mandant habt.
     */
    public function tenantPrefix(int $eiId, string $base = 'm'): string
    {
        if ($eiId < 1) throw new \InvalidArgumentException("eiId muss >= 1 sein");
        $prefix = $base . $eiId . '_';
        $this->validatePrefix($prefix);
        return $prefix;
    }
    
    /**
     * Generische Table-Funktion für Tenant-Tabellen:
     * Achtung: hier ggf. eigene Whitelist pflegen, sonst Injection-Risiko.
     */
    public function tenantTable(int $eiId, string $suffix, string $base = 'm'): string
    {
        $this->validateIdentifier($suffix);
        $tp = $this->tenantPrefix($eiId, $base);
        return $tp . $suffix;
    }
    
    // -------------------------
    // Neue Tabellen-spezifische Methoden
    // -------------------------
    
    // fv_adm_mail (Admin E-Mail Tabelle)
    
    /**
     * Admin-Mail Eintrag anlegen
     * @param array $data ['be_ids'=>int, 'em_mail_grp'=>string, 'em_active'=>string, 'em_new_uid'=>int, 'em_changed_uid'=>string]
     * @return int Insert ID
     */
    public function createAdminMail(array $data): int
    {
        return $this->insert('adm_mail', $data);
    }
    
    /**
     * Admin-Mail Eintrag aktualisieren
     * @param int $emId
     * @param array $data
     * @return int affected rows
     */
    public function updateAdminMail(int $emId, array $data): int
    {
        return $this->update('adm_mail', $data, ['em_id' => $emId]);
    }
    
    /**
     * Admin-Mail Eintrag holen
     * @param int $emId
     * @return array|null
     */
    public function getAdminMail(int $emId): ?array
    {
        return $this->selectOne('adm_mail', ['em_id' => $emId]);
    }
    
    /**
     * Admin-Mail Einträge nach Gruppe und Status filtern
     * @param string|null $group
     * @param string|null $status 'a'|'i'|'' oder null für alle
     * @return array
     */
    public function getAdminMails(?string $group = null, ?string $status = null): array
    {
        $where = [];
        if ($group !== null) {
            $where['em_mail_grp'] = $group;
        }
        if ($status !== null) {
            $where['em_active'] = $status;
        }
        return $this->select('adm_mail', $where);
    }
    
    // fv_ben_dat (Benutzerdaten)
    
    /**
     * Benutzerdaten anlegen
     * @param array $data
     * @return int Insert ID
     */
    public function createUserData(array $data): int
    {
        return $this->insert('ben_dat', $data);
    }
    
    /**
     * Benutzerdaten aktualisieren
     * @param int $fdId
     * @param array $data
     * @return int affected rows
     */
    public function updateUserData(int $fdId, array $data): int
    {
        return $this->update('ben_dat', $data, ['fd_id' => $fdId]);
    }
    
    /**
     * Benutzerdaten nach Benutzer-ID holen
     * @param int $beId
     * @return array|null
     */
    public function getUserDataByUserId(int $beId): ?array
    {
        return $this->selectOne('ben_dat', ['be_id' => $beId]);
    }
    
    // fv_mandant (Mandanten-Stamm)
    
    /**
     * Mandant anlegen
     * @param array $data
     * @return int Insert ID
     */
    public function createMandant(array $data): int
    {
        return $this->insert('mandant', $data);
    }
    
    /**
     * Mandant aktualisieren
     * @param int $eiId
     * @param array $data
     * @return int affected rows
     */
    public function updateMandant(int $eiId, array $data): int
    {
        return $this->update('mandant', $data, ['ei_id' => $eiId]);
    }
    
    /**
     * Mandant nach ID holen
     * @param int $eiId
     * @return array|null
     */
    public function getMandant(int $eiId): ?array
    {
        return $this->selectOne('mandant', ['ei_id' => $eiId]);
    }
    
    // fv_module (Modul-Beschreibung)
    
    /**
     * Modul anlegen
     * @param array $data
     * @return int Insert ID
     */
    public function createModule(array $data): int
    {
        return $this->insert('module', $data);
    }
    
    /**
     * Modul aktualisieren
     * @param int $fmId
     * @param array $data
     * @return int affected rows
     */
    public function updateModule(int $fmId, array $data): int
    {
        return $this->update('module', $data, ['fm_id' => $fmId]);
    }
    
    /**
     * Modul nach ID holen
     * @param int $fmId
     * @return array|null
     */
    public function getModule(int $fmId): ?array
    {
        return $this->selectOne('module', ['fm_id' => $fmId]);
    }
    
    
}