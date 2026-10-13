<?php
/**
 * ============================================================================
 * CONFIGURACIÓN CENTRAL - ctm_morebius
 * ============================================================================
 * Seguimiento de inventario informático y asignaciones de equipos al personal.
 * Base "morebius" es READ-ONLY. Cambios solo en "contexto_unificado".
 * ============================================================================
 */

// ── Constantes de la aplicación ─────────────────────────────────────────────
if (!defined('MORE_APP'))     define('MORE_APP', true);
if (!defined('APP_SISTEMA'))  define('APP_SISTEMA', 'AICO_666');
if (!defined('APP_TITULO'))   define('APP_TITULO', 'Morebius - Inventario IT');
if (!defined('APP_NAME'))     define('APP_NAME', 'Seguimiento de Inventario y Asignaciones');
if (!defined('APP_VERSION'))  define('APP_VERSION', '2.0.0');

// ── Rutas ────────────────────────────────────────────────────────────────────
if (!defined('APP_ROOT'))     define('APP_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
if (!defined('APP_CONFIG'))   define('APP_CONFIG', APP_ROOT . 'config' . DIRECTORY_SEPARATOR);
if (!defined('APP_INCLUDES')) define('APP_INCLUDES', APP_ROOT . 'includes' . DIRECTORY_SEPARATOR);
if (!defined('APP_ASSETS'))   define('APP_ASSETS', APP_ROOT . 'assets' . DIRECTORY_SEPARATOR);
if (!defined('APP_WEB'))      define('APP_WEB', APP_ROOT . 'web' . DIRECTORY_SEPARATOR);

// ── Configuración local (desarrollo / producción, NO se sube a git) ─────────
// Si existe config/config.local.php se carga primero y sus valores mandan.
// Ver config.local.php.ejemplo en esta misma carpeta.
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// ── URLs ─────────────────────────────────────────────────────────────────────
if (!isset($servidor)) $servidor = 'http://localhost/ctm/contexto_unificado/ctm_morebius/';
if (!defined('APP_URL'))      define('APP_URL', $servidor);

// ── Configuración general ────────────────────────────────────────────────────
$TAMANO_PAGINA = 20;
$email_mesadeayuda = "atencion_usuarios@saltogrande.org";
$servidor_img = 'https://intranet.saltogrande.org/imagenes/img_personal/';

// ── Configuración de errores ─────────────────────────────────────────────────
if (!defined('MOSTRAR_ERRORES')) define('MOSTRAR_ERRORES', 'off');
ini_set('display_errors', MOSTRAR_ERRORES);
error_reporting(E_ALL);
ini_set('extension', 'php_openssl.dll');

// ── Conexión a Base de Datos ─────────────────────────────────────────────────
// DB '0' y '1' = contexto_unificado (tablas more_*, vistas, etc.)
// DB '2'       = morebius (READ-ONLY: inventario, plantel, arbol, etc.)
if (!function_exists('getDbConfig')) {
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
                'database' => 'morebius',
                'port'     => 3306,
                'charset'  => 'utf8mb4',
                'cert'     => ''
            ],
            '3' => [
                'host'     => 'odbc',
                'user'     => 'libroguardia',
                'password' => '4rfugy7',
                'database' => 'inventario',
                'port'     => 3306,
                'charset'  => 'utf8mb4',
                'cert'     => ''
            ],
            '4' => [
                'host'     => 'odbc',
                'user'     => 'CTMGAG\\farinong',
                'password' => 'M@cross2021',
                'database' => 'gedb',
                'port'     => 3306,
                'charset'  => 'utf8mb4',
                'cert'     => ''
            ]
        ];
        return isset($configs[$db]) ? $configs[$db] : $configs['0'];
    }
}

// ── Vida útil de equipos por tipo ────────────────────────────────────────────
function vida_util($tipoArt = '1') {
    $tipos = [
        '1' => ['maxima' => 4, 'media' => 3, 'minima' => 2],
        '2' => ['maxima' => 4, 'media' => 3, 'minima' => 2],
        '3' => ['maxima' => 5, 'media' => 4, 'minima' => 3],
        '4' => ['maxima' => 4, 'media' => 3, 'minima' => 2],
        '5' => ['maxima' => 4, 'media' => 3, 'minima' => 2],
    ];
    return $tipos[$tipoArt] ?? ['maxima' => 4, 'media' => 3, 'minima' => 2];
}

// ── Configuración de comunicaciones ──────────────────────────────────────────
function conf_COM($tipo_com = 'SG') {
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
