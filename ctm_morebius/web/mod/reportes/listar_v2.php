<?php
/**
 * Reportes - Listado de artículos (celulares, notebooks, etc.)
 * Consulta articulos_con + tipos + marcas + plantel
 */

$buscar   = Enmascarar_post('buscar');
$tipo_art = Enmascarar_post('tipo_art') ?: Enmascarar_get('tipo_art');

// Filtro búsqueda
$filtro_sql = '';
if ($buscar) {
    $filtro_sql .= " AND (UPPER(a.detalle) LIKE UPPER('%$buscar%')
                     OR UPPER(a.Nro_Linea) LIKE UPPER('%$buscar%')
                     OR UPPER(m.detmarca) LIKE UPPER('%$buscar%')
                     OR UPPER(a.MODELO) LIKE UPPER('%$buscar%')
                     OR UPPER(a.NSERIE) LIKE UPPER('%$buscar%')
                     OR UPPER(p.EmpNombre) LIKE UPPER('%$buscar%'))";
}

// Filtro tipo
$filtro_tipo = '';
if ($tipo_art && $tipo_art != 'null') {
    $filtro_tipo = " AND a.tipo_art = '$tipo_art'";
}

// Solo activos si no es admin
$filtro_baja = '';
if ($nivel < 9) {
    $filtro_baja = " AND (a.DE_BAJA <> 1 OR a.DE_BAJA IS NULL)";
}

$sql = "SELECT a.nro_art, a.fecha, a.tipo_art, a.detalle, a.legajo_art, a.nota,
               a.devuelto, a.DE_BAJA, a.Nro_Linea, a.MARCA, a.MODELO, a.NSERIE, a.IMEI,
               t.detalle AS tipo_nombre,
               m.detmarca AS marca_nombre,
               p.EmpNombre, p.EmpCentroCostoNom
          FROM articulos_con a
          LEFT JOIN articulos_tipos t ON a.tipo_art = t.tipo_art
          LEFT JOIN artimarcas m ON a.MARCA = m.marcaid
          LEFT JOIN ctm_plantel p ON a.legajo_art = p.EmpLegajo
         WHERE 1=1 $filtro_baja $filtro_tipo $filtro_sql
         ORDER BY a.nro_art DESC;";
$result = sql_do_query($sql, '2', $usu);
?>

<?php echo generarTituloSeccion('Artículos - Seguimiento', 'bi bi-box-seam'); ?>

<!-- Filtros -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small mb-0">Búsqueda</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>"
                           placeholder="Detalle, marca, modelo, serie, persona..." onchange="this.form.submit();">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-0">Tipo de Artículo</label>
                <?php
                $sql_tipos = "SELECT tipo_art AS ID, detalle AS NOMBRE FROM articulos_tipos ORDER BY detalle;";
                echo imp_combo_w_oc('tipo_art', $sql_tipos, '2', $usu, $tipo_art);
                ?>
            </div>
            <div class="col-md-3 text-end">
                <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-pade mb-0">
                <?php echo generarTablaHeader(['#', 'Art.', 'Tipo', 'Detalle', 'Marca / Modelo', 'Serie', 'Línea', 'Asignado a', 'Estado']); ?>
                <tbody>
                <?php
                $n = 0;
                while ($row = sql_do_array($result)) {
                    $n++;
                ?>
                    <tr class="<?php echo claseFilaAlternada($n); ?>">
                        <td class="text-center"><?php echo $n; ?></td>
                        <td class="text-center fw-semibold"><?php echo $row['NRO_ART']; ?></td>
                        <td class="text-center"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['TIPO_NOMBRE'] ?? ''); ?></span></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($row['DETALLE'] ?? '', 0, 35, '...')); ?></td>
                        <td>
                            <?php echo htmlspecialchars($row['MARCA_NOMBRE'] ?? ''); ?>
                            <?php if ($row['MODELO']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($row['MODELO']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><small><?php echo htmlspecialchars($row['NSERIE'] ?? ''); ?></small></td>
                        <td class="text-center"><?php echo htmlspecialchars($row['NRO_LINEA'] ?? ''); ?></td>
                        <td>
                            <?php if ($row['EMPNOMBRE']): ?>
                                <a href="index.php?estado=44&legajo=<?php echo $row['LEGAJO_ART']; ?>" class="text-decoration-none text-dark d-flex align-items-center gap-2" data-bs-toggle="tooltip" title="Ver equipos de esta persona">
                                    <?php echo fotoEmpleado($row['LEGAJO_ART'], $row['EMPNOMBRE'], $servidor_img, 28); ?>
                                    <span><?php echo htmlspecialchars($row['EMPNOMBRE']); ?></span>
                                </a>
                                <small class="text-muted ps-5"><?php echo htmlspecialchars($row['EMPCENTROCOSTONOM'] ?? ''); ?></small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php echo generarBadgeEstado($row['DE_BAJA']); ?>
                            <?php echo generarBadgeDevuelto($row['DEVUELTO']); ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($n == 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No se encontraron artículos</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <small class="text-muted"><?php echo $n; ?> artículo(s) encontrado(s)</small>
    </div>
</div>
