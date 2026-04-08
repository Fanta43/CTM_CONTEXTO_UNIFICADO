<?php
/**
 * ============================================================================
 * Navbar Bootstrap 5 - Sistema PADE
 * ============================================================================
 * Menú de navegación responsivo generado dinámicamente desde la BD.
 * Reemplaza web/int/menu.php con diseño moderno Bootstrap 5.
 * ============================================================================
 */

// Variables necesarias
$estado = $_GET['estado'] ?? '1';
if (!$estado) $estado = '1';

// Breadcrumb (Ariadna)
?>
<nav class="navbar navbar-expand-lg navbar-pade navbar-dark">
    <div class="container-fluid">
        <!-- Logo / Marca -->
        <a class="navbar-brand d-flex align-items-center" href="index.php?estado=6">
            <i class="bi bi-shield-exclamation me-2"></i>
            <?php echo APP_NAME; ?>
        </a>
        
        <!-- Botón hamburguesa mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPade" 
                aria-controls="navbarPade" aria-expanded="false" aria-label="Menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Menú principal -->
        <div class="collapse navbar-collapse" id="navbarPade">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
<?php
// Menú principal desde la BD
$sql_menu = "SELECT * FROM ciclo_menus WHERE menu='1' AND estado='1' ORDER BY id ASC;";
$result_menu = sql_do_query($sql_menu, '0', $usu);

while ($row_menu = sql_do_array($result_menu)) {
    $menuId = $row_menu['ID'];
    $menuOpcion = $row_menu['OPCION'];
    $menuNivacc = $row_menu['NIVACC'];
    
    // Solo mostrar si el usuario tiene el nivel de acceso requerido
    if ($menuNivacc > $nivel) continue;
    
    // Buscar submenús
    $sql_sub = "SELECT * FROM ciclo_menus WHERE submenu='$menuId' AND estado='1' ORDER BY opcion;";
    $result_sub = sql_do_query($sql_sub, '1', $usu);
    $submenus = [];
    while ($row_sub = sql_do_array($result_sub)) {
        if ($row_sub['NIVACC'] <= $nivel) {
            $submenus[] = $row_sub;
        }
    }
    
    $isActive = ($estado == $menuId) ? ' active' : '';
    
    if (count($submenus) > 0) {
        // Menú con dropdown
        ?>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle<?php echo $isActive; ?>" href="#" role="button" 
               data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo htmlspecialchars($menuOpcion); ?>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="index.php?estado=<?php echo $menuId; ?>">
                    <i class="bi bi-house-door me-2"></i><?php echo htmlspecialchars($menuOpcion); ?>
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <?php foreach ($submenus as $sub): ?>
                <li>
                    <a class="dropdown-item<?php echo ($estado == $sub['ID']) ? ' active' : ''; ?>" 
                       href="index.php?estado=<?php echo $sub['ID']; ?>">
                        <?php echo htmlspecialchars($sub['OPCION']); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php
    } else {
        // Menú simple sin hijos
        ?>
        <li class="nav-item">
            <a class="nav-link<?php echo $isActive; ?>" href="index.php?estado=<?php echo $menuId; ?>">
                <?php echo htmlspecialchars($menuOpcion); ?>
            </a>
        </li>
        <?php
    }
}
?>
            </ul>
            
            <!-- Usuario/nivel se muestra en el footer -->
        </div>
    </div>
</nav>

<!-- Breadcrumb -->
<div class="breadcrumb-pade">
<?php
// Generar breadcrumb desde la BD (Ariadna)
$sql_bread = "WITH RECURSIVE hierarchy AS (
                SELECT ID, OPCION, ESTADO, DIRECCION, SUBMENU, DESCRIPCION, MENU, NIVACC, TIPO
                FROM ciclo_menus WHERE ID = '$estado'
                UNION ALL
                SELECT m.ID, m.OPCION, m.ESTADO, m.DIRECCION, m.SUBMENU, m.DESCRIPCION, m.MENU, m.NIVACC, m.TIPO
                FROM ciclo_menus m JOIN hierarchy h ON m.ID = h.SUBMENU
              ) SELECT * FROM hierarchy ORDER BY SUBMENU;";

$result_bread = sql_do_query($sql_bread, '1', $usu);
$breadcrumbs = [];
$var_ini = '';

while ($row_bread = sql_do_array($result_bread)) {
    if (empty($var_ini)) $var_ini = $row_bread['ID'];
    $breadcrumbs[] = $row_bread;
}
?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php?estado=1"><i class="bi bi-house-door"></i></a></li>
            <?php foreach ($breadcrumbs as $bc): ?>
            <li class="breadcrumb-item<?php echo ($bc['ID'] == $estado) ? ' active' : ''; ?>">
                <?php if ($bc['ID'] != $estado): ?>
                    <a href="index.php?estado=<?php echo $bc['ID']; ?>"><?php echo htmlspecialchars($bc['OPCION']); ?></a>
                <?php else: ?>
                    <?php echo htmlspecialchars($bc['OPCION']); ?>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>
</div>

<?php
// Obtener la dirección del contenido a incluir
$sql_dir = "SELECT * FROM ciclo_menus WHERE id='$estado';";
$result_dir = sql_do_query($sql_dir, '1', $usu);
$row_dir = sql_do_array($result_dir);
$direcion = $row_dir['DIRECCION'] ?? '';
?>
