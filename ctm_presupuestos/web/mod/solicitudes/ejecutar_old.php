<?php
/**
 * ============================================================================
 * Registrar Ejecución
 * ============================================================================
 * ID menú: 404 | Tablas: ciclo_solicitud_recurso, ciclo_solicitud_partida, ciclo_partida
 * Confirma el pago efectivo de solicitudes aprobadas.
 * Mueve monto de comprometido a ejecutado. Verifica umbrales post-ejecución.
 * ============================================================================
 */

$db  = '1';
$msg = '';

$unidad_usuario = $_SESSION['id_unidad'] ?? '';
$rol_usuario    = $_SESSION['rol'] ?? '';

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — EJECUTAR
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'ejecutar') {

    $sol_id    = Enmascarar_post('sol_id');
    $numero_oc = Enmascarar_post('numero_oc');

    // Validar solicitud aprobada
    $sql_sol = "SELECT * FROM ciclo_solicitud_recurso WHERE id = '$sol_id' AND estado = 'aprobada';";
    $row_sol = sql_do_array(sql_do_query($sql_sol, $db, $usu));

    if (!$row_sol) {
        $msg = generarAlertJS('Error', 'La solicitud no existe o no está aprobada.', 'error');
    } else {
        // Mover comprometido → ejecutado por cada línea
        $sql_lin = "SELECT id_partida, monto FROM ciclo_solicitud_partida WHERE id_solicitud = '$sol_id';";
        $res_lin = sql_do_query($sql_lin, $db, $usu);
        $alertas = 0;

        while ($rl = sql_do_array($res_lin)) {
            $mto = (float)$rl['MONTO'];
            $pid = $rl['ID_PARTIDA'];

            // Control: comprometido no negativo
            $row_pa = sql_do_array(sql_do_query(
                "SELECT monto_comprometido, monto_ejecutado, monto_aprobado FROM ciclo_partida WHERE id = '$pid';", $db, $usu));
            $comp_actual = (float)($row_pa['MONTO_COMPROMETIDO'] ?? 0);
            $ejec_actual = (float)($row_pa['MONTO_EJECUTADO'] ?? 0);
            $apro_actual = (float)($row_pa['MONTO_APROBADO'] ?? 0);

            $nuevo_comp = max(0, $comp_actual - $mto);
            $nuevo_ejec = $ejec_actual + $mto;

            sql_do_query("UPDATE ciclo_partida SET monto_comprometido = '$nuevo_comp', monto_ejecutado = '$nuevo_ejec',
                          auditoria_usu = '$usu' WHERE id = '$pid';", $db, $usu);

            // Alerta si ejecutado supera aprobado
            if ($apro_actual > 0 && $nuevo_ejec > $apro_actual) {
                $pct_sobre = round(($nuevo_ejec / $apro_actual) * 100, 2);
                sql_do_query("INSERT INTO ciclo_alerta_consumo (id_partida, id_umbral, porcentaje_actual, fecha, resuelta, observaciones)
                              VALUES ('$pid', NULL, '$pct_sobre', NOW(), 0, 'Ejecución supera monto aprobado');", $db, $usu);
                $alertas++;
            }

            // Verificar umbrales configurados
            $row_pct = sql_do_array(sql_do_query(
                "SELECT porcentaje_consumo FROM v_saldo_partida WHERE partida_id = '$pid';", $db, $usu));
            $pct = (float)($row_pct['PORCENTAJE_CONSUMO'] ?? 0);

            $res_umb = sql_do_query("SELECT * FROM ciclo_config_umbral WHERE activo = 1 ORDER BY porcentaje;", $db, $usu);
            while ($ru = sql_do_array($res_umb)) {
                if ($pct >= (float)$ru['PORCENTAJE']) {
                    $umb_id = $ru['ID'];
                    $sql_al_check = "SELECT id FROM ciclo_alerta_consumo
                                     WHERE id_partida = '$pid' AND id_umbral = '$umb_id' AND resuelta = 0;";
                    if (!sql_do_array(sql_do_query($sql_al_check, $db, $usu))) {
                        sql_do_query("INSERT INTO ciclo_alerta_consumo (id_partida, id_umbral, porcentaje_actual, fecha, resuelta)
                                      VALUES ('$pid', '$umb_id', '$pct', NOW(), 0);", $db, $usu);
                        $alertas++;
                    }
                }
            }
        }

        // Actualizar solicitud
        $oc_val = empty($numero_oc) ? '' : $numero_oc;
        sql_do_query("UPDATE ciclo_solicitud_recurso
                      SET estado = 'ejecutada', fecha_ejecucion = NOW(), numero_oc = '$oc_val', auditoria_usu = '$usu'
                      WHERE id = '$sol_id';", $db, $usu);

        $msg_extra = ($alertas > 0) ? " Se generaron $alertas alertas de umbral." : '';
        $msg = generarAlertJS('Ejecutada', "Solicitud #$sol_id ejecutada correctamente.$msg_extra", 'success', "index.php?estado=$estado");
    }
}

echo $msg;

// ── Filtro por rol ───────────────────────────────────────────────────────────
$where_unidad = '';
if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
    $where_unidad = "AND sr.id_unidad = '$unidad_usuario'";
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_unidad = "AND (sr.id_unidad = '$unidad_usuario'
                     OR sr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
}

$sql_list = "SELECT sr.*, u.nombre AS SOLICITANTE, uo.nombre AS UNIDAD
             FROM ciclo_solicitud_recurso sr
             JOIN ciclo_usuario u ON sr.id_usuario_solicita = u.id
             JOIN ciclo_unidad_organizativa uo ON sr.id_unidad = uo.id
             WHERE sr.estado = 'aprobada'
             $where_unidad
             ORDER BY sr.fecha_aprobacion ASC;";
$result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Registrar Ejecución', 'bi bi-cash-stack'); ?>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Nro', 'Solicitante', 'Unidad', 'Descripción', 'Monto', 'Fecha Aprob.', 'Acciones']); ?>
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
                <td><?php echo htmlspecialchars($row['DESCRIPCION'] ?? ''); ?></td>
                <td class="text-end fw-bold">$ <?php echo number_format((float)$row['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center small"><?php echo enmascararfecha($row['FECHA_APROBACION'] ?? ''); ?></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-pade-alt btn-sm"
                                onclick="toggleDetalle(<?php echo $row['ID']; ?>)" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-success btn-sm"
                                onclick="mostrarEjecutar(<?php echo $row['ID']; ?>)" title="Ejecutar">
                            <i class="bi bi-cash-stack me-1"></i>Ejecutar
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
                    <table class="table table-sm mb-0">
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
                </td>
            </tr>
            <!-- Formulario de ejecución inline -->
            <tr id="ejecutar_<?php echo $row['ID']; ?>" style="display:none;">
                <td colspan="7" class="bg-light p-3">
                    <form method="post" action="index.php?estado=<?php echo $estado; ?>">
                        <input type="hidden" name="accion_post" value="ejecutar">
                        <input type="hidden" name="sol_id" value="<?php echo $row['ID']; ?>">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Número de Orden de Compra (opcional)</label>
                                <input type="text" class="form-control form-control-sm" name="numero_oc"
                                       placeholder="Ej: OC-2026-001">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-check2-all me-1"></i>Confirmar Ejecución
                                </button>
                            </div>
                        </div>
                    </form>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-check-circle me-2"></i>No hay solicitudes aprobadas pendientes de ejecución.
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
function mostrarEjecutar(id) {
    var row = document.getElementById('ejecutar_' + id);
    row.style.display = (row.style.display === 'none') ? '' : 'none';
}
</script>
