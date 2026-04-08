<?php
/**
 * ============================================================================
 * CONFIGURACIÓN CENTRAL - ctm_inventarios
 * ============================================================================
 * Seguimiento del inventario de equipos informáticos: PCs, monitores,
 * impresoras, otros dispositivos, préstamos y reparaciones.
 * Base "morebius" es READ-ONLY. Cambios solo en "contexto_unificado".
 * ============================================================================
 */

// ── Constantes de la aplicación ─────────────────────────────────────────────
if (!defined('INVE_APP'))     define('INVE_APP', true);
if (!defined('APP_SISTEMA'))  define('APP_SISTEMA', 'CTM_062');
if (!defined('APP_TITULO'))   define('APP_TITULO', 'Inventarios - Seguimiento IT');
if (!defined('APP_NAME'))     define('APP_NAME', 'Sistema de Inventarios');
if (!defined('APP_VERSION'))  define('APP_VERSION', '2.0.0');

// ── Rutas ────────────────────────────────────────────────────────────────────
if (!defined('APP_ROOT'))     define('APP_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
if (!defined('APP_CONFIG'))   define('APP_CONFIG', APP_ROOT . 'config' . DIRECTORY_SEPARATOR);
if (!defined('APP_INCLUDES')) define('APP_INCLUDES', APP_ROOT . 'includes' . DIRECTORY_SEPARATOR);
if (!defined('APP_ASSETS'))   define('APP_ASSETS', APP_ROOT . 'assets' . DIRECTORY_SEPARATOR);
if (!defined('APP_WEB'))      define('APP_WEB', APP_ROOT . 'web' . DIRECTORY_SEPARATOR);

// ── URLs ─────────────────────────────────────────────────────────────────────
$servidor = 'http://localhost/ctm/contexto_unificado/ctm_inventarios/';
if (!defined('APP_URL'))      define('APP_URL', $servidor);

// ── Configuración general ────────────────────────────────────────────────────
$TAMANO_PAGINA = 20;
$email_mesadeayuda = "atencion_usuarios@saltogrande.org";
$servidor_img = 'https://intranet.saltogrande.org/imagenes/img_personal/';

// ── Configuración de errores ─────────────────────────────────────────────────
ini_set('display_errors', 'off');
error_reporting(E_ALL);
ini_set('extension', 'php_openssl.dll');

// ── Conexión a Base de Datos ─────────────────────────────────────────────────
// DB '0' y '1' = contexto_unificado (tablas inve_*, vistas, etc.)
// DB '2'       = ctm_ocsweb  (OCS Inventory - READ-ONLY)
// DB '3'       = morebius    (READ-ONLY: articulos_con, artipcel, etc.)
// DB '4'       = inventario  (legacy, no disponible localmente)
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
            'database' => 'ctm_ocsweb',
            'port'     => 3306,
            'charset'  => 'utf8mb4',
            'cert'     => ''
        ],
        '3' => [
            'host'     => 'localhost',
            'user'     => 'root',
            'password' => '',
            'database' => 'morebius',
            'port'     => 3306,
            'charset'  => 'utf8mb4',
            'cert'     => ''
        ],
        '4' => [
            'host'     => 'localhost',
            'user'     => 'root',
            'password' => '',
            'database' => 'inventario',
            'port'     => 3306,
            'charset'  => 'utf8mb4',
            'cert'     => ''
        ]
    ];
    return isset($configs[$db]) ? $configs[$db] : $configs['0'];
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
