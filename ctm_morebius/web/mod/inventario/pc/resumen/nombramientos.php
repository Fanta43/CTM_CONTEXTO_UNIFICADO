<div class="col-12 separar1 p-0 float-start bg-white">
    <div class="bg-white">
        <div class="p-3">
            <div class="col-12 pb-2 d-flex align-items-center">
                <img src="img/icon/person.png" class="me-2" style="height: 30px; width: 30px;">
                <h6>Nombramientos actuales</h6>
            </div>
        
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

<?php


$sqlx="SELECT DISTINCT YEAR(FECHA_CESE) AS anio
        FROM vw_nombramientos
       WHERE NRODOC = '$ndoc'
         AND YEAR(FECHA_CESE) <> YEAR(STR_TO_DATE('$hoy', '%Y-%m-%d'))  
    ORDER BY anio DESC;";
            
            //$resultx = sql_do_query($sqlx,'1',$usu);
            $n=0;
            while($rowx=sql_do_array($resultx)){


?>




<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
        <div class="accordion accordion-flush" id="accordion<?php echo $n;?>">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse<?php echo $n;?>" aria-expanded="false" aria-controls="flush-collapse<?php echo $n;?>">
                    <h6 class="m-0"><img src="img/icon/domicilio.png" class="me-2" style="height: 20px; width: 15px;"><?php echo $rowx['ANIO'];?></h6>
                </button>
            </h2>
            <div id="flush-collapse<?php echo $n;?>" class="accordion-collapse collapse" data-bs-parent="#accordion<?php echo $n;?>">
                <div class="accordion-body">

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
                                AND YEAR(a.fecha_cese) = '".$rowx['ANIO']."'
                           ORDER BY a.destino, a.fecha_posesion;";
            
            //$result = sql_do_query($sql,'1',$usu);
            $n=0;
            while($row=sql_do_array($result)){
                $n++;
                if ($n % 2 == 0) {
                    $color_fila =  "fondo-II";
                } else {
                    $color_fila =  "fondo-I";
                }
            ?>
            <tr class="<?php echo $color_fila; ?>">
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


<?php
            }
?>
