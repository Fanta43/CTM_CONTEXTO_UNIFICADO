<?php
/**
 * ============================================================================
 * INDEX v2 - ctm_inventarios
 * ============================================================================
 * Punto de entrada principal del módulo reingeniado.
 * ============================================================================
 */

require_once 'includes/functions.php';
require_once APP_CONFIG . 'session.php';

// ── Cargar layout ────────────────────────────────────────────────────────────
require_once APP_INCLUDES . 'header.php';
require_once APP_INCLUDES . 'navbar.php';
?>

<main class="main-content fade-in">
<?php
    if ($direcion && file_exists($direcion)) {
        include $direcion;
    } else {
        include 'web/mod/inicio/dashboard.php';
    }
?>
</main>

<?php require_once APP_INCLUDES . 'footer.php'; ?>
