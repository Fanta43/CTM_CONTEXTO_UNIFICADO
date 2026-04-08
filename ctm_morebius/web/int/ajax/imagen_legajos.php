<?php
    include_once('seg_ajax.php');
    include_once('../../../conf/conf.php');
    include_once('../../../conf/funcion.php');
    ini_set( 'display_errors', 'off' );
    
    $cod_curso = Enmascarar_post('valorCaja1');
    $anio = Enmascarar_post('valorCaja2');
    $anio_1 = Enmascarar_post('valorCaja2')-1;
    $anio_2 = Enmascarar_post('valorCaja2')-2;
    
    try{

                                //consulta------------------
                               $sql="SELECT COUNT(n.NRO_LEGAJO) AS cant FROM vw_vistageneral n WHERE n.COD_CURSO = '$cod_curso' AND YEAR(STR_TO_DATE(n.FECHA_FIN, '%Y-%m-%d %H:%i:%s')) IN ('$anio', '$anio_1', '$anio_2');";
                                //------------------

                                //ejecucion----------------
                                //echo $sql;
		    		$consulta2 = sql_do_query($sql,'1',$usu);
                                $rowi=sql_do_array($consulta2);
		    		//var_dump($consulta);
		    		//----------------
                                /*
                                $x = null;
                                $str_item = '';
                                while($rowi=sql_do_array($consulta2)){
                                    if($x){$str_item .= ' <strong>X</strong> ';}
                                    $str_item .=  $rowi['NOMBRE'];
                                  $x++;  
                                }
                                */
                                if(intval($rowi['CANT'])==0){
                                    echo '';
                                }else{
                                    echo intval($rowi['CANT']);
                                }
        
    }catch (Exception $e){
        
        $e->getMessage();
        echo $e;
    }
    ?>