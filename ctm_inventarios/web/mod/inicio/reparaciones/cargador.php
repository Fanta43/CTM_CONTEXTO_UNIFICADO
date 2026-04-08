<?php
function consultaReparacion($parametros, $db = '1', $usu = '999999') {
    
    $devolucion_fecha = date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d") + 90));

    $codigopc = $parametros['codigopc'];

    $reparacionid = $parametros['reparacionid'];
    
    if($reparacionid){
        
        $sql_extras ="SELECT e.reparacionid, 
                             e.id_aspnetuser, 
                             e.id_zonas_inventario, 
                             e.fecha_envio, 
                             e.id_pc, 
                             e.id_impresoras, 
                             e.id_monitores, 
                             e.id_otros, 
                             e.id_proveedores, 
                             e.problema, 
                             e.observaciones, 
                             e.reparado, 
                             e.devolucion_estimada, 
                             e.fecha_devolucion, 
                             e.estadoid, 
                             e.auditoria_usu, 
                             e.auditoria_fecha 
                        FROM inve_reparaciones e
                       WHERE e.reparacionid = '$reparacionid';";
        //echo $sql;
        $row_original_extras =sql_do_array(sql_do_query($sql_extras,'1',$usu));
        
    }else{

        $sql_prestamo_extras = "
                         SELECT e.reparacionid, 
                                e.id_aspnetuser, 
                                e.id_zonas_inventario, 
                                e.fecha_envio, 
                                e.id_pc, 
                                e.id_impresoras, 
                                e.id_monitores, 
                                e.id_otros, 
                                e.id_proveedores, 
                                e.problema, 
                                e.observaciones, 
                                e.reparado, 
                                e.devolucion_estimada, 
                                e.fecha_devolucion, 
                                e.estadoid, 
                                e.auditoria_usu, 
                                e.auditoria_fecha 
                           FROM inve_reparaciones e
                          WHERE e.id_pc = (SELECT pc.id FROM pc WHERE pc.codigopc = '$codigopc'); ";
        //echo $sql_id_pc;
        $row_original_extras = sql_do_array(sql_do_query($sql_prestamo_extras, '1', $usu));
    }

    return ($row_original_extras);
}

?>

?>
