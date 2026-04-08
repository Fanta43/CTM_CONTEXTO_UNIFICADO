<style type="text/css">

#area #form1 .fondo-II img {
	margin: 5px;
}

</style>

<?php
//ini_set( 'display_errors', 'on' );

$monitorid = Enmascarar_post('monitorid');

$guardar_monitor = Enmascarar_post('guardar_monitor');

$guardar_baja = Enmascarar_post('guardar_baja');

$codigo = Enmascarar_post('codigo');
if(!$codigo) $codigo = 'M'.$monitorid;
$marca = Enmascarar_post('marca');
if(!$marca) $marca = '-';
$numeroserie = Enmascarar_post('numeroserie');
if(!$numeroserie) $numeroserie = '-';
$descripcion = Enmascarar_post('descripcion');
if(!$descripcion) $descripcion = '-';
$id_proveedores = Enmascarar_post('id_proveedores');
if(!$id_proveedores) $id_proveedores = '0';
$pcc = Enmascarar_post('pcc');
if(!$pcc) $pcc = '-';
$oc = Enmascarar_post('oc');
if(!$oc) $oc = '-';
$fechacompra = substr(Enmascarar_post('fechacompra'), 0, 10);
if(!$fechacompra) $fechacompra = $hoy;
$fechavtogarantia = substr(Enmascarar_post('fechavtogarantia'),0,10);
if(!$fechavtogarantia) $fechavtogarantia = date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d") + 365));
$baja = Enmascarar_post('baja');
if(!$baja) $baja = '0';
$fechabaja = substr(Enmascarar_post('fechabaja'),0,10);
if(!$fechabaja) $fechabaja = '0000-00-00';
$motivobaja = Enmascarar_post('motivobaja');
if(!$motivobaja) $motivobaja = '-';
$id_zonas_inventario = Enmascarar_post('id_zonas_inventario');
if(!$id_zonas_inventario) $id_zonas_inventario = '3';
$estadoid = Enmascarar_post('estadoid');
if(!$estadoid) $estadoid = '1';

if($monitorid){
    $sql = " SELECT monitorid, 
                    codigo, 
                    marca, 
                    numeroserie, 
                    descripcion, 
                    id_proveedores,
                    pcc, 
                    oc, 
                    fechacompra, 
                    fechavtogarantia, 
                    baja, 
                    fechabaja,
                    motivobaja, 
                    id_zonas_inventario, 
                    estadoid, 
                    auditoria_usu, 
                    auditoria_fecha 
               FROM inve_monitores
              WHERE monitorid = '$monitorid';";
    //echo $sql_id_pc;
    $row_prellenado = sql_do_array(sql_do_query($sql, '1', $usu));
     
}

if($guardar_baja){
    
        if ($baja == '0') {
            $estadoid = '1';
        } else {
            $estadoid = '0';
        }

        $sql = "UPDATE inve_monitores 
                   SET baja = '$baja', 
                       fechabaja = '$fechabaja',  
                       motivobaja = '$motivobaja',  
                       estadoid = '$estadoid', 
                       auditoria_usu = '$usu', 
                       auditoria_fecha = '$hoy'
                 WHERE monitorid = '$monitorid';";

        //echo $sql;
        $result = sql_do_query($sql, '1', $usu);

        if($result){
            echo '<script>swal({
                       title: "Buen Trabajo!",
                       text: "Has dado de baja el monitor!",
                       icon: "success",
                       dangerMode: false,
                       buttons: {
                           confirm: {text: "Continuar", value: true},
                       },
                  })
                  .then((value) => {
                      window.location.replace("index.php?estado=21");
                  })    
                 </script>';
        }
        

}

if($guardar_monitor){
  
    if($monitorid){
        
        $sql = "UPDATE inve_monitores 
                   SET marca = '$marca', 
                       numeroserie = '$numeroserie', 
                       descripcion = '$descripcion', 
                       id_proveedores = '$id_proveedores',
                       pcc = '$pcc', 
                       oc = '$oc', 
                       fechacompra = '$fechacompra', 
                       fechavtogarantia = '$fechavtogarantia', 
                       id_zonas_inventario = '$id_zonas_inventario', 
                       estadoid = '$estadoid', 
                       auditoria_usu = '$usu', 
                       auditoria_fecha = '$hoy'
                 WHERE monitorid = '$monitorid';";

        
                $result = sql_do_query($sql, '1', $usu);

                if($result){
                    echo '<script>swal({
                               title: "Buen Trabajo!",
                               text: "Has actualizado el monitor!",
                               icon: "success",
                               dangerMode: false,
                               buttons: {
                                   confirm: {text: "Continuar", value: true},
                               },
                          })
                          .then((value) => {
                              window.location.replace("index.php?estado=21");
                          })    
                         </script>';
                }
        
    }else{
        
        $monitorid = get_id('INVE_MONITORES');
        
       
$sql = "INSERT INTO inve_monitores (
                    monitorid,
                    marca, 
                    numeroserie, 
                    descripcion, 
                    id_proveedores, 
                    pcc, 
                    oc, 
                    fechacompra, 
                    fechavtogarantia, 
                    baja, 
                    fechabaja, 
                    motivobaja, 
                    id_zonas_inventario, 
                    estadoid, 
                    auditoria_usu, 
                    auditoria_fecha
                ) VALUES (
                    '$monitorid',
                    '$marca', 
                    '$numeroserie', 
                    '$descripcion', 
                    '$id_proveedores', 
                    '$pcc', 
                    '$oc', 
                    '$fechacompra', 
                    '$fechavtogarantia', 
                    '$baja', 
                    '$fechabaja', 
                    '$motivobaja', 
                    '$id_zonas_inventario', 
                    '$estadoid', 
                    '$usu', 
                    '$hoy'
                )";

        
        $result = sql_do_query($sql, '1', $usu);


            if($result){
                echo '<script>swal({
                           title: "Buen Trabajo!",
                           text: "Has dado de alta el nuevo Monitor!",
                           icon: "success",
                           dangerMode: false,
                           buttons: {
                               confirm: {text: "Continuar", value: true},
                           },
                      })
                      .then((value) => {
                          window.location.replace("index.php?estado=21");
                      })    
                     </script>';
            }
    } 
}

?>


<br />
        <div class="col-12 p-2 pb-0"> 
    <div class='titulo_mediano_center_rojo'><?php echo $mensaje;?></div>
        <div class="row flex-column align-items-start" style="height: 40px;">
            <strong style="font-size: 25px;">Definici&oacute;n del monitor</strong>
        </div>
    <br />
    <form id="form1" name="form1" method="post" action="">
    <div class="row flex-column align-items-start bg-white">
                <div class="p-3">
                        <div class="col-12">
                            
<div class="col-3 float-start p-1">
    <div class="mb-3">
        <label for="codigo" class="m-0">C&oacute;digo</label>
        <input type="text" class="form-control fs-12" name="codigo" id="codigo" value="<?php echo $row_prellenado['CODIGO']; ?>" placeholder="C&oacute;digo del equipo">
    </div>
</div>

<div class="col-3 float-start p-1">
    <div class="mb-3">
        <label for="marca" class="m-0">Marca</label>
        <input type="text" class="form-control fs-12" name="marca" id="marca" value="<?php echo $row_prellenado['MARCA']; ?>" placeholder="Marca del equipo">
    </div>
</div>

<div class="col-3 float-start p-1">
    <div class="mb-3">
        <label for="numeroserie" class="m-0">N&uacute;mero de Serie</label>
        <input type="text" class="form-control fs-12" name="numeroserie" id="numeroserie" value="<?php echo $row_prellenado['NUMEROSERIE']; ?>" placeholder="Número de serie del equipo">
    </div>
</div>

<div class="col-3 float-start p-1">
    <div class="mb-3">
        <label for="descripcion" class="m-0">Descripci&oacute;n</label>
        <input type="text" class="form-control fs-12" name="descripcion" id="descripcion" value="<?php echo $row_prellenado['DESCRIPCION']; ?>" placeholder="Descripci&oacute;n del equipo">
    </div>
</div>

                            
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="ubicacionSelect" class=" m-0">Proveedor</label>
                                        <select class="form-control" name="id_proveedores" id="id_proveedores">
                                            <?php
                                            $sql = "SELECT proveedorid AS id, nombre AS nombre FROM inve_proveedores WHERE estadoid = '1';";
                                            $result = sql_do_query($sql, '1', $usu);
                                            while ($row = sql_do_array($result)) {
                                                if($row['ID'] == $row_prellenado['ID_PROVEEDORES']){$sele = ' selected = selected ';}else{$sele = '';}
                                                echo '<option '.$sele.' value="' . htmlspecialchars($row['ID']) . '">' . htmlspecialchars($row['NOMBRE']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div> 

<div class="col-3 float-start p-1">
    <div class="mb-3">
        <label for="pcc" class="m-0">PCC</label>
        <input type="text" class="form-control fs-12" name="pcc" id="pcc" value="<?php echo $row_prellenado['PCC']; ?>" placeholder="PCC">
    </div>
</div>

<div class="col-3 float-start p-1">
    <div class="mb-3">
        <label for="oc" class="m-0">OC</label>
        <input type="text" class="form-control fs-12" name="oc" id="oc" value="<?php echo $row_prellenado['OC']; ?>" placeholder="Orden de compra">
    </div>
</div>

<div class="col-3 float-start p-1">
    <div class="mb-3">
        <label for="fechacompra" class="m-0">Fecha de Compra</label>
        <input type="date" class="form-control fs-12" name="fechacompra" id="fechacompra" value="<?php if($row_prellenado['FECHACOMPRA']){echo substr($row_prellenado['FECHACOMPRA'], 0, 10);}else{echo $hoy;} ?>" placeholder="Fecha de compra del equipo">
    </div>
</div>

<div class="col-3 float-start p-1">
    <div class="mb-3">
        <label for="fechavtogarantia" class="m-0">Fecha Vto Garantía</label>
        <input type="date" class="form-control fs-12" name="fechavtogarantia" id="fechavtogarantia" value="<?php if($row_prellenado['FECHAVTOGARANTIA']){echo substr($row_prellenado['FECHAVTOGARANTIA'], 0, 10);}else{echo date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d") + 90));} ?>" placeholder="Fecha de vencimiento de la garantía">
    </div>
</div>



                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="ubicacionSelect" class=" m-0">Zona de Inventario</label>
                                        <select class="form-control" name="id_zonas_inventario" id="id_zonas_inventario">
                                            <?php
                                            $sql = "SELECT zonaid AS id, zona AS nombre FROM inve_zonas_inventario WHERE estadoid = '1';";
                                            $result = sql_do_query($sql, '1', $usu);
                                            while ($row = sql_do_array($result)) {
                                                if($row['ID'] == $row_prellenado['ID_ZONAS_INVENTARIO']){$sele = ' selected = selected ';}else{$sele = '';}
                                                echo '<option '.$sele.' value="' . htmlspecialchars($row['ID']) . '">' . htmlspecialchars($row['NOMBRE']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div> 


                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="ubicacionSelect" class=" m-0">Estado</label>
                                        <select class="form-control" name="estadoid" id="estadoid">
                                            <?php
                                            $sql = "SELECT estadoid, descripcion FROM inve_estados WHERE tabla = 'GRAL' ORDER BY estadoid DESC;";
                                            $result = sql_do_query($sql, '1', $usu);
                                            while ($row = sql_do_array($result)) {
                                                if($row['ESTADOID'] == $row_prellenado['ESTADOID']){$sele = ' selected = selected ';}else{$sele = '';}
                                                echo '<option '.$sele.' value="' . htmlspecialchars($row['ESTADOID']) . '">' . htmlspecialchars($row['DESCRIPCION']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div> 
                            
                                <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                    <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                        <input name="monitorid" id="monitorid" type="hidden" value="<?php echo $monitorid; ?>" />
                                        <input name="guardar_monitor" id="guardar_monitor" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                    </div>
                                </div>                      
                         </div>
          </div>
    </div>
    </form>
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
                        
    <form id="form1" name="form1" method="post" action="">
    <div class="row flex-column align-items-start bg-white">
                <div class="p-3">
                        <div class="col-12">
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="pais_linea" class="form-label m-0">De baja</label>
                                        <select class="form-control fs-12" name="baja" id="baja">
                                            <option value="N/A">Seleccione</option>
                                            <option value="0" <?php if($row_prellenado['BAJA']=='0') echo 'selected=selected'; ?> >No</option>
                                            <option value="1" <?php if($row_prellenado['BAJA']=='1') echo 'selected=selected'; ?> >Si</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="fechabaja" class="m-0">Fecha de Baja</label>
                                        <input type="date" class="form-control fs-12" name="fechabaja" id="fechabaja" value="<?php if($row_prellenado['FECHABAJA'] and substr($row_prellenado['FECHABAJA'], 0, 10)!='0000-00-00'){echo substr($row_prellenado['FECHABAJA'], 0, 10);}else{echo date("Y-m-d");} ?>" placeholder="Fecha de baja del equipo">
                                    </div>
                                </div>   

                                <div class="col-6 float-start p-1">
                                    <div class="mb-3">
                                        <label for="motivobaja" class="m-0">Motivo de Baja</label>
                                        <input type="text" class="form-control fs-12" name="motivobaja" id="motivobaja" value="<?php echo $row_prellenado['MOTIVOBAJA']; ?>" placeholder="Motivo de baja del equipo">
                                    </div>
                                </div>
   
                                <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                    <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                        <input name="monitorid" id="monitorid" type="hidden" value="<?php echo $monitorid; ?>" />
                                            <input name="guardar_baja" id="guardar_baja" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                        </div>
                                    </div>
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
    
    