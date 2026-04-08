<?php

    ini_set( 'display_errors', 'off' );

    include_once('seg_ajax.php');
    include_once('../../../conf/conf.php');
    include_once('../../../conf/funcion.php');
    

    
    $alertaid = Enmascarar_post('valorCaja1');
    $usuarioid = Enmascarar_post('valorCaja2');
    $tipo_alertaid = Enmascarar_post('valorCaja3');
    $tipo_mensajeid = Enmascarar_post('valorCaja4');
    
    try{

           $sql2="SELECT p.usuarioid, 
                         p.nombredeusuario, 
                         p.apellido, 
                         p.nombres,
                         p.celular,
                         p.email 
                    FROM vw_personas p
                   WHERE p.usuarioid = '$usuarioid';";

            $rowusu=sql_do_array(sql_do_query($sql2,'1',$usu));
            

                switch ($tipo_mensajeid)
                {
                    
                 case '3':
                            //$tipo_mensajeid = '3';

                            $id_msg_ctrl = get_id_msg_ctrl($tipo_mensajeid, $alertaid, $usuarioid, $usu, '1');

                            $para = "?id=".$id_msg_ctrl."&msj=".$alertaid;

                            $mensajews = str_replace('<credenciales\>', $para, $mensaje_alert);

                            $resWhatsApp = padeEnviarWhatsApp($rowusu['CELULAR'],$mensajews);

                            $msgWhatsApp = alta_msg($id_msg_ctrl, $tipo_alertaid, '3', $resWhatsApp, $alertaid, $usuarioid, $usu, $hoy, '1', $sistema);
                            
                            if($resWhatsApp == 1) {echo "Ok";}else{echo "Error";}
                 break;
                    
                 case '2':
                            //$tipo_mensajeid = '2';

                            $id_msg_ctrl = get_id_msg_ctrl($tipo_mensajeid, $alertaid, $usuarioid, $usu, '1');

                            $para = "?id=".$id_msg_ctrl."&msj=".$alertaid;

                            $mensajesms = str_replace('<credenciales\>', $para, $mensaje_alert);

                            $resSMS = padeEnviarSMS($rowusu['CELULAR'],$mensajesms);

                            $msgSMS = alta_msg($id_msg_ctrl, $tipo_alertaid, '2', $resSMS, $alertaid, $usuarioid, $usu, $hoy, '1', $sistema);
                            
                            if($resSMS == 1) {echo "Ok";}else{echo "Error";}
                            
                            
                 break;
                 case '1':
                            //$tipo_mensajeid = '1';

                            $id_msg_ctrl = get_id_msg_ctrl($tipo_mensajeid, $alertaid, $usuarioid, $usu, '1');

                            $para = "?id=".$id_msg_ctrl."&msj=".$alertaid;

                            $mensajeemail = str_replace('<credenciales\>', $para, $mensaje_alert);

                            $resEmail = padeEnviarEmail($rowusu['EMAIL'],$mensajeemail);

                            $msgEmail = alta_msg($id_msg_ctrl, $tipo_alertaid, '1', $resEmail, $alertaid, $usuarioid, $usu, $hoy, '1', $sistema);
                            
                            if($resEmail == 1) {echo "Ok";}else{echo "Error";}

                 break;
                 default:
                            //$tipo_mensajeid = '1';

                            $id_msg_ctrl = get_id_msg_ctrl($tipo_mensajeid, $alertaid, $usuarioid, $usu, '1');

                            $para = "?id=".$id_msg_ctrl."&msj=".$alertaid;

                            $mensajeemail = str_replace('<credenciales\>', $para, $mensaje_alert);

                            $resEmail = padeEnviarEmail($rowusu['EMAIL'],$mensajeemail);

                            $msgEmail = alta_msg($id_msg_ctrl, $tipo_alertaid, '1', $resEmail, $alertaid, $usuarioid, $usu, $hoy, '1', $sistema);
                            
                            if($resEmail == 1) {echo "Ok";}else{echo "Error";}
                 }
        
    }catch (Exception $e){
        
        $e->getMessage();
        echo $e;
    }
    
    ?>