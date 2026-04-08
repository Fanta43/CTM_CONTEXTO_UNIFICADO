<style>
    .mb-10 {
        margin-bottom: 10px;
    }
</style>

<div class="separar1 p-0 float-start bg-white">
          <div class="bg-white" >
                <div class="p-3">
                        <div class="col-12 pb-2">
                                <h6><img src="img/icon/pc.png" class="me-2" style="height: 30px; width: 30px;">Datos Obligatorios</h6>
                        </div>
                    <form id="form_guardar" name="form_guardar" method="post" action="<?php echo getCurrentUrlWithTab($tabValue, $codigopc); ?>">
                        <div class="row col-12">
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Codigo Pc</label>
                                                <input type="text" class="form-control fs-12" name="codigopc"' id="codigopc" value="<?php echo $rowhanterior['CODIGOPC']; ?>" placeholder="Codigo Pc">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="fechacompra" class="form-label m-0">Fecha de Compra</label>
                                        <input type="date" class="form-control fs-12" name="fechacompra" id="fechacompra" value="<?php echo substr($rowhanterior['FECHACOMPRA'], 0, 10); ?>" placeholder="Fecha de Compra">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >SR</label>
                                                <input type="text" class="form-control fs-12" name="sr" id="sr" value="<?php echo $rowhanterior['SR']; ?>" placeholder="SR">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >OC</label>
                                                <input type="text" class="form-control fs-12" name="oc" id="oc" value="<?php echo $rowhanterior['OC']; ?>" placeholder="OC">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Monto</label>
                                        <input type="text" class="form-control fs-12" name="monto" id="monto" value="<?php echo $rowhanterior['MONTO']; ?>" placeholder="Monto">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="ubicacionSelect" class="form-label m-0">Proveedor</label>
                                        
                                            <?php
                                                $query = "SELECT proveedorid, nombre FROM more_proveedores";
                                                echo imp_combo_w_fs12('id_proveedores', $query, '1', $usu, $rowhanterior['ID_PROVEEDORES']);
                                            ?>

                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="fechavtogarantia" class="form-label m-0">Vto de Garantia</label>
                                        <input type="date" class="form-control fs-12" name="fechavtogarantia" id="fechavtogarantia" value="<?php echo substr($rowhanterior['FECHAVTOGARANTIA'], 0, 10); ?>" placeholder="Vto de Garantia">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Legajo</label>
                                                <input type="text" class="form-control fs-12" name="legajo" id="legajo" value="<?php echo $rowhanterior['LEGAJO']; ?>" placeholder="Legajo">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="ubicacionSelect" class="form-label m-0">Ubicaci&oacute;n</label>
                                        
                                            <?php
                                                $query =  "SELECT a.conidcc AS id, a.connomcc AS nombre FROM vw_accc a;";
                                                echo imp_combo_w_fs12('centrocosto', $query, '1', $usu, $rowhanterior['CENTROCOSTO']);
                                            ?>

                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                                <div class="mb-3">
                                                    <label for="exampleInputEmail1" class="form-label m-0">PCC</label>
                                                    <input type="text" class="form-control fs-12" id="pcc" name="pcc" value="<?php echo $rowhanterior['PCC']; ?>" placeholder="PCC">
                                                </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleInputEmail1" class="form-label m-0">Numero oblea</label>
                                        <input type="text" class="form-control fs-12" id="numoblea" name="numoblea" value="<?php echo $rowhanterior['NUMOBLEA']; ?>" placeholder="Numero oblea">
                                    </div>
                                </div>
                                 <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleInputEmail1" class="form-label m-0">Zonas de Inventario</label>
                                            <?php
                                                if($rowhanterior['ID_ZONAS_INVENTARIO']){
                                                    $id_zona = $rowhanterior['ID_ZONAS_INVENTARIO'];
                                                }else{
                                                    $id_zona = '3';
                                                }
                                                $query =  "SELECT a.zonaid AS id, a.zona AS nombre FROM more_zonas_inventario a;";
                                                echo imp_combo_w_fs12('id_zonas_inventario', $query, '1', $usu, $id_zona);
                                            ?>
                                    </div>
                                </div>
                                <div class="col-6 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleInputEmail1" class="form-label m-0">Observaciones</label>
                                        <input type="text" class="form-control fs-12" id="observaciones" name="observaciones" value="<?php echo $rowhanterior['OBSERVACIONES']; ?>" placeholder="Observaciones">
                                    </div>
                                </div>
                                <?php
                                if($_SESSION['HABILITA_EDICION'] == 'SI'){
                                ?>
                                    <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                        <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                            <input name="obligatorio_id" id="obligatorio_id" type="hidden" value="<?php echo $rowhanterior['PCID']; ?>" />
                                            <input name="obligatorio_guardar" id="obligatorio_guardar" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                        </div>
                </form>
                                        &nbsp;
                                        <form id="form_herramientas" name="form_herramientas" method="post" action="index.php?estado=17">
                                            <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                                <input type="hidden" name="id_pc"' id="id_pc" value="<?php echo $rowhanterior['PCID']; ?>" />
                                                <input name="guardar_baja" id="guardar_baja" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Prestamo" />
                                            </div>
                                        </form>
                                        &nbsp;
                                        <form id="form_herramientas" name="form_herramientas" method="post" action="index.php?estado=12">
                                            <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                                <input type="hidden" name="nro_art"' id="nro_art" value="<?php echo $nro_art; ?>" />
                                                <input type="hidden" name="codigopc"' id="codigopc" value="<?php echo $codigopc; ?>" />
                                                <input name="guardar_baja" id="guardar_baja" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Reparación" />
                                            </div>
                                        </form>  
                                    </div>
                                <?php
                                }
                                ?>
                                
                         </div>
                    </form>
          </div>
     </div>
</div>

<?php

$obligatorios_importar  = Enmascarar_post('obligatorios_importar');


$prellenado = $rowhanterior;

$obligatorio_id  = $rowhardware['PCID'];

if($obligatorios_importar){
    
    $sql="   SELECT so.sistemaoperativoid, 
                    so.sistema, 
                    so.estadoid, 
                    so.auditoria_usu, 
                    so.auditoria_fecha 
               FROM more_sistemaoperativo so 
              WHERE so.estadoid = '1' 
              AND UPPER(so.sistema) LIKE UPPER('%".$rowhardware['OSNAME']."%');";
    //echo $sql;
    $rowso=sql_do_array(sql_do_query($sql,'1',$usu));
    
    if($rowso['ID']){
        $id_sistemaoperativo = $rowso['ID'];
    }else{
        $id_sistemaoperativo = get_id('more_SISTEMAOPERATIVO');
        

        
        $sql = "INSERT INTO more_sistemaoperativo(
                            sistemaoperativoid, 
                            sistema, 
                            estadoid, 
                            auditoria_usu, 
                            auditoria_fecha
                )VALUES(
                            $id_sistemaoperativo, 
                            UPPER('".$rowhardware['OSNAME']."'), 
                            '1', 
                            $usu, 
                            $hoy
                );";
 
        //echo $sql;
        $result = sql_do_query($sql, '1', $usu);
        
    }
    
    if(!$rowhanterior['ID_MONITORES']){
        
        $prellenado['ID_MONITORES'] = $rowhardware['ID_MAX_MONITOR'];

    }else{
        
        $prellenado['ID_MONITORES'] = $rowhanterior['ID_MONITORES'];
        
    }
  
    if($rowhanterior['CODIGOPC'] != $rowhardware['NAME']){        
        $prellenado['CODIGOPC'] = strtoupper($rowhardware['NAME']);
    }else{        
        $prellenado['CODIGOPC'] = strtoupper($rowhanterior['CODIGOPC']);
    }
        
    $a_upper = strtoupper($rowhardware['NAME']);
    $b_upper = strtoupper($rowhanterior['CODIGOPC']);    
    if($a_upper != $b_upper){
        //echo "<h1>".$a_upper."-".$b_upper.'</h1>'; 
        $tipoequipo = str_replace($b_upper, '', $a_upper);
        if(!$tipoequipo) $tipoequipo = 'PC';
    }
    
    $prellenado['MARCA'] = $rowhardware['SMODEL'].' ('.$rowhardware['MMANUFACTURER'].')'; 
    $prellenado['TIPOPROCESADOR'] = $rowhardware['PROCESSORT']; 
    $prellenado['VELOCIDADPROCESADOR'] = $rowhardware['PROCESSORS']; 
    $prellenado['MEMORIARAM'] =  $rowhardware['MEMORY']; 
    $prellenado['IP'] =  $rowhardware['IPADDR']; 
    $prellenado['MAC'] = $rowhardware['MACADDR']; 
    $prellenado['NUMEROSERIE'] = $rowhardware['UUID'];
    //$prellenado['MARCA'] = '';
    $prellenado['ID_SISTEMAOPERATIVO'] = $id_sistemaoperativo;
    $prellenado['LEGAJO'] = $rowhanterior['LEGAJO']; 
    $prellenado['CLAVE1'] = $rowhanterior['CLAVE1']; 
    $prellenado['CLAVE2'] = $rowhanterior['CLAVE2']; 
    $prellenado['CLAVE3'] = $rowhanterior['CLAVE3']; 
    $prellenado['CLAVE4'] = $rowhardware['WINPRODID']; 
    $prellenado['CLAVE5'] = $rowhardware['WINPRODKEY']; 
    $prellenado['DISCOSCOD'] = $rowhardware['TOTAL_DISCO_DESCRIP']; 
    $prellenado['DISCO'] = floor($rowhardware['TOTAL_DISCO_LIBRE']/(1024)).'GB de ('.floor($rowhardware['TOTAL_DISCO']/(1024)).'GB)'; 
    
    $a_upper = strtoupper($rowhardware['NAME']);
    $b_upper = strtoupper($rowhanterior['CODIGOPC']);
    
    if($a_upper != $b_upper){
        //echo "<h1>".$a_upper."-".$b_upper.'</h1>'; 
        $prellenado['TIPOEQUIPO'] = str_replace($b_upper, '', $a_upper);
        if(!$prellenado['TIPOEQUIPO'] && $rowhanterior['TIPOEQUIPO']) $prellenado['TIPOEQUIPO'] = $rowhanterior['TIPOEQUIPO'];
        if(!$prellenado['TIPOEQUIPO']) $prellenado['TIPOEQUIPO'] = 'PC';    
    }
    
   //echo "<h1>".$prellenado['TIPOEQUIPO']."</h1>";
    
    $sqlleg_="   
                      SELECT a.legajo_art
                        FROM articulos_con a 
                       WHERE UPPER(a.referencia) LIKE UPPER('$codigopc%')
                          OR UPPER(a.detalle) LIKE UPPER('$codigopc%') 
                          OR UPPER(a.nota) LIKE UPPER('$codigopc%')
                    ORDER BY a.nro_art DESC;";
    //echo $sqlleg_;
    $rowleg=sql_do_array(sql_do_query($sqlleg_,'3',$usu),'3',$usu);
    
    $prellenado['LEGAJO_ART'] = $rowleg_['LEGAJO_ART'];

    if($result){

        echo '<script>swal({
                       title: "Buen Trabajo!",
                       text: "Has modificado los datos!",
                       icon: "success",
                       dangerMode: false,
                       buttons: {
                           confirm: {text: "Continuar",
                                     value: true,
                           },
                       },
                      })
                       .then((value) => {
                            //window.location.replace("index.php?estado=20");
                           })    
         </script>';
    }
}

?>


<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
        <div class="accordion accordion-flush" id="accordion2">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse2" aria-expanded="false" aria-controls="flush-collapse2">
                    <h6 class="m-0"><img src="img/icon/libro.png" class="me-2" style="height: 20px; width: 20px;">Caracteristicas</h6>
                </button>
            </h2>
            <div id="flush-collapse2" class="accordion-collapse collapse" data-bs-parent="#accordion2">
                <div class="accordion-body">
                    <div class="row">
            <div class="bg-white" >
                <div class="p-3">
                        <form id="form_guardar" name="form_guardar" method="post" action="">
                        <div class="row col-12">
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Codigo Pc</label>
                                                <input type="text" class="form-control fs-12" name="codigopc"' id="codigopc" value="<?php echo $prellenado['CODIGOPC']; ?>" placeholder="Codigo Pc">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleInputEmail1" class="form-label m-0">Tipo de Equipo</label>                                      
                                            <?php
                                                $query =  "SELECT a.codigo AS id, a.descripcion AS nombre FROM more_tipos_pc a;";
                                                echo imp_combo_w_fs12('tipoequipo', $query, '1', $usu, $prellenado['TIPOEQUIPO']);
                                            ?>
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Legajo</label>
                                                <input type="text" class="form-control fs-12" name="legajo" id="legajo" value="<?php echo $prellenado['LEGAJO']; ?>" placeholder="Legajo">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Marca</label>
                                                <input type="text" class="form-control fs-12" id="marca" name="marca"  value="<?php echo $prellenado['MARCA']; ?>" placeholder="Marca">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Tipo Procesador</label>
                                                <input type="text" class="form-control fs-12 me-1" id="tipoprocesador" name="tipoprocesador"  value="<?php echo $prellenado['TIPOPROCESADOR']; ?>" placeholder="Tipo Procesador">
                                        </div>
                                </div>

                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Velocidad Procesador</label>
                                                <input type="text" class="form-control fs-12" id="velocidadprocesador"  name="velocidadprocesador"  value="<?php echo $prellenado['VELOCIDADPROCESADOR']; ?>" placeholder="Velocidad Procesador">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                                <label for="exampleInputEmail1" class="form-label m-0">Memoria RAM</label>
                                                <input type="text" class="form-control" id="memoriaram"  name="memoriaram" value="<?php echo $prellenado['MEMORIARAM']; ?>" placeholder="Memoria RAM">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >IP</label>
                                                <input type="text" class="form-control fs-12" id="ip"  name="ip" value="<?php echo $prellenado['IP']; ?>" placeholder="IP">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >MAC</label>
                                                <input type="text" class="form-control fs-12" id="mac"  name="mac" value="<?php echo $prellenado['MAC']; ?>" placeholder="MAC">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Numero Serie</label>
                                                <input type="text" class="form-control fs-12" id="numeroserie"  name="numeroserie" value="<?php echo $prellenado['NUMEROSERIE']; ?>" placeholder="Numero Serie">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Clave 1</label>
                                        <input type="text" class="form-control fs-12" id="clave1"  name="clave1" value="<?php echo $prellenado['CLAVE1']; ?>" placeholder="Clave 1">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Clave 2</label>
                                        <input type="text" class="form-control fs-12" id="clave2"  name="clave2" value="<?php echo $prellenado['CLAVE2']; ?>" placeholder="Clave 2">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Clave 3</label>
                                        <input type="text" class="form-control fs-12" id="clave3"  name="clave3" value="<?php echo $prellenado['CLAVE3']; ?>" placeholder="Clave 3">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Clave 4</label>
                                        <input type="text" class="form-control fs-12" id="clave4"  name="clave4" value="<?php echo $prellenado['CLAVE4']; ?>" placeholder="Clave 4">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Clave 5</label>
                                        <input type="text" class="form-control fs-12" id="clave5"  name="clave5" value="<?php echo $prellenado['CLAVE5']; ?>" placeholder="Clave 5">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Discos Informaci&oacuten</label>
                                        <input type="text" class="form-control fs-12" id="discoscod"  name="discoscod" value="<?php echo $prellenado['DISCOSCOD']; ?>" placeholder="Discos Informaci&oacuten">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Capacidad de disco</label>
                                        <input type="text" class="form-control fs-12" id="disco"  name="disco"  value="<?php echo $prellenado['DISCO']; ?>" placeholder="Capacidad de disco">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleInputEmail1" class="form-label m-0">Monitor</label>
                                            <?php
                                                $query =  "SELECT m.id, CONCAT(m.caption, ' (', m.manufacturer,')') AS nombre FROM ocs_monitors m WHERE m.hardware_id = (SELECT a.id FROM ocs_hardware a WHERE UPPER(a.name) LIKE UPPER('$codigopc%'));";
                                                echo imp_combo_w_fs12('id_monitores', $query, '2', $usu, $prellenado['ID_MONITORES']);
                                            ?>
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleInputEmail1" class="form-label m-0">Sistema Operativo</label>                                      
                                            <?php
                                                $query =  "SELECT a.sistemaoperativoid AS id, a.sistema AS nombre FROM more_sistemaoperativo a;";
                                                echo imp_combo_w_fs12('id_sistemaoperativo', $query, '1', $usu, $prellenado['ID_SISTEMAOPERATIVO']);
                                            ?>
                                    </div>
                                </div>
                                <?php
                                if($_SESSION['HABILITA_EDICION'] == 'SI'){
                                ?>
                                    <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                        <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                            <input type="hidden" name="codigopc"' id="codigopc" value="<?php echo $prellenado['CODIGOPC']; ?>" />
                                            <input type="hidden" name="obligatorio_id" id="obligatorio_id" value="<?php echo $prellenado['PCID']; ?>" />
                                            <input type="hidden" name="tipoequipo" id="tipoequipo"  value="<?php echo $prellenado['TIPOEQUIPO']; ?>" />
                                            <input type="submit" name="obligatorios_importar" id="obligatorios_importar" class="btn btn-pna-oscuro" style="width: 200px;" value="Importar" />
                                            &nbsp;
                                            <input name="guardar_importar" id="guardar_importar" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>
                         </div>  
                    </form>
                    
                    
                    
                </div>
            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
        <div class="accordion accordion-flush" id="accordion1">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse1" aria-expanded="false" aria-controls="flush-collapse1">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Edici&oacute;n de devoluci&oacute;n o baja</h6>
                </button>
            </h2>
            <div id="flush-collapse1" class="accordion-collapse collapse" data-bs-parent="#accordion1">
                <div class="accordion-body">
                    <div class="col-12">
            <div class="bg-white" >
                <div class="p-3">
                        <form id="form_guardar" name="form_guardar" method="post" action="">
                        <div class="row col-12">
                            
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="pais_linea" class="form-label m-0">Devuelto</label>
                                        <select class="form-control fs-12" name="devuelto" id="devuelto">
                                            <option value="N/A">Seleccione</option>
                                            <option value="0" <?php if($art['DEVUELTO']=='0') echo 'selected=selected'; ?> >No</option>
                                            <option value="1" <?php if($art['DEVUELTO']=='1') echo 'selected=selected'; ?> >Si</option>
                                        </select>
                                    </div>
                                </div>
                            
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="pais_linea" class="form-label m-0">De baja</label>
                                        <select class="form-control fs-12" name="de_baja" id="de_baja">
                                            <option value="N/A">Seleccione</option>
                                            <option value="0" <?php if($art['DE_BAJA']=='0') echo 'selected=selected'; ?> >No</option>
                                            <option value="1" <?php if($art['DE_BAJA']=='1') echo 'selected=selected'; ?> >Si</option>
                                        </select>
                                    </div>
                                </div>

<?php 

    if(!$art['FCH_BAJA'] or substr($art['FCH_BAJA'], 0,4) == '1900'){
        $fch_baja = substr($hoy_completo, 0, 10); 
    }else{
        $fch_baja = substr($art['FCH_BAJA'], 0, 10); 
    }

?>               
                            
    <div class="col-6 float-start p-1">
        <div class="mb-3">
            <label for="fecha" class="form-label m-0">Fecha de baja</label>
            <input type="date" class="form-control fs-12" id="fch_baja" name="fch_baja" value="<?php echo $fch_baja;  ?>" placeholder="Fecha de Baja">
        </div>
    </div>

    <div class="col-12 float-start p-1">
        <div class="mb-3">
            <label for="nota" class="form-label m-0">Nota devoluci&oacute;n</label>
            <input type="text" class="form-control fs-12" id="nota_devolucion" name="nota_devolucion" value="<?php echo $art['NOTA']; ?>" placeholder="Nota">
        </div>
    </div>
   
                                <?php
                                //echo "<h1>P2 -$nro_art</h1>";
                                //$nro_art = $art['NRO_ART'];
                                if($_SESSION['HABILITA_EDICION'] == 'SI'){
                                ?>
                                    <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                        <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                            <input type="hidden" name="nro_art"' id="nro_art" value="<?php echo $nro_art; ?>" />
                                            <input type="hidden" name="codigopc"' id="codigopc" value="<?php echo $codigopc; ?>" />
                                            <input name="guardar_baja" id="guardar_baja" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>
                         </div>  
                    </form>
                </div>
            </div>
                        
                        
                        
                        
                        
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    
    



