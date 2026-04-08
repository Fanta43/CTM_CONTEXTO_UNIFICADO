<?php

    //ini_set( 'display_errors', 'on' );

    $sql="TRUNCATE TABLE inve_reparaciones;";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);

    $sql="   SELECT p.Id, 
                    p.Id_AspNetUser, 
                    p.Id_Zonas_Inventario, 
                    p.Fecha_Envio, 
                    p.Id_PC, 
                    p.Id_Impresoras, 
                    p.Id_Monitores, 
                    p.Id_Otros, 
                    p.Id_Proveedores, 
                    p.Problema, 
                    p.Observaciones,
                    p.Reparado, 
                    p.Fecha_Devolucion 
               FROM reparaciones p 
           ORDER BY p.Id;";
        echo $sql." <br />";
        $result = sql_do_query($sql,'4',$usu);
        $n=0;
        while($row=sql_do_array($result,'4',$usu)) {
            
            if($row['DEVOLUCION_ESTIMADA']){
                $devolucion_estimada = $row['DEVOLUCION_ESTIMADA'];
            }else{
                $devolucion_estimada = (new DateTime($row['FECHAPRESTAMO']))->modify('+90 days')->format('Y-m-d');
            }
            
            $sql = "INSERT INTO inve_reparaciones (
                                reparacionid, 
                                id_aspnetuser, 
                                id_zonas_inventario,
                                fecha_envio, 
                                id_pc, 
                                id_impresoras, 
                                id_monitores,
                                id_otros, 
                                id_proveedores, 
                                problema, 
                                observaciones, 
                                reparado, 
                                devolucion_estimada,
                                fecha_devolucion, 
                                estadoid, 
                                auditoria_usu, 
                                auditoria_fecha
                    ) VALUES (
                                '".$row['ID']."',
                                '".$row['ID_ASPNETUSER']."',
                                '".$row['ID_ZONAS_INVENTARIO']."',
                                CONVERT(datetime, '".substr($row['FECHA_ENVIO'],0,10)."', 120),
                                '".$row['ID_PC']."',
                                '".$row['ID_IMPRESORAS']."', 
                                '".$row['ID_MONITORES']."',
                                '".$row['ID_OTROS']."',
                                '".$row['ID_PROVEEDORES']."',
                                '".htmlentities($row['PROBLEMA'])."',
                                '".htmlentities($row['OBSERVACIONES'])."',
                                '".htmlentities($row['REPARADO'])."',
                                '".$devolucion_estimada."',
                                CONVERT(datetime, '".substr($row['FECHA_DEVOLUCION'],0,10)."', 120),
                                '1',
                                '".$usu."',
                                CONVERT(datetime, '".substr($hoy,0,10)."', 120)
                    );";
            echo $sql." <br />";
            $prestamoid_max = $row['ID'];
            $result_1 = sql_do_query($sql, '1', $usu);
        }
        
    $sql="UPDATE inve_id SET id_tabla = '".($prestamoid_max + 1)."' WHERE tabla = 'INVE_REPARACIONES';";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);
        