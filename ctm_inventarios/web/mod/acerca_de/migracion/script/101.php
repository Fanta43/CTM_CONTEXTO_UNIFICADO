<?php

    //ini_set( 'display_errors', 'on' );

    $sql="TRUNCATE TABLE inve_pc;";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);

    $sql="   SELECT id, 
                    codigopc, 
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
                    disco 
               FROM pc
           ORDER BY id;";
        echo $sql." <br />";
        $result = sql_do_query($sql,'4',$usu);
        $n=0;
        while($row=sql_do_array($result,'4',$usu)) {

            preg_match('/[a-zA-Z]+$/', strtoupper($row['CODIGOPC']), $matches);
            $ultimoTexto = $matches[0];
            $sql = "SELECT tipopcid FROM inve_tipos_pc WHERE estadoid = '1' AND UPPER(codigo) = UPPER('$ultimoTexto');";
            $row_tp = sql_do_array(sql_do_query($sql,'1',$usu));
            if($row_tp['TIPOPCID'] or $row_tp['TIPOPCID'] == 'P'){
                $tipopc = $row_tp['TIPOPCID'];
            }else{
                $tipopc = '1';
            }
            
            $sql_in = "INSERT INTO inve_pc (
                                pcid, 
                                codigopc, 
                                legajo, 
                                centrocosto, 
                                marca, 
                                tipoprocesador, 
                                tipoequipo, 
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
                                sr, 
                                oc, 
                                monto, 
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
                                disco, 
                                estadoid, 
                                auditoria_usu, 
                                auditoria_fecha
                    ) VALUES (
                                '".$row['ID']."', 
                                '".$row['CODIGOPC']."', 
                                '".$row['LEGAJO']."',
                                '".$row['CENTROCOSTO']."', 
                                '".htmlentities($row['MARCA'])."', 
                                '".$row['TIPOPROCESADOR']."', 
                                '".$tipopc."', 
                                '".$row['VELOCIDADPROCESADOR']."', 
                                '".$row['MEMORIARAM']."', 
                                '".htmlentities($row['OBSERVACIONES'])."', 
                                '".$row['IP']."', 
                                '".$row['MAC']."', 
                                '".$row['NUMEROSERIE']."', 
                                '".$row['ID_MONITORES']."', 
                                '".$row['ID_PROVEEDORES']."', 
                                '".$row['PCC']."', 
                                CONVERT(datetime, '".substr($row['FECHAVTOGARANTIA'],0,10)."', 120), 
                                '".$row['SR']."', 
                                '".$row['OC']."', 
                                '".$row['MONTO']."', 
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
                                '".$row['DISCO']."',
                                '1',
                                '".$usu."',
                                CONVERT(datetime, '".$hoy."', 120)
                    );";
            echo $sql_in." <br />";
            $prestamoid_max = $row['ID'];
            $result_1 = sql_do_query($sql_in, '1', $usu);
        }
        
    $sql="UPDATE inve_id SET id_tabla = '".($prestamoid_max + 1)."' WHERE tabla = 'INVE_PC';";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);
        