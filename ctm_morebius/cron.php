<?php
    ini_set( 'display_errors', 'off' );
    include_once('conf/funcion.php');
    $sistema='CTM_055';
    date_default_timezone_set("America/Argentina/Buenos_Aires");
       
    $estado = substr(Enmascarar_get('p1'), 0, 3);
    if(!$estado) $estado = $argv[1];
    $usu = substr(Enmascarar_get('p2'), 0, 8);
    if(!$usu) $usu = $argv[2];
    $destino = substr(Enmascarar_get('p3'), 0, 3);
    if(!$destino) $destino = $argv[3];
    $pass = substr(Enmascarar_get('p4'), 0, 10);
    if(!$pass) $pass = $argv[4];
    $contab = substr(Enmascarar_get('p5'), 0, 10);
    if(!$contab) $contab = $argv[5];
    
    
    
    
    
    
    $hoy = date("Y-m-d H:i:s");
    $ayer = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m")  , date("d")-1));
    
    //http://localhost/ctm/ctm_administrativo/ctm_consola/cron.php?p1=11&p2=99999999&p3=100&p4=1A2b3c4d
    //http://localhost/ctm/ctm_administrativo/ctm_consola/cron.php?p1=12&p2=99999999&p3=100&p4=1A2b3c4d
    //http://localhost/ctm/ctm_administrativo/ctm_consola/cron.php?p1=13&p2=99999999&p3=100&p4=1A2b3c4d&p5=7
  

    function validacion_usuario_cron($parametros){
    
    // pkg_adm.ADM_OUT(u.password) as password,
	
     $sql= "SELECT u.usuarioid,
                   u.nombredeusuario,
                   u.contrasenia as password,
                   u.ndoc,
                   u.organizacionid,
                   sp.nro_priv AS nivacc,
                   z.cuatrigrama AS destino
              FROM vw_personas u,
                   org_organizaciones z,
                   usu_user_sist us,
                   usu_sist_privilegio sp,
                   usu_sistemas s
             WHERE u.organizacionid = z.organizacionid  
               AND u.usuarioid = us.usuarioid
               AND us.codigo_sistema = s.codigo
               AND us.priv_sist_id = sp.priv_sist_id
               AND s.codigo = '".$parametros['sistema']."'
               AND u.nombredeusuario = '".$parametros['usuario']."';" ;
            //echo $sql;
            $dbusuario = sql_do_array(sql_do_query($sql,'1',$parametros['usuario']));
            
            /*
                    $dbusuario['USUARIOID'] = $regusuario['USUARIOID'];
                    $dbusuario['NOMBREDEUSUARIO'] = $regusuario['NOMBREDEUSUARIO'];
                    $dbusuario['NDOC'] = $regusuario['NDOC'];
                    $dbusuario['PASSWORD'] = $regusuario['PASSWORD'];
                    $dbusuario['NIVACC'] = $regusuario['NIVACC'];
                    $dbusuario['ORGANIZACIONID'] = $regusuario['ORGANIZACIONID'];
             * */
             

            if(!$dbusuario['NOMBREDEUSUARIO']){
                   $resultado['error'] = "Error_Usuario";
            }else{
                if($dbusuario['PASSWORD'] != $parametros['password']){
                	$resultado['error'] = "Error_Password";
                }
            }               
           
            //var_dump($resultado);
            
            if(!$resultado['error']){
                $resultado = Array
                    (
                     'usuario' => $dbusuario['USUARIOID'],
                     'destino' => $dbusuario['ORGANIZACIONID'],
                     'nivacc'  => $dbusuario['NIVACC']
                    );
            } else {
                
                echo "<script>
                        alert('Error de Acceso');
                      </script>";
                
            }
            
	$res = $resultado;

    return $res;
    
    }
    
    if($estado and $usu and $destino and $pass){
        
            $parametros = Array
            (
                'usuario'   => $usu,
                'sistema'   => $sistema,
                'password'  => $pass
            );
    
            $res = validacion_usuario_cron($parametros);
            
            //var_dump($res);
            

            if($res['nivacc']=='99'){
                $sql3="select * from more_cron where id='".$estado."';";
                //echo $sql3;
                $result3 = sql_do_query($sql3,'0',$usu);
                $row3=sql_do_array($result3);
                $direcion=$row3['DIRECCION'];
                //var_dump($contab);
                include_once "$direcion";
            }else{
                echo "Error: Nivel Acceso";
            }
    }else{
        echo "Error: Parametros";
    }

?>
