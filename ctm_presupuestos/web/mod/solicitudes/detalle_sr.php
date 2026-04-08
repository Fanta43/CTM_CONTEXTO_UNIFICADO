<?php
/**
 * ============================================================================
 * Detalle de una Solicitud de Recurso (SR) individual
 * ============================================================================
 * Tabla: pres_k2b_sr_logs
 * Muestra toda la información de una SR: cabecera (datos comunes) y
 * todos los renglones con detalle completo de cada línea.
 * Se accede desde listar.php con ?accion=detalle&sr=CODIGO
 * ============================================================================
 */

$sr_codigo = Enmascarar_get('sr');
if (empty($sr_codigo)) {
    echo '<div class="alert alert-warning m-3"><i class="bi bi-exclamation-triangle me-2"></i>No se especificó código de SR.</div>';
    return;
}

// ── POST: Guardar EETT de un renglón de SR ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_eett_sr']) && $_POST['accion_eett_sr'] === 'guardar') {
    $pk_codigo  = addslashes(trim($_POST['pk_codigo'] ?? ''));
    $pk_mat     = addslashes(trim($_POST['pk_matricula'] ?? ''));
    $pk_lin     = (int)($_POST['pk_lin'] ?? 0);
    $pk_estado  = addslashes(trim($_POST['pk_estado'] ?? ''));

    if ($pk_codigo !== '' && $pk_mat !== '') {
        $eett_fields = [
            'eett_denominacion', 'eett_caracteristicas', 'eett_representacion_grafica',
            'eett_normas_fabricacion', 'eett_ensayo_recepcion', 'eett_responsable_recepcion',
            'eett_marcas', 'eett_aplicacion', 'eett_forma_entrega', 'eett_observaciones',
            'eett_proveedores'
        ];
        $cols = ['codigo', 'matricula', 'lin', 'estado'];
        $vals = ["'$pk_codigo'", "'$pk_mat'", "$pk_lin", "'$pk_estado'"];
        $sets = [];
        foreach ($eett_fields as $ef) {
            $v = addslashes(trim($_POST[$ef] ?? ''));
            $cols[] = $ef;
            $vals[] = "'$v'";
            $sets[] = "$ef = '$v'";
        }
        $justif = addslashes(trim($_POST['justificacion_compra'] ?? ''));
        $cols[] = 'justificacion_compra';
        $vals[] = "'$justif'";
        $sets[] = "justificacion_compra = '$justif'";
        $cols[] = 'auditoria_usu';
        $vals[] = "'$usu'";
        $sets[] = "auditoria_usu = '$usu'";

        sql_do_query("INSERT INTO pres_k2b_sr_logs_eett (" . implode(', ', $cols) . ")
                      VALUES (" . implode(', ', $vals) . ")
                      ON DUPLICATE KEY UPDATE " . implode(', ', $sets), $db, $usu);
    }
}

// ── Cargar todos los renglones de esta SR ───────────────────────────────────
$sql = "SELECT sr.*,
               ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE,
               uo.nombre AS UNIDAD_NOMBRE,
               (sr.precio_unitario * sr.cantidad_autorizada) AS IMPORTE_LINEA,
               cg.nombre AS CAT_NOMBRE
        FROM pres_k2b_sr_logs sr
        LEFT JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
        LEFT JOIN ciclo_unidad_organizativa uo ON sr.esquema_cc = uo.cc
        LEFT JOIN pres_k2b_matricula_categoria mc ON mc.matricula = sr.matricula
        LEFT JOIN ciclo_categoria_gasto cg ON cg.id = mc.id_categoria
        WHERE sr.codigo = '$sr_codigo'
        ORDER BY sr.lin;";
$res = sql_do_query($sql, $db, $usu);

$lineas = [];
while ($r = sql_do_array($res)) {
    $lineas[] = $r;
}

if (empty($lineas)) {
    echo '<div class="alert alert-warning m-3"><i class="bi bi-exclamation-triangle me-2"></i>No se encontraron datos para la SR <strong>' . htmlspecialchars($sr_codigo) . '</strong>.</div>';
    return;
}

// ── Datos de cabecera (comunes a todos los renglones) ───────────────────────
$cab = $lineas[0];
$total_lineas = count($lineas);

// Calcular totales
$total_importe_lineas = 0;
$total_oc_usd = 0;
$total_cant_solicitada = 0;
$total_cant_autorizada = 0;
$total_cant_comprada = 0;
$total_cant_recepcionada = 0;
foreach ($lineas as $l) {
    $total_importe_lineas   += (float)($l['IMPORTE_LINEA'] ?? 0);
    $total_oc_usd           += (float)($l['MONTO_LINEA_OC_USD'] ?? 0);
    $total_cant_solicitada  += (float)($l['CANTIDAD_SOLICITADA'] ?? 0);
    $total_cant_autorizada  += (float)($l['CANTIDAD_AUTORIZADA'] ?? 0);
    $total_cant_comprada    += (float)($l['CANTIDAD_COMPRADA'] ?? 0);
    $total_cant_recepcionada += (float)($l['CANTIDAD_RECEPCIONADA'] ?? 0);
}
// Fallback: si no hay monto_linea_oc_usd, usar importe_total
$total_oc_display = ($total_oc_usd > 0) ? $total_oc_usd : (float)($cab['IMPORTE_TOTAL'] ?? 0);

// Estado → ícono
$est_k2b = $cab['ESTADO'] ?? '';
if ($est_k2b === 'FINALIZADA') $est_icon = '2';
elseif ($est_k2b === 'RECHAZADA') $est_icon = '5';
elseif ($est_k2b === 'CUMPLIDA' || $est_k2b === 'CUMPLIMIENTOSTOCK') $est_icon = '4';
else $est_icon = '3';
if ($est_icon === '3' && (!empty($cab['ORDEN_COMPRA'] ?? '') || $total_oc_usd > 0)) $est_icon = '4';

// Badge color
$badge_class = 'bg-primary';
if ($est_k2b === 'RECHAZADA') $badge_class = 'bg-danger';
elseif ($est_k2b === 'FINALIZADA') $badge_class = 'bg-secondary';
elseif ($est_k2b === 'CUMPLIDA' || $est_k2b === 'CUMPLIMIENTOSTOCK') $badge_class = 'bg-success';
elseif ($est_k2b === 'ENPROCESOCOMPRA' || $est_k2b === 'ENPROCESOAUTORIZACION') $badge_class = 'bg-warning text-dark';
?>

<!-- Botón volver -->
<div class="mb-3">
    <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver al listado
    </a>
</div>

<?php echo generarTituloSeccion('SR ' . htmlspecialchars($sr_codigo), 'bi bi-file-earmark-text'); ?>

<!-- CABECERA DE LA SR -->
<div class="row g-3 mb-4">
    <!-- Info general -->
    <div class="col-md-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información General</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Código SR</div>
                        <div class="fw-bold fs-5"><?php echo htmlspecialchars($sr_codigo); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Estado</div>
                        <div>
                            <img src="img/ico/<?php echo $est_icon; ?>.png" width="20" height="20" class="me-1" />
                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($est_k2b); ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Fecha</div>
                        <div class="fw-semibold"><?php echo enmascararfecha($cab['FECHA'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Usuario</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($cab['USUARIO'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Fecha Entrega Solicitada</div>
                        <div class="fw-semibold"><?php echo enmascararfecha($cab['FECHA_ENTREGA_SOL'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Gestor</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($cab['GESTOR'] ?? '—'); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Ítem de Gasto</div>
                        <div>
                            <span class="fw-bold"><?php echo htmlspecialchars($cab['ITEM_CODIGO'] ?? ''); ?></span>
                            <span class="text-muted mx-1">—</span>
                            <?php echo htmlspecialchars($cab['ITEM_NOMBRE'] ?? ''); ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Unidad</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($cab['UNIDAD_NOMBRE'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Esquema</div>
                        <div class="small text-muted"><?php echo htmlspecialchars($cab['ESQUEMA'] ?? ''); ?></div>
                    </div>
                    <?php if (!empty($cab['OBSERVACION'])): ?>
                    <div class="col-12">
                        <div class="small text-muted">Observación</div>
                        <div class="small"><?php echo nl2br(htmlspecialchars($cab['OBSERVACION'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($cab['DELEGADA']) && $cab['DELEGADA'] !== '0'): ?>
                    <div class="col-md-4">
                        <div class="small text-muted">Delegada</div>
                        <div><span class="badge bg-info text-dark">Sí</span></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Totales -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-info text-dark py-2">
                <h6 class="mb-0"><i class="bi bi-calculator me-2"></i>Resumen</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Renglones</span>
                    <span class="fw-bold"><?php echo $total_lineas; ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Importe Total SR</span>
                    <span class="fw-bold">U$D <?php echo number_format((float)($cab['IMPORTE_TOTAL'] ?? 0), 2, ',', '.'); ?></span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Σ Importe líneas</span>
                    <span class="fw-semibold">U$D <?php echo number_format($total_importe_lineas, 2, ',', '.'); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Σ OC (USD)</span>
                    <span class="fw-semibold text-success">U$D <?php echo number_format($total_oc_display, 2, ',', '.'); ?></span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">Cant. Solicitada</span>
                    <span><?php echo number_format($total_cant_solicitada, 2, ',', '.'); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">Cant. Autorizada</span>
                    <span><?php echo number_format($total_cant_autorizada, 2, ',', '.'); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">Cant. Comprada</span>
                    <span><?php echo number_format($total_cant_comprada, 2, ',', '.'); ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">Cant. Recepcionada</span>
                    <span><?php echo number_format($total_cant_recepcionada, 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RENGLONES (líneas de la SR) -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary bg-opacity-75 text-white py-2">
        <h6 class="mb-0"><i class="bi bi-list-ol me-2"></i>Renglones de la SR — <?php echo $total_lineas; ?> línea<?php echo $total_lineas > 1 ? 's' : ''; ?></h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr class="table-light">
                        <th rowspan="2" class="text-center align-middle">Lín.</th>
                        <th rowspan="2" class="align-middle">Matrícula</th>
                        <th rowspan="2" class="align-middle">Descripción</th>
                        <th rowspan="2" class="align-middle">Categoría</th>
                        <th colspan="3" class="text-center border-bottom-0" style="background:#eef2ff; border-left:2px solid #93a3d1;">Solicitud</th>
                        <th colspan="3" class="text-center border-bottom-0" style="background:#ecfdf5; border-left:2px solid #6ec9a0;">Orden de Compra</th>
                        <th rowspan="2" class="align-middle" style="border-left:2px solid #d1d5db;">Obs.</th>
                    </tr>
                    <tr class="table-light small">
                        <th class="text-end" style="background:#eef2ff; border-left:2px solid #93a3d1;">P. Unit.</th>
                        <th class="text-end" style="background:#eef2ff;">Cant.</th>
                        <th class="text-end" style="background:#eef2ff;">Importe</th>
                        <th class="text-end" style="background:#ecfdf5; border-left:2px solid #6ec9a0;">OC</th>
                        <th class="text-end" style="background:#ecfdf5;">Monto OC</th>
                        <th class="text-end" style="background:#ecfdf5;">Proveedor</th>
                    </tr>
                </thead>
                <tbody>
<?php foreach ($lineas as $i => $l):
    $imp_linea = (float)($l['IMPORTE_LINEA'] ?? 0);
    $moc = (float)($l['MONTO_LINEA_OC_USD'] ?? 0);
?>
                    <tr>
                        <td class="text-center fw-bold"><?php echo ($i + 1); ?></td>
                        <td class="small fw-semibold text-nowrap"><?php echo htmlspecialchars($l['MATRICULA'] ?? ''); ?></td>
                        <td class="small" title="<?php echo htmlspecialchars($l['DESCRIPCION'] ?? ''); ?>">
                            <?php echo htmlspecialchars(mb_substr($l['DESCRIPCION'] ?? '', 0, 80)); ?><?php echo (mb_strlen($l['DESCRIPCION'] ?? '') > 80) ? '...' : ''; ?>
                        </td>
                        <td class="small"><?php echo htmlspecialchars($l['CAT_NOMBRE'] ?? ''); ?></td>
                        <td class="text-end small" style="border-left:2px solid #93a3d1;">$ <?php echo number_format((float)($l['PRECIO_UNITARIO'] ?? 0), 2, ',', '.'); ?></td>
                        <td class="text-end small"><?php echo number_format((float)($l['CANTIDAD_SOLICITADA'] ?? 0), 2, ',', '.'); ?></td>
                        <td class="text-end small fw-semibold">U$D <?php echo number_format($imp_linea, 2, ',', '.'); ?></td>
                        <td class="small text-nowrap" style="border-left:2px solid #6ec9a0;"><?php echo htmlspecialchars($l['ORDEN_COMPRA'] ?? ''); ?></td>
                        <td class="text-end small fw-semibold"><?php echo ($moc > 0) ? 'U$D ' . number_format($moc, 2, ',', '.') : '—'; ?></td>
                        <td class="small"><?php echo htmlspecialchars(mb_substr($l['PROVEEDOR'] ?? '', 0, 30)); ?></td>
                        <td class="small text-muted" style="border-left:2px solid #d1d5db;"><?php echo htmlspecialchars(mb_substr($l['OBSERVACION_LINEA'] ?? $l['OBSERVACION'] ?? '', 0, 40)); ?></td>
                    </tr>
<?php endforeach; ?>
                    <tr class="table-dark fw-bold">
                        <td colspan="4" class="text-end">TOTALES</td>
                        <td class="text-end" style="border-left:2px solid #93a3d1;"></td>
                        <td class="text-end"><?php echo number_format($total_cant_solicitada, 2, ',', '.'); ?></td>
                        <td class="text-end">U$D <?php echo number_format($total_importe_lineas, 2, ',', '.'); ?></td>
                        <td style="border-left:2px solid #6ec9a0;"></td>
                        <td class="text-end">U$D <?php echo number_format($total_oc_usd, 2, ',', '.'); ?></td>
                        <td></td>
                        <td style="border-left:2px solid #d1d5db;"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- PROCESO DE COMPRA (si hay OC/Pedido) -->
<?php
$tiene_compra = false;
foreach ($lineas as $l) {
    if (!empty($l['PEDIDO']) || !empty($l['ORDEN_COMPRA']) || !empty($l['ORDEN_STOCK'])) {
        $tiene_compra = true;
        break;
    }
}

// ── Cargar historial de cambios de estado por línea ─────────────────────────
$historial_por_lin = [];
$sql_hist = "SELECT lin, estado_anterior, estado_nuevo,
                    MIN(fecha_deteccion) AS fecha_cambio
             FROM pres_k2b_sr_estado_log
             WHERE codigo = '$sr_codigo'
             GROUP BY lin, estado_anterior, estado_nuevo
             ORDER BY lin, MIN(fecha_deteccion)";
$res_hist = sql_do_query($sql_hist, $db, $usu);
while ($rh = sql_do_array($res_hist)) {
    $lin_key = (int)($rh['LIN'] ?? 0);
    $historial_por_lin[$lin_key][] = $rh;
}
?>
<?php if ($tiene_compra): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success bg-opacity-75 text-white py-2">
        <h6 class="mb-0"><i class="bi bi-cart-check me-2"></i>Proceso de Compra — Seguimiento por renglón</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr class="table-light">
                        <th rowspan="2" class="text-center align-middle">Lín.</th>
                        <th rowspan="2" class="align-middle">Matrícula</th>
                        <th colspan="2" class="text-center border-bottom-0" style="background:#eef2ff; border-left:2px solid #93a3d1;">SR</th>
                        <th colspan="3" class="text-center border-bottom-0" style="background:#fff7ed; border-left:2px solid #d4a054;">Pedido</th>
                        <th colspan="6" class="text-center border-bottom-0" style="background:#ecfdf5; border-left:2px solid #6ec9a0;">Orden de Compra</th>
                        <th rowspan="2" class="align-middle" style="border-left:2px solid #d1d5db;">Stock</th>
                    </tr>
                    <tr class="table-light small">
                        <th style="background:#eef2ff; border-left:2px solid #93a3d1;">Estado</th>
                        <th style="background:#eef2ff;">F. Entrega Sol.</th>
                        <th style="background:#fff7ed; border-left:2px solid #d4a054;">Pedido</th>
                        <th style="background:#fff7ed;">Est. Línea</th>
                        <th style="background:#fff7ed;">Comprador</th>
                        <th style="background:#ecfdf5; border-left:2px solid #6ec9a0;">OC</th>
                        <th style="background:#ecfdf5;">Est. Línea OC</th>
                        <th style="background:#ecfdf5;">Proveedor</th>
                        <th class="text-end" style="background:#ecfdf5;">Monto (USD)</th>
                        <th style="background:#ecfdf5;">F. Entrega</th>
                        <th style="background:#ecfdf5;">Prórroga</th>
                    </tr>
                </thead>
                <tbody>
<?php foreach ($lineas as $i => $l):
    $moc = (float)($l['MONTO_LINEA_OC_USD'] ?? 0);
    $dias_p = (int)($l['DIAS_PRORROGA'] ?? 0);
    $f_prorroga = $l['FECHA_PRORROGA'] ?? '';
?>
                    <tr>
                        <td class="text-center fw-bold"><?php echo ($i + 1); ?></td>
                        <td class="small fw-semibold text-nowrap"><?php echo htmlspecialchars($l['MATRICULA'] ?? ''); ?></td>
                        <td class="small" style="border-left:2px solid #93a3d1;">
                            <?php
                                $est = $l['ESTADO'] ?? '';
                                $bc = 'bg-primary';
                                if ($est === 'RECHAZADA') $bc = 'bg-danger';
                                elseif ($est === 'FINALIZADA') $bc = 'bg-secondary';
                                elseif ($est === 'CUMPLIDA' || $est === 'CUMPLIMIENTOSTOCK') $bc = 'bg-success';
                                elseif ($est === 'ENPROCESOCOMPRA' || $est === 'ENPROCESOAUTORIZACION') $bc = 'bg-warning text-dark';
                            ?>
                            <span class="badge <?php echo $bc; ?> small"><?php echo htmlspecialchars($est); ?></span>
                        </td>
                        <td class="small"><?php echo enmascararfecha($l['FECHA_ENTREGA_SOL'] ?? ''); ?></td>
                        <td class="small" style="border-left:2px solid #d4a054;"><?php echo htmlspecialchars($l['PEDIDO'] ?? ''); ?></td>
                        <td class="small"><?php echo htmlspecialchars($l['ESTADO_LINEA_PEDIDO'] ?? ''); ?></td>
                        <td class="small"><?php echo htmlspecialchars($l['COMPRADOR_PEDIDO'] ?? ''); ?></td>
                        <td class="small fw-semibold" style="border-left:2px solid #6ec9a0;"><?php echo htmlspecialchars($l['ORDEN_COMPRA'] ?? ''); ?></td>
                        <td class="small"><?php echo htmlspecialchars($l['ESTADO_LINEA_ORDEN_COMPRA'] ?? ''); ?></td>
                        <td class="small"><?php echo htmlspecialchars(mb_substr($l['PROVEEDOR'] ?? '', 0, 30)); ?></td>
                        <td class="text-end small fw-semibold"><?php echo ($moc > 0) ? 'U$D ' . number_format($moc, 2, ',', '.') : '—'; ?></td>
                        <td class="small"><?php echo enmascararfecha($l['FECHA_ENTREGA_FIN'] ?? ''); ?></td>
                        <td class="small"><?php
                            if ($dias_p > 0 || !empty($f_prorroga)) {
                                echo ($dias_p > 0 ? $dias_p . 'd ' : '') . enmascararfecha($f_prorroga);
                            }
                        ?></td>
                        <td class="small" style="border-left:2px solid #d1d5db;"><?php echo htmlspecialchars($l['ORDEN_STOCK'] ?? ''); ?></td>
                    </tr>
<?php
    // Timeline de estados para esta línea
    $lin_num = (int)($l['LIN'] ?? 0);
    $hist = $historial_por_lin[$lin_num] ?? [];
    if (!empty($hist)):
?>
                    <tr>
                        <td style="background:#f9fafb; border-top:0;"></td>
                        <td style="background:#f9fafb; border-top:0;"></td>
                        <td colspan="12" class="p-0" style="background:#f9fafb; border-top:0; border-left:2px solid #93a3d1;">
                            <div class="d-flex align-items-center gap-1 px-2 py-1 flex-wrap" style="font-size:11px;">
                                <i class="bi bi-clock-history text-muted me-1"></i>
<?php foreach ($hist as $hi => $h):
    $est_n = $h['ESTADO_NUEVO'] ?? '';
    $hbc = 'bg-primary';
    if ($est_n === 'RECHAZADA') $hbc = 'bg-danger';
    elseif ($est_n === 'FINALIZADA') $hbc = 'bg-secondary';
    elseif ($est_n === 'CUMPLIDA' || $est_n === 'CUMPLIMIENTOSTOCK') $hbc = 'bg-success';
    elseif ($est_n === 'ENPROCESOCOMPRA' || $est_n === 'ENPROCESOAUTORIZACION') $hbc = 'bg-warning text-dark';
    $fecha_c = $h['FECHA_CAMBIO'] ?? '';
    $fecha_fmt = !empty($fecha_c) ? date('d/m/y', strtotime($fecha_c)) : '';
?>
<?php if ($hi > 0): ?><i class="bi bi-arrow-right text-muted" style="font-size:9px;"></i><?php endif; ?>
                                <span class="badge <?php echo $hbc; ?>" style="font-size:10px; padding:1px 5px;"><?php echo htmlspecialchars($est_n); ?></span>
                                <span class="text-muted"><?php echo $fecha_fmt; ?></span>
<?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
<?php endif; ?>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- DATOS DE TRAZABILIDAD -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-secondary bg-opacity-50 py-2">
        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Trazabilidad</h6>
    </div>
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-3">
                <span class="text-muted">Fecha Log:</span>
                <span class="fw-semibold"><?php echo enmascararfecha($cab['FECHA_LOG'] ?? ''); ?></span>
            </div>
            <div class="col-md-3">
                <span class="text-muted">Esquema:</span>
                <span class="fw-semibold"><?php echo htmlspecialchars($cab['ESQUEMA'] ?? ''); ?></span>
            </div>
            <div class="col-md-3">
                <span class="text-muted">Período:</span>
                <span class="fw-semibold"><?php echo htmlspecialchars($cab['ESQUEMA_ANIO'] ?? ''); ?></span>
            </div>
            <div class="col-md-3">
                <span class="text-muted">CC:</span>
                <span class="fw-semibold"><?php echo htmlspecialchars($cab['ESQUEMA_CC'] ?? ''); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- EETT Y JUSTIFICACIÓN POR RENGLÓN DE SR -->
<?php
// Cargar EETT de cada renglón desde pres_k2b_sr_logs_eett
$sr_code_esc = addslashes($sr_codigo);
$eett_por_row = [];
$res_eett_rows = sql_do_query("SELECT * FROM pres_k2b_sr_logs_eett WHERE codigo = '$sr_code_esc'", $db, $usu);
while ($re = sql_do_array($res_eett_rows)) {
    $rkey = $re['CODIGO'] . '|' . $re['MATRICULA'] . '|' . $re['LIN'] . '|' . $re['ESTADO'];
    $eett_por_row[$rkey] = $re;
}

// Cargar EETT de matrículas como fallback
$mats_unicas = [];
foreach ($lineas as $l) {
    $m = trim($l['MATRICULA'] ?? '');
    if ($m !== '') $mats_unicas[$m] = 1;
}
$eett_mat_fallback = [];
if (!empty($mats_unicas)) {
    $mats_esc = array_map(function($m) { return "'" . addslashes($m) . "'"; }, array_keys($mats_unicas));
    $res_fb = sql_do_query("SELECT * FROM pres_k2b_matricula_categoria WHERE matricula IN (" . implode(',', $mats_esc) . ")", $db, $usu);
    while ($rf = sql_do_array($res_fb)) {
        $eett_mat_fallback[$rf['MATRICULA']] = $rf;
    }
}

$eett_campos_sr = [
    'eett_denominacion'           => ['label' => 'Denominación del Material',    'type' => 'textarea', 'rows' => 2],
    'eett_caracteristicas'        => ['label' => 'Características Nominales',    'type' => 'textarea', 'rows' => 3],
    'eett_representacion_grafica' => ['label' => 'Representación Gráfica',       'type' => 'textarea', 'rows' => 2],
    'eett_normas_fabricacion'     => ['label' => 'Normas de Fabricación',        'type' => 'textarea', 'rows' => 2],
    'eett_ensayo_recepcion'       => ['label' => 'Ensayo para la Recepción',     'type' => 'textarea', 'rows' => 2],
    'eett_responsable_recepcion'  => ['label' => 'Responsable de la Recepción',  'type' => 'input',    'rows' => 1],
    'eett_marcas'                 => ['label' => 'Marcas',                       'type' => 'textarea', 'rows' => 2],
    'eett_aplicacion'             => ['label' => 'Aplicación',                   'type' => 'textarea', 'rows' => 2],
    'eett_forma_entrega'          => ['label' => 'Forma de Entrega',             'type' => 'textarea', 'rows' => 2],
    'eett_observaciones'          => ['label' => 'Observaciones',                'type' => 'textarea', 'rows' => 2],
    'eett_proveedores'            => ['label' => 'Proveedores',                  'type' => 'textarea', 'rows' => 2],
];

$rows_con_eett = 0;
foreach ($lineas as $l) {
    $rkey = ($l['CODIGO'] ?? '') . '|' . ($l['MATRICULA'] ?? '') . '|' . ($l['LIN'] ?? '') . '|' . ($l['ESTADO'] ?? '');
    if (!empty(($eett_por_row[$rkey] ?? [])['EETT_DENOMINACION'] ?? '')) $rows_con_eett++;
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark bg-opacity-75 text-white py-2 d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#collapseEETTSR">
        <h6 class="mb-0">
            <i class="bi bi-file-earmark-ruled me-2"></i>Especificaciones Técnicas (EETT) por Renglón
            <span class="badge bg-info ms-2" style="font-size:0.65rem;"><?php echo $rows_con_eett; ?>/<?php echo $total_lineas; ?> con EETT</span>
        </h6>
        <i class="bi bi-chevron-down"></i>
    </div>
    <div class="collapse" id="collapseEETTSR">
        <div class="card-body p-2">
<?php if (empty($lineas)): ?>
            <div class="text-center text-muted py-3">No hay renglones en esta SR.</div>
<?php else: ?>
            <div class="accordion" id="accEETTSR">
<?php foreach ($lineas as $i => $l):
    $row_codigo  = $l['CODIGO'] ?? '';
    $row_mat     = $l['MATRICULA'] ?? '';
    $row_lin     = $l['LIN'] ?? 0;
    $row_estado  = $l['ESTADO'] ?? '';
    $row_desc    = $l['DESCRIPCION'] ?? '';
    $rkey = $row_codigo . '|' . $row_mat . '|' . $row_lin . '|' . $row_estado;
    $acc_id = 'eettRow_' . ($i + 1);

    // EETT: primero del renglón, luego fallback de matrícula
    $eett_data = $eett_por_row[$rkey] ?? [];
    $tiene_eett_row = !empty($eett_data['EETT_DENOMINACION'] ?? '');
    $from_fallback = false;
    if (!$tiene_eett_row && !empty($eett_mat_fallback[$row_mat] ?? [])) {
        $eett_data = $eett_mat_fallback[$row_mat];
        $from_fallback = !empty($eett_data['EETT_DENOMINACION'] ?? '');
    }
    $tiene_justif = !empty($eett_data['JUSTIFICACION_COMPRA'] ?? '');
?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#col_<?php echo $acc_id; ?>">
                            <span class="badge bg-secondary me-2">Lín. <?php echo ($i + 1); ?></span>
                            <span class="fw-bold me-2"><?php echo htmlspecialchars($row_mat); ?></span>
                            <span class="small text-muted me-2"><?php echo htmlspecialchars(mb_substr($row_desc, 0, 50)); ?></span>
                            <?php if ($tiene_eett_row): ?>
                                <span class="badge bg-success" style="font-size:0.6rem;"><i class="bi bi-check-lg"></i> EETT</span>
                            <?php elseif ($from_fallback): ?>
                                <span class="badge bg-warning text-dark" style="font-size:0.6rem;"><i class="bi bi-arrow-repeat"></i> EETT (matrícula)</span>
                            <?php else: ?>
                                <span class="badge bg-light text-danger border" style="font-size:0.6rem;"><i class="bi bi-exclamation-triangle"></i> Sin EETT</span>
                            <?php endif; ?>
                            <?php if ($tiene_justif): ?>
                                <span class="badge bg-info ms-1" style="font-size:0.6rem;"><i class="bi bi-file-text"></i> Justif.</span>
                            <?php endif; ?>
                        </button>
                    </h2>
                    <div id="col_<?php echo $acc_id; ?>" class="accordion-collapse collapse" data-bs-parent="#accEETTSR">
                        <div class="accordion-body">
                            <?php if ($from_fallback): ?>
                                <div class="alert alert-warning small py-1 px-2 mb-2">
                                    <i class="bi bi-info-circle me-1"></i>Pre-cargado desde la matrícula <strong><?php echo htmlspecialchars($row_mat); ?></strong>. Modifique según corresponda a este renglón y guarde.
                                </div>
                            <?php endif; ?>
                            <form method="post" action="index.php?estado=<?php echo $estado; ?>&accion=detalle&sr=<?php echo urlencode($sr_codigo); ?>">
                                <input type="hidden" name="accion_eett_sr" value="guardar">
                                <input type="hidden" name="pk_codigo" value="<?php echo htmlspecialchars($row_codigo); ?>">
                                <input type="hidden" name="pk_matricula" value="<?php echo htmlspecialchars($row_mat); ?>">
                                <input type="hidden" name="pk_lin" value="<?php echo (int)$row_lin; ?>">
                                <input type="hidden" name="pk_estado" value="<?php echo htmlspecialchars($row_estado); ?>">

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold mb-0 small"><i class="bi bi-file-earmark-ruled me-1"></i>EETT — Lín. <?php echo ($i+1); ?> — <?php echo htmlspecialchars($row_mat); ?></h6>
                                    <div class="d-flex gap-1">
                                        <?php if ($tiene_eett_row || $from_fallback): ?>
                                        <a href="web/mod/solicitudes/imprimir_eett.php?tipo=sr&codigo=<?php echo urlencode($row_codigo); ?>&matricula=<?php echo urlencode($row_mat); ?>&lin=<?php echo (int)$row_lin; ?>&estado=<?php echo urlencode($row_estado); ?>"
                                           target="_blank" class="btn btn-outline-dark btn-sm">
                                            <i class="bi bi-printer me-1"></i>Imprimir
                                        </a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="iaCompletarEETTSR('<?php echo addslashes($row_mat); ?>', '<?php echo addslashes($row_desc); ?>', '<?php echo $acc_id; ?>')">
                                            <i class="bi bi-robot me-1"></i>Auto-completar con IA
                                        </button>
                                    </div>
                                </div>
                                <div id="iaMsg_<?php echo $acc_id; ?>" class="d-none mb-2"></div>
                                <div class="row g-2">
                                    <?php foreach ($eett_campos_sr as $campo_key => $campo_cfg):
                                        $val = htmlspecialchars($eett_data[strtoupper($campo_key)] ?? '');
                                    ?>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small mb-1"><?php echo $campo_cfg['label']; ?></label>
                                        <?php if ($campo_cfg['type'] === 'input'): ?>
                                            <input type="text" class="form-control form-control-sm" name="<?php echo $campo_key; ?>"
                                                   id="<?php echo $acc_id; ?>_<?php echo $campo_key; ?>"
                                                   value="<?php echo $val; ?>" placeholder="<?php echo $campo_cfg['label']; ?>">
                                        <?php else: ?>
                                            <textarea class="form-control form-control-sm" name="<?php echo $campo_key; ?>"
                                                      id="<?php echo $acc_id; ?>_<?php echo $campo_key; ?>"
                                                      rows="<?php echo $campo_cfg['rows']; ?>" placeholder="<?php echo $campo_cfg['label']; ?>"><?php echo $val; ?></textarea>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <hr class="my-2">

                                <h6 class="fw-bold mb-2 small"><i class="bi bi-justify-left me-1"></i>Justificación de Compra</h6>
                                <textarea class="form-control form-control-sm" name="justificacion_compra"
                                          id="<?php echo $acc_id; ?>_justificacion_compra"
                                          rows="3" placeholder="Justificación de compra..."><?php echo htmlspecialchars($eett_data['JUSTIFICACION_COMPRA'] ?? ''); ?></textarea>

                                <div class="d-flex justify-content-end mt-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-floppy me-1"></i>Guardar EETT — Lín. <?php echo ($i+1); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
<?php endforeach; ?>
            </div>
<?php endif; ?>
        </div>
    </div>
</div>

<script>
function iaCompletarEETTSR(matricula, descripcion, accId) {
    var msgEl = document.getElementById('iaMsg_' + accId);
    msgEl.className = 'alert alert-info small py-1 px-2 mb-2';
    msgEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Consultando IA para ' + matricula + ' — ' + descripcion.substring(0, 40) + '...';

    var fd = new FormData();
    fd.append('matricula', matricula);
    fd.append('descripcion_renglon', descripcion);

    fetch('web/mod/configuracion/matricula_eett_ia_ajax.php', {
        method: 'POST',
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.ok) {
            msgEl.className = 'alert alert-danger small py-1 px-2 mb-2';
            msgEl.textContent = 'Error: ' + (data.error || 'Sin respuesta');
            return;
        }

        var campos = data.campos || {};
        var keys = [
            'eett_denominacion', 'eett_caracteristicas', 'eett_representacion_grafica',
            'eett_normas_fabricacion', 'eett_ensayo_recepcion', 'eett_responsable_recepcion',
            'eett_marcas', 'eett_aplicacion', 'eett_forma_entrega', 'eett_observaciones',
            'eett_proveedores', 'justificacion_compra'
        ];
        var filled = 0;
        keys.forEach(function(k) {
            var el = document.getElementById(accId + '_' + k);
            if (el && campos[k]) {
                el.value = campos[k];
                el.style.borderColor = '#198754';
                filled++;
            }
        });

        msgEl.className = 'alert alert-success small py-1 px-2 mb-2';
        msgEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + filled + ' campos completados con IA (' + (data.modelo || '') + '). Revise y haga clic en Guardar.';
    })
    .catch(function(err) {
        msgEl.className = 'alert alert-danger small py-1 px-2 mb-2';
        msgEl.textContent = 'Error de conexión: ' + err.message;
    });
}
</script>
