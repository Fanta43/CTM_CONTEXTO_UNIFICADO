<?php

    //ini_set( 'display_errors', 'on' );

    $sql="TRUNCATE TABLE inve_historicopc;";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);

    $sql="   SELECT id, 
                    codigo, 
                    marca, 
                    tipoprocesador, 
                    velocidadprocesador, 
                    memoriaram, 
                    observaciones, 
                    ip, 
                    mac,
                    numeroserie, 
                    id_monitores, 
                    id_proveedores, 
                    pcc, 
                    fechavtogarantia, 
                    oc, 
                    fechacompra, 
                    baja, 
                    fechabaja, 
                    motivobaja, 
                    id_zonas_inventario, 
                    id_sistemaoperativo, 
                    clave1, 
                    clave2, 
                    clave3, 
                    clave4, 
                    clave5, 
                    numoblea, 
                    discoscod, 
                    id_pc 
               FROM historicopc;";
        echo $sql." <br />";
        $result = sql_do_query($sql,'4',$usu);
        $n=0;
        while($row=sql_do_array($result,'4',$usu)) {
            $sql = "INSERT INTO inve_historicopc (
                                historicopcid, 
                                codigo, 
                                marca,
                                tipoprocesador, 
                                velocidadprocesador, 
                                memoriaram, 
                                observaciones, 
                                ip, 
                                mac, 
                                numeroserie, 
                                id_monitores, 
                                id_proveedores, 
                                pcc, 
                                fechavtogarantia, 
                                oc, 
                                fechacompra, 
                                baja, 
                                fechabaja, 
                                motivobaja, 
                                id_zonas_inventario, 
                                id_sistemaoperativo, 
                                clave1, 
                                clave2, 
                                clave3, 
                                clave4, 
                                clave5, 
                                numoblea, 
                                discoscod, 
                                id_pc, 
                                estadoid, 
                                auditoria_usu, 
                                auditoria_fecha
                    ) VALUES (
                                '".$row['ID']."', 
                                '".$row['CODIGO']."', 
                                '".$row['MARCA']."', 
                                '".$row['TIPOPROCESADOR']."',  
                                '".$row['VELOCIDADPROCESADOR']."',  
                                '".$row['MEMORIARAM']."',  
                                '".$row['OBSERVACIONES']."',  
                                '".$row['IP']."',  
                                '".$row['MAC']."',  
                                '".$row['NUMEROSERIE']."',  
                                '".$row['ID_MONITORES']."',  
                                '".$row['ID_PROVEEDORES']."',  
                                '".$row['PCC']."', 
                                CONVERT(datetime, '".substr($row['FECHAVTOGARANTIA'],0,10)."', 120),
                                '".$row['OC']."', 
                                CONVERT(datetime, '".substr($row['FECHACOMPRA'],0,10)."', 120),
                                '".$row['BAJA']."',
                                CONVERT(datetime, '".substr($row['FECHABAJA'],0,10)."', 120),
                                '".$row['MOTIVOBAJA']."',  
                                '".$row['ID_ZONAS_INVENTARIO']."',  
                                '".$row['ID_SISTEMAOPERATIVO']."',  
                                '".$row['CLAVE1']."',  
                                '".$row['CLAVE2']."',  
                                '".$row['CLAVE3']."',  
                                '".$row['CLAVE4']."',  
                                '".$row['CLAVE5']."',  
                                '".$row['NUMOBLEA']."',  
                                '".$row['DISCOSCOD']."',  
                                '".$row['ID_PC']."',     
                                '1',
                                '".$usu."',
                                CONVERT(datetime, '".substr($hoy,0,10)."', 120)
                    );";
            echo $sql." <br />";
            $prestamoid_max = $row['ID'];
            $result_1 = sql_do_query($sql, '1', $usu);
        }
        
    $sql="UPDATE inve_id SET id_tabla = '".($prestamoid_max + 1)."' WHERE tabla = 'INVE_HISTORICOPC';";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);