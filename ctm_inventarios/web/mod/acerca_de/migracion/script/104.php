<?php

    //ini_set( 'display_errors', 'on' );

    $sql="TRUNCATE TABLE inve_otros;";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);

    $sql="   SELECT id, 
                    codigo, 
                    marca, 
                    observacion, 
                    id_proveedores, 
                    numeroserie, 
                    pcc, 
                    oc, 
                    fechavtogarantia, 
                    fechacompra, 
                    baja, 
                    fechabaja, 
                    motivobaja, 
                    id_zonas_inventario, 
                    descripcion 
               FROM otros
           ORDER BY id;";
        echo $sql." <br />";
        $result = sql_do_query($sql,'4',$usu);
        $n=0;
        while($row=sql_do_array($result,'4',$usu)) {
            
            /*
            if($row['FECHABAJA']){
                //$fb = "CONVERT(datetime, '".substr($row['FECHABAJA'],0,10)."', 120)";
            }else{
                $fb = 'NULL';
            }
             * 
             */
            
            $sql = "INSERT INTO inve_otros (
                                otroid, 
                                codigo, 
                                marca, 
                                observacion, 
                                id_proveedores, 
                                numeroserie, 
                                pcc, 
                                oc, 
                                fechavtogarantia, 
                                fechacompra, 
                                baja, 
                                fechabaja, 
                                motivobaja, 
                                id_zonas_inventario, 
                                descripcion,
                                estadoid, 
                                auditoria_usu, 
                                auditoria_fecha
                    ) VALUES (
                                '".$row['ID']."', 
                                '".$row['CODIGO']."', 
                                '".htmlentities($row['MARCA'])."',  
                                '".htmlentities($row['OBSERVACION'])."', 
                                '".$row['ID_PROVEEDORES']."',  
                                '".$row['NUMEROSERIE']."',  
                                '".$row['PCC']."',  
                                '".$row['OC']."',
                                CONVERT(datetime, '".substr($row['FECHAVTOGARANTIA'],0,10)."', 120),
                                CONVERT(datetime, '".substr($row['FECHACOMPRA'],0,10)."', 120), 
                                '".$row['BAJA']."',
                                CONVERT(datetime, '".substr($row['FECHABAJA'],0,10)."', 120), 
                                '".htmlentities($row['MOTIVOBAJA'])."', 
                                '".$row['ID_ZONAS_INVENTARIO']."', 
                                '".htmlentities($row['DESCRIPCION'])."', 
                                '1',
                                '".$usu."',
                                CONVERT(datetime, '".substr($hoy,0,10)."', 120)
                    );";
            echo $sql." <br />";
            $prestamoid_max = $row['ID'];
            $result_1 = sql_do_query($sql, '1', $usu);
        }
        
    $sql="UPDATE inve_id SET id_tabla = '".($prestamoid_max + 1)."' WHERE tabla = 'INVE_OTROS';";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);
        