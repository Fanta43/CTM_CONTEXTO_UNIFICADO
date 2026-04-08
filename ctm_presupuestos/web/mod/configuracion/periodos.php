<?php
/**
 * ============================================================================
 * Períodos Fiscales - ABM
 * ============================================================================
 * ID menú: 101 | Tabla: ciclo_periodo_fiscal
 * Alta/Baja/Modificación de ejercicios fiscales.
 * ============================================================================
 */

$db = '1';
$accion   = Enmascarar_get('accion');
$id       = Enmascarar_get('id');
$msg      = '';

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post = Enmascarar_post('accion_post');

    // ── CREAR ────────────────────────────────────────────────────────────────
    if ($accion_post === 'crear') {
        $anio         = Enmascarar_post('anio');
        $fecha_inicio = Enmascarar_post('fecha_inicio');
        $fecha_fin    = Enmascarar_post('fecha_fin');

        // Validar fecha_fin > fecha_inicio
        if ($fecha_fin <= $fecha_inicio) {
            $msg = generarAlertJS('Error', 'La fecha fin debe ser posterior a la fecha inicio.', 'error');
        } else {
            // Validar año único
            $sql_check = "SELECT id FROM ciclo_periodo_fiscal WHERE anio = '$anio';";
            $res_check = sql_do_query($sql_check, $db, $usu);
            if (sql_do_array($res_check)) {
                $msg = generarAlertJS('Error', "Ya existe un período para el año $anio.", 'error');
            } else {
                // Validar que no haya otro período abierto
                $sql_abierto = "SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto';";
                $res_abierto = sql_do_query($sql_abierto, $db, $usu);
                if (sql_do_array($res_abierto)) {
                    $msg = generarAlertJS('Atención', 'Ya existe un período abierto. Ciérrelo antes de crear uno nuevo.', 'warning');
                } else {
                    $sql_ins = "INSERT INTO ciclo_periodo_fiscal (anio, fecha_inicio, fecha_fin, estado, auditoria_usu)
                                VALUES ('$anio', '$fecha_inicio', '$fecha_fin', 'abierto', '$usu');";
                    sql_do_query($sql_ins, $db, $usu);
                    $msg = generarAlertJS('Período creado', "Ejercicio fiscal $anio creado correctamente.", 'success', "index.php?estado=$estado");
                }
            }
        }
    }

    // ── EDITAR ───────────────────────────────────────────────────────────────
    if ($accion_post === 'editar') {
        $edit_id      = Enmascarar_post('edit_id');
        $fecha_inicio = Enmascarar_post('fecha_inicio');
        $fecha_fin    = Enmascarar_post('fecha_fin');

        if ($fecha_fin <= $fecha_inicio) {
            $msg = generarAlertJS('Error', 'La fecha fin debe ser posterior a la fecha inicio.', 'error');
        } else {
            // Solo editar si está abierto
            $sql_est = "SELECT estado FROM ciclo_periodo_fiscal WHERE id = '$edit_id';";
            $row_est = sql_do_array(sql_do_query($sql_est, $db, $usu));
            if ($row_est['ESTADO'] !== 'abierto') {
                $msg = generarAlertJS('Error', 'Solo se pueden modificar períodos en estado abierto.', 'error');
            } else {
                $sql_upd = "UPDATE ciclo_periodo_fiscal SET fecha_inicio = '$fecha_inicio', fecha_fin = '$fecha_fin',
                            auditoria_usu = '$usu' WHERE id = '$edit_id';";
                sql_do_query($sql_upd, $db, $usu);
                $msg = generarAlertJS('Actualizado', 'El período fue actualizado correctamente.', 'success', "index.php?estado=$estado");
            }
        }
    }

    // ── CAMBIAR ESTADO ───────────────────────────────────────────────────────
    if ($accion_post === 'cambiar_estado') {
        $cambio_id     = Enmascarar_post('cambio_id');
        $nuevo_estado  = Enmascarar_post('nuevo_estado');

        $sql_actual = "SELECT estado FROM ciclo_periodo_fiscal WHERE id = '$cambio_id';";
        $row_actual = sql_do_array(sql_do_query($sql_actual, $db, $usu));
        $estado_actual = $row_actual['ESTADO'] ?? '';

        if ($estado_actual === 'cerrado') {
            $msg = generarAlertJS('Error', 'No se puede reabrir un período cerrado.', 'error');
        } elseif ($nuevo_estado === 'abierto') {
            // Validar que no haya otro abierto
            $sql_otro = "SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto' AND id != '$cambio_id';";
            if (sql_do_array(sql_do_query($sql_otro, $db, $usu))) {
                $msg = generarAlertJS('Error', 'Ya existe otro período abierto.', 'warning');
            } else {
                sql_do_query("UPDATE ciclo_periodo_fiscal SET estado = '$nuevo_estado', auditoria_usu = '$usu' WHERE id = '$cambio_id';", $db, $usu);
                $msg = generarAlertJS('Estado cambiado', "El período fue cambiado a '$nuevo_estado'.", 'success', "index.php?estado=$estado");
            }
        } else {
            sql_do_query("UPDATE ciclo_periodo_fiscal SET estado = '$nuevo_estado', auditoria_usu = '$usu' WHERE id = '$cambio_id';", $db, $usu);
            $msg = generarAlertJS('Estado cambiado', "El período fue cambiado a '$nuevo_estado'.", 'success', "index.php?estado=$estado");
        }
    }
}

echo $msg;

// ═══════════════════════════════════════════════════════════════════════════════
// VISTA: FORMULARIO NUEVO / EDITAR
// ═══════════════════════════════════════════════════════════════════════════════
if ($accion === 'nuevo' || $accion === 'editar'):
    $row = null;
    if ($accion === 'editar' && $id) {
        $sql_edit = "SELECT * FROM ciclo_periodo_fiscal WHERE id = '$id';";
        $row = sql_do_array(sql_do_query($sql_edit, $db, $usu));
        if (!$row) { echo generarAlertJS('Error', 'Período no encontrado.', 'error', "index.php?estado=$estado"); return; }
    }
    $titulo_form = ($accion === 'nuevo') ? 'Nuevo Período Fiscal' : 'Editar Período Fiscal';
?>

<?php echo generarTituloSeccion($titulo_form, 'bi bi-calendar-range'); ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="<?php echo ($accion === 'nuevo') ? 'crear' : 'editar'; ?>">
            <?php if ($row): ?>
                <input type="hidden" name="edit_id" value="<?php echo $row['ID']; ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Año <span class="text-danger">*</span></label>
                    <?php if ($accion === 'nuevo'): ?>
                        <input type="number" class="form-control" name="anio" min="2020" max="2050"
                               value="<?php echo date('Y'); ?>" required>
                    <?php else: ?>
                        <input type="text" class="form-control" value="<?php echo $row['ANIO']; ?>" disabled>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Fecha Inicio <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="fecha_inicio"
                           value="<?php echo $row['FECHA_INICIO'] ?? date('Y') . '-01-01'; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Fecha Fin <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="fecha_fin"
                           value="<?php echo $row['FECHA_FIN'] ?? date('Y') . '-12-31'; ?>" required>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-pade"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
// ═══════════════════════════════════════════════════════════════════════════════
// VISTA: LISTADO
// ═══════════════════════════════════════════════════════════════════════════════
else:
    $sql_list = "SELECT * FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
    $result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Períodos Fiscales', 'bi bi-calendar-range'); ?>

<?php if ($nivel >= 6): ?>
<div class="mb-3">
    <a href="index.php?estado=<?php echo $estado; ?>&accion=nuevo" class="btn btn-pade">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Período
    </a>
</div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Año', 'Fecha Inicio', 'Fecha Fin', 'Estado', 'Acciones']); ?>
        <tbody>
<?php
    $n = 0;
    while ($row = sql_do_array($result_list)):
        $n++;
        $estado_badge = '';
        switch ($row['ESTADO']) {
            case 'abierto':    $estado_badge = '<span class="badge bg-success">Abierto</span>'; break;
            case 'en_cierre':  $estado_badge = '<span class="badge bg-warning text-dark">En Cierre</span>'; break;
            case 'cerrado':    $estado_badge = '<span class="badge bg-secondary">Cerrado</span>'; break;
            default:           $estado_badge = '<span class="badge bg-light text-dark">' . htmlspecialchars($row['ESTADO']) . '</span>';
        }
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold"><?php echo htmlspecialchars($row['ANIO']); ?></td>
                <td class="text-center"><?php echo enmascararfecha($row['FECHA_INICIO']); ?></td>
                <td class="text-center"><?php echo enmascararfecha($row['FECHA_FIN']); ?></td>
                <td class="text-center"><?php echo $estado_badge; ?></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <?php if ($nivel >= 6 && $row['ESTADO'] === 'abierto'): ?>
                            <a href="index.php?estado=<?php echo $estado; ?>&accion=editar&id=<?php echo $row['ID']; ?>"
                               class="btn btn-pade-alt btn-sm btn-accion" title="Editar">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        <?php endif; ?>
                        <?php if ($nivel >= 9 && $row['ESTADO'] !== 'cerrado'): ?>
                            <?php
                            $opciones_estado = [];
                            if ($row['ESTADO'] === 'abierto')   $opciones_estado = ['en_cierre' => 'Pasar a En Cierre'];
                            if ($row['ESTADO'] === 'en_cierre') $opciones_estado = ['cerrado' => 'Cerrar Período', 'abierto' => 'Reabrir'];
                            foreach ($opciones_estado as $nvo => $txt):
                            ?>
                                <?php echo generarBotonForm(
                                    '<i class="bi bi-arrow-repeat me-1"></i>' . $txt,
                                    "index.php?estado=$estado",
                                    ['accion_post' => 'cambiar_estado', 'cambio_id' => $row['ID'], 'nuevo_estado' => $nvo],
                                    'btn btn-outline-secondary btn-sm'
                                ); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No hay períodos fiscales registrados.</td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
