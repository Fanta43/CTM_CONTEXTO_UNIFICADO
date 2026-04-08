<?php
/**
 * ============================================================================
 * SESIÓN SEGURA - ctm_morebius
 * ============================================================================
 * Validación de sesión para el sistema de inventarios AICO_666.
 * ============================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    session_start();
}

$sistema = 'AICO_666';
date_default_timezone_set("America/Argentina/Buenos_Aires");

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
$usu     = $_SESSION["us"] ?? '0';
$nivel   = $_SESSION[$sistema] ?? '0';
$destino = $_SESSION["destino"] ?? '';
$hoy     = date("Y-m-d H:i:s");
$hoy_completo = date("Y-m-d H:i:s");
$vence   = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") + 365));

// ── Verificar permisos del menú actual ───────────────────────────────────────
if (isset($estado) && $estado) {
    $sql_perm = "SELECT * FROM more_menus WHERE id='" . $estado . "';";
    $result_perm = sql_do_query($sql_perm, '1', $usu);
    $row_perm = sql_do_array($result_perm);

    if ($row_perm && isset($row_perm['NIVACC']) && $row_perm['NIVACC'] > $_SESSION[$sistema]) {
        echo generarAlertJS('No permitido!', 'No posee los privilegios suficientes!', 'error', 'index.php?estado=1');
    }
}
