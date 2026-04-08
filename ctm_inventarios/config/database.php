<?php
/**
 * ============================================================================
 * DATABASE SINGLETON - ctm_inventarios
 * ============================================================================
 * Clase Database Singleton + wrappers de compatibilidad con código legacy.
 * ============================================================================
 */

class Database {
    private static $instances = [];
    private $connection;
    private $config;

    private function __construct($config) {
        $this->config = $config;
        $this->connect();
    }

    public static function getInstance($db = '0') {
        if (!isset(self::$instances[$db])) {
            $config = getDbConfig($db);
            self::$instances[$db] = new self($config);
        }
        return self::$instances[$db];
    }

    private function connect() {
        $this->connection = mysqli_init();
        if (!empty($this->config['cert'])) {
            mysqli_ssl_set($this->connection, null, null, $this->config['cert'], null, null);
            mysqli_real_connect($this->connection, $this->config['host'], $this->config['user'],
                $this->config['password'], $this->config['database'], $this->config['port'], null, MYSQLI_CLIENT_SSL);
        } else {
            mysqli_real_connect($this->connection, $this->config['host'], $this->config['user'],
                $this->config['password'], $this->config['database'], $this->config['port']);
        }
        if (mysqli_connect_errno()) { error_log('Error DB: ' . mysqli_connect_error()); return; }
        $this->connection->set_charset($this->config['charset'] ?? 'utf8mb4');
    }

    public function getConnection() { return $this->connection; }

    public function execute($query, $params = [], $types = '') {
        if (empty($params)) {
            $result = mysqli_query($this->connection, $query);
            if ($result === false) error_log('SQL Error: ' . mysqli_error($this->connection));
            return $result;
        }
        $stmt = mysqli_prepare($this->connection, $query);
        if (!$stmt) { error_log('Prepare Error: ' . mysqli_error($this->connection)); return false; }
        if (empty($types)) $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return ($result === false && mysqli_stmt_errno($stmt) === 0) ? true : $result;
    }

    public static function fetchUpper($result) {
        if (!$result || !($row = mysqli_fetch_assoc($result))) return null;
        $assoc = [];
        foreach ($row as $key => $value) $assoc[strtoupper($key)] = $value;
        return $assoc;
    }

    public static function fetchAll($result) {
        $rows = [];
        while ($row = self::fetchUpper($result)) $rows[] = $row;
        return $rows;
    }

    public static function numRows($result) { return $result ? mysqli_num_rows($result) : 0; }
    public function lastInsertId() { return mysqli_insert_id($this->connection); }
    public function escape($s) { return mysqli_real_escape_string($this->connection, $s); }
    public function close() { if ($this->connection) mysqli_close($this->connection); }
    public function __destruct() { $this->close(); }
}

// ═══════════════════════════════════════════════════════════════════════════════
// WRAPPERS DE COMPATIBILIDAD (misma firma que el código legacy)
// ═══════════════════════════════════════════════════════════════════════════════

function sql_cnx_data($db = '0') {
    $c = getDbConfig($db);
    return ['host' => $c['host'], 'user' => $c['user'], 'pws' => $c['password'], 'db' => $c['database'], 'cert' => $c['cert'] ?? ''];
}

function sql_do_query($query, $db = '0', $user = '99999999') {
    return Database::getInstance($db)->execute($query);
}

function sql_do_array($recurso, $db = '0') {
    return Database::fetchUpper($recurso);
}

function registros($recurso, $db = '0') {
    return Database::numRows($recurso);
}

function mysql_fetch_alias_array($result) {
    return Database::fetchUpper($result);
}

if (!function_exists('mysqli_field_name')) {
    function mysqli_field_name($result, $offset) {
        $p = mysqli_fetch_field_direct($result, $offset);
        return is_object($p) ? $p->name : null;
    }
}
