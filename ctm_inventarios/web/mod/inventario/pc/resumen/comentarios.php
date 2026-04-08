<style>
    .mb-10 {
        margin-bottom: 10px;
    }
</style>

<?php
//var_dump($rowhardware);

$tipo_regsinopticoid = '1';
$tipo_inventarioid = '1';
$legago = $rowhanterior['LEGAJO']; 
$codigopc = $rowhanterior['CODIGOPC']; 
$pcid = $rowhanterior['PCID']; 
$gusu = consultaPersona($legago, '1', $usu);
$codigocargo = $gusu['CARGOCOD']; 

?>

<div class="col-12 separar1 p-0 float-start bg-white">
          <div class="bg-white" >
                <div class="p-3">
                        <div class="col-12 pb-2">
                                <h6><img src="img/icon/pc.png" class="me-2" style="height: 30px; width: 30px;">Comentarios</h6>
                        </div>

                    <form id="form_guardar" name="form_guardar" method="post" action="<?php echo getCurrentUrlWithTab($tabValue, $codigopc); ?>">
                        <div class="row col-12">
                            
        <div class="col-3 p-1">
            <div class="mb-3">
                <label for="legajoInput" class="form-label m-0">Legajo</label>
                <input type="text" class="form-control fs-12" name="legajo" id="legajo" value="<?php echo $legago; ?>" placeholder="Legajo">
            </div>
        </div>
        <div class="col-3 p-1">
            <div class="mb-3">
                <label for="codigoPcInput" class="form-label m-0">Codigo Pc</label>
                <input type="text" class="form-control fs-12" name="codigopc" id="codigopc" value="<?php echo $codigopc; ?>" placeholder="Codigo Pc">
            </div>
        </div>
        <div class="col-3 p-1">
            <div class="mb-3">
                <label for="codigoCargoInput" class="form-label m-0">Código del Cargo</label>
                <input type="text" class="form-control fs-12" name="codigocargo" id="codigocargo" value="<?php echo $codigocargo; ?>" placeholder="Código del Cargo">
            </div>
        </div>
        <div class="col-3 p-1">
            <div class="mb-3">
                <label for="proveedorSelect" class="form-label m-0">Tipo de Registro</label>
                <?php
                $query = "SELECT tipo_regsinopticoid AS id, descripcion AS nombre FROM inve_tipo_regsinoptico WHERE estadoid = '1'";
                echo imp_combo_w_fs12('tipo_regsinopticoid', $query, '1', $usu, $tipo_regsinopticoid);
                ?>
            </div>
        </div>
        <!-- Ajuste para ocupar el 100% del ancho -->
        <div class="col-12 p-1">
            <div class="mb-3">
                <label for="observacionesInput" class="form-label m-0">Observaciones</label>
                <textarea class="form-control fs-12" id="observaciones" name="observaciones" rows="5" maxlength="4092" placeholder="Observaciones">Comentario: <?php echo $rowhanterior['OBSERVACIONES']; ?></textarea>

            </div>
        </div>
        <?php if($_SESSION['HABILITA_EDICION'] == 'SI'){ ?>
            <div class="col-12 p-1" style="display: flex; justify-content: center; align-items: center;">
                <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                    <input name="codigopc" id="codigopc" type="hidden" value="<?php echo $codigopc; ?>" />
                    <input name="tipo_inventarioid" id="tipo_inventarioid" type="hidden" value="<?php echo $tipo_inventarioid; ?>" />
                    <input name="comentario_guardar" id="comentario_guardar" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                </div>
            </div>
        <?php } ?>
    </div>
</form>

          </div>
     </div>
</div>


<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
        <div class="accordion accordion-flush" id="accordion4">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse4" aria-expanded="false" aria-controls="flush-collapse4">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Hist&oacute;ricos</h6>
                </button>
            </h2>
            <div id="flush-collapse4" class="accordion-collapse collapse" data-bs-parent="#accordion4">
                <div class="accordion-body">
                    <div class="col-12">
 
                        
            <div class="bg-white" >
                <div class="p-3">
                        <form id="form_guardar" name="form_guardar" method="post" action="">

                            
                            
                            
                            
<table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th align="center">ESTADO</th>
                <th align="center">DESCRIPCI&Oacute;N</th>
                <th align="center">OBSERVACIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $sql="   SELECT rs.reg_sinapticoid, 
                                rs.legajo,
                                rs.pcid,
                                rs.codigopc, 
                                rs.cargocod, 
                                rs.tipo_inventarioid, 
                                rs.tipo_registroid, 
                                rs.fecha_inicio, 
                                rs.observaciones, 
                                rs.estadoid, 
                                rs.auditoria_usu, 
                                rs.auditoria_fecha 
                           FROM inve_registro_sinoptico_continuo rs
                          WHERE rs.pcid  = '$pcid'
                             OR rs.codigopc LIKE '$codigopc%'  
                            AND rs.tipo_inventarioid = '$tipo_inventarioid'
                            AND rs.tipo_registroid = '$tipo_regsinopticoid'     
                       ORDER BY rs.reg_sinapticoid DESC;";
            //echo $sql;
            $result = sql_do_query($sql,'1',$usu);
            $n=0;
            while($rowx=sql_do_array($result)){
                if(!$rowx['PCID']){
                    
                    $sqln="SELECT pcid FROM inve_pc WHERE codigopc LIKE '".$rowx['CODIGOPC']."%';";
                    //echo $sql;
                    $rown = sql_do_array(sql_do_query($sqln,'1',$usu));
                    
                    $sqlu = "     UPDATE inve_registro_sinoptico_continuo 
                                    SET pcid='".$rown['PCID']."'
                                  WHERE reg_sinapticoid='".$rowx['REG_SINAPTICOID']."';";
                    //echo $sql;
                    $result = sql_do_query($sqlu, '1', $usu);               
                    
                }
                //$responsable = getUsuario($rowx['LEGAJO_ART'], '1',$usu);
                //$tecnico = getUsuario($rowx['USUARIO'], '1',$usu);
                //$cc = getCentroCosto($rowx['SECTOR'], '1',$usu);
            ?>
            <tr>
                <td class="text-left">
                    <strong>FECHA:</strong> <?php echo enmascararFecha($rowx['FECHA_INICIO']);?><br>
                </td>
                <td>
                    <strong>LEGAJO:</strong> <?php echo $rowx['LEGAJO'];?><br>
                    <strong>CODIGO:</strong> <?php echo $rowx['CODIGO'];?><br>
                    <strong>CARGO CARGO:</strong> <?php echo $rowx['CARGOCOD'];?><br>
                </td>
                <td>
                    <?php echo $rowx['OBSERVACIONES'];?>
            </tr>
            <?php 
            $n++;
            }
            ?>
        </tbody>
    </table>
                         </div>  
                    </form>
                </div>
            </div>
                        
                        
                        
                        
                        
                        
                    </div>
                </div>
            </div>
        </div>
    </div>