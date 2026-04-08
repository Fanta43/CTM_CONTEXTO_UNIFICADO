<?php
/**
 * ============================================================================
 * CONFIGURACIÓN CENTRAL - ctm_presupuestos
 * ============================================================================
 * Migrado desde conf/conf.php usando herramientas_mejora
 * ============================================================================
 */

// ── Constantes de la aplicación ─────────────────────────────────────────────
if (!defined('PRES_APP'))     define('PRES_APP', true);
if (!defined('APP_SISTEMA'))  define('APP_SISTEMA', 'CTM_055');
if (!defined('APP_TITULO'))   define('APP_TITULO', 'Sistema Presupuestos');
if (!defined('APP_NAME'))     define('APP_NAME', 'Gestión de Presupuestos de Unidades');
if (!defined('APP_VERSION'))  define('APP_VERSION', '2.0.0');

// ── Rutas ────────────────────────────────────────────────────────────────────
if (!defined('APP_ROOT'))     define('APP_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
if (!defined('APP_CONFIG'))   define('APP_CONFIG', APP_ROOT . 'config' . DIRECTORY_SEPARATOR);
if (!defined('APP_INCLUDES')) define('APP_INCLUDES', APP_ROOT . 'includes' . DIRECTORY_SEPARATOR);
if (!defined('APP_ASSETS'))   define('APP_ASSETS', APP_ROOT . 'assets' . DIRECTORY_SEPARATOR);
if (!defined('APP_WEB'))      define('APP_WEB', APP_ROOT . 'web' . DIRECTORY_SEPARATOR);

// ── URLs ─────────────────────────────────────────────────────────────────────
$servidor = 'http://localhost/ctm/contexto_unificado/ctm_presupuestos/';
if (!defined('APP_URL'))      define('APP_URL', $servidor);

// ── Configuración general ────────────────────────────────────────────────────
$TAMANO_PAGINA = 20;
$email_mesadeayuda = "gonzalo.farinon@gmail.com";
$padedata = 'web/arch/';
$tiempo_refresco = '30000';

// ── Configuración de IA (Ollama) ─────────────────────────────────────────────
if (!defined('OLLAMA_HOST'))  define('OLLAMA_HOST',  'http://localhost:11434');
if (!defined('OLLAMA_MODEL')) define('OLLAMA_MODEL', 'qwen3.5:9b');
if (!defined('OLLAMA_MODEL_GRANDE')) define('OLLAMA_MODEL_GRANDE', 'phi3:14b');

// ── Configuración de errores ─────────────────────────────────────────────────
ini_set('display_errors', 'on');
error_reporting(E_ALL);
ini_set('extension', 'php_openssl.dll');

// ── Conexión a Base de Datos ─────────────────────────────────────────────────
function getDbConfig($db = '0') {
    $configs = [
        '0' => [
            'host'     => 'localhost',
            'user'     => 'root',
            'password' => '',
            'database' => 'contexto_unificado',
            'port'     => 3306,
            'charset'  => 'utf8mb4',
            'cert'     => ''
        ],
        '1' => [
            'host'     => 'localhost',
            'user'     => 'root',
            'password' => '',
            'database' => 'contexto_unificado',
            'port'     => 3306,
            'charset'  => 'utf8mb4',
            'cert'     => ''
        ],
        '2' => [
            'host'     => 'localhost',
            'user'     => 'root',
            'password' => '',
            'database' => 'contexto_unificado',
            'port'     => 3306,
            'charset'  => 'utf8mb4',
            'cert'     => ''
        ]
    ];
    return isset($configs[$db]) ? $configs[$db] : $configs['0'];
}

// ── Configuración de comunicaciones (legacy) ─────────────────────────────────
function conf_COM($tipo_com = 'BCRA') {
    switch ($tipo_com) {
        case 'SG':
            return [
                'estado'     => 1,
                'host'       => 'smtp.gmail.com',
                'smtpauth'   => true,
                'username'   => 'farinong@saltogrande.org',
                'useradmin'  => 'farinong@saltogrande.org',
                'password'   => 'ozec frsf lmrh spco',
                'smtpsecure' => 'tsl',
                'port'       => 587
            ];
        default:
            return ['estado' => 0];
    }
}
