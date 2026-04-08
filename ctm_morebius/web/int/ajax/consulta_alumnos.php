<?php

    ini_set( 'display_errors', 'off' );

    include_once('seg_ajax.php');
    include_once('../../../conf/conf.php');
    include_once('../../../conf/funcion.php');
    

    
    $limit = isset($_REQUEST['length']) ? intval($_REQUEST['length']) : 10;
    $start = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;
    $search = isset($_REQUEST['search']['value']) ? $_REQUEST['search']['value'] : "";

    
    try{

           $query = "SELECT a.DNI, 
                            MAX(a.APELLIDO) AS apellido, 
                            MAX(a.NOMBRES) AS nombres, 
                            SUM(a.CARGA_HORARIA) AS suma_carga, 
                            COUNT(a.CERTIFICADO) AS certificado 
                       FROM vw_vistageneral a ";

                if ($search) {
                    // Implementa tu lógica de búsqueda aquí
                    $query .= "WHERE a.APELLIDO LIKE '%".$search."%' ";
                    // Agrega más condiciones si es necesario
                }

                $query .= "GROUP BY a.DNI ";
                $query .= "ORDER BY a.APELLIDO, a.NOMBRES LIMIT ".$start.", ".$limit;
                
                $result = sql_do_query($query,'2',$usu);
                $n = 0;
                
                while ($row = sql_do_array($result)) {
                    $n++;
                       $data[] = [
                            $row['DNI'],
                            $row['APELLIDO'],
                            $row['NOMBRES'],
                            $row['SUMA_CARGA'],
                            $row['CERTIFICADO']
                       ];

                }
                
            $sql2="SELECT COUNT(*) AS cant FROM vw_vistageneral GROUP BY DNI ;";
            $rowtot=sql_do_array(sql_do_query($sql2,'1',$usu));
            
            $json_data = [
                "draw" => intval($_REQUEST['draw']),
                "recordsTotal" => intval($rowtot['CANT']),
                "recordsFiltered" => intval($n),
                "data" => $data
            ];
            
    header("Content-Type: application/json");
    echo json_encode($json_data);
        
    }catch (Exception $e){
        
        $e->getMessage();
        echo $e;
    }
    
    ?>