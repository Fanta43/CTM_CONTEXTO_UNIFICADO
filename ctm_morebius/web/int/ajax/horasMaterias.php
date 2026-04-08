<?php
    include_once('seg_ajax.php');
    include_once('../../../conf/conf.php');
    include_once('../../../conf/funcion.php');
    ini_set( 'display_errors', 'off' );
    
    $cod_materia = Enmascarar_post('valorCaja1');
    $anio = Enmascarar_post('valorCaja2');
    

    
    try{

                                //consulta------------------
                               $sql="SELECT SUM(n.HORAS) as cant FROM vw_nombramientos n WHERE n.COD_MATERIA = '$cod_materia' AND YEAR(STR_TO_DATE(n.FECHA_CESE, '%Y-%m-%d %H:%i:%s')) = '$anio';";
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