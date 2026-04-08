<?php
/**
 * ============================================================================
 * Usuarios y Roles - ABM
 * ============================================================================
 * ID menú: 106 | Tabla: ciclo_usuario, ciclo_unidad_organizativa
 * ABM de usuarios del sistema con asignación de unidad y rol.
 * ============================================================================
 */

$db = '1';
$accion = Enmascarar_get('accion');
$id     = Enmascarar_get('id');
$msg    = '';

// Cargar roles desde la BD
$roles_validos = [];
$roles_labels  = [];
$roles_niveles = [];
$res_roles = sql_do_query("SELECT codigo, nombre, nivel_jerarquico FROM ciclo_rol WHERE activo = 1 ORDER BY nivel_jerarquico;", $db, $usu);
while ($rr = sql_do_array($res_roles)) {
    $roles_validos[] = $rr['CODIGO'];
    $roles_labels[$rr['CODIGO']] = $rr['NOMBRE'];
    $roles_niveles[$rr['CODIGO']] = (int)$rr['NIVEL_JERARQUICO'];
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post = Enmascarar_post('accion_post');

    // ── CREAR ────────────────────────────────────────────────────────────────
    if ($accion_post === 'crear') {
        $nombre    = Enmascarar_post('nombre');
        $email     = Enmascarar_post('email');
        $clave     = $_POST['clave'] ?? '';
        $id_unidad = Enmascarar_post('id_unidad');
        $rol       = Enmascarar_post('rol');

        if (empty($nombre) || empty($email) || empty($clave) || $id_unidad === 'null' || !in_array($rol, $roles_validos)) {
            $msg = generarAlertJS('Error', 'Todos los campos son obligatorios y el rol debe ser válido.', 'error');
        } else {
            // Email único
            $sql_dup = "SELECT id FROM ciclo_usuario WHERE email = '$email';";
            if (sql_do_array(sql_do_query($sql_dup, $db, $usu))) {
                $msg = generarAlertJS('Error', "Ya existe un usuario con el email '$email'.", 'error');
            } else {
                // Validar unidad existe
                $sql_uni = "SELECT id FROM ciclo_unidad_organizativa WHERE id = '$id_unidad';";
                if (!sql_do_array(sql_do_query($sql_uni, $db, $usu))) {
                    $msg = generarAlertJS('Error', 'La unidad seleccionada no existe.', 'error');
                } else {
                    $clave_hash = hash('sha256', $clave);
                    $sql_ins = "INSERT INTO ciclo_usuario (nombre, email, clave_hash, id_unidad, rol, activo, auditoria_usu)
                                VALUES ('$nombre', '$email', '$clave_hash', '$id_unidad', '$rol', 1, '$usu');";
                    sql_do_query($sql_ins, $db, $usu);
                    $msg = generarAlertJS('Usuario creado', "El usuario '$nombre' fue creado correctamente.", 'success', "index.php?estado=$estado");
                }
            }
        }
    }

    // ── EDITAR ───────────────────────────────────────────────────────────────
    if ($accion_post === 'editar') {
        $edit_id   = Enmascarar_post('edit_id');
        $nombre    = Enmascarar_post('nombre');
        $email     = Enmascarar_post('email');
        $clave     = $_POST['clave'] ?? '';
        $id_unidad = Enmascarar_post('id_unidad');
        $rol       = Enmascarar_post('rol');

        if (empty($nombre) || empty($email) || $id_unidad === 'null' || !in_array($rol, $roles_validos)) {
            $msg = generarAlertJS('Error', 'Nombre, email, unidad y rol son obligatorios.', 'error');
        } else {
            // Email único (excepto el propio)
            $sql_dup = "SELECT id FROM ciclo_usuario WHERE email = '$email' AND id != '$edit_id';";
            if (sql_do_array(sql_do_query($sql_dup, $db, $usu))) {
                $msg = generarAlertJS('Error', "Ya existe otro usuario con el email '$email'.", 'error');
            } else {
                $sql_upd = "UPDATE ciclo_usuario SET nombre = '$nombre', email = '$email',
                            id_unidad = '$id_unidad', rol = '$rol', auditoria_usu = '$usu'
                            WHERE id = '$edit_id';";
                sql_do_query($sql_upd, $db, $usu);

                // Actualizar clave solo si se envió una nueva
                if (!empty($clave)) {
                    $clave_hash = hash('sha256', $clave);
                    sql_do_query("UPDATE ciclo_usuario SET clave_hash = '$clave_hash' WHERE id = '$edit_id';", $db, $usu);
                }

                $msg = generarAlertJS('Actualizado', 'El usuario fue actualizado correctamente.', 'success', "index.php?estado=$estado");
            }
        }
    }

    // ── TOGGLE ACTIVO ────────────────────────────────────────────────────────
    if ($accion_post === 'toggle_activo') {
        $toggle_id = Enmascarar_post('toggle_id');

        // No desactivar último admin
        $sql_usr = "SELECT activo, rol FROM ciclo_usuario WHERE id = '$toggle_id';";
        $row_usr = sql_do_array(sql_do_query($sql_usr, $db, $usu));

        if ($row_usr['ACTIVO'] == 1 && $row_usr['ROL'] === 'admin') {
            $sql_admins = "SELECT COUNT(*) AS TOTAL FROM ciclo_usuario WHERE rol = 'admin' AND activo = 1;";
            $row_admins = sql_do_array(sql_do_query($sql_admins, $db, $usu));
            if ($row_admins['TOTAL'] <= 1) {
                $msg = generarAlertJS('Error', 'No se puede desactivar al último administrador.', 'error');
            } else {
                sql_do_query("UPDATE ciclo_usuario SET activo = 0, auditoria_usu = '$usu' WHERE id = '$toggle_id';", $db, $usu);
                $msg = generarAlertJS('Desactivado', 'El usuario fue desactivado.', 'success', "index.php?estado=$estado");
            }
        } elseif ($row_usr['ACTIVO'] == 1) {
            sql_do_query("UPDATE ciclo_usuario SET activo = 0, auditoria_usu = '$usu' WHERE id = '$toggle_id';", $db, $usu);
            $msg = generarAlertJS('Desactivado', 'El usuario fue desactivado.', 'success', "index.php?estado=$estado");
        } else {
            sql_do_query("UPDATE ciclo_usuario SET activo = 1, auditoria_usu = '$usu' WHERE id = '$toggle_id';", $db, $usu);
            $msg = generarAlertJS('Activado', 'El usuario fue activado.', 'success', "index.php?estado=$estado");
        }
    }

    // ── ELIMINAR ──────────────────────────────────────────────────────────────
    if ($accion_post === 'eliminar') {
        $del_id = Enmascarar_post('del_id');

        // No eliminar último admin
        $sql_usr = "SELECT rol FROM ciclo_usuario WHERE id = '$del_id';";
        $row_usr = sql_do_array(sql_do_query($sql_usr, $db, $usu));

        if ($row_usr['ROL'] === 'admin') {
            $sql_admins = "SELECT COUNT(*) AS TOTAL FROM ciclo_usuario WHERE rol = 'admin';";
            $row_admins = sql_do_array(sql_do_query($sql_admins, $db, $usu));
            if ($row_admins['TOTAL'] <= 1) {
                $msg = generarAlertJS('Error', 'No se puede eliminar al único administrador.', 'error');
            } else {
                // Migrar referencias FK a id=1
                sql_do_query("UPDATE ciclo_solicitud_recurso SET id_usuario_solicita = 1 WHERE id_usuario_solicita = '$del_id';", $db, $usu);
                sql_do_query("UPDATE ciclo_solicitud_recurso SET id_usuario_aprueba = 1 WHERE id_usuario_aprueba = '$del_id';", $db, $usu);
                sql_do_query("UPDATE ciclo_revision SET id_usuario = 1 WHERE id_usuario = '$del_id';", $db, $usu);
                sql_do_query("DELETE FROM ciclo_usuario WHERE id = '$del_id';", $db, $usu);
                $msg = generarAlertJS('Eliminado', 'El usuario fue eliminado. Sus registros fueron migrados.', 'success', "index.php?estado=$estado");
            }
        } else {
            sql_do_query("UPDATE ciclo_solicitud_recurso SET id_usuario_solicita = 1 WHERE id_usuario_solicita = '$del_id';", $db, $usu);
            sql_do_query("UPDATE ciclo_solicitud_recurso SET id_usuario_aprueba = 1 WHERE id_usuario_aprueba = '$del_id';", $db, $usu);
            sql_do_query("UPDATE ciclo_revision SET id_usuario = 1 WHERE id_usuario = '$del_id';", $db, $usu);
            sql_do_query("DELETE FROM ciclo_usuario WHERE id = '$del_id';", $db, $usu);
            $msg = generarAlertJS('Eliminado', 'El usuario fue eliminado. Sus registros fueron migrados.', 'success', "index.php?estado=$estado");
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
        $row = sql_do_array(sql_do_query("SELECT * FROM ciclo_usuario WHERE id = '$id';", $db, $usu));
        if (!$row) { echo generarAlertJS('Error', 'Usuario no encontrado.', 'error', "index.php?estado=$estado"); return; }
    }
    $titulo_form = ($accion === 'nuevo') ? 'Nuevo Usuario' : 'Editar Usuario';
    $sql_unidades = "SELECT id AS ID, nombre AS NOMBRE FROM ciclo_unidad_organizativa ORDER BY nombre;";
?>

<?php echo generarTituloSeccion($titulo_form, 'bi bi-person-plus'); ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="<?php echo ($accion === 'nuevo') ? 'crear' : 'editar'; ?>">
            <?php if ($row): ?>
                <input type="hidden" name="edit_id" value="<?php echo $row['ID']; ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre"
                           value="<?php echo htmlspecialchars($row['NOMBRE'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email"
                           value="<?php echo htmlspecialchars($row['EMAIL'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Contraseña <?php echo ($accion === 'nuevo') ? '<span class="text-danger">*</span>' : ''; ?></label>
                    <input type="password" class="form-control" name="clave"
                           placeholder="<?php echo ($accion === 'editar') ? 'Dejar vacío para no cambiar' : ''; ?>"
                           <?php echo ($accion === 'nuevo') ? 'required' : ''; ?>>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Unidad <span class="text-danger">*</span></label>
                    <?php echo imp_combo_w('id_unidad', $sql_unidades, $db, $usu, $row['ID_UNIDAD'] ?? ''); ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
                    <?php echo imp_combo_w('rol',
                        "SELECT codigo AS ID, nombre AS NOMBRE FROM ciclo_rol WHERE activo = 1 ORDER BY nivel_jerarquico;",
                        $db, $usu, $row['ROL'] ?? ''); ?>
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
    $sql_list = "SELECT u.*, uo.nombre AS UNIDAD
                 FROM ciclo_usuario u
                 JOIN ciclo_unidad_organizativa uo ON u.id_unidad = uo.id
                 ORDER BY uo.nombre, u.nombre;";
    $result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Usuarios y Roles', 'bi bi-people'); ?>

<?php if ($nivel >= 6): ?>
<div class="mb-3">
    <a href="index.php?estado=<?php echo $estado; ?>&accion=nuevo" class="btn btn-pade">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Usuario
    </a>
</div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Nombre', 'Email', 'Unidad', 'Rol', 'Estado', 'Acciones']); ?>
        <tbody>
<?php
    $n = 0;
    while ($row = sql_do_array($result_list)):
        $n++;
        $rol_badge_class = 'bg-secondary';
        if ($row['ROL'] === 'admin')       $rol_badge_class = 'bg-danger';
        elseif ($row['ROL'] === 'director') $rol_badge_class = 'bg-warning text-dark';
        elseif ($row['ROL'] === 'gerente')  $rol_badge_class = 'bg-info text-dark';
        elseif ($row['ROL'] === 'jefe_unidad') $rol_badge_class = 'bg-primary';

        $activo_badge = ($row['ACTIVO'] == 1)
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-secondary">Inactivo</span>';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td><?php echo htmlspecialchars($row['NOMBRE']); ?></td>
                <td><?php echo htmlspecialchars($row['EMAIL']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['UNIDAD']); ?></td>
                <td class="text-center"><span class="badge <?php echo $rol_badge_class; ?>"><?php echo $roles_labels[$row['ROL']] ?? $row['ROL']; ?></span></td>
                <td class="text-center"><?php echo $activo_badge; ?></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <?php if ($nivel >= 6): ?>
                            <a href="index.php?estado=<?php echo $estado; ?>&accion=editar&id=<?php echo $row['ID']; ?>"
                               class="btn btn-pade-alt btn-sm btn-accion" title="Editar">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        <?php endif; ?>
                        <?php if ($nivel >= 9): ?>
                            <?php echo generarBotonForm(
                                ($row['ACTIVO'] == 1) ? '<i class="bi bi-toggle-on"></i>' : '<i class="bi bi-toggle-off"></i>',
                                "index.php?estado=$estado",
                                ['accion_post' => 'toggle_activo', 'toggle_id' => $row['ID']],
                                ($row['ACTIVO'] == 1) ? 'btn btn-outline-warning btn-sm' : 'btn btn-outline-success btn-sm'
                            ); ?>
                        <?php endif; ?>
                        <?php if ($nivel >= 9 && $row['ID'] != '1'): ?>
                            <?php echo generarBotonForm(
                                '<i class="bi bi-trash"></i>',
                                "index.php?estado=$estado",
                                ['accion_post' => 'eliminar', 'del_id' => $row['ID']],
                                'btn btn-outline-danger btn-sm'
                            ); ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
