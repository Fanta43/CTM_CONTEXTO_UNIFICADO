<style type="text/css"> 
    #area #form1 .fondo-II img {
        margin: 5px;
    }
</style>
<?php
//ini_set('display_errors', 'on');
$excepcionesid = Enmascarar_post('excepcionesid');
$legajo = Enmascarar_post('legajo');
$guardar_personal = Enmascarar_post('guardar_personal');
$empcargocod = Enmascarar_post('empcargocod');
$centrocosto = Enmascarar_post('centrocosto');
$antecedentes = Enmascarar_post('antecedentes');
$tipo_articuloid = Enmascarar_post('tipo_articuloid');
$tipo_equipoid = Enmascarar_post('tipoequipoid');
$estadoid = Enmascarar_post('estadoid');
if(!$estadoid) $estadoid = '0';
// Obtener datos prellenados si existe el legajo
if ($excepcionesid) {
    $row_prellenado_exceptuado =  obtenerExcepcion('1', $usu, $excepcionesid);
    if(!$legajo)  $legajo = $row_prellenado_exceptuado['LEGAJO'];
}

if($legajo){ 
    $row_prellenado =  obtenerLegajosPersona('2', $usu, $legajo);
    //var_dump($row_prellenado);
}
// Guardar o actualizar datos del personal exceptuado
if ($guardar_personal) {
    if ($excepcionesid) {
        // Actualizar registro existente
        $sql = "UPDATE more_personal_exceptuado
                   SET legajo = '$legajo',
                       empcargocod = '$empcargocod',
                       centrocosto = '$centrocosto',
                       antecedentes = '$antecedentes',
                       tipo_articuloid = '$tipo_articuloid',
                       tipo_equipoid = '$tipo_equipoid',
                       estadoid = '$estadoid', 
                       auditoria_usu = '$usu', 
                       auditoria_fecha = '$hoy'
                 WHERE excepcionesid = '$excepcionesid';";
        $result = sql_do_query($sql, '1', $usu);
        //echo $sql;     
        if ($result) {
            echo '<script>swal({
                       title: "¡Buen Trabajo!",
                       text: "¡Has actualizado el registro del personal exceptuado!",
                       icon: "success",
                       dangerMode: false,
                       buttons: {
                           confirm: {text: "Continuar", value: true},
                       },
                  })
                  .then((value) => {
                      window.location.replace("index.php?estado=39");
                  })    
                 </script>';
        }
    } else {
        // Insertar nuevo registro
        $excepcionesid = get_id('MORE_PERSONAL_EXCEPTUADO');
        $sql = "INSERT INTO more_personal_exceptuado (
                        excepcionesid,
                        legajo,
                        empcargocod,
                        centrocosto,
                        antecedentes,
                        tipo_articuloid,
                        tipo_equipoid,
                        estadoid, 
                        auditoria_usu, 
                        auditoria_fecha
                ) VALUES (
                        '$excepcionesid',
                        '$legajo',
                        '$empcargocod',
                        '$centrocosto',
                        '$antecedentes', 
                        '$tipo_articuloid',
                        '$tipo_equipoid',
                        '$estadoid', 
                        '$usu', 
                        '$hoy'
                );";
        //echo $sql;        
        $result = sql_do_query($sql, '1', $usu);
        if ($result) {
            echo '<script>swal({
                       title: "¡Buen Trabajo!",
                       text: "¡Has registrado un nuevo personal exceptuado!",
                       icon: "success",
                       dangerMode: false,
                       buttons: {
                           confirm: {text: "Continuar", value: true},
                       },
                  })
                  .then((value) => {
                      window.location.replace("index.php?estado=39");
                  })    
                 </script>';
        }
    } 
}

// Si no hay datos prellenados, asignar valores predeterminados
if (!$row_prellenado['APELLIDO_NOMBRE']) $row_prellenado['APELLIDO_NOMBRE'] = 'Sin Nombre';
$legajo_c = (!$legajo) ? $row_prellenado_exceptuado['LEGAJO']: $legajo;
//echo "<h1>-->$empcargocod</h1>";
//echo "<h1>-->$centrocosto</h1>";
$empcargocod_c = (!$empcargocod or $empcargocod == 'null') ? $row_prellenado['EMPCARGOCOD'] : $empcargocod;
$centrocosto_c = (!$centrocosto or $centrocosto == 'null') ? $row_prellenado['CENTROCOSTO'] : $centrocosto;                                     
$tipo_articuloid_c = (!$row_prellenado_exceptuado['TIPO_ARTICULOID']) ? $tipo_articuloid: $row_prellenado_exceptuado['TIPO_ARTICULOID'];
$tipo_equipoid_c = (!$row_prellenado_exceptuado['TIPO_EQUIPOID']) ? $tipo_equipoid: $row_prellenado_exceptuado['TIPO_EQUIPOID'];
$estadoid_c = (!$row_prellenado_exceptuado['ESTADOID']) ? $estadoid: $row_prellenado_exceptuado['ESTADOID'];                                                           
//echo "<h1>$empcargocod_c<--</h1>";
//echo "<h1>$centrocosto_c<--</h1>";

?>
<div class="area"><br />
    <div class="col-12">
        <div class="col-12 p-2 pb-0"> 
            <div class='titulo_mediano_center_rojo'><?php echo $mensaje; ?></div>
            <div class="row flex-column align-items-start" style="height: 40px;">
                <strong style="font-size: 25px;">Definición del Personal Exceptuado</strong>
            </div>
            <br />
            <form id="form1" name="form1" method="post" action="">
                <div class="row flex-column align-items-start bg-white">
                    <div class="p-3">
                        <div class="col-12">
                            <div class="col-6 float-start p-1">
                                <div class="mb-3">
                                    <label for="apellido_nombre" class="m-0">Apellido y Nombre</label>
                                    <?php
                                        $query_mysql = "SELECT EmpLegajo AS id, CONCAT(EmpNombre, ' ', EmpLegajo) AS nombre FROM ctm_plantel WHERE EmpLegajo IS NOT NULL ORDER BY EmpNombre;";
                                        $query = "
                                             SELECT EmpLegajo AS id, 
                                                    EmpNombre + ' ' + CAST(EmpLegajo AS VARCHAR) AS nombre 
                                               FROM ctm_plantel 
                                              WHERE EmpLegajo IS NOT NULL 
                                           ORDER BY EmpNombre;                                        
                                        ";
                                        
                                        echo imp_combo_w_oc('legajo', $query, '2', $usu, $legajo_c);
                                    ?> 
                                </div>
                            </div>
                            <div class="col-6 float-start p-1">
                                <div class="mb-3">
                                    <label for="empcargocod" class="m-0">Código de Cargo</label>
                                    <?php
                                        echo imp_combo_herr_sc('empcargocod', 'ctm_cargos', '2', $usu, 'CARGOCOD', 'CARGONOM', $empcargocod_c);
                                    ?>
                                </div>
                            </div>
                            <div class="col-6 float-start p-1">
                                <div class="mb-3">
                                    <label for="centrocosto" class="m-0">Centro de Costo</label>
                                    <?php
                                        echo imp_combo_herr_sc('centrocosto', 'ctm_centroscosto', '2', $usu, 'CENTROCOSTO', 'CENTROCOSTONOM', $centrocosto_c);
                                    ?>
                                </div>
                            </div>
                            <div class="col-6 float-start p-1">
                                <div class="mb-3">
                                    <label for="tipo_articuloid" class="m-0">Tipos de Artículos</label>
                                    <?php
                                         echo imp_combo_herr_sc('tipo_articuloid', 'articulos_tipos', '2', $usu, 'TIPO_ART', 'DETALLE', $tipo_articuloid_c);
                                    ?>
                                </div>
                            </div>
                            <div class="col-6 float-start p-1">
                                <div class="mb-3">
                                    <label for="tipoequipoid" class="m-0">Tipos de Equipos</label>
                                    <?php
                                       echo imp_combo_herr_sc('tipoequipoid', 'more_tipos_equipos', '1', $usu, 'TIPOEQUIPOID', 'DESCRIPCION', $tipo_equipoid_c);
                                    ?>
                                </div>
                            </div>
                            <div class="col-6 float-start p-1">
                                <div class="mb-3">
                                    <label for="apellido_nombre" class="m-0">Estado de la autorización</label>
                                    <?php
                                        $sql = "SELECT estadoid AS id, descripcion AS nombre FROM more_estados WHERE tabla = 'GRAL' ORDER BY estadoid DESC;";
                                        echo imp_combo_w_oc('estadoid', $sql, '1', $usu, $estadoid_c);
                                    ?> 
                                </div>
                            </div>
                            <div class="col-12 float-start p-1">
                                <div class="mb-3">
                                    <label for="antecedentes" class="m-0">Antecedentes</label>
                                    <textarea class="form-control fs-12" name="antecedentes" id="antecedentes" placeholder="Antecedentes del Personal"><?php echo $row_prellenado['ANTECEDENTES']; ?></textarea>
                                </div>
                            </div>
                            <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                        <input name="excepcionesid" id="excepcionesid" type="hidden" value="<?php echo $excepcionesid; ?>" />
                                        <input name="guardar_personal" id="guardar_personal" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>