<style type="text/css">

#area #form1 .fondo-II img {
	margin: 5px;
}

</style>

<?php
$zonaid = Enmascarar_post('zonaid');
$guardar_zona = Enmascarar_post('guardar_zona');

$zona = Enmascarar_post('zona');
$estadoid = Enmascarar_post('estadoid');


if($zonaid){
    $sql_prestamo = "SELECT zonaid, 
                            zona, 
                            estadoid, 
                            auditoria_usu, 
                            auditoria_fecha 
                       FROM more_zonas_inventario 
                      WHERE zonaid = '$zonaid';";
    //echo $sql_id_pc;
    $row_prellenado = sql_do_array(sql_do_query($sql_prestamo, '1', $usu));
    
}

if($guardar_zona){
  
    if($zonaid){
        $sql = "UPDATE more_zonas_inventario
                   SET zona = '$zona', 
                       estadoid = '$estadoid', 
                       auditoria_usu = '$usu', 
                       auditoria_fecha = $hoy
                 WHERE zonaid = '$zonaid'; ";
        
                $result = sql_do_query($sql, '1', $usu);

                if($result){
                    echo '<script>swal({
                               title: "Buen Trabajo!",
                               text: "Has actualizado la nueva zona!",
                               icon: "success",
                               dangerMode: false,
                               buttons: {
                                   confirm: {text: "Continuar", value: true},
                               },
                          })
                          .then((value) => {
                              window.location.replace("index.php?estado=34");
                          })    
                         </script>';
                }
        
    }else{
        
        $zonaid = get_id('more_ZONAS_INVENTARIO');
        
        $sql = "INSERT INTO more_zonas_inventario (
                            zonaid, 
                            zona, 
                            estadoid, 
                            auditoria_usu, 
                            auditoria_fecha
                )VALUES(
                            '$zonaid', 
                            '$zona', 
                            '$estadoid', 
                            '$usu', 
                            '$hoy'
                ); ";
        
            $result = sql_do_query($sql, '1', $usu);

            if($result){
                echo '<script>swal({
                           title: "Buen Trabajo!",
                           text: "Has dado de alta la nueva zona!",
                           icon: "success",
                           dangerMode: false,
                           buttons: {
                               confirm: {text: "Continuar", value: true},
                           },
                      })
                      .then((value) => {
                          window.location.replace("index.php?estado=34");
                      })    
                     </script>';
            }
    } 

}

?>


<div class="area"><br />
    <div class="col-12">
        <div class="col-12 p-2 pb-0">
            <div class="col-12 row m-0 ">
                <div class="col-md-6 m-0">
                    <?php 
                        include_once $dir_raiz."resumen/ariadna.php";
                    ?>
                </div>
            </div>
            
    <div class='titulo_mediano_center_rojo'><?php echo $mensaje;?></div>
        <div class="row flex-column align-items-start" style="height: 40px;">
            <strong style="font-size: 25px;">Definici&oacute;n de zona del inventario</strong>
        </div>
    <br />
    <form id="form1" name="form1" method="post" action="">
    <div class="row flex-column align-items-start bg-white">
                <div class="p-3">
                        <div class="col-12">
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class=" m-0" >Nombre de la Zona</label>
                                        <input type="text" class="form-control fs-12" name="zona" id="zona" value="<?php echo $row_prellenado['ZONA']; ?>" placeholder="Descripci&oacute;n de la zona">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="ubicacionSelect" class=" m-0">Estado de la zona</label>
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
                                <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                    <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                        <input name="zonaid" id="zonaid" type="hidden" value="<?php echo $zonaid; ?>" />
                                        <input name="guardar_zona" id="guardar_zona" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                    </div>
                                </div>                      
                         </div>
          </div>
    </div>
    </form>
  </div>