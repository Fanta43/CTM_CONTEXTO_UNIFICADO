<div class="col-12 separar1 p-0 float-start bg-white">
    <div class="bg-white">
        <div class="p-3">
            <div class="col-12 pb-2 d-flex align-items-center">
                <img src="img/icon/person.png" class="me-2" style="height: 30px; width: 30px;">
                <h6>Antecedentes en educaci&oacute;n</h6>
            </div>
            <div class="col-12">
                <div class="row border-bottom font-weight-bold">
                    <div class="col text-center">ANTECEDENTE</div>
                    <div class="col text-center">CATEGORIA</div>
                    <div class="col text-center">INSTITUCI&Oacute;N</div>
                    <div class="col text-center">DESDE</div>
                    <div class="col text-center">HASTA</div>
                </div>
                <?php 
                $sql="   SELECT dt.docente_antecedenteid,
                                tt.descripcion AS tipo_antecedentes,
                                ca.descripcion AS categoria,
                                dt.nombre_facu,
                                TO_CHAR(dt.fecha_desde, 'dd-mm-yyyy') AS fecha_desde,
                                TO_CHAR(dt.fecha_hasta, 'dd-mm-yyyy') AS fecha_hasta
                           FROM doce_docentes_antecedentes dt
                     INNER JOIN doce_tipo_antecedentes tt ON dt.tipo_antecedenteid = tt.tipo_antecedenteid
                     INNER JOIN doce_categoria_antecedentes ca ON ca.antecedente_categoriaid = dt.antecedente_categoriaid
                          WHERE dt.antecedente_categoriaid IN (1,2,3,4)
                            AND dt.ndoc = '$ndoc'
                       ORDER BY dt.fecha_desde DESC;";
                
                //$result = sql_do_query($sql,'1',$usu);
                while($row=sql_do_array($result)){
                ?>
                <div class="row border-bottom align-items-center">
                    <div class="col text-center"><?php echo $row['TIPO_ANTECEDENTES'];?></div>
                    <div class="col text-center"><?php echo $row['CATEGORIA'];?></div>
                    <div class="col text-center"><?php echo $row['NOMBRE_FACU'];?></div>
                    <div class="col text-center"><?php echo $row['FECHA_DESDE'];?></div>
                    <div class="col text-center"><?php echo $row['FECHA_HASTA'];?></div>
                </div>
                <?php 
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
        <div class="accordion accordion-flush" id="accordion1">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse1" aria-expanded="false" aria-controls="flush-collapse1">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;">Otros antecedentes</h6>
                </button>
            </h2>
            <div id="flush-collapse1" class="accordion-collapse collapse" data-bs-parent="#accordion1">
                <div class="accordion-body">
                    
                    
                    
                                <div class="col-12">
                <div class="row border-bottom font-weight-bold">
                    <div class="col text-center">ANTECEDENTE</div>
                    <div class="col text-center">CATEGORIA</div>
                    <div class="col text-center">INSTITUCI&Oacute;N</div>
                    <div class="col text-center">DESDE</div>
                    <div class="col text-center">HASTA</div>
                </div>
                <?php 
                $sql="   SELECT dt.docente_antecedenteid,
                                tt.descripcion AS tipo_antecedentes,
                                ca.descripcion AS categoria,
                                dt.nombre_facu,
                                TO_CHAR(dt.fecha_desde, 'dd-mm-yyyy') AS fecha_desde,
                                TO_CHAR(dt.fecha_hasta, 'dd-mm-yyyy') AS fecha_hasta
                           FROM doce_docentes_antecedentes dt
                     INNER JOIN doce_tipo_antecedentes tt ON dt.tipo_antecedenteid = tt.tipo_antecedenteid
                     INNER JOIN doce_categoria_antecedentes ca ON ca.antecedente_categoriaid = dt.antecedente_categoriaid
                          WHERE dt.antecedente_categoriaid IN (5,6,7,8)
                            AND dt.ndoc = '$ndoc'
                       ORDER BY dt.fecha_desde DESC;";
                
                //$result = sql_do_query($sql,'1',$usu);
                while($row=sql_do_array($result)){
                ?>
                <div class="row border-bottom align-items-center">
                    <div class="col text-center"><?php echo $row['TIPO_ANTECEDENTES'];?></div>
                    <div class="col text-center"><?php echo $row['CATEGORIA'];?></div>
                    <div class="col text-center"><?php echo $row['NOMBRE_FACU'];?></div>
                    <div class="col text-center"><?php echo $row['FECHA_DESDE'];?></div>
                    <div class="col text-center"><?php echo $row['FECHA_HASTA'];?></div>
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