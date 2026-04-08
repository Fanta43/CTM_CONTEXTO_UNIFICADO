<?php
/**
 * Tablas Externas - Vista general de datos importados (Kaspersky, RRHH extras, etc.)
 * Muestra las tablas externas disponibles con conteo de registros.
 */

// Tablas externas en contexto_unificado
$tablas_ext = [
    ['tabla' => 'more_v_all_users',            'nombre' => 'KASP - Usuarios',          'db' => '1', 'icono' => 'bi-people'],
    ['tabla' => 'more_v_host_user_relation',   'nombre' => 'KASP - Relación Host/User', 'db' => '1', 'icono' => 'bi-link-45deg'],
    ['tabla' => 'more_v_hosts',                'nombre' => 'KASP - Equipos (Hosts)',    'db' => '1', 'icono' => 'bi-hdd-network'],
    ['tabla' => 'more_v_hst_loggedin_users',   'nombre' => 'KASP - Último Logueo',     'db' => '1', 'icono' => 'bi-box-arrow-in-right'],
    ['tabla' => 'more_consulta_externa',       'nombre' => 'RRHH - Datos Externos',    'db' => '1', 'icono' => 'bi-person-vcard'],
];
?>

<?php echo generarTituloSeccion('Tablas Externas', 'bi bi-database'); ?>

<div class="row g-3">
    <?php foreach ($tablas_ext as $te): ?>
    <?php
        $sql_cnt = "SELECT COUNT(*) AS CANT FROM " . $te['tabla'] . ";";
        $row_cnt = sql_do_array(sql_do_query($sql_cnt, $te['db'], $usu));
        $cant = $row_cnt['CANT'] ?? 0;
    ?>
    <div class="col-lg-4 col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:50px;height:50px;flex-shrink:0;">
                    <i class="bi <?php echo $te['icono']; ?> fs-4 text-primary"></i>
                </div>
                <div>
                    <h6 class="mb-0"><?php echo htmlspecialchars($te['nombre']); ?></h6>
                    <small class="text-muted"><?php echo number_format($cant); ?> registros</small>
                    <br><code class="small"><?php echo $te['tabla']; ?></code>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="mt-3">
    <div class="alert alert-info small mb-0">
        <i class="bi bi-info-circle me-2"></i>
        Estas tablas son alimentadas por procesos externos (CRON, importación manual). Los datos aquí mostrados son de solo lectura.
    </div>
</div>
