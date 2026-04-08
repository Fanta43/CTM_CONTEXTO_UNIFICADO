<?php

    ini_set( 'display_errors', 'off' );

    include_once('seg_ajax.php');
    include_once('../../../conf/conf.php');
    include_once('../../../conf/funcion.php');
    

    
    $gastoid = Enmascarar_post('gastoid');
    $usuarioid = Enmascarar_post('usuarioid');
    
    try{

           if (isset($gastoid) && isset($usuarioid)) {
                $sql="DELETE FROM gast_inversiones WHERE gastoid = '$gastoid' AND usuarioid = '$usuarioid';";
                $result=sql_do_query($sql,'1',$usu);
            }
        
    }catch (Exception $e){
        
        $e->getMessage();
        echo $e;
    }
    
?>