<?php
/**
 * ============================================================================
 * SESIÓN SEGURA - ctm_presupuestos
 * ============================================================================
 * Migrado desde conf/seccion.php usando herramientas_mejora
 * Usa SweetAlert2 en lugar de swal v1.
 * ============================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    session_start();
}

$sistema = 'CTM_061';

// ── Validar sesión ───────────────────────────────────────────────────────────
$sesionValida = true;
if (!isset($_SESSION["us"]) && !isset($_SESSION[$sistema]) && (!isset($_GET['estado']) || $_GET['estado'] != '7')) {
    $sesionValida = false;
}
if (isset($_SESSION['REMOTE_ADDR']) && $_SESSION['REMOTE_ADDR'] != $_SERVER['REMOTE_ADDR']) {
    $sesionValida = false;
}
if (isset($_SESSION['HTTP_USER_AGENT']) && $_SESSION['HTTP_USER_AGENT'] != $_SERVER['HTTP_USER_AGENT']) {
    $sesionValida = false;
}

if (!$sesionValida) {
    $_SESSION["us"] = '0';
    $_SESSION["nivel"] = '0';
    header("location:../ctm_entorno/default.php");
    exit();
}

// ── Variables de sesión ──────────────────────────────────────────────────────
$usu = $_SESSION["us"] ?? '0';
$nivel = $_SESSION[$sistema] ?? '0';
$destino = $_SESSION["destino"] ?? '882';
$hoy = date("Y-m-d H:i:s");

// ── Rol efectivo del sistema ─────────────────────────────────────────────────
// $_SESSION[$sistema] = nivel máximo del usuario (del login, NO se modifica)
// $_SESSION['ciclo_rol_efectivo'] = rol elegido por el usuario (puede bajar, nunca subir)

// Cambiar rol efectivo vía POST (desde el footer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol_efectivo'])) {
    $nuevo_rol_code = sani_input($_POST['cambiar_rol_efectivo']);
    // Validar que el rol exista y su nivel_jerarquico <= nivel máximo del usuario
    $sql_val_rol = "SELECT codigo, nivel_jerarquico FROM ciclo_rol WHERE codigo = '$nuevo_rol_code' AND activo = 1;";
    $row_val_rol = sql_do_array(sql_do_query($sql_val_rol, '1', $usu));
    if ($row_val_rol && (int)$row_val_rol['NIVEL_JERARQUICO'] <= (int)$nivel) {
        $_SESSION['ciclo_rol_efectivo'] = $nuevo_rol_code;
    }
}

// Inicializar rol efectivo si no existe
if (empty($_SESSION['ciclo_rol_efectivo'])) {
    // Buscar el rol más alto que no supere el nivel del usuario
    $sql_rol_init = "SELECT codigo FROM ciclo_rol WHERE activo = 1 AND nivel_jerarquico <= '$nivel'
                     ORDER BY nivel_jerarquico DESC LIMIT 1;";
    $row_rol_init = sql_do_array(sql_do_query($sql_rol_init, '1', $usu));
    $_SESSION['ciclo_rol_efectivo'] = $row_rol_init['CODIGO'] ?? 'operador';
}

// Exponer variables globales del rol efectivo
$rol_usuario = $_SESSION['ciclo_rol_efectivo'];
$sql_rol_eff = "SELECT nombre, nivel_jerarquico FROM ciclo_rol WHERE codigo = '$rol_usuario' AND activo = 1;";
$row_rol_eff = sql_do_array(sql_do_query($sql_rol_eff, '1', $usu));
$rol_usuario_nombre = $row_rol_eff['NOMBRE'] ?? ucfirst($rol_usuario);
$nivel_rol_efectivo = (int)($row_rol_eff['NIVEL_JERARQUICO'] ?? 1);

// Cargar roles disponibles para el switcher del footer (nivel <= nivel máximo del usuario)
$roles_disponibles = [];
$sql_roles_disp = "SELECT codigo, nombre, nivel_jerarquico FROM ciclo_rol WHERE activo = 1 AND nivel_jerarquico <= '$nivel'
                   ORDER BY nivel_jerarquico;";
$res_roles_disp = sql_do_query($sql_roles_disp, '1', $usu);
while ($rd = sql_do_array($res_roles_disp)) {
    $roles_disponibles[] = $rd;
}

// ── Verificar permisos del menú actual ───────────────────────────────────────
if (isset($estado) && $estado) {
    $sql_perm = "SELECT * FROM ciclo_menus WHERE id='" . $estado . "';";
    $result_perm = sql_do_query($sql_perm, '1', $usu);
    $row_perm = sql_do_array($result_perm);
    
    if ($row_perm && isset($row_perm['NIVACC']) && $row_perm['NIVACC'] > $_SESSION[$sistema]) {
        echo generarAlertJS('No permitido!', 'No posee los privilegios suficientes!', 'error', 'index.php?estado=1');
    }
}

// ── Estructura organizacional del usuario ────────────────────────────────────
$sql_est = "SELECT e.estructuraid, p.organicaid 
              FROM usu_estructuras e, usu_organicas o, vw_personas p 
             WHERE p.organicaid = o.organicaid 
               AND o.estructuraid = e.estructuraid 
               AND p.usuarioid = '$usu';";
$result_est = sql_do_query($sql_est, '1', $usu);
$estructura_principal = sql_do_array($result_est);
$est_estructuraid = $estructura_principal['ESTRUCTURAID'] ?? '';
$est_organicaid = $estructura_principal['ORGANICAID'] ?? '';
