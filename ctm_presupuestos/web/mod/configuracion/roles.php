<?php
/**
 * ============================================================================
 * Roles del Sistema - ABM
 * ============================================================================
 * ID menú: 108 | Tabla: ciclo_rol
 * Columnas: id, codigo, nombre, descripcion, nivel_jerarquico, activo,
 *           auditoria_usu, auditoria_fecha
 * Gestión de roles asignables a usuarios. El código es inmutable después de creado.
 * ============================================================================
 */

$db = '1';
$accion = Enmascarar_get('accion');
$id     = Enmascarar_get('id');
$msg    = '';

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post = Enmascarar_post('accion_post');

    // ── CREAR ────────────────────────────────────────────────────────────────
    if ($accion_post === 'crear') {
        $codigo           = Enmascarar_post('codigo');
        $nombre           = Enmascarar_post('nombre');
        $descripcion      = Enmascarar_post('descripcion');
        $nivel_jerarquico = Enmascarar_post('nivel_jerarquico');

        if (empty($codigo) || empty($nombre)) {
            $msg = generarAlertJS('Error', 'El código y el nombre son obligatorios.', 'error');
        } else {
            $sql_dup = "SELECT id FROM ciclo_rol WHERE codigo = '$codigo';";
            if (sql_do_array(sql_do_query($sql_dup, $db, $usu))) {
                $msg = generarAlertJS('Error', "Ya existe un rol con el código '$codigo'.", 'error');
            } else {
                $sql_ins = "INSERT INTO ciclo_rol (codigo, nombre, descripcion, nivel_jerarquico, activo, auditoria_usu)
                            VALUES ('$codigo', '$nombre', '$descripcion', '$nivel_jerarquico', 1, '$usu');";
                sql_do_query($sql_ins, $db, $usu);
                $msg = generarAlertJS('Rol creado', "El rol '$nombre' fue creado correctamente.", 'success', "index.php?estado=$estado");
            }
        }
    }

    // ── EDITAR ───────────────────────────────────────────────────────────────
    if ($accion_post === 'editar') {
        $edit_id          = Enmascarar_post('edit_id');
        $nombre           = Enmascarar_post('nombre');
        $descripcion      = Enmascarar_post('descripcion');
        $nivel_jerarquico = Enmascarar_post('nivel_jerarquico');

        if (empty($nombre)) {
            $msg = generarAlertJS('Error', 'El nombre es obligatorio.', 'error');
        } else {
            $sql_upd = "UPDATE ciclo_rol SET nombre = '$nombre', descripcion = '$descripcion',
                        nivel_jerarquico = '$nivel_jerarquico', auditoria_usu = '$usu' WHERE id = '$edit_id';";
            sql_do_query($sql_upd, $db, $usu);
            $msg = generarAlertJS('Actualizado', 'El rol fue actualizado correctamente.', 'success', "index.php?estado=$estado");
        }
    }

    // ── TOGGLE ACTIVO ────────────────────────────────────────────────────────
    if ($accion_post === 'toggle_activo') {
        $toggle_id = Enmascarar_post('toggle_id');

        // No desactivar 'admin'
        $sql_check = "SELECT codigo, activo FROM ciclo_rol WHERE id = '$toggle_id';";
        $row_check = sql_do_array(sql_do_query($sql_check, $db, $usu));

        if ($row_check['CODIGO'] === 'admin') {
            $msg = generarAlertJS('Error', 'El rol Administrador no puede desactivarse.', 'error');
        } else {
            $nuevo = ($row_check['ACTIVO'] == 1) ? 0 : 1;
            sql_do_query("UPDATE ciclo_rol SET activo = $nuevo, auditoria_usu = '$usu' WHERE id = '$toggle_id';", $db, $usu);
            $msg = generarAlertJS('Estado cambiado', 'El rol fue ' . ($nuevo ? 'activado' : 'desactivado') . '.', 'success', "index.php?estado=$estado");
        }
    }

    // ── ELIMINAR ─────────────────────────────────────────────────────────────
    if ($accion_post === 'eliminar') {
        $del_id = Enmascarar_post('del_id');

        $sql_check = "SELECT codigo FROM ciclo_rol WHERE id = '$del_id';";
        $row_check = sql_do_array(sql_do_query($sql_check, $db, $usu));

        if ($row_check['CODIGO'] === 'admin') {
            $msg = generarAlertJS('Error', 'El rol Administrador no puede eliminarse.', 'error');
        } else {
            // Verificar si hay usuarios con este rol
            $sql_usu = "SELECT COUNT(*) AS TOTAL FROM ciclo_usuario WHERE rol = '{$row_check['CODIGO']}';";
            $row_usu = sql_do_array(sql_do_query($sql_usu, $db, $usu));
            if ((int)$row_usu['TOTAL'] > 0) {
                $msg = generarAlertJS('Error', "No se puede eliminar: hay {$row_usu['TOTAL']} usuario(s) con este rol. Reasígnelos primero.", 'warning');
            } else {
                sql_do_query("DELETE FROM ciclo_rol WHERE id = '$del_id';", $db, $usu);
                $msg = generarAlertJS('Eliminado', 'El rol fue eliminado correctamente.', 'success', "index.php?estado=$estado");
            }
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
        $row = sql_do_array(sql_do_query("SELECT * FROM ciclo_rol WHERE id = '$id';", $db, $usu));
        if (!$row) { echo generarAlertJS('Error', 'Rol no encontrado.', 'error', "index.php?estado=$estado"); return; }
    }
    $titulo_form = ($accion === 'nuevo') ? 'Nuevo Rol' : 'Editar Rol';
?>

<?php echo generarTituloSeccion($titulo_form, 'bi bi-shield-lock'); ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="<?php echo ($accion === 'nuevo') ? 'crear' : 'editar'; ?>">
            <?php if ($row): ?>
                <input type="hidden" name="edit_id" value="<?php echo $row['ID']; ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Código <span class="text-danger">*</span></label>
                    <?php if ($accion === 'nuevo'): ?>
                        <input type="text" class="form-control" name="codigo" maxlength="30" required
                               placeholder="ej: jefe_area" pattern="[a-z_]+" title="Solo minúsculas y guión bajo">
                    <?php else: ?>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['CODIGO']); ?>" disabled>
                    <?php endif; ?>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre"
                           value="<?php echo htmlspecialchars($row['NOMBRE'] ?? ''); ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Nivel Jerárquico <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="nivel_jerarquico" min="1" max="9"
                           value="<?php echo htmlspecialchars($row['NIVEL_JERARQUICO'] ?? '1'); ?>" required>
                    <small class="text-muted">1=base, 9=máximo</small>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Descripción</label>
                    <input type="text" class="form-control" name="descripcion"
                           value="<?php echo htmlspecialchars($row['DESCRIPCION'] ?? ''); ?>"
                           placeholder="Breve descripción del rol y sus permisos">
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
    $sql_list = "SELECT r.*, (SELECT COUNT(*) FROM ciclo_usuario u WHERE u.rol = r.codigo) AS CANT_USUARIOS
                 FROM ciclo_rol r
                 ORDER BY r.nivel_jerarquico, r.codigo;";
    $result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Roles del Sistema', 'bi bi-shield-lock'); ?>

<?php if ($nivel >= 9): ?>
<div class="mb-3">
    <a href="index.php?estado=<?php echo $estado; ?>&accion=nuevo" class="btn btn-pade">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Rol
    </a>
</div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Código', 'Nombre', 'Descripción', 'Nivel', 'Usuarios', 'Estado', 'Acciones']); ?>
        <tbody>
<?php
    $n = 0;
    while ($row = sql_do_array($result_list)):
        $n++;
        $activo_badge = ($row['ACTIVO'] == 1)
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-secondary">Inactivo</span>';

        $nivel_badge_class = 'bg-secondary';
        $nj = (int)$row['NIVEL_JERARQUICO'];
        if ($nj >= 9)      $nivel_badge_class = 'bg-danger';
        elseif ($nj >= 5)  $nivel_badge_class = 'bg-warning text-dark';
        elseif ($nj >= 3)  $nivel_badge_class = 'bg-info text-dark';
        elseif ($nj >= 2)  $nivel_badge_class = 'bg-primary';

        $es_admin = ($row['CODIGO'] === 'admin');
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center"><code><?php echo htmlspecialchars($row['CODIGO']); ?></code></td>
                <td class="fw-bold"><?php echo htmlspecialchars($row['NOMBRE']); ?></td>
                <td class="small text-muted"><?php echo htmlspecialchars($row['DESCRIPCION'] ?? ''); ?></td>
                <td class="text-center"><span class="badge <?php echo $nivel_badge_class; ?>"><?php echo $nj; ?></span></td>
                <td class="text-center"><span class="badge bg-primary"><?php echo $row['CANT_USUARIOS']; ?></span></td>
                <td class="text-center"><?php echo $activo_badge; ?></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <?php if ($nivel >= 9): ?>
                            <a href="index.php?estado=<?php echo $estado; ?>&accion=editar&id=<?php echo $row['ID']; ?>"
                               class="btn btn-pade-alt btn-sm btn-accion" title="Editar">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <?php if (!$es_admin): ?>
                                <?php echo generarBotonForm(
                                    ($row['ACTIVO'] == 1) ? '<i class="bi bi-toggle-on"></i>' : '<i class="bi bi-toggle-off"></i>',
                                    "index.php?estado=$estado",
                                    ['accion_post' => 'toggle_activo', 'toggle_id' => $row['ID']],
                                    ($row['ACTIVO'] == 1) ? 'btn btn-outline-warning btn-sm' : 'btn btn-outline-success btn-sm'
                                ); ?>
                                <?php if ((int)$row['CANT_USUARIOS'] === 0): ?>
                                    <?php echo generarBotonForm(
                                        '<i class="bi bi-trash"></i>',
                                        "index.php?estado=$estado",
                                        ['accion_post' => 'eliminar', 'del_id' => $row['ID']],
                                        'btn btn-outline-danger btn-sm'
                                    ); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No hay roles registrados.</td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
