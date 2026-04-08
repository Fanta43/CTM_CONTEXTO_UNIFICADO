<?php
/**
 * ============================================================================
 * Aprobar Solicitudes
 * ============================================================================
 * ID menú: 403 | Tablas: ciclo_solicitud_recurso, ciclo_solicitud_partida, ciclo_usuario
 * Bandeja del jefe/gerente para aprobar o rechazar solicitudes de su equipo.
 * El aprobador no puede ser el mismo que el solicitante.
 * ============================================================================
 */

$db  = '1';
$msg = '';

$unidad_usuario = $_SESSION['id_unidad'] ?? '';
$rol_usuario    = $_SESSION['rol'] ?? '';

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post = Enmascarar_post('accion_post');
    $sol_id      = Enmascarar_post('sol_id');
    $observaciones = Enmascarar_post('observaciones');

    // Validar solicitud pendiente
    $sql_sol = "SELECT * FROM ciclo_solicitud_recurso WHERE id = '$sol_id' AND estado = 'pendiente';";
    $row_sol = sql_do_array(sql_do_query($sql_sol, $db, $usu));

    if (!$row_sol) {
        $msg = generarAlertJS('Error', 'La solicitud no existe o no está pendiente.', 'error');
    } elseif ($row_sol['ID_USUARIO_SOLICITA'] == $usu) {
        $msg = generarAlertJS('Error', 'No puede aprobar/rechazar sus propias solicitudes.', 'error');
    } else {

        // ── APROBAR ──────────────────────────────────────────────────────────
        if ($accion_post === 'aprobar') {
            sql_do_query("UPDATE ciclo_solicitud_recurso
                          SET estado = 'aprobada', id_usuario_aprueba = '$usu', fecha_aprobacion = NOW(), auditoria_usu = '$usu'
                          WHERE id = '$sol_id';", $db, $usu);
            $msg = generarAlertJS('Aprobada', 'La solicitud #' . $sol_id . ' fue aprobada.', 'success', "index.php?estado=$estado");
        }

        // ── RECHAZAR ─────────────────────────────────────────────────────────
        if ($accion_post === 'rechazar') {
            // Liberar comprometido
            $sql_lin = "SELECT id_partida, monto FROM ciclo_solicitud_partida WHERE id_solicitud = '$sol_id';";
            $res_lin = sql_do_query($sql_lin, $db, $usu);
            while ($rl = sql_do_array($res_lin)) {
                $mto = (float)$rl['MONTO'];
                $pid = $rl['ID_PARTIDA'];
                $row_pa = sql_do_array(sql_do_query("SELECT monto_comprometido FROM ciclo_partida WHERE id = '$pid';", $db, $usu));
                $nuevo_comp = max(0, (float)($row_pa['MONTO_COMPROMETIDO'] ?? 0) - $mto);
                sql_do_query("UPDATE ciclo_partida SET monto_comprometido = '$nuevo_comp' WHERE id = '$pid';", $db, $usu);
            }

            sql_do_query("UPDATE ciclo_solicitud_recurso
                          SET estado = 'rechazada', id_usuario_aprueba = '$usu', observaciones_rechazo = '$observaciones', auditoria_usu = '$usu'
                          WHERE id = '$sol_id';", $db, $usu);
            $msg = generarAlertJS('Rechazada', 'La solicitud #' . $sol_id . ' fue rechazada y el monto comprometido liberado.', 'info', "index.php?estado=$estado");
        }
    }
}

echo $msg;

// ── Filtro de unidades según rol ─────────────────────────────────────────────
$where_unidad = '';
if (in_array($rol_usuario, ['jefe_unidad']) && !empty($unidad_usuario)) {
    $where_unidad = "AND sr.id_unidad = '$unidad_usuario'";
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_unidad = "AND (sr.id_unidad = '$unidad_usuario'
                     OR sr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
}

$sql_list = "SELECT sr.*, u.nombre AS SOLICITANTE, uo.nombre AS UNIDAD
             FROM ciclo_solicitud_recurso sr
             JOIN ciclo_usuario u ON sr.id_usuario_solicita = u.id
             JOIN ciclo_unidad_organizativa uo ON sr.id_unidad = uo.id
             WHERE sr.estado = 'pendiente'
             $where_unidad
             ORDER BY sr.fecha_solicitud ASC;";
$result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Aprobar Solicitudes', 'bi bi-clipboard-check'); ?>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Nro', 'Solicitante', 'Unidad', 'Fecha', 'Descripción', 'Monto', 'Acciones']); ?>
        <tbody>
<?php
$n = 0;
while ($row = sql_do_array($result_list)):
    $n++;
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold">#<?php echo $row['ID']; ?></td>
                <td><?php echo htmlspecialchars($row['SOLICITANTE']); ?></td>
                <td class="text-center small"><?php echo htmlspecialchars($row['UNIDAD']); ?></td>
                <td class="text-center small"><?php echo enmascararfecha($row['FECHA_SOLICITUD'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['DESCRIPCION'] ?? ''); ?></td>
                <td class="text-end fw-bold">$ <?php echo number_format((float)$row['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-pade-alt btn-sm"
                                onclick="toggleDetalle(<?php echo $row['ID']; ?>)" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </button>
                        <?php echo generarBotonForm(
                            '<i class="bi bi-check-lg me-1"></i>Aprobar',
                            "index.php?estado=$estado",
                            ['accion_post' => 'aprobar', 'sol_id' => $row['ID']],
                            'btn btn-success btn-sm'
                        ); ?>
                        <button type="button" class="btn btn-outline-danger btn-sm"
                                onclick="mostrarRechazo(<?php echo $row['ID']; ?>)" title="Rechazar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <!-- Detalle expandible -->
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
            <!-- Formulario de rechazo inline -->
            <tr id="rechazo_<?php echo $row['ID']; ?>" style="display:none;">
                <td colspan="7" class="bg-light p-3">
                    <form method="post" action="index.php?estado=<?php echo $estado; ?>">
                        <input type="hidden" name="accion_post" value="rechazar">
                        <input type="hidden" name="sol_id" value="<?php echo $row['ID']; ?>">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Motivo del rechazo</label>
                                <textarea class="form-control form-control-sm" name="observaciones" rows="2"
                                          placeholder="Indique el motivo del rechazo..." required></textarea>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-danger btn-sm w-100">
                                    <i class="bi bi-x-circle me-1"></i>Confirmar Rechazo
                                </button>
                            </div>
                        </div>
                    </form>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-check-circle me-2"></i>No hay solicitudes pendientes de aprobación.
            </td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function toggleDetalle(id) {
    var row = document.getElementById('detalle_' + id);
    row.style.display = (row.style.display === 'none') ? '' : 'none';
}
function mostrarRechazo(id) {
    var row = document.getElementById('rechazo_' + id);
    row.style.display = (row.style.display === 'none') ? '' : 'none';
}
</script>
