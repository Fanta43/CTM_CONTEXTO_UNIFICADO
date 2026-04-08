<?php

    //ini_set( 'display_errors', 'on' );

    $sql="DELETE FROM inve_proveedores WHERE email1 = null AND email2 = null AND email3 = null;";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);

    $sql="   SELECT id, 
                    nombre, 
                    direccion, 
                    telefono 
               FROM proveedores
           ORDER BY id;";
        echo $sql." <br />";
        $result = sql_do_query($sql,'4',$usu);
    
        $n=0;
        while($row=sql_do_array($result,'4',$usu)) {
            
            $sql="SELECT proveedorid
                    FROM inve_proveedores 
                   WHERE proveedorid = '".$row['ID']."'
                ORDER BY proveedorid;";
            echo $sql." <br />";
            $row_prueba=sql_do_array(sql_do_query($sql,'1',$usu));

            if($row_prueba['PROVEEDORID']){
                
                $sql = "UPDATE inve_proveedores SET 
                            nombre = '".htmlentities($row['NOMBRE'])."', 
                            direccion = '".htmlentities($row['DIRECCION'])."', 
                            telefono = '".$row['TELEFONO']."',
                            estadoid = '1',
                            auditoria_usu = '".$usu."',
                            auditoria_fecha = CONVERT(datetime, '".substr($hoy,0,10)."', 120)
                        WHERE proveedorid = '".$row['ID']."';";
                echo $sql." <br />";
                $prestamoid_max = $row['ID'];
                $result_1 = sql_do_query($sql, '1', $usu);

            }else{
                
                            
                $sql = "INSERT INTO inve_proveedores (
                                    proveedorid, 
                                    nombre, 
                                    direccion, 
                                    telefono, 
                                    email1, 
                                    email2, 
                                    email3, 
                                    estadoid, 
                                    auditoria_usu, 
                                    auditoria_fecha
                        ) VALUES (
                                    '".$row['ID']."', 
                                    '".htmlentities($row['NOMBRE'])."', 
                                    '".htmlentities($row['DIRECCION'])."', 
                                    '".$row['TELEFONO']."',  
                                    '".$row['EMAIL1']."', 
                                    '".$row['EMAIL2']."',  
                                    '".$row['EMAIL3']."',
                                    '1',
                                    '".$usu."',
                                    CONVERT(datetime, '".substr($hoy,0,10)."', 120)
                                        
                        );";
                echo $sql." <br />";
                $prestamoid_max = $row['ID'];
                $result_1 = sql_do_query($sql, '1', $usu);

            }    

        }
        
    $sql="UPDATE inve_id SET id_tabla = '".($prestamoid_max + 1)."' WHERE tabla = 'INVE_OTROS';";
    echo $sql." <br />";
    $result_0 = sql_do_query($sql,'1',$usu);
        