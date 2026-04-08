<?php
    
    //var_dump($rowpersona);
    
    $foto = 'http://intranet/imagenes/img_personal/hi_res/'.$legajo.'.jpg';
    
    //echo "<h1>../ctm_inventarios/img/ico/".trim($rowpersona['EMPNACIONALIDAD']).'.png</h1>';
    if(is_file( "../ctm_inventarios/img/ico/".trim($rowpersona['EMPNACIONALIDAD']).'.png')){
    	$img_so = "../ctm_inventarios/img/ico/".trim($rowpersona['EMPNACIONALIDAD']).'.png';
    }else{
	$img_so = "../ctm_inventarios/img/ico/ctm.png";
    }

    $usuario = consultaPersona($rowhanterior['LEGAJO'],  '1', $usu);
    //$usuario = getUsuario($rowhanterior['LEGAJO'],  '1', $usu);
    
    
?>
<style>
    .image-container {
        width: 125px; /* Tamaño fijo para el contenedor */
        height: 125px; /* Tamaño fijo para el contenedor */
        overflow: hidden; /* Oculta cualquier parte de la imagen que exceda el tamaño del contenedor */
        border-radius: 10px; /* Redondea las esquinas */
        display: flex; /* Habilita flexbox */
        align-items: normal; /* Centra la imagen verticalmente */
        justify-content: center; /* Centra la imagen horizontalmente */
    }

    .image-container img {
        width: 100%; 
        height: available; 
    }
</style>


        <div class="col-12 row separar1 div-1  bg-white">
                <div class="col-12 p-2 border-separador">
                    <div class="col-5 float-start">
                        <div class="image-container">
                            <img src="<?php echo $foto; ?>" alt="" class="card-img-top">
                        </div>
                    </div>
                    <div class="col-7 row float-end align-items-end">
                        <p><strong><?php echo strtoupper($rowpersona['EMPNOMBRE']); ?></strong><br><?php echo ucwords(strtolower($rowpersona['EMPLEGAJO'])); ?></p>
                    </div>
                </div>
                <div>
                        <div class="col-10 p-2 float-start fs-12" >
                            <p>
                                <img src="img/icon/estado.png" class="me-2" style="height: 15px; width: 15px;"> Cargo: <strong><?php echo $rowpersona['EMPCARGONOM']; ?></strong><br>
                                <img src="img/icon/legajo.png" class="me-2" style="height: 15px; width: 15px;"> Categoria y Puntaje: <strong><?php echo $rowpersona['EMPCATEGORIANIVEL'].".".$rowpersona['EMPCATEGORIAPUNTAJE'];?></strong><br>
                                <img src="img/icon/cuit.png" class="me-2" style="height: 15px; width: 15px;"> Situacion de Revista: <strong><?php echo $rowpersona['SITUACION_DE_REVISTA']; ?></strong><br>
                                <img src="img/icon/cuit.png" class="me-2" style="height: 15px; width: 15px;"> Email: <strong><?php echo $rowpersona['EMPEMAIL']; ?></strong><br>
                                <?php
                                    //$reportaa = getUsuario($rowpersona['EMPREPORTAA'],  '1', $usu)
                                    $reportaa = consultaPersona($rowpersona['EMPREPORTAA'],  '1', $usu);
                                ?>
                                <img src="img/icon/estado.png" class="me-2" style="height: 15px; width: 15px;"> Reporta A: <strong><?php echo $usuario['APELLIDO'].', '.$usuario['NOMBRES']; ?></strong><br>
                                <img src="img/icon/puesto.png" class="me-2" style="height: 15px; width: 15px;"> Lugar F&iacute;sico: <strong><?php echo $rowpersona['EMPLUGARFISICONOM']; ?></strong><br>
                                <img src="img/icon/puesto.png" class="me-2" style="height: 15px; width: 15px;"> Centro de Costo: <strong><?php echo getCentroCosto($rowpersona['CENTROCOSTO'],  '1', $usu); ?></strong><br>
                                <img src="img/icon/fechaingreso.png" class="me-2" style="height: 15px; width: 15px;"> Fecha de Ingreso: <strong><?php echo date('d/m/Y', strtotime($rowpersona['EMPFCHALTA'])); ?></strong><br>
                                <img src="img/icon/fechaingreso.png" class="me-2" style="height: 15px; width: 15px;"> Fecha de Nacimiento: <strong><?php echo date('d/m/Y', strtotime($rowpersona['EMPFCHNAC'])); ?></strong><br>
                            </p>
                        </div>
                        <div class="col-2 pt-3 float-end">
                                <img src="<?php echo $img_so; ?>" class="me-1" style="height: 30px;">
                        </div>	
                </div>
        </div>
