<?php

    if(!$rowhanterior['ID_SISTEMAOPERATIVO'] || $rowhanterior['ID_SISTEMAOPERATIVO'] = 'null'){
        $so_id = 0;
    }else{
        $so_id = $rowhanterior['ID_SISTEMAOPERATIVO'];
        $sqlso="SELECT so.sistemaoperativoid, 
                       so.Sistema AS nombre_so
                  FROM more_sistemaoperativo so 
                 WHERE so.estadoid = '1'
                   AND so.sistemaoperativoid = '".$so_id."';";
         //echo $sqlso;
         $rowso=sql_do_array(sql_do_query($sqlso,'1',$usu));
    }
    
    
    
    if(is_file( "../ctm_inventarios/img/so/".$so_id.'.png')){
    	$foto = "../ctm_inventarios/img/so/".$so_id.'.png';
    }else{
	$foto = "../ctm_inventarios/img/so/0.png";
    }
    
    if(is_file( "../ctm_inventarios/img/so/".$so_id.'.png')){
    	$img_so = "../ctm_inventarios/img/so/".$so_id.'.png';
    }else{
	$img_so = "../ctm_inventarios/img/so/0.png";
    }

    //$usuario = getUsuario($rowhanterior['LEGAJO'],  '1', $usu);
    $usuario = consultaPersona($rowhanterior['LEGAJO'],  '1', $usu);
    //var_dump($usuario);
    
    
?>
<style>
    .image-container {
        width: 125px; /* Tamaño fijo para el contenedor */
        height: 125px; /* Tamaño fijo para el contenedor */
        overflow: hidden; /* Oculta cualquier parte de la imagen que exceda el tamaño del contenedor */
        border-radius: 10px; /* Redondea las esquinas */
        display: flex; /* Habilita flexbox */
        align-items: center; /* Centra la imagen verticalmente */
        justify-content: center; /* Centra la imagen horizontalmente */
    }

    .image-container img {
        width: 100%; /* Ajusta la imagen al ancho del contenedor */
        height: auto; /* La altura se ajusta automáticamente para mantener la proporción de la imagen */
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
                        <p><strong><?php echo strtoupper($rowhanterior['CODIGOPC']); ?>,</strong><br><?php echo ucwords(strtolower($rowhanterior['MARCA'])); ?></p>
                    </div>
                </div>
                <div>
                        <div class="col-10 p-2 float-start fs-12" >
                            <p>
                                <img src="img/icon/legajo.png" class="me-2" style="height: 15px; width: 15px;"> Sistema Operativo: <strong><?php echo $rowso['NOMBRE_SO']; ?></strong><br>
                                <img src="img/icon/fecha.png" class="me-2" style="height: 15px; width: 15px;"> Fecha de Compra: <strong><?php echo enmascararFecha($rowhanterior['FECHACOMPRA']); ?></strong><br>
                                <img src="img/icon/cuit.png" class="me-2" style="height: 15px; width: 15px;"> SR: <strong><?php echo $rowhanterior['SR']; ?></strong><br>
                                <img src="img/icon/cuit.png" class="me-2" style="height: 15px; width: 15px;"> OC: <strong><?php echo $rowhanterior['OC']; ?></strong><br>
                                <img src="img/icon/cuit.png" class="me-2" style="height: 15px; width: 15px;"> Monto: <strong><?php echo $rowhanterior['MONTO']; ?></strong><br>
                                <img src="img/icon/puesto.png" class="me-2" style="height: 15px; width: 15px;"> Centro de Costo: <strong><?php echo getCentroCosto($rowhanterior['CENTROCOSTO'],  '1', $usu); ?></strong><br>
                                <img src="img/icon/estado.png" class="me-2" style="height: 15px; width: 15px;"> Asignaci&oacute;n: <strong><?php echo $usuario['APELLIDO'].', '.$usuario['NOMBRES']; ?></strong><br>
                                <img src="img/icon/fechaingreso.png" class="me-2" style="height: 15px; width: 15px;"> Ultimo contacto: <strong><?php echo date('d/m/Y', strtotime($rowhanterior['FECHA_ALTA'])); ?></strong><br>
                            </p>
                        </div>
                        <div class="col-2 pt-3 float-end">
                                <img src="<?php echo $img_so; ?>" class="me-1" style="height: 40px;">
                        </div>	
                </div>
        </div>
