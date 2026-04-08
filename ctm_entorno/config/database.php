<?php
/**
 * ============================================================================
 * Clase Database - Conexión segura con prepared statements
 * ============================================================================
 * Sistema de Tres Vías
 * ============================================================================
 */

if (!defined('ENTO_APP')) {
    define('ENTO_APP', true);
}

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
            mysqli_real_connect(
                $this->connection,
                $this->config['host'],
                $this->config['user'],
                $this->config['password'],
                $this->config['database'],
                $this->config['port'],
                null,
                MYSQLI_CLIENT_SSL
            );
        } else {
            mysqli_real_connect(
                $this->connection,
                $this->config['host'],
                $this->config['user'],
                $this->config['password'],
                $this->config['database'],
                $this->config['port']
            );
        }
        
        if (mysqli_connect_errno()) {
            error_log('Error de conexión a BD: ' . mysqli_connect_error());
            return;
        }
        
        $this->connection->set_charset($this->config['charset'] ?? 'utf8mb4');
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function execute($query, $params = [], $types = '') {
        if (empty($params)) {
            $result = mysqli_query($this->connection, $query);
            if ($result === false) {
                error_log('Error SQL: ' . mysqli_error($this->connection) . ' | Query: ' . $query);
            }
            return $result;
        }
        
        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt === false) {
            error_log('Error al preparar SQL: ' . mysqli_error($this->connection) . ' | Query: ' . $query);
            return false;
        }
        
        if (empty($types)) {
            $types = str_repeat('s', count($params));
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        if ($result === false && mysqli_stmt_errno($stmt) === 0) {
            return true;
        }
        
        return $result;
    }
    
    public static function fetchUpper($result) {
        if (!$result || !($row = mysqli_fetch_assoc($result))) {
            return null;
        }
        $assoc = [];
        foreach ($row as $key => $value) {
            $assoc[strtoupper($key)] = $value;
        }
        return $assoc;
    }
    
    public static function fetchAll($result) {
        $rows = [];
        while ($row = self::fetchUpper($result)) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public static function numRows($result) {
        if (!$result) return 0;
        return mysqli_num_rows($result);
    }
    
    public function lastInsertId() {
        return mysqli_insert_id($this->connection);
    }
    
    public function escape($string) {
        return mysqli_real_escape_string($this->connection, $string);
    }
    
    public function close() {
        if ($this->connection) {
            mysqli_close($this->connection);
        }
    }
    
    public function __destruct() {
        $this->close();
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES WRAPPER DE COMPATIBILIDAD
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('sql_cnx_data')) {
    function sql_cnx_data($db = '0') {
        $config = getDbConfig($db);
        return [
            'host' => $config['host'],
            'user' => $config['user'],
            'pws'  => $config['password'],
            'db'   => $config['database'],
            'cert' => $config['cert'],
        ];
    }
}

if (!function_exists('sql_do_query')) {
    function sql_do_query($query, $db = '0', $user = '99999999') {
        $database = Database::getInstance($db);
        return $database->execute($query);
    }
}

if (!function_exists('sql_do_array')) {
    function sql_do_array($recurso, $db = '0') {
        return Database::fetchUpper($recurso);
    }
}

if (!function_exists('registros')) {
    function registros($recurso, $db = '0') {
        return Database::numRows($recurso);
    }
}

if (!function_exists('mysql_fetch_alias_array')) {
    function mysql_fetch_alias_array($result) {
        return Database::fetchUpper($result);
    }
}
