<?php
/**
 * ============================================================================
 * Resumen/Detalle de PC - ctm_inventarios v2
 * ============================================================================
 */

$codigopc = Enmascarar_get('codigopc');
if (!$codigopc) $codigopc = Enmascarar_post('codigopc');
if (!$codigopc) { echo generarAlertJS('Error', 'No se especificó un equipo.', 'error', 'index.php?estado=20'); return; }

$tab = $_GET['tab'] ?? '1';

// ── Cargar datos del equipo ──────────────────────────────────────────────────
$rowhanterior = consultaInventario($codigopc, '1', $usu);
if (!$rowhanterior) { echo generarAlertJS('No encontrado', 'El equipo no existe.', 'warning', 'index.php?estado=20'); return; }

// ── SO ───────────────────────────────────────────────────────────────────────
$so_nombre = '';
$so_id = $rowhanterior['ID_SISTEMAOPERATIVO'] ?? 0;
if ($so_id && $so_id != 'null') {
    $sqlso = "SELECT sistema FROM inve_sistemaoperativo WHERE sistemaoperativoid = '$so_id' AND estadoid='1';";
    $rowso = sql_do_array(sql_do_query($sqlso, '1', $usu));
    $so_nombre = $rowso['SISTEMA'] ?? '';
}

// ── Persona asignada ─────────────────────────────────────────────────────────
$usuario = consultaPersona($rowhanterior['LEGAJO'], '1', $usu);
$cc_nombre = getCentroCosto($rowhanterior['CENTROCOSTO'], '1', $usu);

// ── Datos para el cargador (POST actions) ────────────────────────────────────
include 'cargador.php';

// Recargar datos post-save
$rowhanterior = consultaInventario($codigopc, '1', $usu);
?>

<?php echo generarTituloSeccion('Detalle: ' . strtoupper($rowhanterior['CODIGOPC']), 'bi bi-pc-display'); ?>

<div class="row g-3 mb-4">
    <!-- Info card -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información General</h6>
            </div>
            <div class="card-body">
                <?php
                $img_so = file_exists("img/so/{$so_id}.png") ? "img/so/{$so_id}.png" : "img/so/0.png";
                ?>
                <div class="text-center mb-3">
                    <img src="<?php echo $img_so; ?>" alt="SO" style="height:60px;" class="mb-2">
                    <h5 class="mb-0"><?php echo strtoupper($rowhanterior['CODIGOPC']); ?></h5>
                    <small class="text-muted"><?php echo htmlspecialchars($rowhanterior['MARCA']); ?></small>
                </div>
                <hr>
                <ul class="list-unstyled small">
                    <li class="mb-2"><i class="bi bi-cpu me-2 text-primary"></i><strong>SO:</strong> <?php echo htmlspecialchars($so_nombre); ?></li>
                    <li class="mb-2"><i class="bi bi-calendar me-2 text-primary"></i><strong>Compra:</strong> <?php echo enmascararFecha($rowhanterior['FECHACOMPRA']); ?></li>
                    <li class="mb-2"><i class="bi bi-receipt me-2 text-primary"></i><strong>SR:</strong> <?php echo htmlspecialchars($rowhanterior['SR']); ?></li>
                    <li class="mb-2"><i class="bi bi-file-earmark me-2 text-primary"></i><strong>OC:</strong> <?php echo htmlspecialchars($rowhanterior['OC']); ?></li>
                    <li class="mb-2"><i class="bi bi-currency-dollar me-2 text-primary"></i><strong>Monto:</strong> <?php echo htmlspecialchars($rowhanterior['MONTO']); ?></li>
                    <li class="mb-2"><i class="bi bi-building me-2 text-primary"></i><strong>CC:</strong> <?php echo htmlspecialchars($cc_nombre); ?></li>
                    <li class="mb-2"><i class="bi bi-person me-2 text-primary"></i><strong>Asignado:</strong> <?php echo htmlspecialchars(($usuario['APELLIDO'] ?? '') . ', ' . ($usuario['NOMBRES'] ?? '')); ?></li>
                    <li class="mb-2"><i class="bi bi-globe me-2 text-primary"></i><strong>IP:</strong> <?php echo htmlspecialchars($rowhanterior['IP']); ?></li>
                    <li class="mb-2"><i class="bi bi-ethernet me-2 text-primary"></i><strong>MAC:</strong> <?php echo htmlspecialchars($rowhanterior['MAC']); ?></li>
                    <li class="mb-2"><i class="bi bi-upc-scan me-2 text-primary"></i><strong>Serie:</strong> <?php echo htmlspecialchars($rowhanterior['NUMEROSERIE']); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <?php
                    // Cargar tabs desde inve_menus (submenu = estado actual)
                    $sql_tabs = "SELECT * FROM inve_menus WHERE menu='20' AND submenu='$estado' AND estado='1' ORDER BY id;";
                    $r_tabs = sql_do_query($sql_tabs, '1', $usu);
                    $tabs = [];
                    while ($rt = sql_do_array($r_tabs)) {
                        if ($rt['NIVACC'] <= $nivel) $tabs[] = $rt;
                    }
                    // Si no hay tabs configurados, mostrar tabs por defecto
                    if (empty($tabs)) {
                        $tabs = [
                            ['ID' => '1', 'OPCION' => 'Datos Obligatorios', 'DIRECCION' => 'web/mod/inventario/pc/resumen/obligatorios.php'],
                        ];
                    }
                    foreach ($tabs as $i => $t):
                        $tabId = $t['ID'];
                        $isActive = ($tab == $tabId || ($i == 0 && $tab == '1' && $tabId != '1'));
                    ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
                           href="<?php echo getCurrentUrlWithTab($tabId, $codigopc); ?>">
                            <?php echo htmlspecialchars($t['OPCION']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card-body">
                <?php
                // Encontrar la dirección del tab activo
                $tabDir = '';
                foreach ($tabs as $t) {
                    if ($tab == $t['ID']) { $tabDir = $t['DIRECCION']; break; }
                }
                if (!$tabDir && !empty($tabs)) $tabDir = $tabs[0]['DIRECCION'];

                if ($tabDir && file_exists($tabDir)) {
                    include $tabDir;
                } else {
                    // Fallback: mostrar datos obligatorios inline
                    include 'web/mod/inventario/pc/resumen/obligatorios_v2.php';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <a href="index.php?estado=20" class="btn btn-pade-alt"><i class="bi bi-arrow-left me-1"></i>Volver al listado</a>
</div>
