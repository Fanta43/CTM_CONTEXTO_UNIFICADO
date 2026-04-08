<?php
/**
 * ============================================================================
 * Mis Solicitudes
 * ============================================================================
 * ID menú: 402 | Tablas: ciclo_solicitud_recurso, ciclo_solicitud_partida, ciclo_periodo_fiscal
 * Listado personal de solicitudes del usuario logueado.
 * Permite anular solicitudes pendientes (libera monto comprometido).
 * ============================================================================
 */

$db  = '1';
$msg = '';

$filtro_estado = Enmascarar_get('filtro_estado');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['filtro_estado'])) {
    $filtro_estado = Enmascarar_post('filtro_estado');
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — ANULAR
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'anular') {
    $sol_id = Enmascarar_post('sol_id');

    // Validar que es del usuario y está pendiente
    $sql_check = "SELECT id FROM ciclo_solicitud_recurso
                  WHERE id = '$sol_id' AND id_usuario_solicita = '$usu' AND estado = 'pendiente';";
    if (!sql_do_array(sql_do_query($sql_check, $db, $usu))) {
        $msg = generarAlertJS('Error', 'Solo puede anular solicitudes propias en estado pendiente.', 'error');
    } else {
        // Liberar comprometido por cada línea
        $sql_lin = "SELECT id_partida, monto FROM ciclo_solicitud_partida WHERE id_solicitud = '$sol_id';";
        $res_lin = sql_do_query($sql_lin, $db, $usu);
        while ($rl = sql_do_array($res_lin)) {
            $mto = (float)$rl['MONTO'];
            $pid = $rl['ID_PARTIDA'];
            // Control: no dejar negativo
            $row_pa = sql_do_array(sql_do_query("SELECT monto_comprometido FROM ciclo_partida WHERE id = '$pid';", $db, $usu));
            $comp_actual = (float)($row_pa['MONTO_COMPROMETIDO'] ?? 0);
            $nuevo_comp = max(0, $comp_actual - $mto);
            sql_do_query("UPDATE ciclo_partida SET monto_comprometido = '$nuevo_comp' WHERE id = '$pid';", $db, $usu);
        }

        sql_do_query("UPDATE ciclo_solicitud_recurso SET estado = 'anulada', auditoria_usu = '$usu' WHERE id = '$sol_id';", $db, $usu);
        $msg = generarAlertJS('Anulada', 'La solicitud fue anulada y el monto comprometido liberado.', 'success', "index.php?estado=$estado");
    }
}

echo $msg;

// ── Filtros ──────────────────────────────────────────────────────────────────
$where_estado = '';
if (!empty($filtro_estado) && $filtro_estado !== 'null') {
    $where_estado = "AND sr.estado = '$filtro_estado'";
}

$sql_list = "SELECT sr.*, pf.anio
             FROM ciclo_solicitud_recurso sr
             JOIN ciclo_periodo_fiscal pf ON sr.id_periodo = pf.id
             WHERE sr.id_usuario_solicita = '$usu'
             $where_estado
             ORDER BY sr.fecha_solicitud DESC;";
$result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Mis Solicitudes', 'bi bi-card-list'); ?>

<!-- FILTROS -->
<div class="row mb-3 align-items-end">
    <div class="col-md-3">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <label class="form-label small fw-semibold mb-1">Estado</label>
            <select class="form-select" name="filtro_estado" onchange="this.form.submit()">
                <option value="null">Todos</option>
                <option value="pendiente" <?php echo ($filtro_estado === 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                <option value="aprobada" <?php echo ($filtro_estado === 'aprobada') ? 'selected' : ''; ?>>Aprobada</option>
                <option value="ejecutada" <?php echo ($filtro_estado === 'ejecutada') ? 'selected' : ''; ?>>Ejecutada</option>
                <option value="rechazada" <?php echo ($filtro_estado === 'rechazada') ? 'selected' : ''; ?>>Rechazada</option>
                <option value="anulada" <?php echo ($filtro_estado === 'anulada') ? 'selected' : ''; ?>>Anulada</option>
            </select>
        </form>
    </div>
    <div class="col-md-3">
        <a href="index.php?estado=401" class="btn btn-pade btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Nueva Solicitud
        </a>
    </div>
</div>

<!-- TABLA -->
<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Nro', 'Período', 'Fecha', 'Descripción', 'Monto Total', 'Estado', 'Acciones']); ?>
        <tbody>
<?php
$n = 0;
while ($row = sql_do_array($result_list)):
    $n++;
    $estado_badge = '';
    switch ($row['ESTADO']) {
        case 'pendiente': $estado_badge = '<span class="badge bg-warning text-dark">Pendiente</span>'; break;
        case 'aprobada':  $estado_badge = '<span class="badge bg-success">Aprobada</span>'; break;
        case 'ejecutada': $estado_badge = '<span class="badge bg-primary">Ejecutada</span>'; break;
        case 'rechazada': $estado_badge = '<span class="badge bg-danger">Rechazada</span>'; break;
        case 'anulada':   $estado_badge = '<span class="badge bg-secondary">Anulada</span>'; break;
    }
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold">#<?php echo $row['ID']; ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['ANIO']); ?></td>
                <td class="text-center small"><?php echo enmascararfecha($row['FECHA_SOLICITUD'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['DESCRIPCION'] ?? ''); ?></td>
                <td class="text-end">$ <?php echo number_format((float)$row['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center"><?php echo $estado_badge; ?></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-pade-alt btn-sm btn-accion"
                                onclick="toggleDetalle(<?php echo $row['ID']; ?>)" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </button>
                        <?php if ($row['ESTADO'] === 'pendiente'): ?>
                            <?php echo generarBotonForm(
                                '<i class="bi bi-x-circle me-1"></i>Anular',
                                "index.php?estado=$estado",
                                ['accion_post' => 'anular', 'sol_id' => $row['ID']],
                                'btn btn-outline-danger btn-sm'
                            ); ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <!-- Fila de detalle expandible -->
            <tr id="detalle_<?php echo $row['ID']; ?>" style="display:none;">
                <td colspan="7" class="bg-light p-3">
                    <?php
                    $sql_det = "SELECT sp.*, ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE
                                FROM ciclo_solicitud_partida sp
                                JOIN ciclo_partida pa ON sp.id_partida = pa.id
                                JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                                WHERE sp.id_solicitud = '{$row['ID']}';";
                    $res_det = sql_do_query($sql_det, $db, $usu);
                    ?>
                    <table class="table table-sm mb-2">
                        <thead class="table-light"><tr><th>Código</th><th>Ítem</th><th class="text-end">Monto</th><th>Detalle</th></tr></thead>
                        <tbody>
                        <?php while ($rd = sql_do_array($res_det)): ?>
                            <tr>
                                <td class="fw-bold small"><?php echo htmlspecialchars($rd['ITEM_CODIGO']); ?></td>
                                <td class="small"><?php echo htmlspecialchars($rd['ITEM_NOMBRE']); ?></td>
                                <td class="text-end small">$ <?php echo number_format((float)$rd['MONTO'], 2, ',', '.'); ?></td>
                                <td class="small text-muted"><?php echo htmlspecialchars($rd['DESCRIPCION_LINEA'] ?? ''); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php if (!empty($row['ESPECIFICACIONES'])): ?>
                    <p class="small mb-1"><strong>Especificaciones:</strong> <?php echo htmlspecialchars($row['ESPECIFICACIONES']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($row['JUSTIFICACION'])): ?>
                    <p class="small mb-0"><strong>Justificación:</strong> <?php echo htmlspecialchars($row['JUSTIFICACION']); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No tiene solicitudes registradas.</td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function toggleDetalle(id) {
    var row = document.getElementById('detalle_' + id);
    row.style.display = (row.style.display === 'none') ? '' : 'none';
}
</script>
