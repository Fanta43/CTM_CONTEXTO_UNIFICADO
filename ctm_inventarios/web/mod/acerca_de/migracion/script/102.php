<?php

    //ini_set( 'display_errors', 'on' );

    $sql="TRUNCATE TABLE inve_impresoras;";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);

    $sql="   SELECT id, 
                    codigo, 
                    marca, 
                    id_tipos, 
                    numeroserie, 
                    observacion, 
                    id_proveedores, 
                    fechacompra, 
                    fechavtogarantia, 
                    oc, 
                    pcc, 
                    ip, 
                    baja,
                    motivobaja, 
                    fechabaja, 
                    conidcc, 
                    id_zonas_inventario 
               FROM impresoras
           ORDER BY id;";
        echo $sql." <br />";
        $result = sql_do_query($sql,'4',$usu);
        $n=0;
        while($row=sql_do_array($result,'4',$usu)) {
            
            $sql = "INSERT INTO inve_impresoras (
                                impresoraid,
                                codigo, 
                                marca, 
                                id_tipos, 
                                numeroserie, 
                                observacion, 
                                id_proveedores, 
                                fechacompra, 
                                fechavtogarantia,
                                oc, 
                                pcc, 
                                ip, 
                                baja, 
                                motivobaja, 
                                fechabaja, 
                                conidcc, 
                                id_zonas_inventario,  
                                estadoid, 
                                auditoria_usu, 
                                auditoria_fecha
                    ) VALUES (
                                '".$row['ID']."', 
                                '".$row['CODIGO']."', 
                                '".htmlentities($row['MARCA'])."',  
                                '".$row['ID_TIPOS']."',  
                                '".$row['NUMEROSERIE']."', 
                                '".htmlentities($row['OBSERVACION'])."',  
                                '".$row['ID_PROVEEDORES']."',  
                                CONVERT(datetime, '".substr($row['FECHACOMPRA'],0,10)."', 120),
                                CONVERT(datetime, '".substr($row['FECHAVTOGARANTIA'],0,10)."', 120), 
                                '".$row['OC']."',  
                                '".$row['PCC']."',  
                                '".$row['IP']."',  
                                '".$row['BAJA']."',  
                                '".htmlentities($row['MOTIVOBAJA'])."', 
                                CONVERT(datetime, '".substr($row['FECHABAJA'],0,10)."', 120), 
                                '".$row['CONIDCC']."', 
                                '".$row['ID_ZONAS_INVENTARIO']."', 
                                '1',
                                '".$usu."',
                                CONVERT(datetime, '".substr($hoy,0,10)."', 120) 
                                
                    );";
            echo $sql." <br />";
            $prestamoid_max = $row['ID'];
            $result_1 = sql_do_query($sql, '1', $usu);
        }
        
    $sql="UPDATE inve_id SET id_tabla = '".($prestamoid_max + 1)."' WHERE tabla = 'INVE_IMPRESORAS';";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);
        