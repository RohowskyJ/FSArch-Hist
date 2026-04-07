<?php
declare(strict_types=1);

namespace FSArch\Login\Basis;

$moduleId = 'ADM-DB';

require_once 'FS_Config_lib.php';

/**
 * FS_Database
 * - PDO Wrapper + sichere Query-Helper
 * - Tabellenpräfix (z.B. "fv_") für Stamm-Tabellen
 * - CRUD Helfer (insert/update/delete/select)
 * - Login/ACL Helfer (User, Passwort, Rollen, Mandantenrechte)
 *
 * Erweiterung:
 * - Log-File für Debugging & Ablaufkontrolle (Levels, Context, Request-ID)
 * - Query-Logging (optional, mit Maskierung sensibler Parameter)
 * - Transaction Logging (BEGIN/COMMIT/ROLLBACK)
 *
 * Hinweise:
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
        'benutzer',      // Benutzer- Daten
        'erlauben',      // allgemeine Erlaubnis = Authentifizierung
        'rolle',         // Rollen- Zuordnung
        'rollen_beschr', // Rollen-Beschreibugen
        'mand_erl',   // Mandanten. Zugriffe
        'mandant',    // wird als FK referenziert
        'adm_mail',   // automatische Admin- E-Mails
        'ben_dat',    // Benutzer- Daten
        'module',     // Module ??
        'mitglieder', // Mitglieder
        'mi_bez',     // Mitglieder Berahlung Beitrag
        'mi_ehrung',  // Mitglieder- Ehrung
        'mi_anmeld', // Mitglieder- Anmeldung
        'staaten',  // Staaten
        'falinks', // Links zu öffentl. Archiven und bibliotheken
        'firmen',  // Firmen
        'unterst', // Unterdtützer
    ];
    
    /** Erlaubte Rechtewerte für Mandant */
    private const MAND_RIGHTS = ['read', 'update', 'nix'];
    
    // ---------------------------------------------------------------------
    // Logging (neu)
    // ---------------------------------------------------------------------
    
    /** Log-Level in aufsteigender Wichtigkeit */
    public const LOG_DEBUG = 100;
    public const LOG_INFO  = 200;
    public const LOG_WARN  = 300;
    public const LOG_ERROR = 400;
    
    /** Logging aktiv? */
    private bool $logEnabled = true;
    
    /** Ab welchem Level wird geloggt? */
    private int $logLevel = self::LOG_INFO;
    
    /** Standard-Log-Datei (kann via setLogFile überschrieben werden) */
    private string $logFile;
    
    /** Query-Logging aktiv? */
    private bool $logQueries = true;
    
    /** Parameter-Maskierung aktiv? */
    private bool $maskSensitive = true;
    
    /** Keys, die standardmäßig maskiert werden (Case-insensitive contains) */
    private array $sensitiveKeyFragments = [
        'pass', 'pwd', 'password',
        'token', 'secret', 'api_key', 'apikey',
        'session', 'cookie',
        'auth', 'bearer',
        'salt',
    ];
    
    /** Request/Correlation ID für Ablaufkontrolle */
    private string $requestId;
    
    /** maximale Länge von SQL/Param-Ausgabe im Log */
    private int $maxLogStringLen = 4000;
    
    
    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix ?? (defined('DB_PREFIX') ? DB_PREFIX : 'fv_');
        $this->validatePrefix($this->prefix);
        
        // Default-Logfile: neben dem Projekt, in /logs (wenn vorhanden), sonst im aktuellen Verzeichnis
        $baseDir = $_SERVER['DOCUMENT_ROOT'] . "login" . DIRECTORY_SEPARATOR . "common" . DIRECTORY_SEPARATOR . 'logs';
        # $baseDir = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'logs');
       #  $baseDir = defined('FS_LOG_DIR') ? (string)FS_LOG_DIR : (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'logs');
        $this->logFile = rtrim($baseDir, DIRECTORY_SEPARATOR) . 'fs_database.log.txt';
        
        $this->requestId = $this->generateRequestId();
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $this->pdo = new \PDO($dsn, DB_USER, DB_PASS, $options);
        
        $this->log(self::LOG_INFO, 'FS_Database initialized', [
            'prefix' => $this->prefix,
            'dsn'    => $this->safeDsnForLog($dsn),
        ]);
    }
    
    // ---------------------- Public Logger API ----------------------------
    
    /** Logging aktivieren/deaktivieren */
    public function setLoggingEnabled(bool $enabled): void
    {
        $this->logEnabled = $enabled;
    }
    
    /** Mindest-Level setzen (DEBUG/INFO/WARN/ERROR) */
    public function setLogLevel(int $level): void
    {
        $this->logLevel = $level;
    }
    
    /** Log-Datei setzen */
    public function setLogFile(string $path): void
    {
        $this->logFile = $path;
    }
    
    /** Query-Logging ein/aus */
    public function setLogQueries(bool $enabled): void
    {
        $this->logQueries = $enabled;
    }
    
    /** Sensible Parameter maskieren ein/aus */
    public function setMaskSensitive(bool $enabled): void
    {
        $this->maskSensitive = $enabled;
    }
    
    /** Request-ID setzen (z.B. aus deiner App/Router Middleware) */
    public function setRequestId(string $requestId): void
    {
        $this->requestId = $requestId;
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
        
        $this->log(self::LOG_INFO, 'Prefix changed', ['prefix' => $this->prefix]);
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
            $this->log(self::LOG_ERROR, 'Invalid table prefix', ['prefix' => $prefix]);
            throw new \InvalidArgumentException("Ungültiges Tabellenpräfix: $prefix");
        }
    }
    
    /** Identifier validieren (Spaltenname/Tabellen-Suffix) */
    private function validateIdentifier(string $name): void
    {
        // erlaubt: a-z A-Z 0-9 _
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            $this->log(self::LOG_ERROR, 'Invalid identifier', ['identifier' => $name]);
            throw new \InvalidArgumentException("Ungültiger Identifier: $name");
        }
    }
    
    /** Stamm-Tabellenname aus Suffix zusammensetzen: prefix + suffix */
    private function table(string $suffix): string
    {
        $this->validateIdentifier($suffix);
        if (!in_array($suffix, $this->allowedTables, true)) {
            $this->log(self::LOG_WARN, 'Table suffix not allowed (whitelist)', ['suffix' => $suffix]);
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
                $this->log(self::LOG_ERROR, 'Invalid ORDER direction', ['col' => (string)$col, 'dir' => (string)$dir]);
                throw new \InvalidArgumentException("Ungültige ORDER Richtung: $dir");
            }
            $parts[] = "`{$col}` {$dir}";
        }
        return ' ORDER BY ' . implode(', ', $parts);
    }
    
    /** Basis-Query Helper */
    // ---------------------------------------------------------------------
    // Basis-Query Helper (mit Logging)
    // ---------------------------------------------------------------------
    
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $t0 = microtime(true);
        
        if ($this->logQueries) {
            $this->log(self::LOG_DEBUG, 'SQL prepare', [
                'sql'    => $this->trimForLog($sql),
                'params' => $this->sanitizeParamsForLog($params),
            ]);
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $k => $v) {
                // PDO bindValue akzeptiert sowohl :name als auch name; wir normalisieren:
                $param = is_string($k) && $k !== '' && $k[0] !== ':' ? ':' . $k : $k;
                $stmt->bindValue($param, $v);
            }
            
            $stmt->execute();
            
            if ($this->logQueries) {
                $this->log(self::LOG_DEBUG, 'SQL executed', [
                    'ms' => (int)round((microtime(true) - $t0) * 1000),
                ]);
            }
            
            return $stmt;
        } catch (\Throwable $e) {
            $this->log(self::LOG_ERROR, 'SQL error', [
                'sql'      => $this->trimForLog($sql),
                'params'   => $this->sanitizeParamsForLog($params),
                'error'    => $e->getMessage(),
                'code'     => $e->getCode(),
                'ms'       => (int)round((microtime(true) - $t0) * 1000),
                'traceTop' => $this->traceTop($e),
            ]);
            throw $e;
        }
    }
    
    
    /** Transaction Helper */
    public function transaction(callable $fn)
    {
        $this->log(self::LOG_INFO, 'TX begin');
        $this->pdo->beginTransaction();
        
        try {
            $result = $fn($this);
            $this->pdo->commit();
            $this->log(self::LOG_INFO, 'TX commit');
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->log(self::LOG_WARN, 'TX rollback', [
                'error'    => $e->getMessage(),
                'code'     => $e->getCode(),
                'traceTop' => $this->traceTop($e),
            ]);
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
                if ($limit < 1) {
                    $this->log(self::LOG_ERROR, 'Invalid LIMIT', ['limit' => $limit]);
                    throw new \InvalidArgumentException("LIMIT muss >= 1 sein");
                }
                $sql .= " LIMIT " . (int)$limit;
                if ($offset !== null) {
                    if ($offset < 0) {
                        $this->log(self::LOG_ERROR, 'Invalid OFFSET', ['offset' => $offset]);
                        throw new \InvalidArgumentException("OFFSET muss >= 0 sein");
                    }
                    $sql .= " OFFSET " . (int)$offset;
                }
            }
            
            $rows = $this->query($sql, $params)->fetchAll();
            
            if ($this->logQueries) {
                $this->log(self::LOG_DEBUG, 'SELECT result', [
                    'table' => $tbl,
                    'rows'  => is_array($rows) ? count($rows) : 0,
                ]);
            }
            
            return $rows;
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
        if (!$data) {
            $this->log(self::LOG_ERROR, 'INSERT called without data', ['tableSuffix' => $tableSuffix]);
            throw new \InvalidArgumentException("INSERT benötigt Daten");
        }
        
        $tbl = $this->table($tableSuffix);
        
        $cols = array_keys($data);
        foreach ($cols as $c) $this->validateIdentifier((string)$c);
        
        $colSql = implode(', ', array_map(fn($c) => "`{$c}`", $cols));
        $phSql  = implode(', ', array_map(fn($c) => ":{$c}", $cols));
        
        $sql = "INSERT INTO `{$tbl}` ({$colSql}) VALUES ({$phSql})";
        $this->query($sql, $data);
        
        $id = (int)$this->pdo->lastInsertId();
        
        $this->log(self::LOG_INFO, 'INSERT ok', [
            'table' => $tbl,
            'id'    => $id,
            'cols'  => $cols,
        ]);
        
        return $id;
    }
    
    /**
     * UPDATE (generisch)
     * @return int affected rows
     */
    public function update(string $tableSuffix, array $data, array $where): int
    {
        if (!$data) {
            $this->log(self::LOG_ERROR, 'UPDATE called without data', ['tableSuffix' => $tableSuffix]);
            throw new \InvalidArgumentException("UPDATE benötigt Daten");
        }
        if (!$where) {
            $this->log(self::LOG_ERROR, 'UPDATE blocked (missing WHERE)', ['tableSuffix' => $tableSuffix]);
            throw new \InvalidArgumentException("UPDATE benötigt WHERE (Sicherheitsmaßnahme)");
        }
        
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
        $cnt = $stmt->rowCount();
        
        $this->log(self::LOG_INFO, 'UPDATE ok', [
            'table' => $tbl,
            'rows'  => $cnt,
            'where' => $this->sanitizeParamsForLog($where),
        ]);
        
        return $cnt;
    }
    
    /**
     * DELETE (generisch)
     * @return int affected rows
     */
    public function delete(string $tableSuffix, array $where): int
    {
        if (!$where) {
            $this->log(self::LOG_ERROR, 'DELETE blocked (missing WHERE)', ['tableSuffix' => $tableSuffix]);
            throw new \InvalidArgumentException("DELETE benötigt WHERE (Sicherheitsmaßnahme)");
        }
        
        $tbl = $this->table($tableSuffix);
        
        $params = [];
        $sql = "DELETE FROM `{$tbl}`";
        $sql .= $this->buildWhere($where, $params);
        
        $stmt = $this->query($sql, $params);
        $cnt = $stmt->rowCount();
        
        $this->log(self::LOG_WARN, 'DELETE ok', [
            'table' => $tbl,
            'rows'  => $cnt,
            'where' => $this->sanitizeParamsForLog($where),
        ]);
        
        return $cnt;
    }
    
    // ---------------------------------------------------------------------
    // Logging internals
    // ---------------------------------------------------------------------
    
    private function log(int $level, string $message, array $context = []): void
    {
        if (!$this->logEnabled) return;
        if ($level < $this->logLevel) return;
        
        $this->ensureLogDirExists();
        
        $levelName = $this->levelName($level);
        $ts = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s.u');
        $pid = function_exists('getmypid') ? (int)getmypid() : null;
        
        $base = [
            'ts'   => $ts,
            'lvl'  => $levelName,
            'rid'  => $this->requestId,
            'pid'  => $pid,
            'mod'  => $GLOBALS['moduleId'] ?? null,
            'msg'  => $message,
        ];
        
        $line = $this->jsonLine(array_filter($base, fn($v) => $v !== null) + $this->normalizeContext($context));
        
        // file append (atomic-ish)
        @file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    private function ensureLogDirExists(): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
    
    private function levelName(int $level): string
    {
        return match ($level) {
            self::LOG_DEBUG => 'DEBUG',
            self::LOG_INFO  => 'INFO',
            self::LOG_WARN  => 'WARN',
            self::LOG_ERROR => 'ERROR',
            default         => (string)$level,
        };
    }
    
    private function jsonLine(array $data): string
    {
        // JSON_UNESCAPED_UNICODE für lesbare Umlaute
        return (string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    private function normalizeContext(array $context): array
    {
        // Kürzen großer Strings und nicht-serialisierbare Werte abfangen
        $out = [];
        foreach ($context as $k => $v) {
            if (is_string($v)) {
                $out[$k] = $this->trimForLog($v);
            } elseif (is_scalar($v) || $v === null) {
                $out[$k] = $v;
            } elseif (is_array($v)) {
                $out[$k] = $this->trimArrayForLog($v);
            } elseif ($v instanceof \Throwable) {
                $out[$k] = ['error' => $v->getMessage(), 'code' => $v->getCode()];
            } else {
                $out[$k] = '[' . gettype($v) . ']';
            }
        }
        return $out;
    }
    
    private function trimForLog(string $s): string
    {
        if (mb_strlen($s) <= $this->maxLogStringLen) return $s;
        return mb_substr($s, 0, $this->maxLogStringLen) . '…(truncated)';
    }
    
    private function trimArrayForLog(array $arr): array
    {
        // Rekursiv, aber defensiv
        $out = [];
        $i = 0;
        foreach ($arr as $k => $v) {
            $i++;
            if ($i > 200) { // harte Grenze
                $out['__truncated__'] = true;
                break;
            }
            if (is_string($v)) $out[$k] = $this->trimForLog($v);
            elseif (is_scalar($v) || $v === null) $out[$k] = $v;
            elseif (is_array($v)) $out[$k] = $this->trimArrayForLog($v);
            else $out[$k] = '[' . gettype($v) . ']';
        }
        return $out;
    }
    
    private function sanitizeParamsForLog(array $params): array
    {
        if (!$this->maskSensitive) return $this->trimArrayForLog($params);
        
        $out = [];
        foreach ($params as $k => $v) {
            $key = (string)$k;
            
            if ($this->isSensitiveKey($key)) {
                $out[$key] = '***';
                continue;
            }
            
            // Heuristik: sehr lange Strings (Tokens) ebenfalls maskieren
            if (is_string($v) && mb_strlen($v) > 200) {
                $out[$key] = mb_substr($v, 0, 12) . '…***';
                continue;
            }
            
            $out[$key] = is_string($v) ? $this->trimForLog($v) : (is_array($v) ? $this->trimArrayForLog($v) : $v);
        }
        return $out;
    }
    
    private function isSensitiveKey(string $key): bool
    {
        $lower = mb_strtolower($key);
        foreach ($this->sensitiveKeyFragments as $frag) {
            if (str_contains($lower, mb_strtolower($frag))) return true;
        }
        return false;
    }
    
    private function traceTop(\Throwable $e): array
    {
        $t = $e->getTrace();
        $top = $t[0] ?? [];
        return [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'top'  => [
                'file' => $top['file'] ?? null,
                'line' => $top['line'] ?? null,
                'func' => $top['function'] ?? null,
                'class'=> $top['class'] ?? null,
            ],
        ];
    }
    
    private function generateRequestId(): string
    {
        // Wenn vorhanden: aus Headern/Server übernehmen, sonst generieren
        $candidates = [
            $_SERVER['HTTP_X_REQUEST_ID'] ?? null,
            $_SERVER['HTTP_X_CORRELATION_ID'] ?? null,
        ];
        foreach ($candidates as $c) {
            if (is_string($c) && $c !== '') return $c;
        }
        
        try {
            return bin2hex(random_bytes(8));
        } catch (\Throwable) {
            return (string)mt_rand(10000000, 99999999);
        }
    }
    
    private function safeDsnForLog(string $dsn): string
    {
        // DSN enthält i.d.R. keine Passwörter, aber wir kürzen sicherheitshalber
        return $this->trimForLog($dsn);
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
     * Rollenl Eintrag für Benutzer anlegen
     * @param array $data ['be_ids'=>int, 'em_mail_grp'=>string, 'em_active'=>string, 'em_new_uid'=>int, 'em_changed_uid'=>string]
     * @return int Insert ID
     */
    public function createRoleByBen(array $data): int
    {
        return $this->insert('rolle', $data);
    }
    
    /**
     * Rollen- Eintragfür Benutzer aktualisieren
     * @param int $emId
     * @param array $data
     * @return int affected rows
     */
    public function updateRoleByBenId(int $beId, array $data): int
    {
        return $this->update('rolle', $data, ['be_id' => $beId]);
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
        
        $sql = "SELECT r.fr_id, r.fl_id, r.fl_aktiv, b.fl_Beschreibung, b.fl_module, b.fl_eigner
                FROM `{$tblRolle}` r
                INNER JOIN `{$tblBeschr}` b ON b.fl_id = r.fl_id
                WHERE r.be_id = :be_id
                ORDER BY r.fr_id DESC";
        return $this->query($sql, ['be_id' => $beId])->fetchAll();
    }
    
    /** 
     * Rolle einlesen
     */
    public function getRoleById(int $frId): array
    {
        $tblRolle = $this->table('rolle');
        $tblBeschr = $this->table('rollen_beschr');
        
        $sql = "SELECT r.fr_id, r.be_id, r.fl_id, r.fr_aktiv, b.fl_Beschreibung, b.fl_modules
                FROM `{$tblRolle}` r
                INNER JOIN `{$tblBeschr}` b ON b.fl_id = r.fl_id
                WHERE r.fr_id = :fr_id
                ORDER BY r.fr_id ASC";
        $result = $this->query($sql, ['fr_id' => $frId])->fetch();
        return $result === false ? [] : $result;
    }

    // Rollen- Beschreibungen 
    /**
     * Rollen- Beschreibung Eintrag anlegen
     * @param array $data 
     * @return int Insert ID
     */
    public function createRoleDescr(array $data): int
    {
        return $this->insert('role_descr', $data);
    }
    
    /**
     * Rollen- Beschreibung Eintrag aktualisieren
     * @param int $emId
     * @param array $data
     * @return int affected rows
     */
    public function updateRoleDescr(int $flId, array $data): int
    {
        return $this->update('rollen_beschr', $data, ['fl_id' => $flId]);
    }
    
    /**
     * Rollen- Beschreibung  Eintrag holen
     * @param int $flId
     * @return array|null
     */
    public function getRoleDescr(int $flId): ?array
    {
        return $this->selectOne('rollen_beschr', ['fl_id' => $flId]);
    }
    
    /**
     * Rollen- Beschreibung  Einträge holen
     * @param int $flId
     * @return array|null
     */
    public function getRoleDescrAll(): ?array
    {
        return $this->select('rollen_beschr');
    }
    // Mandanten- Funktionen
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
     * Admin-Mail Eintrag nach ID holen
     * @param int $emId
     * @return array|null
     */
    public function getAdminMailById(int $emId): ?array
    {
        return $this->selectOne('adm_mail', ['em_id' => $emId]);
    }
    
    /**
     * Holt Mitglieder-Daten basierend auf dem Listentyp und optionalen Suchparametern
     * @param string $listType z.B. 'Alle', 'Aktiv', 'InAktiv', ...
     * @param string|null $search optionaler Suchstring
     * @return array
     */
    public function getAdminMailByIdBE(int $emId, int $beId): array {
        // SQL mit JOIN auf fv_ben_dat (Alias b) und fv_adm_mail (Alias a)
        $sql = "
        SELECT
            a.em_id,
            a.be_ids,
            a.em_active,
            a.em_mail_grp,
            b.fd_name,
            b.fd_email
        FROM fv_adm_mail a
        LEFT JOIN fv_ben_dat b ON b.be_id = a.be_ids
    ";
        
        $where = [];
        $params = [];
        $orderBy = "ORDER BY b.fd_id";
        
        // Filter auf emId, falls angegeben (ungleich 0 oder größer 0)
        if ($emId > 0) {
            $where[] = "a.em_id = :emId";
            $params[':emId'] = $emId;
        }
        
        // Filter auf beId
        if ($beId > 0) {
            $where[] = "b.be_id = :beId";
            $params[':beId'] = $beId;
        }
        
        // Falls WHERE-Bedingungen existieren, anfügen
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        // ORDER BY anfügen
        $sql .= " " . $orderBy;
        
        // Beispiel: Annahme, es gibt eine PDO-Verbindung $this->pdo
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        // Ergebnis als Array zurückgeben
        return $stmt->fetch();
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
    
    /**
     * Benutzerdaten nach Benutzer-ID holen
     * @param int $beId
     * @return array|null
     */
    public function getUserDataById(int $fdId): ?array
    {
        return $this->selectOne('ben_dat', ['fd_id' => $fdId]);
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
    
    //  --- Firmen- Verwaltung  ---

    /**
     * Firmen Eintrag anlegen
     * @param array $data ['be_ids'=>int, 'em_mail_grp'=>string, 'em_active'=>string, 'em_new_uid'=>int, 'em_changed_uid'=>string]
     * @return int Insert ID
     */
    public function createFirmen(array $data): int
    {
        return $this->insert('firmen', $data);
    }
    
    /**
     * Firmen Eintrag aktualisieren
     * @param int $emId
     * @param array $data
     * @return int affected rows
     */
    public function updateFirmen(int $efiId, array $data): int
    {
        return $this->update('firmen', $data, ['fi_id' => $fiId]);
    }
    
    /**
     * Firmen Eintrag nach ID holen
     * @param int $emId
     * @return array|null
     */
    
    public function getFirmenById(int $id): ?array
    {
        return $this->selectOne('firmen', ['fi_id' => $id]);
    }
    
    /**
     * Firmen Einträge nach Funktion filtern
     * @param string|null $group
     * @param string|null $status 'a'|'i'|'' oder null für alle
     * @return array
     */
    public function getFirmenByFunkt(?string $funkt = null ): array
    {
        $where = [];
        if ($funkt !== null) {
            $where['fi_funkt'] = $funkt;
        }
      
        return $this->select('firmen', $where);
    }
    
    //  --- Unterstützer und Sponsoren- Verwaltung  ---
    
    /**
     * Unterstüzer Eintrag anlegen
     * @param array $data ['be_ids'=>int, 'em_mail_grp'=>string, 'em_active'=>string, 'em_new_uid'=>int, 'em_changed_uid'=>string]
     * @return int Insert ID
     */
    public function createUnterst(array $data): int
    {
        return $this->insert('unterst', $data);
    }
    
    /**
     * Unterstüzer  Eintrag aktualisieren
     * @param int $emId
     * @param array $data
     * @return int affected rows
     */
    public function updateUnterst(int $efiId, array $data): int
    {
        return $this->update('unterst', $data, ['fi_id' => $fiId]);
    }
    
    /**
     * Unterstüzer  Eintrag nach ID holen
     * @param int $emId
     * @return array|null
     */
    
    public function getUnterstById(int $id): ?array
    {
        return $this->selectOne('unterst', ['fi_id' => $id]);
    }
    
    /**
     * Unterstüzer  Einträge nach Funktion filtern
     * @param string|null $group
     * @param string|null $status 'a'|'i'|'' oder null für alle
     * @return array
     */
    public function getUnterstByFunkt(?string $kateg = null ): array
    {
        $where = [];
        if ($fkateg !== null) {
            $where['fu_kateg'] = $kategt;
        }
        
        return $this->select('unterst ', $where);
    }
    
}