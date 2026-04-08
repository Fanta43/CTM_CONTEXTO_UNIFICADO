<style type="text/css">
#area #form1 .fondo-II img {
	margin: 5px;
}
</style>

<?php
// Configuración inicial
ini_set('display_errors', 'on');
$ubicaid = Enmascarar_post('ubicaid');
$guardar_zona = Enmascarar_post('guardar_zona');
$ubicadet = Enmascarar_post('sistema'); // reutilizamos el input "sistema" como nombre de zona
$estadoid = Enmascarar_post('estadoid');

if ($ubicaid) {
    $sql_prellenado = "SELECT UbicaId, UbicaDet, Ubica_Activa FROM artcelubi WHERE UbicaId = '$ubicaid';";
    $row_prellenado = sql_do_array(sql_do_query($sql_prellenado, '2', $usu), '2', $usu);
}

if ($guardar_zona) {
    if ($ubicaid) {
        $sql = "UPDATE artcelubi
                   SET UbicaDet = '$ubicadet',  
                       Ubica_Activa = '$estadoid'
                 WHERE UbicaId = '$ubicaid';";

        //echo $sql;
        $result = sql_do_query($sql, '2', $usu);

        if ($result) {
            echo '<script>swal({
                       title: "Buen Trabajo!",
                       text: "Has actualizado la zona!",
                       icon: "success",
                       dangerMode: false,
                       buttons: {
                           confirm: {text: "Continuar", value: true},
                       },
                  })
                  .then((value) => {
                      window.location.replace("index.php?estado=32");
                  })
                 </script>';
        }
    } else {

        $sql = "INSERT INTO artcelubi (
                            UbicaDet, 
                            Ubica_Activa
                ) VALUES ( 
                            '$ubicadet',  
                            '$estadoid'
                );";
        //echo $sql;
        $result = sql_do_query($sql, '2', $usu);

        if ($result) {
            echo '<script>swal({
                       title: "Buen Trabajo!",
                       text: "Has dado de alta una nueva zona!",
                       icon: "success",
                       dangerMode: false,
                       buttons: {
                           confirm: {text: "Continuar", value: true},
                       },
                  })
                  .then((value) => {
                      window.location.replace("index.php?estado=32");
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
                <strong style="font-size: 25px;">Definici&oacute;n de zona</strong>
            </div>
            <br />
            <form id="form1" name="form1" method="post" action="">
                <div class="row flex-column align-items-start bg-white">
                    <div class="p-3">
                        <div class="col-12">
                            <div class="col-3 float-start p-1">
                                <div class="mb-3">
                                    <label for="sistema" class="m-0">Nombre de la Zona</label>
                                    <input type="text" class="form-control fs-12" name="sistema" id="sistema" value="<?php echo $row_prellenado['UBICADET']; ?>" placeholder="Descripci&oacute;n de la zona">
                                </div>
                            </div>
                            <div class="col-3 float-start p-1">
                                <div class="mb-3">
                                    <label for="estadoid" class="m-0">Estado de la zona</label>
                                    <select class="form-control" name="estadoid" id="estadoid">
                                        <option value="1" <?php if ($row_prellenado['UBICA_ACTIVA'] == '1') echo 'selected'; ?>>Activo</option>
                                        <option value="0" <?php if ($row_prellenado['UBICA_ACTIVA'] == '0') echo 'selected'; ?>>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                    <input name="ubicaid" id="ubicaid" type="hidden" value="<?php echo $ubicaid; ?>" />
                                    <input name="guardar_zona" id="guardar_zona" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                </div>
                            </div>                      
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>