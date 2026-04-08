<?php
/**
 * ============================================================================
 * FUNCIONES UNIFICADAS - Sistema de Tres Vías
 * ============================================================================
 */

if (!defined('ENTO_APP')) {
    define('ENTO_APP', true);
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}
if (!defined('APP_CONFIG')) {
    define('APP_CONFIG', APP_ROOT . 'config' . DIRECTORY_SEPARATOR);
}
if (!defined('APP_INCLUDES')) {
    define('APP_INCLUDES', APP_ROOT . 'includes' . DIRECTORY_SEPARATOR);
}

require_once APP_CONFIG . 'config.php';
require_once APP_CONFIG . 'database.php';
require_once APP_CONFIG . 'session.php';
require_once APP_CONFIG . 'modules.php';

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES DE SANITIZACIÓN
// ═══════════════════════════════════════════════════════════════════════════════

function sani_input($data) {
    if (is_array($data)) {
        return array_map('sani_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return strip_tags($data);
}

function Enmascarar_post($campo) {
    return isset($_POST[$campo]) ? sani_input($_POST[$campo]) : null;
}

function Enmascarar_get($campo) {
    return isset($_GET[$campo]) ? sani_input($_GET[$campo]) : null;
}

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES DE UTILIDADES
// ═══════════════════════════════════════════════════════════════════════════════

function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
        return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Obtener los sistemas disponibles para un usuario
 */
function getSistemasUsuario($usu) {
    // $usu es ndoc, necesitamos obtener el usuarioid
    $sql_uid = "SELECT usuarioid FROM vw_personas WHERE ndoc = '$usu' LIMIT 1;";
    $row_uid = sql_do_array(sql_do_query($sql_uid, '1', $usu));
    $usuarioid = $row_uid['USUARIOID'] ?? $usu;
    
    $sql = "SELECT us.codigo_sistema, s.nombre, sp.nro_priv  
              FROM usu_user_sist us, usu_sistemas s, usu_sist_privilegio sp 
             WHERE us.priv_sist_id = sp.priv_sist_id 
               AND us.codigo_sistema = s.codigo 
               AND s.estadoid = '5'
               AND us.usuarioid = '$usuarioid';";
    $result = sql_do_query($sql, '2', $usu);
    $sistemas = [];
    while ($row = sql_do_array($result)) {
        $sistemas[] = $row;
        $_SESSION[$row['CODIGO_SISTEMA']] = $row['NRO_PRIV'];
    }
    return $sistemas;
}

/**
 * Obtener datos personales del usuario
 */
function getDatosPersonales($usu) {
    $sql = "SELECT nombres, apellido, ndoc FROM vw_personas WHERE ndoc = '$usu';";
    $row = sql_do_array(sql_do_query($sql, '1', $usu));
    if ($row) {
        $_SESSION['nombres'] = $row['NOMBRES'];
        $_SESSION['apellido'] = $row['APELLIDO'];
        $_SESSION['ndoc'] = $row['NDOC'];
    }
    return $row;
}

/**
 * Obtener notificaciones pendientes
 */
function getNotificaciones($usu) {
    $sql = "SELECT notificacionid FROM ento_notificaciones WHERE ndoc = '$usu';";
    return sql_do_array(sql_do_query($sql, '1', $usu));
}

/**
 * Foto del usuario
 */
function getFotoUsuario($usu) {
    $foto = "web/mod/personal/scripts/img_personalpna/img/$usu.jpg";
    if (is_file($foto)) return $foto;
    return "img/sin_imagen.jpg";
}

// ═══════════════════════════════════════════════════════════════════════════════
// COLORES DE SISTEMAS (reemplaza 566 líneas de CSS inline)
// ═══════════════════════════════════════════════════════════════════════════════

function getColorSistema($codigo) {
    return getModuleColor($codigo);
}

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES DE AUTENTICACIÓN
// ═══════════════════════════════════════════════════════════════════════════════

function validacion_usuario_liviano($parametros) {
    $sql = "SELECT nombredeusuario, contrasenia, ndoc 
              FROM vw_personas 
             WHERE nombredeusuario = '" . $parametros['usuario'] . "' 
               AND estadoid = '1';";
    $recusuario = sql_do_query($sql, '1', $parametros['usuario']);
    $dbusuario = sql_do_array($recusuario);
    
    $resultado = [];
    if (!($dbusuario['NOMBREDEUSUARIO'] ?? null)) {
        $resultado['error'] = 'Error_Usuario';
    } elseif ($dbusuario['CONTRASENIA'] != $parametros['password']) {
        $resultado['error'] = 'Error_Password';
    }
    
    if (!isset($resultado['error'])) {
        $resultado = ['usuario' => $dbusuario['NDOC'], 'nivacc' => '1'];
    }
    return $resultado;
}

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES UI HELPERS
// ═══════════════════════════════════════════════════════════════════════════════

function generarAlertJS($titulo, $texto, $tipo = 'success', $redireccion = '') {
    $redirect = $redireccion ? "window.location.replace('$redireccion');" : '';
    return "<script>
        Swal.fire({title:'$titulo',text:'$texto',icon:'$tipo',confirmButtonText:'Continuar'})
        .then(function(){" . $redirect . "});
    </script>";
}
