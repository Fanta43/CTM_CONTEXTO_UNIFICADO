<?php
////ini_set( 'display_errors', 'on' );

/************************/
//$rowhardware = consultaOCS($codigopc, '2', $usu);
$rowhanterior = consultaInventario($codigopc, '1', $usu);

$comentario_guardar = Enmascarar_post('comentario_guardar');
$obligatorio_guardar = Enmascarar_post('obligatorio_guardar');
$obligatorio_insert = Enmascarar_post('obligatorio_insert');
$obligatorios_importar = Enmascarar_post('obligatorios_importar');
$guardar_importar = Enmascarar_post('guardar_importar');
$obligatorio_id = Enmascarar_post('obligatorio_id');

$guardar_baja_morebius  = Enmascarar_post('guardar_baja_morebius');
$nro_art  = Enmascarar_post('nro_art');
$nota_devolucion = Enmascarar_post('nota_devolucion');
$guardar_morebius  = Enmascarar_post('guardar_morebius');

$varias_si  = Enmascarar_post('varias_si');
$varias_numeros  = Enmascarar_post('varias_numeros');
if(!$varias_numeros) $varias_numeros  = '1';

if($obligatorio_insert) {
    
    $n = 0;
    while($n <= $varias_numeros){
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

        $result = insertarDatosInventario($codigopc, $parametros, '1', $usu);
        $n++;
        
    }
    
    if($result){
        echo '<script>swal({
                   title: "Buen Trabajo!",
                   text: "Has dado de alta el nuevo elemento!",
                   icon: "success",
                   dangerMode: false,
                   buttons: {
                       confirm: {text: "Continuar", value: true},
                   },
              })
              .then((value) => {
                  window.location.replace("index.php?estado=20");
              })    
             </script>';
        return 1;
    }
    
    
}

if($comentario_guardar) {
        ////ini_set( 'display_errors', 'on' );
        //echo "<h1>ENTRA<h1>";
        $parametros = [
            'legajo' => Enmascarar_post('legajo'),
            'codigopc' => Enmascarar_post('codigopc'),
            'tipo_inventarioid' => Enmascarar_post('tipo_inventarioid'),
            'tipo_registroid' => Enmascarar_post('tipo_regsinopticoid'),
            'cargocod' => Enmascarar_post('codigocargo'),
            'observaciones' => Enmascarar_post('observaciones')
        ];
        //var_dump($parametros);

    $result = registroSinopticoContinuo($parametros, '1', $usu);
        
    if($result){
        echo '<script>swal({
                   title: "Buen Trabajo!",
                   text: "Has dado de alta el nuevo comentario!",
                   icon: "success",
                   dangerMode: false,
                   buttons: {
                       confirm: {text: "Continuar", value: true},
                   },
              }) 
             </script>';
        return 1;
    }
    
    
}

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
    actualizarDatosInventario($obligatorio_id, $parametros, '1', $usu);
}


if($guardar_morebius=="Guardar") {
    
    $nro_art = Enmascarar_post('nro_art');
    
    $parametros = [
        
        'garantia_vence' => Enmascarar_post('garantia_vence'),
        'marca' => Enmascarar_post('marca'),
        'modelo' => Enmascarar_post('modelox'),
        'nserie' => Enmascarar_post('nserie'),
        'imei' => Enmascarar_post('imei'),
        'planc' => Enmascarar_post('planc'),
        'nserie_modem' => Enmascarar_post('nserie_modem'),  
        'fecha' => Enmascarar_post('fecha'),
        'tipo_art' => Enmascarar_post('tipo_art'),
        'detalle' => Enmascarar_post('detalle'), 
        'sector' => Enmascarar_post('sector'),
        'legajo_art' => Enmascarar_post('legajo_art'),
        'nota' => Enmascarar_post('nota'),
        'referencia' => Enmascarar_post('referencia'),      
        'pais_linea' => Enmascarar_post('pais_linea'),
        'ubicaid' => Enmascarar_post('ubicaid'),

    ];
    //var_dump($parametros);
    actualizarDatosMorebius($nro_art, $parametros, '3', $usu);
}

if($guardar_baja_morebius=="Guardar") {
    
    $nro_art = Enmascarar_post('nro_art');    
    $parametros = [
        
        'devuelto' => Enmascarar_post('devuelto'),
        'de_baja' => Enmascarar_post('de_baja'),
        'fch_baja' => Enmascarar_post('fch_baja'),
        'nota_devolucion' => Enmascarar_post('nota_devolucion'),

    ];
    //var_dump($parametros);
    bajaDatosMorebius($nro_art, $parametros, '3', $usu);
}

?>