<div class="col-12 separar1 p-0 float-start bg-white">
    <div class="bg-white">
        <div class="p-3">
            <div class="col-12 pb-2 d-flex align-items-center">
                <img src="img/icon/person.png" class="me-2" style="height: 30px; width: 30px;">
                <h6>Registros en Morebius</h6>
            </div>
            <div class="col-12">
                
                
                
  
                
                

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th align="center">REGISTRO</th>
                <th align="center">ESTADO</th>
                <th align="center">DESCRIPCI&Oacute;N</th>
                <th align="center">DETALLES</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            
                $sql="   
                         SELECT a.nro_art,
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
                                (SELECT c.tipceldet FROM artipcel c WHERE c.tipcel = a.tipcel) AS tipo_celular,
                                a.ubicaid,
                                (SELECT d.UbicaDet FROM artcelubi d WHERE d.UbicaId = a.ubicaid) AS ubicacion,
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
                       WHERE UPPER(a.referencia) LIKE UPPER('$codigopc%')
                          OR UPPER(a.detalle) LIKE UPPER('$codigopc%') 
                          OR UPPER(a.nota) LIKE UPPER('$codigopc%');";
            
            $result = sql_do_query($sql,'3',$usu);
            $n=0;
            while($row=sql_do_array($result)){
                
                if($row['DEVUELTO']!='0'){
                    $devuelto = '<strong>DEVUELTO:</strong> SI<br>';
                }else{
                    $devuelto = '';
                }
                
                if($row['DE_BAJA']!='0'){
                    $de_baja = '<strong>BAJA:</strong> SI (<'.$row['FCH_BAJA'].')<br>';
                }else{
                    $de_baja = '';
                }
                
                //var_dump($row);
            ?>
            <tr>
                <td class="align-middle text-center"><strong><?php echo $row['NRO_ART'];?></strong></td>
                <td class="text-left">
                    <strong>INGRESO:</strong> <?php echo enmascararFecha($row['INGRESO']);?><br>
                    <?php echo $devuelto; ?>
                    <?php echo $de_baja; ?>
                </td>
                <td>
                    <strong>DETALLE:</strong> <?php echo $row['DETALLE'];?><br>
                    <strong>NOTA:</strong> <?php echo $row['NOTA'];?><br>
                    <strong>REFERENCIAS:</strong> <?php echo $row['REFERENCIA'];?><br>
                    
                </td>
                <td>
                    <strong>TIPO CELULAR:</strong> <?php echo $row['TIPO_CELULAR'];?><br>
                    <strong>UBICACI&Oacute;N:</strong> <?php echo $row['UBICACION'];?><br>
                    <strong>SECTOR:</strong> <?php echo imp_est_accc($row['SECTOR'], '1', $usu);?><br>
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





<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
        <div class="accordion accordion-flush" id="accordion1">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse1" aria-expanded="false" aria-controls="flush-collapse1">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Cursando</h6>
                </button>
            </h2>
            <div id="flush-collapse1" class="accordion-collapse collapse" data-bs-parent="#accordion1">
                <div class="accordion-body">
                    <div class="col-12">
                        
                        
                        
                        
                        
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th align="center">UA</th>
                <th align="center">HS</th>
                <th align="center">DESIGNACION</th>
                <th align="center">TAREAS</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sql="     SELECT a.destino,
                                    a.agrupamiento,
                                    a.categoriauniversitaria AS categoria,
                                    a.dedicacion,
                                    a.areadisciplinar AS area,
                                    a.tareas,
                                    DATE_FORMAT(a.fecha_posesion, '%d/%m/%Y') AS fecha_posesion,
                                    DATE_FORMAT(a.fecha_cese, '%d/%m/%Y') AS fecha_cese,
                                    a.caracter,
                                    a.horas
                               FROM vw_nombramientos a
                              WHERE a.nrodoc = '$ndoc'
                                AND YEAR(a.fecha_cese) >= YEAR(STR_TO_DATE('$hoy', '%Y-%m-%d'))
                           ORDER BY a.destino, a.fecha_posesion;";
            
            //$result = sql_do_query($sql,'1',$usu);
            $n=0;
            while($row=sql_do_array($result)){
            ?>
            <tr>
                <td class="text-center"><strong><?php echo $row['DESTINO'];?></strong></td>
                <td class="text-center"><strong><?php echo $row['HORAS'];?></strong></td>
                <td>
                    <strong>AGRUPAMIENTO:</strong> <?php echo $row['AGRUPAMIENTO'];?><br>
                    <strong>CATEGORIA:</strong> <?php echo $row['CATEGORIA'];?><br>
                    <strong>DEDICACION:</strong> <?php echo $row['DEDICACION'];?><br>
                    <strong>CARACTER:</strong> <?php echo $row['CARACTER'];?>
                </td>
                <td>
                    <strong>AREA DISCIPLINAR:</strong> <?php echo $row['AREA'];?><br>
                    <strong>TAREA:</strong> <?php echo $row['TAREAS'];?><br>
                    <strong>PERIODO:</strong> <?php echo $row['FECHA_POSESION'].' - '.$row['FECHA_CESE'];?>
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
        <div class="accordion accordion-flush" id="accordion2">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse2" aria-expanded="false" aria-controls="flush-collapse2">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Capacitaciones</h6>
                </button>
            </h2>
            <div id="flush-collapse2" class="accordion-collapse collapse" data-bs-parent="#accordion2">
                <div class="accordion-body">
                    
                    
                    
                    <div class="col-12">
                <div class="row border-bottom font-weight-bold">
                    <div class="col text-center">NIVEL</div>
                    <div class="col text-center">UNIVERSIDAD</div>
                    <div class="col text-center">TITULO</div>
                    <div class="col text-center">FECHA EMISION</div>
                    <div class="col text-center">DOCUMENTO</div>
                </div>
                <?php 
                $sql=" SELECT d_tit.docente_tituloid, 
                                    t_tit.descripcion AS tipo_titulo, 
                                    d_tit.nombre_titulo, 
                                    d_tit.nombre_facu,
                                    d_tit.fecha_emision
                               FROM doce_docentes_titulos AS d_tit
                         INNER JOIN doce_tipo_titulos AS t_tit ON d_tit.tipo_tituloid = t_tit.tipo_tituloid
                              WHERE t_tit.tipo_tituloid NOT IN(1,2,3,4,5,6,8,9,11)
                                AND d_tit.ndoc = '$ndoc'
                           ORDER BY d_tit.fecha_emision DESC;";
                
                //$result = sql_do_query($sql,'1',$usu);
                while($row=sql_do_array($result)){
                    $archivo = '../aced_docentes/web/arch/'.$ndoc.'/certificados/'.$row['DOCENTE_TITULOID'].'.pdf';
                    if(is_file($archivo)){
                ?>
                <div class="row border-bottom align-items-center">
                    <div class="col text-center"><?php echo strtoupper($row['TIPO_TITULO']);?></div>
                    <div class="col text-center"><?php echo strtoupper($row['NOMBRE_FACU']);?></div>
                    <div class="col text-center"><?php echo strtoupper($row['NOMBRE_TITULO']);?></div>
                    <div class="col text-center"><?php echo enmascararFecha($row['FECHA_EMISION']);?></div>
                    <div class="col text-center">
                        <a href="#" target="_blank" onclick="window.open('<?php echo $archivo;?>', '_blank', 'width=500,status=no,directories=no,menubar=no,toolbar=no,scrollbars=no,location=no,resizable=no,titlebar=no'); return false;">
                            <img src="img/icon/edu.png" class="me-2" style="height: 20px;">
                        </a>
                    </div>
                </div>
                <?php 
                    }
                }
                ?>
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
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Congresos y eventos</h6>
                </button>
            </h2>
            <div id="flush-collapse3" class="accordion-collapse collapse" data-bs-parent="#accordion3">
                <div class="accordion-body">
                    
                    
                    
                    <div class="col-12">
                <div class="row border-bottom font-weight-bold">
                    <div class="col text-center">NIVEL</div>
                    <div class="col text-center">UNIVERSIDAD</div>
                    <div class="col text-center">TITULO</div>
                    <div class="col text-center">FECHA EMISION</div>
                </div>
                <?php 
                $sql=" SELECT d_tit.docente_tituloid, 
                                    t_tit.descripcion AS tipo_titulo, 
                                    d_tit.nombre_titulo, 
                                    d_tit.nombre_facu,
                                    d_tit.fecha_emision
                               FROM doce_docentes_titulos AS d_tit
                         INNER JOIN doce_tipo_titulos AS t_tit ON d_tit.tipo_tituloid = t_tit.tipo_tituloid
                              WHERE t_tit.tipo_tituloid IN('12','13')
                                AND d_tit.ndoc = '$ndoc'
                           ORDER BY d_tit.fecha_emision DESC;";
                
                //$result = sql_do_query($sql,'1',$usu);
                while($row=sql_do_array($result)){
                ?>
                <div class="row border-bottom align-items-center">
                    <div class="col text-center"><?php echo strtoupper($row['TIPO_TITULO']);?></div>
                    <div class="col text-center"><?php echo strtoupper($row['NOMBRE_FACU']);?></div>
                    <div class="col text-center"><?php echo strtoupper($row['NOMBRE_TITULO']);?></div>
                    <div class="col text-center"><?php echo enmascararFecha($row['FECHA_EMISION']);?></div>
                </div>
                <?php 
                }
                ?>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>
