<?php
//ini_set( 'display_errors', 'on' );
/************************/
$rowpersona = consultaPersona($legajo, '1', $usu);

$obligatorio_guardar = Enmascarar_post('obligatorio_guardar');
$obligatorios_importar = Enmascarar_post('obligatorios_importar');
$guardar_importar = Enmascarar_post('guardar_importar');
$obligatorio_id = Enmascarar_post('obligatorio_id');

$guardar_baja  = Enmascarar_post('guardar_baja');
$nro_art  = Enmascarar_post('nro_art');
$nota_devolucion = Enmascarar_post('nota_devolucion');
$guardar_morebius  = Enmascarar_post('guardar_morebius');



if($obligatorio_guardar) {
    $parametros = [
        'obligatorio_id' => Enmascarar_post('obligatorio_id'),
        'legajo' => Enmascarar_post('legajo'),
        'centrocosto' => Enmascarar_post('centrocosto'),
        'pcc' => Enmascarar_post('pcc'),
        'numoblea' => Enmascarar_post('numoblea'),
        'observaciones' => Enmascarar_post('observaciones'),
        'fechavtogarantia' => Enmascarar_post('fechavtogarantia'),
        'sr' => Enmascarar_post('sr'),
        'oc' => Enmascarar_post('oc'),
        'monto' => Enmascarar_post('monto'),
        'fechacompra' => Enmascarar_post('fechacompra'),
        'id_zonas_inventario' => Enmascarar_post('id_zonas_inventario')
    ];

    actualizarDatosInventario($codigopc, $parametros, '1', $usu);
}

if($guardar_importar=="Guardar") {
    
    $obligatorio_id = Enmascarar_post('obligatorio_id');
    
    $parametros = [
        'obligatorio_id' => Enmascarar_post('obligatorio_id'),
        'tipoprocesador' => Enmascarar_post('tipoprocesador'),
        'legajo' => Enmascarar_post('legajo'),
        'codigopc' => Enmascarar_post('codigopc'),
        'marca' => Enmascarar_post('marca'),
        'tipoequipo' => Enmascarar_post('tipoequipo'),
        'id_sistemaoperativo' => Enmascarar_post('id_sistemaoperativo'),
        'velocidadprocesador' => Enmascarar_post('velocidadprocesador'),
        'memoriaram' => Enmascarar_post('memoriaram'),
        'id_monitores' => Enmascarar_post('id_monitores'),
        'ip' => Enmascarar_post('ip'),
        'mac' => Enmascarar_post('mac'),  
        'numeroserie' => Enmascarar_post('numeroserie'),
        'clave1' => Enmascarar_post('clave1'),
        'clave2' => Enmascarar_post('clave2'),
        'clave3' => Enmascarar_post('clave3'),
        'clave4' => Enmascarar_post('clave4'),
        'clave5' => Enmascarar_post('clave5'),
        'discoscod' => Enmascarar_post('discoscod'),
        'disco' => Enmascarar_post('disco'),
    ];
    //var_dump($parametros);
    actualizarDatosInventarioExtra($obligatorio_id, $parametros, '1', $usu);
}
  
//var_dump($_POST);
/********************************/

//if($obligatorios_importar){

if($guardar_morebius){

            $usuario = $usu;
            $ingreso = $hoy_completo;    
            
            $devuelto = '0'; 
            $de_baja = '0'; 
            $fch_baja = '1900-01-01';
            
            $nro_linea = 'N/A'; 
            $pin = '0'; 
            $puk = 'N/A'; 
            $celprincipal = '0'; 
            $estacion_tel = 'N/A';
            $artplan = '1'; 
            $tipcel = '1';
            
            $garantia_vence = Enmascarar_post('garantia_vence');
            if(!$garantia_vence){$garantia_vence = $vence;}

            $marca = Enmascarar_post('marca');
            $modelo = Enmascarar_post('modelox');
            $nserie = Enmascarar_post('nserie');
            $imei = Enmascarar_post('imei');
            $planc = Enmascarar_post('planc');
            $nserie_modem = Enmascarar_post('nserie_modem');        

            $fecha = Enmascarar_post('fecha');
            $tipo_art = Enmascarar_post('tipo_art');
            $detalle = Enmascarar_post('detalle'); 
            $sector = Enmascarar_post('sector');
            $legajo_art = Enmascarar_post('legajo_art');
            $nota = Enmascarar_post('nota');
            $referencia = Enmascarar_post('referencia');      

            $pais_linea = Enmascarar_post('pais_linea');
            $ubicaid = Enmascarar_post('ubicaid');

            if($nro_art){
                
                $parametros['nro_art'] = $nro_art;
                $parametros['nota_devolucion'] = $nota;                
                articulosHistoricos($parametros, '3', $usu);

                $sql = "     UPDATE articulos_con a
                                SET a.fecha = '$fecha',
                                    a.tipo_art = '$tipo_art',
                                    a.detalle = '$detalle',
                                    a.sector = '$sector',
                                    a.legajo_art = '$legajo_art',
                                    a.nota = '$nota',
                                    a.referencia = '$referencia',
                                    a.devuelto = '$devuelto',
                                    a.de_baja = '$de_baja',
                                    a.fch_baja = '$fch_baja',
                                    a.nro_linea = '$nro_linea',
                                    a.pais_linea = '$pais_linea',
                                    a.tipcel = '$tipcel',
                                    a.ubicaid = '$ubicaid',
                                    a.pin = '$pin',
                                    a.puk = '$puk',
                                    a.marca = '$marca',
                                    a.modelo = '$modelo',
                                    a.nserie = '$nserie',
                                    a.imei = '$imei',
                                    a.planc = '$planc',
                                    a.nserie_modem = '$nserie_modem',
                                    a.estacion_tel = '$estacion_tel',
                                    a.garantia_vence = '$garantia_vence',
                                    a.artplan = '$artplan',
                                    a.celprincipal = '$celprincipal',
                                    a.usuario = '$usuario',
                                    a.ingreso = '$ingreso'
                              WHERE a.nro_art = '$nro_art';";
                echo $sql;
                $result = sql_do_query($sql, '3', $usu);
                
                $parametros['nro_art'] = $nro_art;
                $parametros['nota_devolucion'] = $nota;                
                articulosHistoricos($parametros, '3', $usu);

                if($result){

                    echo '<script>swal({
                                   title: "Buen Trabajo!",
                                   text: "Has modificado los datos!",
                                   icon: "success",
                                   dangerMode: false,
                                   buttons: {
                                       confirm: {text: "Continuar",
                                                 value: true,
                                       },
                                   },
                                  })
                     </script>';
                }

            }else{

                $parametros['nro_art'] = $nro_art;
                $parametros['nota_devolucion'] = $nota;                
                articulosHistoricos($parametros, '3', $usu);
                
                $sqlnuevo="SELECT (MAX(nro_art)+1) AS nuevo_id FROM articulos_con;";
                //echo $sql;
                $nro=sql_do_array(sql_do_query($sqlnuevo,'3',$usu));
                
                $nro_art=$nro['NUEVO_ID'];
                
                $sql = "INSERT INTO articulos_con (
                                    nro_art,
                                    fecha,
                                    tipo_art,
                                    detalle,
                                    sector,
                                    legajo_art,
                                    nota,
                                    referencia,
                                    usuario,
                                    ingreso,
                                    devuelto,
                                    de_baja,
                                    fch_baja,
                                    nro_linea,
                                    pais_linea,
                                    tipcel,
                                    ubicaid,
                                    pin,
                                    puk,
                                    marca,
                                    modelo,
                                    nserie,
                                    imei,
                                    planc,
                                    nserie_modem,
                                    estacion_tel,
                                    garantia_vence,
                                    artplan,
                                    celprincipal
                         ) VALUES (
                                    '$nro_art',
                                    '$fecha',
                                    '$tipo_art',
                                    '$detalle',
                                    '$sector',
                                    '$legajo_art',
                                    '$nota',
                                    '$referencia',
                                    '$usuario',
                                    '$ingreso',
                                    '$devuelto',
                                    '$de_baja',
                                    '$fch_baja',
                                    '$nro_linea',
                                    '$pais_linea',
                                    '$tipcel',
                                    '$ubicaid',
                                    '$pin',
                                    '$puk',
                                    '$marca',
                                    '$modelo',
                                    '$nserie',
                                    '$imei',
                                    '$planc',
                                    '$nserie_modem',
                                    '$estacion_tel',
                                    '$garantia_vence',
                                    '$artplan',
                                    '$celprincipal'
                         );";
                echo $sql;
                $result = sql_do_query($sql, '3', $usu);

                if($result){

                    echo '<script>swal({
                                   title: "Buen Trabajo!",
                                   text: "Has insertado los datos!",
                                   icon: "success",
                                   dangerMode: false,
                                   buttons: {
                                       confirm: {text: "Continuar",
                                                 value: true,
                                       },
                                   },
                                  })   
                     </script>';
                }
        
    
            }
}

if($guardar_baja){

                $usuario = $usu;
                $ingreso = $hoy_completo;    

                $devuelto = Enmascarar_post('devuelto');
                $de_baja = Enmascarar_post('de_baja');
                $fch_baja = Enmascarar_post('fch_baja');
                
                $parametros['nro_art'] = $nro_art;
                $parametros['nota_devolucion'] = $nota_devolucion;
                
                articulosHistoricos($parametros, '3', $usu);

                $sql = "     UPDATE articulos_con a
                                SET a.devuelto = '$devuelto',
                                    a.de_baja = '$de_baja',
                                    a.fch_baja = '$fch_baja',
                                    a.usuario = '$usuario',
                                    a.ingreso = '$ingreso'
                              WHERE a.nro_art = '$nro_art';";
                //echo $sql;
                $result = sql_do_query($sql, '3', $usu);

                if($result){

                    echo '<script>swal({
                                   title: "Buen Trabajo!",
                                   text: "Has dado de actualizado correctamente el registro!",
                                   icon: "success",
                                   dangerMode: false,
                                   buttons: {
                                       confirm: {text: "Continuar",
                                                 value: true,
                                       },
                                   },
                                  })
                     </script>';
                }

}
?>