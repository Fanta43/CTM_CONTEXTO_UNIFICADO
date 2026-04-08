<?php
function consultaPrestamo($parametros, $db = '1', $usu = '999999') {
    
    $devolucion_fecha = date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d") + 90));

    $codigopc = $parametros['codigopc'];

    $prestamoid = $parametros['prestamoid'];

    $legajo = $parametros['legajo'];
    
    if($prestamoid){

        $sql_extras ="SELECT e.prestamoid,
                             e.id_aspnetuser, 
                             e.fechaprestamo, 
                             e.responsable, 
                             e.id_pc, 
                             e.id_impresoras, 
                             e.id_monitores, 
                             e.id_otros, 
                             e.dispositivo, 
                             e.observaciones, 
                             e.devuelto, 
                             e.fechadevolucion,
                             e.legajo, 
                             e.centrocosto, 
                             e.devolucion_estimada, 
                             e.estadoid, 
                             e.auditoria_usu, 
                             e.auditoria_fecha 
                        FROM more_prestamos e
                       WHERE e.prestamoid = '$prestamoid';";
        //echo $sql;
        $row_original_extras =sql_do_array(sql_do_query($sql_extras,'1',$usu));
        
         
        
    }else{

        $prestamoid = $row_original['ID'];

        $sql_prestamo_extras = "
                         SELECT pe.prestamoid,
                                pe.id_aspnetuser, 
                                pe.fechaprestamo, 
                                pe.responsable, 
                                pe.id_pc, 
                                pe.id_impresoras, 
                                pe.id_monitores, 
                                pe.id_otros, 
                                pe.dispositivo, 
                                pe.observaciones, 
                                pe.devuelto, 
                                pe.fechadevolucion,
                                pe.legajo, 
                                pe.centrocosto, 
                                pe.devolucion_estimada,
                                pe.legajo, 
                                pe.centrocosto, 
                                pe.devolucion_estimada, 
                                pe.estadoid, 
                                pe.auditoria_usu, 
                                pe.auditoria_fecha 
                           FROM more_prestamos pe
                          WHERE pe.id_pc = (SELECT pc.id FROM pc WHERE pc.codigopc = '$codigopc'); ";
        //echo $sql_id_pc;
        $row_original_extras = sql_do_array(sql_do_query($sql_prestamo_extras, '1', $usu));
          
    }

    if(!$legajo){$legajo = $row_original_extras['LEGAJO'];}
    
    if($legajo){

        $sql ="SELECT EmpLegajo, 
                      EmpNombre AS responsable,
                      CentroCosto
                 FROM autog_rhpro_ctm_plantel_vw 
                WHERE EmpLegajo = '$legajo';";
        //echo $sql;
        $row_persona = sql_do_array(sql_do_query($sql,'1',$usu));

        $row_original['RESPONSABLE'] =  $row_persona['RESPONSABLE'];  
        $row_original['CENTROCOSTO'] =  $row_persona['CENTROCOSTO'];  

    }

        return ($row_resultado);
}

?>
