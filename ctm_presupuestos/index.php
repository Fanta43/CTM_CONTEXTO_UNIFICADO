<?php
/**
 * ============================================================================
 * INDEX - ctm_presupuestos (refactorizado)
 * ============================================================================
 * Punto de entrada del módulo. Usa nueva infraestructura de includes/.
 * Carga dentro de iframe desde ctm_entorno.
 * ============================================================================
 */

// ── Estado de navegación ─────────────────────────────────────────────────────
$estado = isset($_GET['estado']) ? substr($_GET['estado'], 0, 3) : '';

// ── Cargar infraestructura ───────────────────────────────────────────────────
include_once('includes/functions.php');

// ── Sin estado → redirigir al entorno ────────────────────────────────────────
if (!$estado) {
    header("location:../ctm_entorno/default.php");
    exit();
}

// ── Logout ───────────────────────────────────────────────────────────────────
if ($_GET['estado'] == '10') {
    if (session_status() === PHP_SESSION_ACTIVE) { session_destroy(); }
    header("location:../ctm_entorno/default.php");
    exit();
}

// ── Sesión y permisos ────────────────────────────────────────────────────────
include_once(APP_CONFIG . 'session.php');

// ── Resolver contenido dinámico desde ciclo_menus ─────────────────────────────
$sql_dir = "SELECT * FROM ciclo_menus WHERE id = '$estado';";
$result_dir = sql_do_query($sql_dir, '1', $usu);
$row_dir = sql_do_array($result_dir);
$direcion = $row_dir['DIRECCION'] ?? 'web/int/presentacion.php';
$nombre_pag = $row_dir['OPCION'] ?? '';

// ── HTML ─────────────────────────────────────────────────────────────────────
include_once(APP_INCLUDES . 'header.php');
include_once(APP_INCLUDES . 'navbar.php');
?>

<div class="main-content fade-in">
    <div class="container-fluid">
        <div class="row">
            <main class="col-12">
                <?php include_once $direcion; ?>
            </main>
        </div>
    </div>
</div>

<?php include_once(APP_INCLUDES . 'footer.php'); ?>
