<div class="col-12 separar1 p-0 float-start bg-white">
    <div class="bg-white">
        <div class="p-3">
            <div class="col-12 pb-2 d-flex align-items-center">
                <img src="img/icon/person.png" class="me-2" style="height: 30px; width: 30px;">
                <h6>Hardware</h6>
            </div>
            <div class="col-12">
                
                
                
                
                
                
                <?php 
                $sql="   SELECT h.id, 
                                h.deviceid, 
                                h.name, 
                                h.workgroup, 
                                h.userdomain, 
                                h.osname, 
                                h.osversion, 
                                h.oscomments, 
                                h.processort, 
                                h.processors, 
                                h.processorn, 
                                h.memory, 
                                h.swap, 
                                h.ipaddr, 
                                h.dns, 
                                h.defaultgateway, 
                                h.etime, 
                                h.lastdate, 
                                h.lastcome, 
                                h.quality, 
                                h.fidelity, 
                                h.userid, 
                                h.type, 
                                h.description, 
                                h.wincompany, 
                                h.winowner, 
                                h.winprodid, 
                                h.winprodkey, 
                                h.useragent, 
                                h.checksum, 
                                h.sstate, 
                                h.ipsrc, 
                                h.uuid, 
                                h.arch, 
                                h.category_id, 
                                h.archive, 
                                h.sistema_extid 
                                FROM ocs_hardware h 
                          WHERE UPPER(h.name) LIKE UPPER('$codigopc%');";
                //echo $sql;
                $row=sql_do_array(sql_do_query($sql,'2',$usu));
                ?>
                <div class="row border-bottom">
                    <div class="col-3 text-center"><?php echo $row['ID']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['DEVICEID']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['NAME']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['WORKGROUP']; ?></div>
                </div>
                <div class="row border-bottom">
                    <div class="col-3 text-center"><?php echo $row['USERDOMAIN']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['OSNAME']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['OSVERSION']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['OSCOMMENTS']; ?></div>
                </div>
                <div class="row border-bottom">
                    <div class="col-3 text-center"><?php echo $row['PROCESSORT']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['PROCESSORS']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['PROCESSORN']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['MEMORY']; ?></div>
                </div>
                <div class="row border-bottom">
                    <div class="col-3 text-center"><?php echo $row['SWAP']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['IPADDR']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['IPADDR']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['DNS']; ?></div>
                </div>
                <div class="row border-bottom">
                    <div class="col-3 text-center"><?php echo $row['DEFAULTGATEWAY']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['ETIME']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['LASTDATE']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['LASTCOME']; ?></div>
                </div>
                <div class="row border-bottom">
                    <div class="col-3 text-center"><?php echo $row['QUALITY']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['FIDELITY']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['USERID']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['TYPE']; ?></div>
                </div>
                <div class="row border-bottom">
                    <div class="col-3 text-center"><?php echo $row['DESCRIPTION']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['WINCOMPANY']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['WINOWNER']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['WINPRODID']; ?></div>
                </div>
                <div class="row border-bottom">
                    <div class="col-3 text-center"><?php echo $row['WINPRODKEY']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['USERAGENT']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['CHECKSUM']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['SSTATE']; ?></div>
                </div>
                <div class="row border-bottom">
                    <div class="col-3 text-center"><?php echo $row['IPSRC']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['UUID']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['ARCH']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['CATEGORY_ID']; ?></div>
                </div>
                <div class="row border-bottom">
                    <div class="col-3 text-center"><?php echo $row['ARCHIVE']; ?></div>
                    <div class="col-3 text-center"><?php echo $row['SISTEMA_EXTID']; ?></div>
                    <div class="col-3 text-center"><?php ?></div>
                    <div class="col-3 text-center"><?php ?></div>
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
                                    d_tit.nombre_facu
                               FROM doce_docentes_titulos AS d_tit
                         INNER JOIN doce_tipo_titulos AS t_tit ON d_tit.tipo_tituloid = t_tit.tipo_tituloid
                              WHERE d_tit.ndoc = '$ndoc'
                                AND d_tit.fecha_emision IS NULL
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
