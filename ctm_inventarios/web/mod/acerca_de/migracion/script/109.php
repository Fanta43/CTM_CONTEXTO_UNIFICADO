<?php

    //ini_set( 'display_errors', 'on' );

    $sql="TRUNCATE TABLE inve_sistemaoperativo;";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);

    $sql="   SELECT id, 
                    tipo 
               FROM tipo;";
        echo $sql." <br />";
        $result = sql_do_query($sql,'4',$usu);
        $n=0;
        while($row=sql_do_array($result,'4',$usu)) {
            $sql = "INSERT INTO inve_tipos_impresoras (
                                tipo_impresoraid, 
                                tipo, 
                                estadoid, 
                                auditoria_usu, 
                                auditoria_fecha
                    ) VALUES (
                                '".$row['ID']."', 
                                '".$row['TIPO']."', 
                                '1',
                                '".$usu."',
                                CONVERT(datetime, '".substr($hoy,0,10)."', 120)
                    );";
            echo $sql." <br />";
            $prestamoid_max = $row['ID'];
            $result_1 = sql_do_query($sql, '1', $usu);
        }
        
    $sql="UPDATE inve_id SET id_tabla = '".($prestamoid_max + 1)."' WHERE tabla = 'INVE_TIPOS_IMPRESORAS';";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);