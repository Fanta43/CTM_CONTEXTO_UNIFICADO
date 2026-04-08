<?php
/**
 * ============================================================================
 * INDEX - Sistema de Tres Vías
 * ============================================================================
 * Marco principal - Arquitectura refactorizada Bootstrap 5
 * ============================================================================
 */
require_once 'includes/functions.php';

$estado = substr($_GET['estado'] ?? '', 0, 8);
if (!$estado) {
    header("location:default.php");
    exit;
}

$sesion = verificarSesion($estado);
if ($sesion === false) {
    header("location:default.php");
    exit;
}

$usu = $sesion['usuario'];
$destino = $sesion['destino'];
$ciclo = $sesion['ciclo'];
if (!isset($_SESSION['ciclo'])) $_SESSION['ciclo'] = date('Y');

$sistema = APP_SISTEMA;
$hoy = date('Y-m-d H:i:s');

if ($estado == '10') {
    include_once 'web/int/salir.php';
    exit;
}

// Datos del usuario
$datosPersonales = getDatosPersonales($usu);
$foto = getFotoUsuario($usu);
$notif = getNotificaciones($usu);
$sistemas = getSistemasUsuario($usu);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="img/ctm_favicon.ico" />

    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Estilos propios -->
    <link href="assets/css/styles.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>

<!-- ═══ TOPBAR ═══════════════════════════════════════════════════════════ -->
<nav class="navbar navbar-dark ento-topbar fixed-top px-3">
    <div class="d-flex align-items-center">
        <!-- Hamburger -->
        <button class="btn btn-outline-light me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            <i class="bi bi-list fs-4"></i>
        </button>
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="index.php?estado=6">
            <img src="img/escudos/escudo_ctm.png" alt="CTM" class="me-2" style="height:40px;">
            <?php echo APP_NAME; ?>
        </a>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <!-- Notificaciones -->
        <a href="index.php?estado=11" class="text-light ento-notification-badge" data-bs-toggle="tooltip" title="Notificaciones">
            <?php if ($notif): ?>
                <i class="bi bi-bell-fill fs-5"></i>
                <span class="badge rounded-pill bg-danger">!</span>
            <?php else: ?>
                <i class="bi bi-bell fs-5"></i>
            <?php endif; ?>
        </a>
        
        <!-- Datos personales -->
        <a href="index.php?estado=12" class="ento-user-info text-decoration-none">
            <img src="<?php echo $foto; ?>" alt="Foto">
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['apellido'] ?? ''); ?></div>
                <div class="user-doc"><?php echo htmlspecialchars($_SESSION['nombres'] ?? ''); ?></div>
            </div>
        </a>
        
        <!-- Salir -->
        <a href="index.php?estado=10" class="btn btn-outline-light btn-sm" data-bs-toggle="tooltip" title="Salir">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</nav>

<!-- ═══ SIDEBAR (Offcanvas) ══════════════════════════════════════════════ -->
<div class="offcanvas offcanvas-start ento-sidebar" tabindex="-1" id="sidebarMenu">
    <div class="offcanvas-body">
        <!-- Home -->
        <a href="index.php?estado=6" class="ento-menu-item <?php echo ($estado == '6') ? 'active' : ''; ?>">
            <img src="img/picto/home.png" alt="Home">
            <span class="menu-label">Home</span>
        </a>
        
        <!-- Sistemas dinámicos -->
        <?php foreach ($sistemas as $sys): ?>
        <a href="index.php?estado=<?php echo $sys['CODIGO_SISTEMA']; ?>" 
           class="ento-menu-item <?php echo ($estado == $sys['CODIGO_SISTEMA']) ? 'active' : ''; ?>">
            <img src="img/picto/<?php echo $sys['CODIGO_SISTEMA']; ?>.png" 
                 alt="<?php echo htmlspecialchars($sys['NOMBRE']); ?>"
                 onerror="this.src='img/picto/home.png'">
            <span class="menu-label"><?php echo htmlspecialchars($sys['NOMBRE']); ?></span>
        </a>
        <?php endforeach; ?>
        
        <!-- Salir -->
        <a href="index.php?estado=10" class="ento-menu-item mt-auto">
            <img src="img/picto/salir.png" alt="Salir">
            <span class="menu-label">Salir</span>
        </a>
    </div>
</div>

<!-- ═══ CONTENIDO PRINCIPAL ══════════════════════════════════════════════ -->
<div class="ento-main">
<?php
    $codigo_sistema = $estado;
    
    if (is_numeric($codigo_sistema)) {
        // Contenido interno (ento_menus)
        $sql3 = "SELECT * FROM ento_menus WHERE id='$codigo_sistema';";
        $result3 = sql_do_query($sql3, '1', $usu);
        $row3 = sql_do_array($result3);
        $direcion = $row3['DIRECCION'] ?? '';
        if (!empty($direcion) && file_exists($direcion)) {
            include_once $direcion;
        }
    } else {
        // Módulo externo (iframe)
        $sql3 = "SELECT pagina_inicio FROM usu_sistemas WHERE codigo='$codigo_sistema';";
        $result3 = sql_do_query($sql3, '2', $usu);
        $row3 = sql_do_array($result3);
        $direcion = $row3['PAGINA_INICIO'] ?? '';
        if (!empty($direcion)) {
            echo '<iframe src="../' . htmlspecialchars($direcion) . '"></iframe>';
        }
    }
?>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>