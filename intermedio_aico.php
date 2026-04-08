<?php
/**
 * ============================================================================
 * Intermediario de Autenticación
 * ============================================================================
 * Flujo: default.php (POST) → intermedio_aico.php → index.php?estado=6
 *
 * Autenticación:
 *   1) WS SOAP (CTMServiciosUDM) - método principal (producción)
 *   2) validacion_usuario_liviano() - fallback local (desarrollo/sin WS)
 *
 * Variables de sesión establecidas:
 *   $_SESSION['us']              - Usuario autenticado
 *   $_SESSION['destino']         - Destino (= usuario)
 *   $_SESSION['REMOTE_ADDR']     - IP para anti-suplantación
 *   $_SESSION['HTTP_USER_AGENT'] - User-Agent para anti-suplantación
 * ============================================================================
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cargar funciones (session.php inicia sesión automáticamente, preservando cap_code)
require_once('ctm_entorno/includes/functions.php');

// Fingerprint de seguridad
$_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
$_SESSION['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];

// ── Función helper: error con SweetAlert2 ───────────────────────────────────
function loginError($titulo, $texto, $tipo = 'error') {
    echo '<!DOCTYPE html><html><head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head><body><script>
    Swal.fire({
        title: "' . addslashes($titulo) . '",
        text: "' . addslashes($texto) . '",
        icon: "' . $tipo . '",
        confirmButtonText: "Reintentar",
        confirmButtonColor: "#3c8dbc"
    }).then(function() {
        window.location = "ctm_entorno/default.php";
    });
    </script></body></html>';
    exit;
}

// ── Autenticación por Web Service SOAP ──────────────────────────────────────
function validacion_usuario_ws($parametros) {
    $url_ws = "https://sg-db.saltogrande.org/CTMServiciosUDM/ServiciosUDM.svc?wsdl";

    try {
        $client = new SoapClient($url_ws);
        $parametros['Legajo'] = $parametros['usuario'];
        $usuario_prueba = intval($parametros['usuario']);

        // Si es numérico (legajo), resolver usuario AD
        if (is_numeric($usuario_prueba)) {
            $response = (array) $client->__soapCall('FindLegajoGetUsuarioAD', array($parametros));
            $resultadoWS = $response['FindLegajoGetUsuarioADResult'] ?? '';
            if ($resultadoWS) {
                $parametros['usuario'] = $resultadoWS;
            }
        }

        // Autenticar contra el WS
        $response = (array) $client->__soapCall('WsAutenticado', array($parametros));
        $res = $response['WsAutenticadoResult'] ?? false;

        return $res;
    } catch (Exception $e) {
        // WS no disponible, retornar false para intentar fallback
        return false;
    }
}

// ── Procesar login ──────────────────────────────────────────────────────────
if (!Enmascarar_post('ingresar')) {
    header("Location: ctm_entorno/default.php");
    exit;
}

// Validar captcha (descomentar cuando esté habilitado en default.php)
/*
$captcha_input = Enmascarar_post('captcha');
$cap_code = $_SESSION['cap_code'] ?? '';
if ($captcha_input != $cap_code) {
    loginError('Error de Captcha', 'El código captcha ingresado no es correcto.', 'warning');
}
*/

// Validar usuario
$usuario = Enmascarar_post('usuario');
if (!$usuario) {
    loginError('Datos Incompletos', 'Debe ingresar su usuario para continuar.', 'warning');
}

$password = Enmascarar_post('password');

// ── Intento 1: Autenticación por WS SOAP ────────────────────────────────────
$parametros_ws = [
    'usuario' => $usuario,
    'pwd'     => $password,
];

$res_ws = validacion_usuario_ws($parametros_ws);

if ($res_ws) {
    // WS OK → sesión con el usuario ingresado (igual que old)
    $_SESSION['us'] = $usuario;
    $_SESSION['destino'] = $usuario;
    header("Location: ctm_entorno/index.php?estado=6");
    exit;
}

// ── Intento 2: Fallback a validación local (BD) ─────────────────────────────
$parametros_local = [
    'usuario'  => $usuario,
    'password' => $password,
];

$res_local = validacion_usuario_liviano($parametros_local);

if (!empty($res_local['usuario'])) {
    $_SESSION['us'] = $res_local['usuario'];
    $_SESSION['destino'] = $res_local['usuario'];
    header("Location: ctm_entorno/index.php?estado=6");
    exit;
}

// ── Ambos métodos fallaron ───────────────────────────────────────────────────
loginError('Error de Acceso', 'Usuario o contraseña incorrectos. Verifique sus datos e intente nuevamente.');
?>