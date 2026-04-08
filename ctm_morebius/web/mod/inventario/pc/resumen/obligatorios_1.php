<style>
    .mb-10 {
        margin-bottom: 10px;
    }
</style>

<?php

    $sql="       SELECT a.id, 
                        a.codigopc, 
                        a.legajo,
                        a.centrocosto,
                        a.marca, 
                        a.tipoprocesador, 
                        a.velocidadprocesador, 
                        a.memoriaram, 
                        a.observaciones, 
                        a.ip, 
                        a.mac, 
                        a.numeroserie, 
                        a.id_monitores, 
                        a.id_proveedores, 
                        a.pcc, 
                        a.fechavtogarantia, 
                        a.sr, 
                        a.oc, 
                        a.monto, 
                        a.fechacompra, 
                        a.baja, 
                        a.fechabaja, 
                        a.motivobaja,
                        a.id_zonas_inventario,
                        a.id_sistemaoperativo,
                        a.clave1,
                        a.clave2, 
                        a.clave3, 
                        a.clave4, 
                        a.clave5, 
                        a.numoblea, 
                        a.discoscod, 
                        a.disco
                   FROM more_pc a
                  WHERE a.codigopc = '$codigopc';";
       //echo $sql;
    //$rowhanterior=sql_do_array(sql_do_query($sql,'1',$usu));

    if(is_file("../aced_docentes/web/arch/$ndoc/".$rowhanterior['NDOC'].'.png')){
            $foto = "../aced_docentes/web/arch/$ndoc/".$rowhanterior['NDOC'].'.png';
    }else{
	    $foto = "img/fotopersonal.png";
    }
    
    if(is_file("../aced_docentes/web/arch/$ndoc/".$rowhanterior['NDOC'].'.png')){
            $foto = "../aced_docentes/web/arch/$ndoc/".$rowhanterior['NDOC'].'.png';
    }else{
	    $foto = "img/fotopersonal.png";
    }
    
//    if(is_file("../assb_detall_a/web/mod/personal/scripts/img_personalpna/img/$ndoc.jpg")){
//        $foto = "../assb_detall_a/web/mod/personal/scripts/img_personalpna/img/$ndoc.jpg";
//    }else{
//        $foto = "img/sin_imagen.jpg";
//    }  
    
    if($rowhanterior['CODIGO_SEXO'] == 'M') {$sexo = 'Maculino';}
    if($rowhanterior['CODIGO_SEXO'] == 'F') {$sexo = 'Femenino';}
    if($rowhanterior['CODIGO_SEXO'] == '')  {$sexo = 'VALOR_NO_ACTUALIZADO';}
    
    $fechaNacimiento = new DateTime($rowhanterior['FECHA_NACIMIENTO']);
    $fechaActual = new DateTime();

    $intervalo = $fechaNacimiento->diff($fechaActual);
    $edad = $intervalo->y;
    
    $sqle="  SELECT e.tipo_docenteid, 
                    (SELECT j.descripcion FROM dtll_jerarquias j WHERE e.retiro_jerarquia = j.jerarquiaid) AS jerarquia,
                    e.retiro_jerarquia AS jerarquiaid,
                    td.descripcion AS tipo_docente,
                    e.estado_civilid, 
                    e.fecha_ingreso,
                    l.descripcion AS localidad,
                    p.descripcion AS provincia,
                    e.nac_provinciaid, 
                    e.nac_localidadid 
               FROM doce_personalpna_extra e
         INNER JOIN doce_tipos_docentes td ON e.tipo_docenteid = td.tipo_docenteid
         INNER JOIN doce_provincias p ON e.nac_provinciaid = p.provinciaid
         INNER JOIN doce_localidades l ON e.nac_localidadid = l.localidadid
              WHERE e.ndoc = '$ndoc';";
    //echo $sqle;
    //$rowhanteriorextra=sql_do_array(sql_do_query($sqle,'1',$usu));

     
?>



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
                                                $query = "SELECT id, nombre FROM more_proveedores";
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
                                        <label for="ubicacionSelect" class="form-label m-0">Proveedor</label>
                                        
                                            <?php
                                                $query =  "SELECT a.conidcc AS id, a.connomcc AS nombre FROM vw_accc a;";
                                                echo imp_combo_w_fs12('centrocosto', $query, '1', $usu, $rowhanterior['CENTROCOSTO']);
                                            ?>

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
                                                $query =  "SELECT a.Id AS id, a.Zona AS nombre FROM more_zonas_inventario a;";
                                                echo imp_combo_w_fs12('id_zonas_inventario', $query, '1', $usu, $id_zona);
                                            ?>
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
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
                                            <input name="obligatorio_id" id="obligatorio_id" type="hidden" value="<?php echo $rowhanterior['ID']; ?>" />
                                            <input name="obligatorio_guardar" id="obligatorio_guardar" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
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
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Marca</label>
                                                <input type="text" class="form-control fs-12" id="marca" name="marca"  value="<?php echo $rowhanterior['MARCA']; ?>" placeholder="Marca">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Tipo Procesador</label>
                                                <input type="text" class="form-control fs-12 me-1" id="tipoprocesador" name="tipoprocesador"  value="<?php echo $rowhanterior['TIPOPROCESADOR']; ?>" placeholder="Tipo Procesador">
                                        </div>
                                </div>

                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Velocidad Procesador</label>
                                                <input type="text" class="form-control fs-12" id="velocidadprocesador"  name="velocidadprocesador"  value="<?php echo $rowhanterior['VELOCIDADPROCESADOR']; ?>" placeholder="Velocidad Procesador">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                                <label for="exampleInputEmail1" class="form-label m-0">Memoria RAM</label>
                                                <input type="text" class="form-control" id="memoriaram"  name="memoriaram" value="<?php echo $rowhanterior['MEMORIARAM']; ?>" placeholder="Memoria RAM">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >IP</label>
                                                <input type="text" class="form-control fs-12" id="ip"  name="ip" value="<?php echo $rowhanterior['IP']; ?>" placeholder="IP">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >MAC</label>
                                                <input type="text" class="form-control fs-12" id="mac"  name="mac" value="<?php echo $rowhanterior['MAC']; ?>" placeholder="MAC">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Numero Serie</label>
                                                <input type="text" class="form-control fs-12" id="numeroserie"  name="numeroserie" value="<?php echo $rowhanterior['NUMEROSERIE']; ?>" placeholder="Numero Serie">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Clave 1</label>
                                        <input type="text" class="form-control fs-12" id="clave1"  name="clave1" value="<?php echo $rowhanterior['CLAVE1']; ?>" placeholder="Clave 1">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Clave 2</label>
                                        <input type="text" class="form-control fs-12" id="clave2"  name="clave2" value="<?php echo $rowhanterior['CLAVE2']; ?>" placeholder="Clave 2">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Clave 3</label>
                                        <input type="text" class="form-control fs-12" id="clave3"  name="clave3" value="<?php echo $rowhanterior['CLAVE3']; ?>" placeholder="Clave 3">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Clave 4</label>
                                        <input type="text" class="form-control fs-12" id="clave4"  name="clave4" value="<?php echo $rowhanterior['CLAVE4']; ?>" placeholder="Clave 4">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Clave 5</label>
                                        <input type="text" class="form-control fs-12" id="clave5"  name="clave5" value="<?php echo $rowhanterior['CLAVE5']; ?>" placeholder="Clave 5">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Discos Informaci&oacuten</label>
                                        <input type="text" class="form-control fs-12" id="discoscod"  name="discoscod" value="<?php echo $rowhanterior['DISCOSCOD']; ?>" placeholder="Discos Informaci&oacuten">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Capacidad de disco</label>
                                        <input type="text" class="form-control fs-12" id="disco"  name="disco"  value="<?php echo $rowhanterior['DISCO']; ?>" placeholder="Capacidad de disco">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleInputEmail1" class="form-label m-0">id_monitores</label>
                                            <?php
                                                $query =  "SELECT m.id, CONCAT(m.caption, ' (', m.manufacturer,')') AS nombre FROM ocs_monitors m WHERE m.hardware_id = (SELECT a.id FROM ocs_hardware a WHERE UPPER(a.name) LIKE UPPER('$codigopc%'));";
                                                echo imp_combo_w_fs12('id_monitores', $query, '2', $usu, $rowhanterior['ID_MONITORES']);
                                            ?>
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleInputEmail1" class="form-label m-0">Sistema Operativo</label>                                      
                                            <?php
                                                $query =  "SELECT a.Id AS id, a.Sistema AS nombre FROM more_sistemaoperativo a;";
                                                echo imp_combo_w_fs12('id_sistemaoperativo', $query, '1', $usu, $rowhanterior['ID_SISTEMAOPERATIVO']);
                                            ?>
                                    </div>
                                </div>
                                <?php
                                if($_SESSION['HABILITA_EDICION'] == 'SI'){
                                ?>
                                    <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                        <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                            <input type="hidden" name="codigopc"' id="codigopc" value="<?php echo $rowhanterior['CODIGOPC']; ?>" />
                                            <input type="hidden" name="obligatorio_id" id="obligatorio_id" value="<?php echo $rowhanterior['ID']; ?>" />
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
    <div class="accordion accordion-flush" id="accordion3">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse3" aria-expanded="false" aria-controls="flush-collapse3">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Idiomas</h6>
                </button>
            </h2>
            <div id="flush-collapse3" class="accordion-collapse collapse">
                <div class="accordion-body p-3">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>DESCRIPCIÓN</th>
                                    <th>HABLA</th>
                                    <th>LEE</th>
                                    <th>ESCRIBE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sql="SELECT di.docente_idiomaid,
                                              di.idiomaid,
                                              di.habla,
                                              di.lee,
                                              di.escribe,
                                              m.descripcion
                                         FROM doce_docentes_idiomas di
                                         JOIN dedu_idiomas m ON di.idiomaid = m.idiomaid
                                        WHERE di.ndoc = '$ndoc'
                                     ORDER BY m.descripcion;";
                                //$result = sql_do_query($sql,'1',$usu);
                                while($row=sql_do_array($result)){ ?>
                                    <tr>
                                        <td><?php echo $row['DESCRIPCION'];?></td>
                                        <td><?php echo getNivelIdioma($row['HABLA']);?></td>
                                        <td><?php echo getNivelIdioma($row['LEE']);?></td>
                                        <td><?php echo getNivelIdioma($row['ESCRIBE']);?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
    <div class="accordion accordion-flush" id="accordion4">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse4" aria-expanded="false" aria-controls="flush-collapse4">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Familiares</h6>
                </button>
            </h2>
            
            
            
            
<div id="flush-collapse4" class="accordion-collapse collapse">
    <div class="accordion-body p-3">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>PARENTESCO</th>
                        <th>DNI</th>
                        <th>APELLIDO</th>
                        <th>NOMBRES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sql=" SELECT df.docente_familiaid,
                                  df.tipo_familiaid, 
                                  tf.descripcion AS tipo_familia, 
                                  df.dni_familia, 
                                  df.apellido, 
                                  df.nombres
                             FROM doce_docentes_familiares AS df
                             JOIN doce_tipo_familiares AS tf ON df.tipo_familiaid = tf.tipo_familiaid
                            WHERE df.ndoc = '$ndoc'
                         ORDER BY df.tipo_familiaid DESC;
                       ";
                    //echo $sql;
                    //$result = sql_do_query($sql,'1',$usu);
                    while($row=sql_do_array($result)){ 
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $row['TIPO_FAMILIA'];?></td>
                            <td class="text-center"><?php echo $row['DNI_FAMILIA'];?></td>
                            <td class="text-center"><?php echo $row['APELLIDO'];?></td>
                            <td class="text-center"><?php echo $row['NOMBRES'];?></td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

            
            
            
            
        </div>
    </div>
</div>

