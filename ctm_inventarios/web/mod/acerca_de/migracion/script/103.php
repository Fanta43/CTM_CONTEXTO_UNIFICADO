<?php

    //ini_set( 'display_errors', 'on' );

    $sql="TRUNCATE TABLE inve_monitores;";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);

    $sql="   SELECT id, 
                    codigo, 
                    marca, 
                    numeroserie, 
                    descripcion, 
                    id_proveedores, 
                    pcc, 
                    oc, 
                    fechacompra, 
                    fechavtogarantia, 
                    baja, 
                    fechabaja, 
                    motivobaja, 
                    id_zonas_inventario 
               FROM monitores
           ORDER BY id;";
        echo $sql." <br />";
        $result = sql_do_query($sql,'4',$usu);
        $n=0;
        while($row=sql_do_array($result,'4',$usu)) {
            
            if($row['BAJA']=='1'){
                $estadoid = '0';
            }else{
                $estadoid = '1';
            }
           
            $sql = "INSERT INTO inve_monitores (
                                monitorid, 
                                codigo, 
                                marca, 
                                numeroserie, 
                                descripcion, 
                                id_proveedores, 
                                pcc, 
                                oc, 
                                fechacompra, 
                                fechavtogarantia, 
                                baja, 
                                fechabaja, 
                                motivobaja, 
                                id_zonas_inventario,
                                estadoid, 
                                auditoria_usu, 
                                auditoria_fecha
                    ) VALUES (
                                '".$row['ID']."', 
                                '".$row['CODIGO']."', 
                                '".$row['MARCA']."',  
                                '".$row['NUMEROSERIE']."',  
                                '".htmlentities($row['DESCRIPCION'])."',  
                                '".$row['ID_PROVEEDORES']."',  
                                '".$row['PCC']."',  
                                '".$row['OC']."',  
                                CONVERT(datetime, '".substr($row['FECHACOMPRA'],0,10)."', 120), 
                                CONVERT(datetime, '".substr($row['FECHAVTOGARANTIA'],0,10)."', 120), 
                                '".$row['BAJA']."',  
                                CONVERT(datetime, '".substr($row['FECHABAJA'],0,10)."', 120), 
                                '".htmlentities($row['MOTIVOBAJA'])."',  
                                '".$row['ID_ZONAS_INVENTARIO']."', 
                                '$estadoid',
                                '$usu',
                                CONVERT(datetime, '".$hoy."', 120)
                    );";
            echo $sql." <br />";
            $prestamoid_max = $row['ID'];
            $result_1 = sql_do_query($sql, '1', $usu);
        }
        
    $sql="UPDATE inve_id SET id_tabla = '".($prestamoid_max + 1)."' WHERE tabla = 'INVE_MONITORES';";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);
        