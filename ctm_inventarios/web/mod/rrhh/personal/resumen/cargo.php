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
           
             
<div class="table-responsive">
    
    
    
    
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th align="center">NRO_ART</th>
                <th align="center">DETALLE</th>
                <th align="center">REFERENCIA</th>
                <th align="center">NOTA</th>
                <th align="center">SITUACI&Oacute;N</th>
            </tr>
        </thead>
        <tbody>
                        <?php
                        $sql = " SELECT a.nro_art, 
                                        a.fecha, 
                                        a.tipo_art, 
                                        a.detalle, 
                                        a.sector, 
                                        a.legajo_art, 
                                        a.nota, 
                                        a.referencia, 
                                        a.usuario, 
                                        a.ingreso, 
                                        a.devuelto, 
                                        a.de_baja, 
                                        a.fch_baja, 
                                        a.nro_linea, 
                                        a.pais_linea, 
                                        a.tipcel, 
                                        a.ubicaid, 
                                        a.pin, 
                                        a.puk, 
                                        a.marca, 
                                        a.modelo, 
                                        a.nserie, 
                                        a.imei, 
                                        a.planc, 
                                        a.nserie_modem, 
                                        a.estacion_tel, 
                                        a.garantia_vence, 
                                        a.artplan, 
                                        a.celprincipal 
                                   FROM articulos_con a
                                  WHERE a.legajo_art = '$legajo' ORDER BY a.nro_art DESC";
                        //echo $sql;
                        $result = sql_do_query($sql, '3', $usu);
                        $n = 0;
                        while ($row = sql_do_array($result)) {
                            $n++;
                            


                if($row['DEVUELTO']!='0'){
                    $devuelto = '<strong>DEVUELTO:</strong> SI<br>';
                }else{
                    $devuelto = '';
                }
                
                if($row['DE_BAJA']!='0'){
                    $de_baja = '<strong>BAJA:</strong> SI('.enmascararFecha($rowmorbius['FCH_BAJA']).')<br>';
                }else{
                    $aticulo[$n] = $rowmorbius;
                    $de_baja = '';
                }
                
                //var_dump($row);

                        ?>
                                <tr>
                                    <td class="align-middle text-center">
                                        <?php echo $row['NRO_ART'];;?>
                                    </td>
                                    <td class="text-left">
                                        <?php echo $row['DETALLE'];?>
                                    </td>
                                    <td class="text-left">
                                        <?php echo $row['REFERENCIA'];?>
                                    </td>
                                    <td class="text-left">
                                        <?php echo $row['NOTA'];?>
                                    </td>
                                    <td class="text-left">
                                        <strong>INGRESO:</strong> <?php echo enmascararFecha($row['FECHA']);?><br>
                                        <strong>ACTUALIZACI&Oacute;N:</strong> <?php echo enmascararFecha($row['INGRESO']);?><br>
                                        <?php echo $devuelto; ?>
                                        <?php echo $de_baja; ?>
                                    </td>
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

<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
        <div class="accordion accordion-flush" id="accordion3">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse3" aria-expanded="false" aria-controls="flush-collapse3">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Registros del Inventario AU</h6>
                </button>
            </h2>
            <div id="flush-collapse3" class="accordion-collapse collapse" data-bs-parent="#accordion3">
                <div class="accordion-body">
                    <div class="col-12">

                        
<div class="table-responsive">
    
    
    
    
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th align="center">NRO INVENTARIO</th>
                <th align="center">COMPRA</th>
                <th align="center">EQUIPO</th>
                <th align="center">HARDWARE</th>
                <th align="center">SITUACI&Oacute;N</th>
            </tr>
        </thead>
        <tbody>
            
                        <?php
                        $sql = " SELECT b.id, 
                                        b.codigopc, 
                                        b.legajo, 
                                        b.centrocosto, 
                                        b.marca, 
                                        b.tipoprocesador, 
                                        b.tipoequipo, 
                                        b.velocidadprocesador, 
                                        b.memoriaram, 
                                        b.observaciones, 
                                        b.ip, 
                                        b.mac,
                                        b.numeroserie, 
                                        b.id_monitores, 
                                        b.id_proveedores, 
                                        b.pcc, 
                                        b.fechavtogarantia, 
                                        b.sr, 
                                        b.oc, 
                                        b.monto, 
                                        b.fechacompra, 
                                        b.baja, 
                                        b.fechabaja, 
                                        b.motivobaja, 
                                        b.id_zonas_inventario, 
                                        b.id_sistemaoperativo, 
                                        b.clave1, 
                                        b.clave2, 
                                        b.clave3, 
                                        b.clave4, 
                                        b.clave5, 
                                        b.numoblea, 
                                        b.discoscod, 
                                        b.disco 
                                   FROM inve_pc b
                                  WHERE b.legajo = '$legajo' ORDER BY b.id DESC";
                        //echo $sql;
                        $result = sql_do_query($sql, '1', $usu);
                        $n = 0;
                        while ($row = sql_do_array($result)) {
                            $n++;
                            
                            if($row['baja']=='1'){
                                $baja = "SI";
                            }else{
                                $baja = "NO";
                            }
                            
                            
                        ?>
            <tr>
                <td class="align-middle text-center">
                    <strong><?php echo $row['ID'];?></strong>
                </td>
                <td class="text-left">
                    <strong>SR:</strong> <?php echo $row['SR'];?><br>
                    <strong>OC:</strong> <?php echo $row['OC'];?><br>
                    <strong>Fecha:</strong> <?php echo $row['FECHACOMPRA'];?><br>
                    <strong>Monto:</strong> <?php echo $row['MONTO'];?><br>
                    <strong>Nro Serie:</strong> <?php echo $row['NUMEROSERIE'];?><br>
                </td>
                <td class="text-left">
                    <strong>Nombre:</strong> <?php echo $row['CODIGOPC'];?><br>
                    <strong>IP:</strong> <?php echo $row['IP'];?><br>
                    <strong>MAC:</strong> <?php echo $row['MAC'];?><br>
                    <strong>Marca:</strong> <?php echo $row['MARCA'];?><br>
                    <strong>Modelo:</strong> <?php echo $row['MODELO'];?><br>
                </td>
                <td class="text-left">
                    <strong>Equipo:</strong> <?php echo $row['TIPOEQUIPO'];?><br>
                    <strong>Procesador:</strong> <?php echo $row['TIPOPROCESADOR'];?><br>
                    <strong>Velocidad:</strong> <?php echo $row['VELOCIDADPROCESADOR'];?><br>
                    <strong>RAM:</strong> <?php echo $row['MEMORIARAM'];?><br>
                    <strong>Disco:</strong> <?php echo $row['DISCO'];?><br>
                </td>
                <td class="text-left">
                   <strong>Baja:</strong> <?php echo $baja;?><br> 
                   <strong>Fecha de Baja:</strong> <?php echo enmascararFecha($row['FECHABAJA']);?><br>
                   <strong>Motivo:</strong> <?php echo $row['MOTIVOBAJA'];?><br>
                   <strong>Fecha de Garantedia:</strong> <?php echo enmascararFecha($row['FECHAVTOGARANTIA']);?><br>
                </td>
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
</div>