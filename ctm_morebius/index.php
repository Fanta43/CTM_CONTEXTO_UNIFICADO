<?php
/**
 * ============================================================================
 * INDEX - ctm_morebius v2.0
 * ============================================================================
 * Punto de entrada. Carga la infraestructura moderna y renderiza el módulo
 * seleccionado dinámicamente desde more_menus.
 * ============================================================================
 */

$estado = isset($_GET['estado']) ? substr($_GET['estado'], 0, 4) : '';

include_once('includes/functions.php');

if (!$estado) {
    header("location:../ctm_entorno/default.php");
    exit();
}

// Destruyo variables de sesión si se solicita salir
if ($estado == '10') {
    include_once('web/int/salir.php');
    exit();
}

// Cargar sesión segura (valida permisos, carga $usu, $nivel, etc.)
include_once('config/session.php');
?>
<?php include_once('includes/header.php'); ?>
<?php include_once('includes/navbar.php'); ?>

<div class="main-content">
    <?php
    if (!empty($direcion) && file_exists($direcion)) {
        include_once $direcion;
    } else {
        echo '<div class="alert alert-warning mt-3"><i class="bi bi-exclamation-triangle me-2"></i>Página no encontrada o sin contenido asignado.</div>';
    }
    ?>
</div>

<?php include_once('includes/footer.php'); ?>
