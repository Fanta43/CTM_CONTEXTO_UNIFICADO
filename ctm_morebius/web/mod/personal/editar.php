<?php
//ini_set('display_errors', 'on');
?>
<style>
/* Estilos generales */
.container {
    max-width: 1200px;
    margin: 0 auto;
    font-family: Arial, sans-serif;
}
.card {
    margin-bottom: 30px;
    border-radius: 10px;
    border: 1px solid #ccc;
}
.card-header {
    background: #f7f7f7;
    border-bottom: 1px solid #ccc;
    padding: 15px;
    font-weight: bold;
    font-size: 18px;
    text-align: center;
}
.card-body {
    padding: 15px;
    background-color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.employee-data {
    flex: 1;
    margin: 20px;
}
.imagen-circular {
    text-align: right;
    /* Aseguramos que el contenedor de la imagen se alinee al inicio */
    align-self: flex-start;
}
.imagen-circular img {
    width: 150px;       /* Ampliamos la imagen */
    height: 200px;      /* Ampliamos la imagen */
    object-fit: cover;
    border-radius: 50%;
    margin-top: 20px;  /* Sube la imagen lo máximo posible */
}
.table {
    width: 100%;
    margin-bottom: 1rem;
    border-collapse: collapse;
}
.table th,
.table td {
    padding: 8px 12px;
    border: 1px solid #ccc;
    text-align: center;
}
.table thead.table-dark th {
    background-color: #343a40;
    color: #fff;
}
.resaltado-rojo {
    background-color: #ff9187;
}
.resaltado-verde {
    background-color: #6bffae;
}
.resaltado-amarillo {
    background-color: #f1d9ba;
}
</style>
<?php
$legajo  = Enmascarar_post('legajo');
$nro_art = Enmascarar_post('nro_art');
$de_baja = Enmascarar_post('de_baja');
$row_empleado = [];
if ($legajo) {
    $sqlEmp = "
        SELECT EmpLegajo,
               EmpNombre,
               EmpCargoNom,
               EmpSucursalNom,
               EmpDocDNI,
               EmpDocCuil,
               EmpFchAlta,
               EmpEmail,
               EmpObraSocial,
               CentroCosto
        FROM ctm_plantel
        WHERE EmpLegajo = '$legajo'
    ";
    $row_empleado = sql_do_array(sql_do_query($sqlEmp, '2', $usu), '2', $usu);
    $archivo = determinarImagen($row_empleado['EMPLEGAJO'], $row_empleado['EMPNOMBRE'], $servidor_img);
}
?>
<div class="container">
    <div class="card">
        <div class="card-header">DATOS DEL EMPLEADO</div>
        <div class="card-body">
            <div class="employee-data">
                <?php if (!empty($row_empleado)) { ?>
                    <p><strong>Legajo:</strong> <?php echo htmlspecialchars($row_empleado['EMPLEGAJO']); ?></p>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($row_empleado['EMPNOMBRE']); ?></p>
                    <p><strong>Cargo:</strong> <?php echo htmlspecialchars($row_empleado['EMPCARGONOM']); ?></p>
                    <p><strong>Sucursal:</strong> <?php echo htmlspecialchars($row_empleado['EMPSUCURSALNOM']); ?></p>
                    <p><strong>DNI:</strong> <?php echo htmlspecialchars($row_empleado['EMPDOCDNI']); ?></p>
                    <p><strong>CUIL:</strong> <?php echo htmlspecialchars($row_empleado['EMPDOCCUIL']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row_empleado['EMPEMAIL']); ?></p>
                    <p><strong>Obra Social:</strong> <?php echo htmlspecialchars($row_empleado['EMPOBRASOCIAL']); ?></p>
                    <p><strong>Fecha de Alta:</strong> <?php echo htmlspecialchars($row_empleado['EMPFCHALTA']); ?></p>
                <?php } else { ?>
                    <p>No se encontró información del empleado (verifique Legajo).</p>
                <?php } ?>
            </div>
            <div class="imagen-circular">
                <?php if (!empty($row_empleado)) { ?>
                    <img src="<?php echo $archivo; ?>" 
                         alt="Imagen de <?php echo htmlspecialchars($row_empleado['EMPNOMBRE']); ?>">
                <?php } ?>
            </div>
        </div>

        <div class="card-header">
            <?php
            if (!empty($row_empleado['EMPLEGAJO'])) {
                echo dibujar_boton_nuevo([
                    'estado' => '100',
                    'legajo_art' => $row_empleado['EMPLEGAJO']
                ]);
            }
            ?>
        </div>


    </div>
</div>

<?php

$host = Enmascarar_post('equipo');
$ip      = Enmascarar_post('ip');  // reutilizamos el input "sistema" como nombre de la marca
$asignar      = Enmascarar_post('asignar');  // reutilizamos el input "sistema" como nombre de la marca
$eliminar_asignar      = Enmascarar_post('eliminar_asignar');  // reutilizamos el input "sistema" como nombre de la marca

// Al hacer clic en "Guardar"
if ($eliminar_asignar) {
        $sql = "UPDATE more_personal_pc
                   SET estadoid = '0',
                       auditoria_usu = '$usu',
                       auditoria_fecha = '$hoy'
                 WHERE legajo = '".$legajo."' AND equipo = '".$host."' AND estadoid = '1';";
        //echo($sql);
        $result = sql_do_query($sql, '1', $usu);

        if ($result) {
            echo '<script>
                    swal({
                        title: "¡Buen trabajo!",
                        text: "¡Has actualizado eliminado la asignación!",
                        icon: "success",
                        dangerMode: false,
                        buttons: {
                            confirm: {text: "Continuar", value: true},
                        },
                    })
                    .then((value) => {
                        window.location.replace("index.php?estado=4");
                    });
                  </script>';
        }

}

// Al hacer clic en "Guardar"
if ($asignar) {

    $sql = "SELECT personal_pcid AS id FROM more_personal_pc WHERE legajo = '$legajo' AND equipo = '$host' AND estadoid = '0';";
    ///echo $sql;  
    $row_prellenado = sql_do_array(sql_do_query($sql, '1', $usu), '1', $usu);
    // Si estamos editando (existe marcaid)
    if ($row_prellenado['PERSONAL_PCID']) {
        $sql = "UPDATE more_personal_pc
                   SET legajo = '$legajo',
                       equipo = '$host',
                       ip = '$ip',
                       estadoid = '1',
                       auditoria_usu = '$usu',
                       auditoria_fecha = '$hoy'
                 WHERE personal_pcid = '".$row_prellenado['PERSONAL_PCID']."';";
        //echo($sql);
        $result = sql_do_query($sql, '1', $usu);
        if ($result) {
            echo '<script>
                    swal({
                        title: "¡Buen trabajo!",
                        text: "¡Has actualizado la asignación!",
                        icon: "success",
                        dangerMode: false,
                        buttons: {
                            confirm: {text: "Continuar", value: true},
                        },
                    })
                    .then((value) => {
                        window.location.replace("index.php?estado=4");
                    });
                  </script>';
        }


    // Si no hay marcaid, creamos un nuevo registro
    } else {
        $sql = "INSERT INTO more_personal_pc (
                      legajo,
                      equipo,
                      ip,
                      estadoid,
                      auditoria_usu,
                      auditoria_fecha
                  ) VALUES (
                      '$legajo',
                      '$host',
                      '$ip',
                      '1',
                      '$usu',
                      '$hoy'
                  );";
        //echo($sql);
        $result = sql_do_query($sql, '1', $usu);

        if ($result) {
            echo '<script>
                    swal({
                        title: "¡Buen trabajo!",
                        text: "¡Has dado de alta una nueva asignación!",
                        icon: "success",
                        dangerMode: false,
                        buttons: {
                            confirm: {text: "Continuar", value: true},
                        },
                    })
                    .then((value) => {
                        window.location.replace("index.php?estado=4");
                    });
                  </script>';
        }

    }
}

if ($legajo) {
    if ($nro_art) {
        if($de_baja == '1'){
            $sql = "
                UPDATE articulos_con
                   SET DE_BAJA  = '1',
                       fch_baja = '$hoy'
                 WHERE nro_art  = '$nro_art';
            ";
            $result = sql_do_query($sql, '2', $usu);
        } else {
            $sql = "
                UPDATE articulos_con
                   SET DE_BAJA  = '0',
                       fch_baja = '1900-01-01'
                 WHERE nro_art  = '$nro_art';
            ";
            $result1 = sql_do_query($sql, '2', $usu);
            $sql = "
                DELETE 
                  FROM articulos_his
                 WHERE nro_art  = '$nro_art';
            ";
            $result1 = sql_do_query($sql, '2', $usu);
        }
        if ($result or $result1) {
            if ($result) {
                    echo '<script>
                    swal({
                        title: "Buen Trabajo!",
                        text: "Se ha actualizado el artículo correctamente!",
                        icon: "success",
                        dangerMode: false,
                        buttons: {
                            confirm: {text: "Continuar", value: true},
                        },
                    })
                    .then((value) => {
                        // Abre la URL en un popup en vez de redirigir
                        window.open(
                            "popups_manager.php?estado=112&nro_art='.$nro_art.'",
                            "Popup",
                            "width=800,height=600,resizable=yes,scrollbars=yes"
                        );
                    })
                    </script>';
            }
            if ($result1) {
                echo '<script>
                swal({
                    title: "Buen Trabajo!",
                    text: "Se ha reactivado el artículo correctamente!",
                    icon: "success",
                    dangerMode: false,
                    buttons: {
                        confirm: {text: "Continuar", value: true},
                    },
                })
                </script>';
            }            
        } else {
            echo '<script>
                    swal({
                        title: "Error!",
                        text: "No se pudo actualizar el artículo.",
                        icon: "error",
                        dangerMode: true,
                        buttons: {
                            confirm: {text: "Continuar", value: true},
                        },
                    });
                  </script>';
        }
    }
}

if ($legajo) {
    $sqlNotebook = "
        SELECT nro_art, fecha, tipo_art, detalle, referencia, nota, CelPrincipal, TipCel, de_baja, legajo_art
          FROM articulos_con
         WHERE tipo_art = '3' 
           AND legajo_art = '$legajo'
         ORDER BY de_baja ASC, nro_art DESC;
    ";
    editarArticulosSeccion("Notebook del usuario", $sqlNotebook, '2', $usu, $row_empleado);
    $sqlCelulares = "
        SELECT nro_art, fecha, tipo_art, detalle, referencia, nota, CelPrincipal, TipCel, de_baja, legajo_art
          FROM articulos_con
         WHERE tipo_art = '1' 
           AND tipcel = '2'
           AND legajo_art = '$legajo'
         ORDER BY de_baja ASC, nro_art DESC;
    ";
    editarArticulosSeccion("Celulares del usuario", $sqlCelulares, '2', $usu, $row_empleado);
    $sqlLineas = "
        SELECT nro_art, fecha, tipo_art, detalle, referencia, nota, CelPrincipal, TipCel, de_baja, legajo_art
          FROM articulos_con
         WHERE tipo_art = '1' 
           AND tipcel = '3'
           AND legajo_art = '$legajo'
         ORDER BY de_baja ASC, nro_art DESC;
    ";
    editarArticulosSeccion("Líneas del usuario", $sqlLineas, '2', $usu, $row_empleado);
    
    editarPCSeccion("Equipo de escritorio del usuario", $legajo, '1', $usu);
}
?>