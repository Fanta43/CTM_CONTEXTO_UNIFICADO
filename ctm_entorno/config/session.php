<?php
/**
 * ============================================================================
 * Gestión de Sesión y Seguridad - Entorno del Sistema
 * ============================================================================
 */

if (!defined('ENTO_APP')) {
    define('ENTO_APP', true);
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

/**
 * Verificar si el usuario tiene sesión válida
 */
function verificarSesion($estado) {
    $sistema = APP_SISTEMA;
    
    if ($estado == '7') {
        return ['usuario' => '0', 'nivel' => '0', 'destino' => ''];
    }
    
    if (!isset($_SESSION['us'])) {
        return false;
    }
    
    if (isset($_SESSION['REMOTE_ADDR']) && isset($_SESSION['HTTP_USER_AGENT'])) {
        if ($_SESSION['REMOTE_ADDR'] !== $_SERVER['REMOTE_ADDR'] ||
            $_SESSION['HTTP_USER_AGENT'] !== $_SERVER['HTTP_USER_AGENT']) {
            destruirSesion();
            return false;
        }
    }
    
    return [
        'usuario' => $_SESSION['us'],
        'nivel'   => '0',
        'destino' => $_SESSION['destino'] ?? '',
        'ciclo'   => $_SESSION['ciclo'] ?? date('Y'),
    ];
}

/**
 * Destruir sesión de forma segura
 */
function destruirSesion() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Limpiar datos de autenticación sin destruir la sesión
 * (preserva cap_code del captcha y otros datos temporales)
 */
function clearAuth() {
    unset($_SESSION['us']);
    unset($_SESSION['destino']);
    unset($_SESSION['ciclo']);
    unset($_SESSION['nombres']);
    unset($_SESSION['apellido']);
    unset($_SESSION['ndoc']);
}

/**
 * Logout completo (destruye toda la sesión)
 */
function logOut() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    destruirSesion();
}
