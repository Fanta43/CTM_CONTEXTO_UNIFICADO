


<div class="col-12 separar1 row div-2 float-start mt-3 bg-white">
        <p class="pt-3"><button class="btn fs-12" type="submit"><img src="img/icon/ausyvac.png" class="me-2" style="height: 20px; width: 20px;"> Ver ausencias <img src="img/icon/flecha-l.png" class="me-1 ms-5" style="height: 10px; width: 7px;"></button></p>
</div>

<?php
    $sqln="SELECT cv.cv_modificacionesid, 
                  cv.observacion
             FROM doce_cv_modificaciones cv
            WHERE cv.ndoc = '$ndoc'
              AND cv.modificacion_usu IS NULL;";
    //echo $sqln;
    //$rowerror=sql_do_array(sql_do_query($sqln,'1',$usu));

?>

<div class="col-12 separar1 row float-start mt-3 p-3 bg-white">
        <h6><img src="img/icon/notas.png" class="me-2" style="height: 20px; width: 15px;"> Nota <img src="img/icon/incog.png" data-bs-toggle="tooltip" data-bs-title="gracias gonza! :D" class="me-1" style="height: 10px; width: 10px;"></h6>
        <h6><?php echo $rowerror['OBSERVACION']; ?></h6>
</div>