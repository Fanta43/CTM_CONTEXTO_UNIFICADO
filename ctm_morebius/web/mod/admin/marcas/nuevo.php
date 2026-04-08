<style type="text/css">
#area #form1 .fondo-II img {
    margin: 5px;
}
</style>

<?php
// Renombramos variables para que tengan sentido con la nueva tabla.
$marcaid       = Enmascarar_post('marcaid');
$guardar_marca = Enmascarar_post('guardar_marca');
$detmarca      = Enmascarar_post('sistema');  // reutilizamos el input "sistema" como nombre de la marca

// Si existe un marcaid, cargamos los datos para prellenar
if ($marcaid) {
    $sql_prellenado = "SELECT marcaid, detmarca 
                         FROM artimarcas
                        WHERE marcaid = '$marcaid';";
    $row_prellenado = sql_do_array(sql_do_query($sql_prellenado, '2', $usu), '2', $usu));
}

// Al hacer clic en "Guardar"
if ($guardar_marca) {

    // Si estamos editando (existe marcaid)
    if ($marcaid) {
        $sql = "UPDATE artimarcas
                   SET detmarca = '$detmarca'
                 WHERE marcaid = '$marcaid';";

        $result = sql_do_query($sql, '2', $usu);

        if ($result) {
            echo '<script>
                    swal({
                        title: "¡Buen trabajo!",
                        text: "¡Has actualizado la marca!",
                        icon: "success",
                        dangerMode: false,
                        buttons: {
                            confirm: {text: "Continuar", value: true},
                        },
                    })
                    .then((value) => {
                        window.location.replace("index.php?estado=30");
                    });
                  </script>';
        }

    // Si no hay marcaid, creamos un nuevo registro
    } else {
        // Obtenemos el siguiente ID disponible (puedes usar auto-increment si la tabla lo tiene configurado)
        $sql_prellenado = "SELECT (MAX(marcaid) + 1) AS id FROM artimarcas;";
        $row_prellenado = sql_do_array(sql_do_query($sql_prellenado, '2', $usu), '2', $usu));
        $marcaid        = $row_prellenado['ID'];

        $sql = "INSERT INTO artimarcas (
                      marcaid,
                      detmarca
                  ) VALUES (
                      '$marcaid',
                      '$detmarca'
                  );";

        $result = sql_do_query($sql, '2', $usu);

        if ($result) {
            echo '<script>
                    swal({
                        title: "¡Buen trabajo!",
                        text: "¡Has dado de alta una nueva marca!",
                        icon: "success",
                        dangerMode: false,
                        buttons: {
                            confirm: {text: "Continuar", value: true},
                        },
                    })
                    .then((value) => {
                        window.location.replace("index.php?estado=30");
                    });
                  </script>';
        }
    }
}
?>

<div class="area"><br />
    <div class="col-12">
        <div class="col-12 p-2 pb-0">
            <div class="col-12 row m-0">
                <div class="col-md-6 m-0">
                    <?php 
                        include_once $dir_raiz."resumen/ariadna.php";
                    ?>
                </div>
            </div>

            <div class='titulo_mediano_center_rojo'><?php echo $mensaje; ?></div>
            <div class="row flex-column align-items-start" style="height: 40px;">
                <strong style="font-size: 25px;">Definición de Marca</strong>
            </div>
            <br />
            <form id="form1" name="form1" method="post" action="">
                <div class="row flex-column align-items-start bg-white">
                    <div class="p-3">
                        <div class="col-12">
                            <div class="col-3 float-start p-1">
                                <div class="mb-3">
                                    <label for="sistema" class="m-0">Nombre de la Marca</label>
                                    <input type="text" class="form-control fs-12" 
                                           name="sistema" 
                                           id="sistema" 
                                           value="<?php echo $row_prellenado['DETMARCA']; ?>" 
                                           placeholder="Descripción de la marca">
                                </div>
                            </div>

                            <!-- 
                                Se elimina la sección de "estado" 
                                pues la tabla artimarcas no contempla
                                un campo activo/inactivo
                            -->

                            <div class="col-12 float-start p-1" 
                                 style="display: flex; justify-content: center; align-items: center;">
                                <div class="mb-3" 
                                     style="width: 100%; display: flex; justify-content: right;">
                                    <input name="marcaid" 
                                           id="marcaid" 
                                           type="hidden" 
                                           value="<?php echo $marcaid; ?>" />
                                    <input name="guardar_marca" 
                                           id="guardar_marca" 
                                           type="submit" 
                                           class="btn btn-pna-oscuro" 
                                           style="width: 200px;" 
                                           value="Guardar" />
                                </div>
                            </div>                      

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
