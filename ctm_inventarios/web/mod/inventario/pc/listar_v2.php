<?php
/**
 * ============================================================================
 * Listado de PCs/Equipos - ctm_inventarios v2
 * ============================================================================
 */

$buscar = Enmascarar_post('buscar');
$filtro_baja = Enmascarar_post('filtro_baja');
if (!$filtro_baja) $filtro_baja = '0';
?>

<?php echo generarTituloSeccion('PCs / Equipos', 'bi bi-pc-display'); ?>

<div class="row mb-3">
    <div class="col-md-8">
        <form method="post" action="" class="d-flex gap-2">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>"
                       placeholder="Buscar por código, marca, IP, serie..." onchange="this.form.submit();">
            </div>
            <select class="form-select" name="filtro_baja" style="max-width: 180px;" onchange="this.form.submit();">
                <option value="0" <?php echo ($filtro_baja == '0') ? 'selected' : ''; ?>>Activos</option>
                <option value="1" <?php echo ($filtro_baja == '1') ? 'selected' : ''; ?>>De baja</option>
                <option value="" <?php echo ($filtro_baja === '') ? 'selected' : ''; ?>>Todos</option>
            </select>
        </form>
    </div>
    <?php if ($nivel >= 6): ?>
    <div class="col-md-4 text-end">
        <a href="index.php?estado=19" class="btn btn-pade"><i class="bi bi-plus-circle me-1"></i>Nuevo equipo</a>
    </div>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-pade table-hover table-sm">
        <?php echo generarTablaHeader(['#', 'Código', 'Marca', 'Tipo', 'IP', 'RAM', 'Legajo', 'Estado', 'Opciones']); ?>
        <tbody>
<?php
$where_baja = ($filtro_baja !== '') ? "AND (a.baja = '$filtro_baja' OR a.baja IS NULL)" : '';
$where_buscar = $buscar ? "AND (UPPER(a.codigopc) LIKE UPPER('%$buscar%')
                             OR UPPER(a.marca) LIKE UPPER('%$buscar%')
                             OR UPPER(a.ip) LIKE UPPER('%$buscar%')
                             OR UPPER(a.numeroserie) LIKE UPPER('%$buscar%')
                             OR UPPER(a.legajo) LIKE UPPER('%$buscar%'))" : '';

$sql = "SELECT a.pcid, a.codigopc, a.marca, a.tipoequipo, a.ip, a.memoriaram, a.legajo, a.baja, a.estadoid
          FROM inve_pc a
         WHERE a.estadoid = '1' $where_baja $where_buscar
         ORDER BY a.pcid DESC;";
$result = sql_do_query($sql, '1', $usu);
$n = 0;
while ($row = sql_do_array($result)) {
    $n++;
    $estBadge = ($row['BAJA'] == '0' || !$row['BAJA'])
        ? '<span class="badge bg-success">Activo</span>'
        : '<span class="badge bg-danger">Baja</span>';
    $rowClass = claseFilaAlternada($n);
?>
            <tr class="<?php echo $rowClass; ?>">
                <td class="text-center"><?php echo $n; ?></td>
                <td class="text-center">
                    <a href="index.php?estado=24&codigopc=<?php echo urlencode($row['CODIGOPC']); ?>" class="fw-semibold">
                        <?php echo htmlspecialchars($row['CODIGOPC']); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($row['MARCA']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['TIPOEQUIPO']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['IP']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['MEMORIARAM']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['LEGAJO']); ?></td>
                <td class="text-center"><?php echo $estBadge; ?></td>
                <td class="text-center">
                    <?php
                    $variables = array("codigopc" => $row['CODIGOPC']);
                    $estados = array("24" => "Resumen");
                    echo impSelectHerramienta($variables, $estados, $n);
                    ?>
                </td>
            </tr>
<?php } ?>
        </tbody>
    </table>
</div>

<?php if ($n == 0): ?>
<div class="alert alert-info text-center"><i class="bi bi-info-circle me-2"></i>No se encontraron equipos con los criterios indicados.</div>
<?php endif; ?>

<div class="text-muted small mt-2"><i class="bi bi-list-ol me-1"></i>Total: <?php echo $n; ?> registro(s)</div>
