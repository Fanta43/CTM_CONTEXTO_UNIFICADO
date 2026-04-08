<?php
/**
 * ============================================================================
 * Datos Obligatorios - Tab de resumen PC v2
 * ============================================================================
 */

$obligatorios_importar = Enmascarar_post('obligatorios_importar');
$prellenado = $rowhanterior;
?>

<form id="form_obligatorios" method="post" action="<?php echo getCurrentUrlWithTab($tab ?? '1', $codigopc); ?>">
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label fw-semibold">Código PC</label>
            <input type="text" class="form-control form-control-sm" name="codigopc" value="<?php echo htmlspecialchars($rowhanterior['CODIGOPC']); ?>" placeholder="Código PC">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Fecha de Compra</label>
            <input type="date" class="form-control form-control-sm" name="fechacompra" value="<?php echo substr($rowhanterior['FECHACOMPRA'], 0, 10); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">SR</label>
            <input type="text" class="form-control form-control-sm" name="sr" value="<?php echo htmlspecialchars($rowhanterior['SR']); ?>" placeholder="SR">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">OC</label>
            <input type="text" class="form-control form-control-sm" name="oc" value="<?php echo htmlspecialchars($rowhanterior['OC']); ?>" placeholder="OC">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Monto</label>
            <input type="text" class="form-control form-control-sm" name="monto" value="<?php echo htmlspecialchars($rowhanterior['MONTO']); ?>" placeholder="Monto">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Proveedor</label>
            <?php
            $query = "SELECT proveedorid AS id, nombre FROM inve_proveedores;";
            echo imp_combo_w_fs12('id_proveedores', $query, '1', $usu, $rowhanterior['ID_PROVEEDORES']);
            ?>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Vto. Garantía</label>
            <input type="date" class="form-control form-control-sm" name="fechavtogarantia" value="<?php echo substr($rowhanterior['FECHAVTOGARANTIA'], 0, 10); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Legajo</label>
            <input type="text" class="form-control form-control-sm" name="legajo" value="<?php echo htmlspecialchars($rowhanterior['LEGAJO']); ?>" placeholder="Legajo">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Ubicación (CC)</label>
            <?php
            $query = "SELECT a.conidcc AS id, a.connomcc AS nombre FROM vw_accc a;";
            echo imp_combo_w_fs12('centrocosto', $query, '1', $usu, $rowhanterior['CENTROCOSTO']);
            ?>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">PCC</label>
            <input type="text" class="form-control form-control-sm" name="pcc" value="<?php echo htmlspecialchars($rowhanterior['PCC']); ?>" placeholder="PCC">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Nro. Oblea</label>
            <input type="text" class="form-control form-control-sm" name="numoblea" value="<?php echo htmlspecialchars($rowhanterior['NUMOBLEA']); ?>" placeholder="Nro. oblea">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Zona Inventario</label>
            <?php
            $id_zona = $rowhanterior['ID_ZONAS_INVENTARIO'] ?: '3';
            $query = "SELECT zonaid AS id, zona AS nombre FROM inve_zonas_inventario;";
            echo imp_combo_w_fs12('id_zonas_inventario', $query, '1', $usu, $id_zona);
            ?>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Observaciones</label>
            <input type="text" class="form-control form-control-sm" name="observaciones" value="<?php echo htmlspecialchars($rowhanterior['OBSERVACIONES']); ?>" placeholder="Observaciones">
        </div>
    </div>

    <?php if (isset($_SESSION['HABILITA_EDICION']) && $_SESSION['HABILITA_EDICION'] == 'SI'): ?>
    <hr>
    <div class="d-flex gap-2 justify-content-end flex-wrap">
        <input type="hidden" name="obligatorio_id" value="<?php echo $rowhanterior['PCID']; ?>">
        <button type="submit" name="obligatorio_guardar" value="Guardar" class="btn btn-pade">
            <i class="bi bi-check-circle me-1"></i>Guardar
        </button>
        <?php echo generarBotonForm('<i class="bi bi-arrow-left-right me-1"></i>Préstamo', 'index.php?estado=17',
            ['id_pc' => $rowhanterior['PCID']], 'btn btn-outline-warning'); ?>
        <?php echo generarBotonForm('<i class="bi bi-tools me-1"></i>Reparación', 'index.php?estado=12',
            ['nro_art' => ($nro_art ?? ''), 'codigopc' => $codigopc], 'btn btn-outline-danger'); ?>
    </div>
    <?php endif; ?>
</form>

<!-- Accordion: Características técnicas -->
<div class="accordion mt-3" id="accordionCaract">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCaract">
                <i class="bi bi-cpu me-2"></i>Características técnicas
            </button>
        </h2>
        <div id="collapseCaract" class="accordion-collapse collapse" data-bs-parent="#accordionCaract">
            <div class="accordion-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tipo Equipo</label>
                        <input type="text" class="form-control form-control-sm" readonly value="<?php echo htmlspecialchars($rowhanterior['TIPOEQUIPO']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Procesador</label>
                        <input type="text" class="form-control form-control-sm" readonly value="<?php echo htmlspecialchars($rowhanterior['TIPOPROCESADOR']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Vel. Procesador</label>
                        <input type="text" class="form-control form-control-sm" readonly value="<?php echo htmlspecialchars($rowhanterior['VELOCIDADPROCESADOR']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">RAM</label>
                        <input type="text" class="form-control form-control-sm" readonly value="<?php echo htmlspecialchars($rowhanterior['MEMORIARAM']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">IP</label>
                        <input type="text" class="form-control form-control-sm" readonly value="<?php echo htmlspecialchars($rowhanterior['IP']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">MAC</label>
                        <input type="text" class="form-control form-control-sm" readonly value="<?php echo htmlspecialchars($rowhanterior['MAC']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Nro. Serie</label>
                        <input type="text" class="form-control form-control-sm" readonly value="<?php echo htmlspecialchars($rowhanterior['NUMEROSERIE']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Disco</label>
                        <input type="text" class="form-control form-control-sm" readonly value="<?php echo htmlspecialchars($rowhanterior['DISCO']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Discos Info</label>
                        <input type="text" class="form-control form-control-sm" readonly value="<?php echo htmlspecialchars($rowhanterior['DISCOSCOD']); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
