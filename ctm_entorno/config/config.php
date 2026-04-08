<?php
/**
 * ============================================================================
 * CONFIGURACIÓN UNIFICADA - Sistema de Tres Vías
 * ============================================================================
 */

if (!defined('ENTO_APP')) {
    define('ENTO_APP', true);
}

date_default_timezone_set('America/Argentina/Buenos_Aires');

ini_set('display_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ── Configuración de la aplicación ──────────────────────────────────────────
if (!defined('APP_NAME')) define('APP_NAME', 'Contexto Unificado');
if (!defined('APP_SISTEMA')) define('APP_SISTEMA', 'AHID_051');
if (!defined('APP_VERSION')) define('APP_VERSION', '3.0.0');
if (!defined('APP_CHARSET')) define('APP_CHARSET', 'UTF-8');

// ── Rutas base ──────────────────────────────────────────────────────────────
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
if (!defined('APP_CONFIG')) define('APP_CONFIG', APP_ROOT . 'config' . DIRECTORY_SEPARATOR);
if (!defined('APP_INCLUDES')) define('APP_INCLUDES', APP_ROOT . 'includes' . DIRECTORY_SEPARATOR);
if (!defined('APP_ASSETS')) define('APP_ASSETS', APP_ROOT . 'assets' . DIRECTORY_SEPARATOR);

// ── URL del servidor ────────────────────────────────────────────────────────
if (!defined('APP_URL')) define('APP_URL', 'http://localhost/ctm/ctm_hidrologia/');

// ── Parámetros ──────────────────────────────────────────────────────────────
if (!defined('TAMANO_PAGINA')) define('TAMANO_PAGINA', 20);
if (!defined('EMAIL_MESA_AYUDA')) define('EMAIL_MESA_AYUDA', 'gonzalo.farinon@gmail.com');

// ── Configuración de Base de Datos ──────────────────────────────────────────
if (!defined('DB_CONFIGS')) define('DB_CONFIGS', serialize([
    'default' => [
        'host'     => 'localhost',
        'user'     => 'root',
        'password' => '',
        'database' => 'contexto_unificado',
        'port'     => 3306,
        'charset'  => 'utf8mb4',
        'cert'     => null,
    ],
    'db1' => [
        'host'     => 'localhost',
        'user'     => 'root',
        'password' => '',
        'database' => 'contexto_unificado',
        'port'     => 3306,
        'charset'  => 'utf8mb4',
        'cert'     => null,
    ],
    'db2' => [
        'host'     => 'localhost',
        'user'     => 'root',
        'password' => '',
        'database' => 'contexto_unificado',
        'port'     => 3306,
        'charset'  => 'utf8mb4',
        'cert'     => null,
    ],
]));

if (!function_exists('getDbConfig')) {
    function getDbConfig($db = '0') {
        $configs = unserialize(DB_CONFIGS);
        $map = ['0' => 'default', '1' => 'db1', '2' => 'db2'];
        $key = isset($map[$db]) ? $map[$db] : 'default';
        return isset($configs[$key]) ? $configs[$key] : $configs['default'];
    }
}

// ── Variables globales de compatibilidad ────────────────────────────────────
$TAMANO_PAGINA     = TAMANO_PAGINA;
$servidor          = APP_URL;
$email_mesadeayuda = EMAIL_MESA_AYUDA;
