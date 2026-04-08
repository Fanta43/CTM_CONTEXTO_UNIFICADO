<?php
include('cargador.php');

//ini_set( 'display_errors', 'on' );

?>

<style type="text/css">

#area #form1 .fondo-II img {
	margin: 5px;
}

</style>

<?php

$devolucion_fecha = date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d") + 90));

$guardar_prestamo = Enmascarar_post('guardar_prestamo');
$prestamoid = Enmascarar_post('prestamoid');
$fechaprestamo = Enmascarar_post('fechaprestamo');
$responsable = Enmascarar_post('responsable');
$id_pc = Enmascarar_post('id_pc');
$id_impresoras = Enmascarar_post('id_impresoras');
$id_monitores = Enmascarar_post('id_monitores');
$id_otros = Enmascarar_post('id_otros');
$dispositivo = Enmascarar_post('dispositivo');
$observaciones = Enmascarar_post('observaciones');
$legajo = Enmascarar_post('legajo');
$centrocosto = Enmascarar_post('centrocosto');
$devolucion_estimada = Enmascarar_post('devolucion_estimada');

$parametros['codigopc'] = Enmascarar_post('codigopc');

$parametros['prestamoid'] = Enmascarar_post('prestamoid');

$parametros['legajo'] = Enmascarar_post('legajo');

$prellenado = consultaReparacion($parametros, '1', $usu);

$responsable =  $prellenado['RESPONSABLE'];

$legajo =  $prellenado['LEGAJO'];

if ($responsable) {
    //echo "<h1>ENTRA</h1>";
    $terminos = explode(' ', trim($responsable)); // Separar todos los términos por espacios
    $terminosFiltrados = array_filter($terminos, function($termino) {
        return !empty($termino); // Filtrar términos vacíos o nulos
    });

    // Preparar términos de búsqueda con comodines, limitados a los primeros 4 términos no vacíos
    $terminosBusqueda = array_slice($terminosFiltrados, 0, 4);

    // Formar la consulta SQL considerando todas las posibles combinaciones de términos válidos
    $condiciones = [];
    foreach ($terminosBusqueda as $termino) {
        $terminoConComodines = "'%" . $termino . "%'";
        $condiciones[] = "UPPER(EmpPrimerApellido) LIKE UPPER(".trim($terminoConComodines).")";
        $condiciones[] = "UPPER(EmpSegundoApellido) LIKE UPPER(".trim($terminoConComodines).")";
        $condiciones[] = "UPPER(EmpPrimerNombre) LIKE UPPER(".trim($terminoConComodines).")";
        $condiciones[] = "UPPER(EmpSegundoNombre) LIKE UPPER(".trim($terminoConComodines).")";
    }

    // Solo añadir la condición de búsqueda si hay términos válidos
    if (!empty($condiciones)) {
        $condicionesString = implode(' OR ', $condiciones);
        $nombreCompletoParaBusqueda = "'%" . implode(' ', $terminosBusqueda) . "%'";

        $sql_legajo = "SELECT EmpLegajo AS id, 
                              EmpNombre AS nombre
                         FROM autog_rhpro_ctm_plantel_vw
                        WHERE ($condicionesString 
                           OR UPPER(EmpNombre) LIKE UPPER(".trim($nombreCompletoParaBusqueda)."));";

        //echo $sql_legajo;
        // Aquí iría el código para ejecutar la consulta y procesar los resultados
    } else {
        echo "No hay términos válidos para la búsqueda.";
    }
}

if($guardar_prestamo){
  
    if($prestamoid){
        $sql = " UPDATE prestamos 
                    SET id_aspnetuser = '$id_aspnetuser', 
                        fechaprestamo = '$fechaprestamo', 
                        responsable = '$responsable', 
                        id_pc = '$id_pc', 
                        id_impresoras = '$id_impresoras', 
                        id_monitores = '$id_monitores', 
                        id_otros = '$id_otros', 
                        dispositivo = '$dispositivo', 
                        observaciones = '$observaciones', 
                        devuelto = '0',
                        fechadevolucion = NULL
                    WHERE Id = '$prestamoid'; ";
        echo $sql;
        $result_1 = sql_do_query($sql, '1', $usu);
        
        $sql = " UPDATE inve_prestamos
                    SET id_aspnetuser = '$usu', 
                        fechaprestamo = '$fechaprestamo', 
                        responsable = '$responsable', 
                        id_pc = '$id_pc', 
                        id_impresoras = '$id_impresoras', 
                        id_monitores = '$id_monitores', 
                        id_otros = '$id_otros', 
                        dispositivo = '$dispositivo', 
                        observaciones = '$observaciones', 
                        devuelto = '0',
                        fechadevolucion = NULL,
                        legajo = '$legajo',
                        centrocosto = '$centrocosto',
                        devolucion_estimada = '$devolucion_estimada',
                        estadoid = '1',
                        auditoria_usu = '$usu',
                        auditoria_fecha = '$hoy'
                  WHERE prestamoid = '$prestamoid'; ";
        echo $sql;
        $result_2 = sql_do_query($sql, '1', $usu);

                if($result_1 && $result_2){
                    echo '<script>swal({
                               title: "Buen Trabajo!",
                               text: "Has actualizado el inventario!",
                               icon: "success",
                               dangerMode: false,
                               buttons: {
                                   confirm: {text: "Continuar", value: true},
                               },
                          })
                          .then((value) => {
                              window.location.replace("index.php?estado=10");
                          })    
                         </script>';
                }
        
    }else{
        
        $prestamoid = get_id('INVE_PRESTAMOS');
        
        $sql = "INSERT INTO prestamos (
                            id, 
                            id_aspnetuser, 
                            fechaprestamo, 
                            responsable, 
                            id_pc, 
                            id_impresoras, 
                            id_monitores, 
                            id_otros, 
                            dispositivo, 
                            observaciones, 
                            devuelto, 
                            fechadevolucion
                ) VALUES (
                            '$prestamoid',
                            '$id_aspnetuser', 
                            '$fechaprestamo', 
                            '$responsable', 
                            '$id_pc', 
                            '$id_impresoras', 
                            '$id_monitores', 
                            '$id_otros', 
                            '$dispositivo', 
                            '$observaciones', 
                            '0', 
                            NULL
                );";
        echo $sql;
        $result_1 = sql_do_query($sql, '1', $usu);
            
        $sql = "INSERT INTO inve_prestamos (
                            prestamoid,
                            id_aspnetuser, 
                            fechaprestamo, 
                            responsable, 
                            id_pc, 
                            id_impresoras, 
                            id_monitores, 
                            id_otros, 
                            dispositivo, 
                            observaciones, 
                            devuelto, 
                            fechadevolucion,
                            legajo, 
                            centrocosto, 
                            devolucion_estimada, 
                            estadoid, 
                            auditoria_usu, 
                            auditoria_fecha
                )VALUES(
                            '$prestamoid',
                            '$usu', 
                            '$fechaprestamo', 
                            '$responsable', 
                            '$id_pc', 
                            '$id_impresoras', 
                            '$id_monitores', 
                            '$id_otros', 
                            '$dispositivo', 
                            '$observaciones', 
                            '0', 
                            NULL,
                            '$legajo', 
                            '$centrocosto', 
                            '$devolucion_estimada', 
                            '1', 
                            '$usu', 
                            '$hoy'
                );";
        echo $sql;
        $result_2 = sql_do_query($sql, '1', $usu);

            if($result_1 && $result_2){
                // ── Registrar evento cross-module ───────────────────────────
                integ_registrar_evento('ctm_inventarios', 'nueva_reparacion', [
                    'cc' => $centrocosto ?? '',
                    'legajo' => $legajo ?? '',
                    'referencia_id' => $prestamoid ?? '',
                    'descripcion' => "Reparacion registrada - Responsable: " . ($responsable ?? '') . " (CC: " . ($centrocosto ?? '') . ")"
                ], '1', $usu);

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
                          window.location.replace("index.php?estado=10");
                      })
                     </script>';
            }
    }

}

$devolucion_prestamo = Enmascarar_post('devolucion_prestamo');
$devuelto = Enmascarar_post('devuelto');
$fechadevolucion = Enmascarar_post('fechadevolucion');

if($devolucion_prestamo && $prestamoid){

        $sql = " UPDATE prestamos 
                    SET devuelto = '$devuelto',
                        fechadevolucion = '$fechadevolucion'
                    WHERE Id = '$prestamoid'; ";
        echo $sql;
        $result_1 = sql_do_query($sql, '1', $usu);
        
        $sql = " UPDATE inve_prestamos 
                    SET devuelto = '$devuelto',
                        fechadevolucion = '$fechadevolucion',
                        estadoid = '1',
                        auditoria_usu = '$usu',
                        auditoria_fecha = '$hoy'
                  WHERE prestamoid = '$prestamoid'; ";
        echo $sql;
        $result_2 = sql_do_query($sql, '1', $usu);

                if($result_1 && $result_2){
                    echo '<script>swal({
                               title: "Buen Trabajo!",
                               text: "Has devuelto el equipo!",
                               icon: "success",
                               dangerMode: false,
                               buttons: {
                                   confirm: {text: "Continuar", value: true},
                               },
                          })
                          .then((value) => {
                              window.location.replace("index.php?estado=10");
                          })    
                         </script>';
                }
   
}

?>

<div class="area"><br />
    <div class="col-12">
        <div class="col-12 p-2 pb-0">

            
    <div class='titulo_mediano_center_rojo'><?php echo $mensaje;?></div>
        <div class="row flex-column align-items-start" style="height: 40px;">
            <strong style="font-size: 25px;">Prestamo de equipos</strong>
        </div>
    <br />
    <form id="form1" name="form1" method="post" action="">
    <div class="row flex-column align-items-start bg-white">
                <div class="p-3">
                        <div class="col-12">
                               <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Id Pc</label>
                                                <input type="text" class="form-control fs-12" name="id_pc" id="id_pc" value="<?php echo $prellenado['ID_PC']; ?>" placeholder="Id Pc">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Id Impresoras</label>
                                                <input type="text" class="form-control fs-12" name="id_impresoras" id="id_impresoras" value="<?php echo $prellenado['ID_IMPRESORAS']; ?>" placeholder="Id Impresoras">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class=" m-0">Id Monitores</label>
                                        <input type="text" class="form-control fs-12" name="id_monitores" id="id_monitores" value="<?php echo $prellenado['ID_MONITORES']; ?>" placeholder="Id Monitores">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                            <label for="exampleFormControlInput1" class=" m-0" >Id Otros</label>
                                            <input type="text" class="form-control fs-12" name="id_otros" id="id_otros" value="<?php echo $prellenado['ID_OTROS']; ?>" placeholder="Id Otros">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Fecha Préstamo:</label>
                                                <input type="date" class="form-control fs-12" name="fechaprestamo"' id="fechaprestamo" value="<?php echo substr($prellenado['FECHAPRESTAMO'],0,10); ?>" placeholder="Fecha Prestamo">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Devoluci&oacute;n Estimada:</label>
                                                <input type="date" class="form-control fs-12" name="devolucion_estimada" id="devolucion_estimada" value="<?php if($prellenado['DEVOLUCION_ESTIMADA']){echo substr($prellenado['DEVOLUCION_ESTIMADA'],0,10);}else{echo $devolucion_fecha;}  ?>" placeholder="Fecha Devoluci&oacute;n">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Responsable</label>
                                                <input type="text" class="form-control fs-12" name="responsable" id="responsable" value="<?php echo $responsable; ?>" placeholder="Responsable">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleInputEmail1" class="form-label m-0">Legajo</label>                                      
                                            <?php
                                                echo imp_combo_w_fs12('legajo', $sql_legajo, '1', $usu, $legajo);
                                            ?>
                                    </div>
                                </div>
                                 <div class="col-12 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Dispositivo</label>
                                                <input type="text" class="form-control fs-12" name="dispositivo" id="dispositivo" value="<?php echo $prellenado['DISPOSITIVO']; ?>" placeholder="Dispositivo">
                                        </div>
                                 </div>
                                <div class="col-12 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Observaciones</label>
                                                <input type="text" class="form-control fs-12" name="observaciones" id="observaciones" value="<?php echo $prellenado['OBSERVACIONES']; ?>" placeholder="Observaciones">
                                        </div>
                                </div>
                                <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                    <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                        <input type="hidden" name="prestamoid" id="prestamoid" value="<?php echo $prestamoid; ?>" />
                                        <input name="guardar_prestamo" id="guardar_prestamo" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                    </div>
                                </div>                      
                         </div>
          </div>
    </div>
    </form>
  </div>
<br />
<?php
if($prestamoid){
?>
<div class="col-12 separar1 p-0 float-start mt-3 bg-white">
        <div class="accordion accordion-flush" id="accordion2">
<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse2" aria-expanded="false" aria-controls="flush-collapse2">
                    <h6 class="m-0"><img src="img/icon/libro.png" class="me-2" style="height: 20px; width: 20px;">Baja</h6>
                </button>
            </h2>
            <div id="flush-collapse2" class="accordion-collapse collapse" data-bs-parent="#accordion2">
                <div class="accordion-body">
                    <div class="row">
            <div class="bg-white" >
                <div class="p-3">
                        <form id="form_guardar" name="form_guardar" method="post" action="">
                        <div class="row col-12">
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="ubicacionSelect" class=" m-0">Devuelto</label>
                                        <select class="form-control" name="devuelto" id="devuelto">
                                            <?php
                                            $sql = "SELECT estadoid, descripcion FROM inve_estados WHERE tabla = 'INVE_PRESTAMOS';";
                                            $result = sql_do_query($sql, '1', $usu);
                                            while ($row = sql_do_array($result)) {
                                                if($row['ESTADOID'] == $prellenado['DEVUELTO']){$sele = ' selected = selected ';}else{$sele = '';}
                                                echo '<option '.$sele.' value="' . htmlspecialchars($row['ESTADOID']) . '">' . htmlspecialchars($row['DESCRIPCION']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div> 
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Devoluci&oacute;n Estimada:</label>
                                                <input type="date" class="form-control fs-12" name="fechadevolucion" id="fechadevolucion" value="<?php echo $hoy; ?>" placeholder="Fecha Devoluci&oacute;n">
                                        </div>
                                </div>
                                <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                    <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                        <input type="hidden" name="prestamoid" id="prestamoid" value="<?php echo $prestamoid; ?>" />
                                        <input name="devolucion_prestamo" id="devolucion_prestamo" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                    </div>
                                </div>   
                         </div>  
                    </form>
                    
                    
                    
                </div>
            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}
?>
