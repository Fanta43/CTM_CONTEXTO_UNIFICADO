<style type="text/css">

#area #form1 .fondo-II img {
	margin: 5px;
}

</style>

<?php
////ini_set( 'display_errors', 'on' );

$proveedorid = Enmascarar_post('proveedorid');
$guardar_proveedor = Enmascarar_post('guardar_proveedor');
$nombre = Enmascarar_post('nombre');
$direccion = Enmascarar_post('direccion');
$telefono = Enmascarar_post('telefono');
$estadoid = Enmascarar_post('estadoid');

$email1 = Enmascarar_post('email1');
$email2 = Enmascarar_post('email2');
$email3 = Enmascarar_post('email3');

if($proveedorid){
    $sql = " SELECT proveedorid, 
                    nombre, 
                    direccion,
                    telefono, 
                    email1, 
                    email2, 
                    email3, 
                    estadoid, 
                    auditoria_usu, 
                    auditoria_fecha 
               FROM  more_proveedores
              WHERE proveedorid = '$proveedorid';";
    //echo $sql_id_pc;
    $row_prellenado = sql_do_array(sql_do_query($sql, '1', $usu));
     
}

if($guardar_proveedor){
  
    if($proveedorid){
        $sql = "UPDATE more_proveedores_extras
                   SET nombre = '$nombre',
                       direccion = '$direccion',
                       telefono = '$telefono',
                       email1 = '$email1', 
                       email2 = '$email2', 
                       email3 = '$email3',
                       estadoid = '$estadoid', 
                       auditoria_usu = '$usu', 
                       auditoria_fecha = '$hoy'
                 WHERE proveedorid = '$proveedorid'; ";
        
                $result = sql_do_query($sql, '1', $usu);

                if($result){
                    echo '<script>swal({
                               title: "Buen Trabajo!",
                               text: "Has actualizado el proveedor!",
                               icon: "success",
                               dangerMode: false,
                               buttons: {
                                   confirm: {text: "Continuar", value: true},
                               },
                          })
                          .then((value) => {
                              window.location.replace("index.php?estado=30");
                          })    
                         </script>';
                }
        
    }else{
        
        $proveedorid = get_id('more_PROVEEDORES');
        
       
        $sql = "INSERT INTO more_proveedores_extras (
                            proveedorid, 
                            nombre, 
                            direccion, 
                            telefono, 
                            email1, 
                            email2, 
                            email3, 
                            estadoid, 
                            auditoria_usu, 
                            auditoria_fecha
                )VALUES( 
                            '$proveedorid', 
                            '$nombre', 
                            '$direccion', 
                            '$telefono', 
                            '$email1', 
                            '$email2', 
                            '$email3', 
                            '$estadoid', 
                            '$usu', 
                            '$hoy'
                );";

        
        $result = sql_do_query($sql, '1', $usu);


            if($result){
                echo '<script>swal({
                           title: "Buen Trabajo!",
                           text: "Has dado de alta el nuevo Proveedor!",
                           icon: "success",
                           dangerMode: false,
                           buttons: {
                               confirm: {text: "Continuar", value: true},
                           },
                      })
                      .then((value) => {
                          window.location.replace("index.php?estado=30");
                      })    
                     </script>';
            }
    } 
}

?>


<div class="area"><br />
    <div class="col-12">
        <div class="col-12 p-2 pb-0"> 
    <div class='titulo_mediano_center_rojo'><?php echo $mensaje;?></div>
        <div class="row flex-column align-items-start" style="height: 40px;">
            <strong style="font-size: 25px;">Definici&oacute;n del proveedor</strong>
        </div>
    <br />
    <form id="form1" name="form1" method="post" action="">
    <div class="row flex-column align-items-start bg-white">
                <div class="p-3">
                        <div class="col-12">
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class=" m-0" >Nombre</label>
                                        <input type="text" class="form-control fs-12" name="nombre" id="nombre" value="<?php echo $row_prellenado['NOMBRE']; ?>" placeholder="Nombre">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class=" m-0" >Direccion</label>
                                        <input type="text" class="form-control fs-12" name="direccion" id="direccion" value="<?php echo $row_prellenado['DIRECCION']; ?>" placeholder="Descripci&oacute;n ">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class=" m-0" >Tel&eacute;fono</label>
                                        <input type="text" class="form-control fs-12" name="telefono" id="telefono" value="<?php echo $row_prellenado['TELEFONO']; ?>" placeholder="Tel&eacute;fono">
                                    </div>
                                </div>

                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="ubicacionSelect" class=" m-0">Estado</label>
                                        <select class="form-control" name="estadoid" id="estadoid">
                                            <?php
                                            $sql = "SELECT estadoid, descripcion FROM more_estados WHERE tabla = 'GRAL' ORDER BY estadoid DESC;";
                                            $result = sql_do_query($sql, '1', $usu);
                                            while ($row = sql_do_array($result)) {
                                                if($row['ESTADOID'] == $row_prellenado['ESTADOID']){$sele = ' selected = selected ';}else{$sele = '';}
                                                echo '<option '.$sele.' value="' . htmlspecialchars($row['ESTADOID']) . '">' . htmlspecialchars($row['DESCRIPCION']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div> 
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class=" m-0" >Email</label>
                                        <input type="text" class="form-control fs-12" name="email1" id="email1" value="<?php echo $row_prellenado['EMAIL1']; ?>" placeholder="Email">
                                        <input type="text" class="form-control fs-12" name="email2" id="email2" value="<?php echo $row_prellenado['EMAIL2']; ?>" placeholder="Email">
                                        <input type="text" class="form-control fs-12" name="email3" id="email3" value="<?php echo $row_prellenado['EMAIL3']; ?>" placeholder="Email">
                                    </div>
                                </div>
                                <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                    <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                        <input name="proveedorid" id="proveedorid" type="hidden" value="<?php echo $proveedorid; ?>" />
                                        <input name="guardar_proveedor" id="guardar_proveedor" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                    </div>
                                </div>                      
                         </div>
          </div>
    </div>
    </form>
  </div>