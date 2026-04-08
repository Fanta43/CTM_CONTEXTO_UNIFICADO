<?php
/**
 * ============================================================================
 * POPUPS MANAGER - ctm_presupuestos (refactorizado)
 * ============================================================================
 * Gestiona ventanas popup del módulo. Carga contenido desde ciclo_menus.
 * ============================================================================
 */

$estado = isset($_GET['estado']) ? substr($_GET['estado'], 0, 3) : '';

// ── Cargar infraestructura ───────────────────────────────────────────────────
include_once('includes/functions.php');
include_once(APP_CONFIG . 'session.php');

// ── Resolver contenido dinámico ──────────────────────────────────────────────
$sql_popup = "SELECT * FROM ciclo_menus WHERE id = '$estado';";
$result_popup = sql_do_query($sql_popup, '0', $usu);
$row_popup = sql_do_array($result_popup);
$direcion = $row_popup['DIRECCION'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_TITULO; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- SweetAlert2 (en HEAD) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- CSS del módulo -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="popup-body">
    <div class="container-fluid p-3">
        <?php if ($direcion) include_once $direcion; ?>
    </div>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>