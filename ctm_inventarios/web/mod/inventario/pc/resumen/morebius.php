<?php 
//ini_set( 'display_errors', 'on' );
$rowmorbius = consultaMorebiusXCod($codigopc, '3', $usu);

if(!$nro_art) $nro_art  = Enmascarar_post('nro_art');
if(!$nro_art) $nro_art  = $rowmorbius['NRO_ART'];



$morbius_importar  = Enmascarar_post('morbius_importar');

// consultaMorebiusXNro($nro_art = '2260', '1', $usu);


if($morbius_importar){

    $disco = floor($rowhardware['TOTAL_DISCO_LIBRE']/(1024)).'GB de ('.floor($rowhardware['TOTAL_DISCO']/(1024)).'GB)'; 
    
    // Suponiendo que $hwinv, $rowhardware y $disco ya están definidos con los datos relevantes
    $detalle = [
        "detalle" => [
            "tipo" => $rowhanterior['TIPOEQUIPO'],
            "modelo" => $rowhardware['SMODEL'] . ' (' . $rowhardware['MMANUFACTURER'] . ')',
            "procesador" => [
                "tipo" => $rowhardware['PROCESSORT'],
                "cores" => $rowhardware['PROCESSORN'],
                "velocidad" => $rowhardware['PROCESSORS']
            ],
            "memoria" => [
                "capacidad" => $rowhardware['MEMORY']
            ],
            "disco" => [
                "capacidad" => $disco
            ]
        ]
    ];

    // Convertir el array a JSON
    $art['DETALLE'] = htmlspecialchars(json_encode($detalle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    $art['REFERENCIA'] = htmlspecialchars(json_encode(["CodigoPC" => $rowhanterior['CODIGOPC'],"SR" => $rowhanterior['SR'],"OC" => $rowhanterior['OC'],"Monto" => $rowhanterior['MONTO'],"FechaCompra" => enmascararFecha($rowhanterior['FECHACOMPRA'])], JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8');
    $art['MODELO']  = $rowhardware['SMODEL'].' ('.$rowhardware['MMANUFACTURER'].')'; 
    $art['NOTA'] = $hoy." - ".$rowhanterior['OBSERVACIONES']; 
    
    $sql="SELECT marcaid, detmarca FROM artimarcas WHERE UPPER(detmarca) LIKE ('%".$rowhardware['SMODEL']."%');";
    //echo $sql;
    $rowso=sql_do_array(sql_do_query($sql,'3',$usu),'3',$usu);
    
    if($rowso['MARCAID']){
        $id_marca = $rowso['MARCAID'];
    }else{

        $sqlnuevo="SELECT (MAX(marcaid)+1) AS nuevo_id FROM artimarcas;";
        //echo $sql;
        $nro=sql_do_array(sql_do_query($sqlnuevo,'3',$usu),'3',$usu);

        $id_marca = $nro['NUEVO_ID'];

        $sql = "INSERT INTO artimarcas(
                            marcaid, 
                            detmarca
                )VALUES(
                            $id_marca, 
                            '".$rowhardware['SMODEL']."'
                );";

        //echo $sql;
        $result = sql_do_query($sql, '3', $usu);
    }
            
            $art['MARCA'] = $id_marca; 
            $art['MODELO'] = $rowhardware['SMODEL'].' ('.$rowhardware['MMANUFACTURER'].')'; 
            $art['NSERIE'] = $rowhardware['UUID'];
            $art['IMEI'] = $rowhardware['MACADDR']; 
            $art['PLANC'] = $rowhardware['WINPRODKEY']; 
            $art['NSERIE_MODEM'] = $rowhardware['IPADDR']; 
            $art['SECTOR'] = $usuario['CENTROCOSTO'];
            $art['FECHA'] = $rowhanterior['FECHACOMPRA'];
            $art['TIPO_ART'] = '3';
            
            if(!$art['UBICAID']) $art['UBICAID'] = '1';
            
            if($art['PAIS_LINEA']=='N/A' or !$art['PAIS_LINEA']){
                if($usuario['EMPNACIONALIDAD']=='Argentina '){
                    $art['PAIS_LINEA'] = 'RA';
                }else{
                    $art['PAIS_LINEA'] = 'ROU';
                }
            }
            
    /*
    echo '<script>swal({
               title: "Buen Trabajo!",
               text: "Has importado los datos del inventario para su utilizacion en Morebius!",
               icon: "success",
               dangerMode: false,
               buttons: {
                    confirm: {text: "Continuar",
                              value: true,
                },
            },
        })
    </script>';*/
}     

?>
    <style>
        /* Clase personalizada para establecer la altura y el borde verde */
        .success-container {
            height: 20mm; /* Altura en milímetros */
            border: 1px solid green; /* Borde en verde */
        }
        
        .error-container {
            height: 20mm; /* Altura en milímetros */
            border: 1px solid red; /* Borde en rojo */
        }
    </style>


<div class="col-12 separar1 p-0 float-start bg-white">
    <div class="bg-white">
        <div class="p-3">
            <div class="col-12 pb-2 d-flex align-items-center">
                <img src="img/icon/person.png" class="me-2" style="height: 30px; width: 30px;">
                <h6 class="mb-0 align-self-center">Registros en Morebius</h6>
            </div>
            <div class="col-12">
<br />               
<?php 

    $sql="SELECT * FROM inve_tipos_pc pc WHERE pc.codigo = '".$rowhanterior['TIPOEQUIPO']."';";
    //echo $sql;
    $rowtipo_pc=sql_do_array(sql_do_query($sql,'1',$usu));
    
    if($rowtipo_pc['MOREBIUS']=='1'){    
         echo '
                    <div class="error-container w-100 p-3">
                        <p class="text-danger">Este equipo debe estar dado de alta en el sistema.</p>
                    </div>
         '; 
    }else{
         echo '
                    <div class="success-container w-100 p-3">
                        <p class="text-success">Este equipo debe no estar dado de alta en el sistema.</p>
                    </div>
         '; 
         
        $hab_ed = 'SI';
        $hab_ed_tx = ' Habilitar Edición ';
        $hab_styl = 'btn-pna-oscuro';
        $_SESSION['HABILITA_EDICION'] = 'NO';
    }           
   
     if($row['DE_BAJA']!='0' and $rowtipo_pc['MOREBIUS']=='1'){               
?>               
                
<br />                
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th align="center">ORD</th>
                <th align="center">ESTADO</th>
                <th align="center">DESCRIPCI&Oacute;N</th>
                <th align="center">DETALLES</th>
            </tr>
        </thead>
        <tbody>
            <?php 

            if($rowmorbius['DEVUELTO']!='0'){
                    $devuelto = '<strong>DEVUELTO:</strong> SI<br>';
                }else{
                    $devuelto = '';
                }
                
                if($rowmorbius['DE_BAJA']!='0'){
                    $de_baja = '<strong>BAJA:</strong> SI('.enmascararFecha($rowmorbius['FCH_BAJA']).')<br>';
                }else{
                    $aticulo[$n] = $rowmorbius;
                    $de_baja = '';
                }
                
                //var_dump($row);
            ?>
            <tr>
                <td class="align-middle text-center"><strong><?php echo $rowmorbius['NRO_ART'];?></strong></td>
                <td class="text-left">
                    <strong>INGRESO:</strong> <?php echo enmascararFecha($rowmorbius['FECHA']);?><br>
                    <strong>ACTUALIZACI&Oacute;N:</strong> <?php echo enmascararFecha($rowmorbius['INGRESO']);?><br>
                    <?php echo $devuelto; ?>
                    <?php echo $de_baja; ?>
                </td>
                <td>
                    <strong>DETALLE:</strong> <?php echo $rowmorbius['DETALLE'];?><br>
                    <strong>NOTA:</strong> <?php echo $rowmorbius['NOTA'];?><br>
                    <strong>REFERENCIAS:</strong> <?php echo $rowmorbius['REFERENCIA'];?><br>
                </td>
                <td>
                    <strong>TIPO CELULAR:</strong> <?php echo $rowmorbius['TIPO_CELULAR'];?><br>
                    <strong>UBICACI&Oacute;N:</strong> <?php echo $rowmorbius['UBICACION'];?><br>
                    <strong>EMPLEADO:</strong> <?php echo imp_usuario($rowmorbius['LEGAJO_ART'], '1', $usu);?><br>
                    <strong>SECTOR:</strong> <?php echo imp_est_accc($rowmorbius['SECTOR'], '1', $usu);?><br>
                </td>
            </tr>
        </tbody>
    </table>
</div>  
              
         
<?php    

     //echo "<h1>P0 -$nro_art</h1>";
        
     } else{
?>  
    <br />  
    <div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th align="center">ESTE EQUIPO NO NECESITA ESTAR REGISTRADO</th>
            </tr>
        </thead>
    </table>
<?php         
     }  

?>               
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
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Edici&oacute;n de datos</h6>
                </button>
            </h2>
            <div id="flush-collapse3" class="accordion-collapse collapse" data-bs-parent="#accordion3">
                <div class="accordion-body">
                    <div class="col-12">
                        
            <div class="bg-white" >
                <div class="p-3">
                        <form id="form_guardar" name="form_guardar" method="post" action="">
                        <div class="row col-12">
           
    <div class="col-3 float-start p-1">
        <div class="mb-3">
            <label for="fecha" class="form-label m-0">Fecha</label>
            <input type="date" class="form-control fs-12" id="fecha" name="fecha" value="<?php echo substr($art['FECHA'], 0, 10); ?>" placeholder="Fecha">
        </div>
    </div>

    <div class="col-3 float-start p-1">
        <div class="mb-3">
            <label for="exampleInputEmail1" class="form-label m-0">Tipo de art&iacute;culo</label>
                <?php
                    $query = "SELECT tipo_art AS id, detalle As nombre FROM articulos_tipos;";
                    echo imp_combo_w_fs12('tipo_art', $query, '3', $usu, $art['TIPO_ART']);
                ?>
        </div>
    </div>
                           
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleInputEmail1" class="form-label m-0">Sector</label>
                                            <?php
                                                $query = "SELECT ConIdcc AS id, ConNomCc AS nombre FROM vw_accc;";
                                                echo imp_combo_w_fs12('sector', $query, '1', $usu, $art['SECTOR']);
                                            ?>
                                    </div>
                                </div>

    <div class="col-3 float-start p-1">
        <div class="mb-3">
            <label for="legajo_art" class="form-label m-0">Legajo</label>
            <input type="text" class="form-control fs-12" id="legajo_art" name="legajo_art" value="<?php echo $art['LEGAJO_ART']; ?>" placeholder="Legajo Art">
        </div>
    </div>

                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="pais_linea" class="form-label m-0">Pa&iacute;s</label>
                                        <select class="form-control fs-12" name="pais_linea" id="pais_linea">
                                            <option value="N/A">Seleccione</option>
                                            <option value="RA" <?php if($art['PAIS_LINEA']=='RA') echo 'selected=selected'; ?> >Argentina</option>
                                            <option value="ROU"<?php if($art['PAIS_LINEA']=='ROU') echo 'selected=selected'; ?> >Uruguay</option>
                                        </select>
                                    </div>
                                </div>
                            
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleInputEmail1" class="form-label m-0">Ubicaci&oacute;n</label>
                                            <?php
                                                $query =  "SELECT ubicaid AS id, ubicadet AS nombre FROM artcelubi WHERE ubica_activa = '1';";
                                                echo imp_combo_w_fs12('ubicaid', $query, '3', $usu, $art['UBICAID']);
                                            ?>
                                    </div>
                                </div>
    
    <div class="col-3 float-start p-1">
        <div class="mb-3">
            <label for="exampleInputEmail1" class="form-label m-0">Marca</label>
                <?php
                    $query = "SELECT marcaid AS id, detmarca AS nombre FROM artimarcas;";
                    echo imp_combo_w_fs12('marca', $query, '3', $usu, $art['MARCA']);
                ?>
        </div>
    </div>
                            
    <div class="col-3 float-start p-1">
        <div class="mb-3">
            <label for="modelox" class="form-label m-0">Modelo</label>
            <input type="text" class="form-control fs-12" id="modelox" name="modelox" value="<?php echo $art['MODELO']; ?>" placeholder="Modelo">
        </div>
    </div>
    
    <div class="col-3 float-start p-1">
        <div class="mb-3">
            <label for="nserie" class="form-label m-0">Número de serie</label>
            <input type="text" class="form-control fs-12" id="nserie" name="nserie" value="<?php echo $art['NSERIE']; ?>" placeholder="Número de Serie">
        </div>
    </div>
    
    <div class="col-3 float-start p-1">
        <div class="mb-3">
            <label for="imei" class="form-label m-0">MAC</label>
            <input type="text" class="form-control fs-12" id="imei" name="imei" value="<?php echo $art['IMEI']; ?>" placeholder="IMEI">
        </div>
    </div>
    
     <div class="col-3 float-start p-1">
        <div class="mb-3">
            <label for="planc" class="form-label m-0">Clave</label>
            <input type="text" class="form-control fs-12" id="planc" name="planc" value="<?php echo $art['PLANC']; ?>" placeholder="Plan C">
        </div>
    </div>
    
    <div class="col-3 float-start p-1">
        <div class="mb-3">
            <label for="nserie_modem" class="form-label m-0">IP</label>
            <input type="text" class="form-control fs-12" id="nserie_modem" name="nserie_modem" value="<?php echo $art['NSERIE_MODEM']; ?>" placeholder="N° Serie Modem">
        </div>
    </div>
    
    <div class="col-3 float-start p-1">
        <div class="mb-3">
            <label for="garantia_vence" class="form-label m-0">Garant&iacute;a</label>
            <input type="date" class="form-control fs-12" id="garantia_vence" name="garantia_vence" value="<?php echo substr($art['GARANTIA_VENCE'], 0, 10); ?>" placeholder="Garantía Vence">
        </div>
    </div>
                            
    <div class="col-9 float-start p-1">
        <div class="mb-3">
            <label for="referencia" class="form-label m-0">Referencia</label>
            <input type="text" class="form-control fs-12" id="referencia" name="referencia"  value="<?php echo $art['REFERENCIA']; ?>"  placeholder="Referencia">
        </div>
    </div>
             
    <div class="col-12 float-start p-1">
        <div class="mb-3">
            <label for="detalle" class="form-label m-0">Detalle</label>
            <input type="text" class="form-control fs-12" id="detalle" name="detalle" value="<?php echo $art['DETALLE']; ?>" placeholder="Detalle">
        </div>
    </div>          
                            
    <div class="col-12 float-start p-1">
        <div class="mb-3">
            <label for="nota" class="form-label m-0">Nota</label>
            <input type="text" class="form-control fs-12" id="nota" name="nota" value="<?php echo $art['NOTA']; ?>" placeholder="Nota">
        </div>
    </div>

                                <?php
                                //echo "<h1>P1 -$nro_art</h1>";
                                if($_SESSION['HABILITA_EDICION'] == 'SI'){
                                ?>
                                    <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                        <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                            <input type="hidden" name="nro_art"' id="nro_art" value="<?php echo $nro_art; ?>" />
                                            <input type="hidden" name="codigopc"' id="codigopc" value="<?php echo $codigopc; ?>" />
                                            <input type="submit" name="morbius_importar" id="morbius_importar" class="btn btn-pna-oscuro" style="width: 200px;" value="Importar" />&nbsp;
                                            <input name="guardar_morebius" id="guardar_morebius" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
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
    
<?php

            //var_dump($rowmorbius);

            $art['DEVUELTO'] = $rowmorbius['DEVUELTO'];
            $art['DE_BAJA'] = $rowmorbius['DE_BAJA']; 
            $art['FCH_BAJA'] = $rowmorbius['FCH_BAJA']; 
            $art['NOTA_DEVOLUCION'] = $rowmorbius['NOTA_DEVOLUCION']; 

            //var_dump($art);
?>
    
    
    
    
    
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
            <input type="text" class="form-control fs-12" id="nota_devolucion" name="nota_devolucion" value="<?php echo $art['NOTA_DEVOLUCION']; ?>" placeholder="Nota">
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
                                            <input name="guardar_baja_morebius" id="guardar_baja_morebius" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
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
                <th align="center">ORD</th>
                <th align="center">ESTADO</th>
                <th align="center">DESCRIPCI&Oacute;N</th>
                <th align="center">DETALLES</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $sql="   SELECT ah.ordinal, 
                                ah.nro_art, 
                                ah.fecha_des, 
                                ah.fecha_has, 
                                ah.sector,
                                ah.legajo_art, 
                                ah.nota, 
                                ah.usuario, 
                                ah.ingreso, 
                                ah.nota_devolucion, 
                                ah.pais_linea 
                           FROM articulos_his ah
                          WHERE ah.nro_art = '$nro_art'
                       ORDER BY ah.ordinal DESC;";
            //echo $sql;
            $result = sql_do_query($sql,'3',$usu);
            $n=0;
            while($rowx=sql_do_array($result)){
                $responsable = getUsuario($rowx['LEGAJO_ART'], '1',$usu);
                $tecnico = getUsuario($rowx['USUARIO'], '1',$usu);
                $cc = getCentroCosto($rowx['SECTOR'], '1',$usu);
            ?>
            <tr>
                <td class="align-middle text-center">
                    <strong><?php echo $rowx['ORDINAL'];?></strong>
                </td>
                <td class="text-left">
                    <strong>DESDE:</strong> <?php echo enmascararFecha($rowx['FECHA_DES']);?><br>
                    <strong>HASTA:</strong> <?php echo enmascararFecha($rowx['FECHA_HAS']);?><br>
                </td>
                <td>
                    <strong>NOTA:</strong> <?php echo $rowx['NOTA'];?><br>
                    <strong>PAIS:</strong> <?php echo $rowx['PAIS_LINEA'];?><br>
                    <strong>SECTOR:</strong> <?php echo $cc;?><br>
                    <strong>RESPONSABLE:</strong> <?php echo $responsable['NOMBRE_STR'];?><br>
                </td>
                <td>
                    <strong>DEVOLUCI&Oacute;N:</strong> <?php echo $rowx['NOTA_DEVOLUCION'];?><br>
                    <strong>T&Eacute;CNICO:</strong> <?php echo $tecnico['NOMBRE_STR'];?><br>
                    <strong>FECHA:</strong> <?php echo $rowx['INGRESO'];?><br>
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
</div>