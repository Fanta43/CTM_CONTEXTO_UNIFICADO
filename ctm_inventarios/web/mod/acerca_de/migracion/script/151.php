<?php

    //ini_set( 'display_errors', 'on' );

    $sql="TRUNCATE TABLE inve_prestamos;";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);

    $sql="   SELECT p.id, 
                    p.id_aspnetuser, 
                    p.fechaprestamo, 
                    p.responsable, 
                    p.id_pc, 
                    p.id_impresoras, 
                    p.id_monitores, 
                    p.id_otros, 
                    p.dispositivo, 
                    p.observaciones, 
                    p.devuelto, 
                    p.fechadevolucion 
               FROM prestamos p 
           ORDER BY p.id;";
        echo $sql." <br />";
        $result = sql_do_query($sql,'4',$usu);
        $n=0;
        while($row=sql_do_array($result,'4',$usu)) {
            
            if($row['DEVOLUCION_ESTIMADA']){
                $devolucion_estimada = $row['DEVOLUCION_ESTIMADA'];
            }else{
                $devolucion_estimada = (new DateTime($row['FECHAPRESTAMO']))->modify('+90 days')->format('Y-m-d');
            }
            
            
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
                    ) VALUES (
                                '".$row['ID']."',
                                '".$row['ID_ASPNETUSER']."',
                                CONVERT(datetime, '".substr($row['FECHAPRESTAMO'],0,10)."', 120),
                                '".htmlentities($row['RESPONSABLE'])."',
                                '".$row['ID_PC']."', 
                                '".$row['ID_IMPRESORAS']."',
                                '".$row['ID_MONITORES']."', 
                                '".$row['ID_OTROS']."',
                                '".htmlentities($row['DISPOSITIVO'])."',
                                '".htmlentities($row['OBSERVACIONES'])."',
                                '".$row['DEVUELTO']."',
                                CONVERT(datetime, '".substr($row['FECHADEVOLUCION'],0,10)."', 120),
                                '".$row['LEGAJO']."', 
                                '".$row['CENTROCOSTO']."',
                                CONVERT(datetime, '".substr($devolucion_estimada,0,10)."', 120),
                                '1',
                                '".$usu."',
                                CONVERT(datetime, '".substr($hoy,0,10)."', 120)
                    );";
            echo $sql." <br />";
            $prestamoid_max = $row['ID'];
            $result_1 = sql_do_query($sql, '1', $usu);
        }
        
    $sql="UPDATE inve_id SET id_tabla = '".($prestamoid_max + 1)."' WHERE tabla = 'INVE_PRESTAMOS';";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);
        