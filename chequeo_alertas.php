<?php
session_start();
ini_set( 'display_errors', 'on' );
include_once('ctm_entorno/conf/conf.php');
include_once('ctm_entorno/conf/funcion.php');
$hoy = date("Y-m-d H:i:s");

$id = sani_input(substr($_GET['id'], 0, 15));
if(!$id) $id = sani_input(substr($_POST['id'], 0, 15));

$msj = sani_input(substr($_GET['msj'], 0, 15));
if(!$msj) $msj = sani_input(substr($_POST['msj'], 0, 15));

$fecha_nacimiento = sani_input(substr($_POST['fecha_nacimiento'], 0, 8));
if(!$fecha_nacimiento) $fecha_nacimiento = sani_input(substr($_GET['fecha_nacimiento'], 0, 8));

$captcha = sani_input(substr($_POST['captcha'], 0, 6));
if(!$captcha) $captcha = sani_input(substr($_GET['captcha'], 0, 6));

?>

        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//ES" "http://www.w3.org/TR/html4/loose.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <title>VALIDACI&Oacute;N PADE</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv = "Content-Type" content = "text / html; charset = ISO-8859-1" />
        <link rel="shortcut icon" type="image/x-icon" href="ctm_entorno/img/ctm_favicon.ico"/>
        <script language='javascript' src="ctm_entorno/conf/sweetalert.min.js"></script>
        <!--
        <link href="ctm_entorno/conf/estilos.css" rel="stylesheet" media="screen">
        <link rel="stylesheet" href="ctm_entorno/conf/css/login.css">
        -->    
                    <style type="text/css">
                    .html,body{
                        /* Para que funcione correctamente en Smartphones y Tablets */
                         height:100vh;
                    }
                    body {
                         /* Ruta relativa o completa a la imagen */
                         background-image: url(ctm_entorno/img/fragmentos/fondo_login.jpg);
                         /* Centramos el fondo horizontal y verticalmente */
                         background-position: center center;
                         /* El fonde no se repite */
                         background-repeat: no-repeat;
                         /* Fijamos la imagen a la ventana para que no supere el alto de la ventana */
                         background-attachment: fixed;
                         /* El fonde se re-escala automáticamente */
                         background-size: cover;
                         /* Color de fondo si la imagen no se encuentra o mientras se está cargando */
                         background-color: #3c8dbc;
                         /* Fuente para el tex
                         to */
                         text-align: center;
                         color: #000;

                         font-family: "Times New Roman", Times, serif;
                    } 
                
                
                    div#contenedor_mensaje {
                        margin-top:0px;
                        width: 390px;
                        background: black;
                        color: #666;
                        margin: auto;
                        box-sizing: border-box;
                        padding: 30px 30px;
                        border-radius: 10px;
                        /*top: 50%;
                        left: 50%;
                        position: absolute;
                        transform: translate(-50%, -50%);
                        
                        /*background: rgba(255,255,255,0.3);*/
                        /*background: rgba(0,0,255,0.1);*/
                        background: rgba(255,255,255);
                    }

                    div#Titulo {
                        margin: auto;
                        margin-top: 0px;
                        width: 100%;
                        height: 50px;
                        color: #666;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        font: 30px "Open Sans", sans-serif;
                        font-weight: bold;
                    }

                    div#Tipos {
                        margin: auto;
                        /*margin-top: 30px;*/
                        width: 100%;
                        height: 80px;
                        border: 1px solid #e30613;
                        background-color: white;
                        color: black;
                        display: flex;
                        justify-content: left;
                        align-items: center;
                        border-radius: 03px;
                        font: 19px "Open Sans", sans-serif;
                        background: rgba(255,255,255,255);
                    }

                    div#Texto_Consejo {
                        margin: auto;
                        margin-top: 12px;
                        width: 100%;
                        height: 50px;
                        background-color: white;
                        /*border: 1px solid #666;*/
                        color: black;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        border-radius: 03px;
                        font: 19px "Open Sans", sans-serif;
                        background: rgba(255,255,255,255);
                    }

                    div#Alerta_descripcion {
                        margin: auto;
                        margin-top: 12px;
                        width: 100%;
                        /*height: 50px;*/
                        background-color: white;
                        border: 1px solid #e30613;
                        color: black;
                        text-align:left;
                        /*display: flex;*/
                        /*justify-content: center;*/
                        /*align-items: center;*/
                        border-radius: 03px;
                        font: 19px "Open Sans", sans-serif;
                        background: rgba(255,255,255,255);
                    }
                    
                    input#salir {
                        margin: auto;
                        margin-top: 12px;
                        width: 100%;
                        height: 40px;
                        color: white;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        border-radius: 03px;
                        font: 18px "Open Sans", sans-serif;
                        font-weight: bold;
                        border-style: none;
                        background: #153a67;
                    }
                    
                   div#logo{
                       height: 225px;
                       top: 25% ;
                       left: 50%;
                       position: absolute;
                       transform: translate(-50%, -50%);
                       box-sizing: border-box;
                       padding: 30px 30px;
                       border-radius: 07%;
                       z-index: 90;
                   }



                   div#contenedor {
                       margin-top:0px;
                       width: 390px;
                       height: 450px;
                       background: black;
                       color: #666;
                       top: 50%;
                       left: 50%;
                       position: absolute;
                       transform: translate(-50%, -50%);
                       box-sizing: border-box;
                       padding: 30px 30px;
                       border-radius: 02%;
                       /*background: rgba(255,255,255,0.3);*/
                       /*background: rgba(0,0,255,0.1);*/
                       background: rgba(255,255,255);


                   }
                    
                    div#Direccion {
                        margin: auto;
                        margin-top: 0px;
                        width: 100%;
                        height: 80px;
                        color: #666;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        font: 30px "Open Sans", sans-serif;
                        font-weight: bold;
                    }

                    div#Usuario {
                        margin: auto;
                        margin-top: 30px;
                        width: 100%;
                        height: 50px;
                        border: 1px solid #666;
                        background-color: white;
                        color: black;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        border-radius: 03px;
                        font: 19px "Open Sans", sans-serif;
                        background: rgba(255,255,255,255);
                    }

                    div#Fecha_nacimiento {
                        margin: auto;
                        margin-top: 30px;
                        width: 100%;
                        height: 50px;
                        border: 1px solid #666;
                        background-color: white;
                        color: black;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        border-radius: 03px;
                        font: 19px "Open Sans", sans-serif;
                        background: rgba(255,255,255,255);
                    }



                    div#Contrasena {
                        margin: auto;
                        margin-top: 12px;
                        width: 100%;
                        height: 50px;
                        background-color: white;
                        border: 1px solid #666;
                        color: black;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        border-radius: 03px;
                        font: 19px "Open Sans", sans-serif;
                        background: rgba(255,255,255,255);
                    }

                    div#Capcha {
                        margin: auto;
                        margin-top: 12px;
                        width: 100%;
                        height: 50px;
                        background-color: white;
                        border: 1px solid #666;
                        color: black;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        border-radius: 03px;
                        font: 19px "Open Sans", sans-serif;
                        background: rgba(255,255,255,255);
                    }

                    input#ingresar {
                        margin: auto;
                        margin-top: 12px;
                        width: 100%;
                        height: 40px;
                        color: white;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        border-radius: 03px;
                        font: 18px "Open Sans", sans-serif;
                        font-weight: bold;
                        border-style: none;
                        background: #3c8dbc;
                    }

                    div#Olvide {
                        margin: auto;
                        margin-top: 12px;
                        width: 100%;
                        height: 40px;
                        color: white;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        border-radius: 03px;
                        background: #3c8dbc;
                        font: 15px "Open Sans", sans-serif;
                    }


                    input#fecha_nacimiento {
                        margin: auto;
                        font: 17px "Open Sans", sans-serif;
                        text-align: center;
                        border: 0;
                        background: white;
                    }

                    input#usuario {
                        margin: auto;
                        font: 17px "Open Sans", sans-serif;
                        text-align: center;
                        border: 0;
                        background: white;
                    }

                    input#password {
                        margin: auto;
                        font: 17px "Open Sans", sans-serif;
                        text-align: center;
                        border: 0;
                    }

                    input#captcha {
                        margin: auto;
                        font: 17px "Open Sans", sans-serif;
                        text-align: center;
                        border: 0;
                    }

                    input#id {
                        margin: auto;
                        font: 17px "Open Sans", sans-serif;
                        text-align: center;
                        border: 0;
                        background: white;
                    }

                    input#msj {
                        margin: auto;
                        font: 17px "Open Sans", sans-serif;
                        text-align: center;
                        border: 0;
                        background: white;
                    }
                    
            </style>
        </head>
<body>



<?php

$salir = sani_input(substr($_POST['salir'], 0, 6));
if($salir){
    
                                    echo '<script>swal({
                                           title: "Gracias!",
                                           text: "Usted se ha notificado del contenido de la Alerta!",
                                           icon: "success",
                                           dangerMode: false,
                                           buttons: {
                                               confirm: {text: "Continuar",
                                                         value: true,
                                               },
                                           },
                                          })
                                           .then((value) => {
                                                window.location.replace("https://www.saltogrande.org/");
                                               })    
                                     </script>';
    /*
    echo "<script>
                        alert('Muchas Gracias');
                        window.location='https://www.saltogrande.org/';
          </script>";*/

}



if(!$captcha && !$fecha_macimiento){
?>
            <div class="text-homemain text-light text-center" style="margin-left: 0px; margin-right: -50px;">
                            <form id="form1" name="form1" method="post" action="chequeo_alertas.php">
                                    <div id="contenedor">

                                        <div id="Direccion">Validaci&oacute;n de Alarmas</div>
                                        <div id="Contrasenia">Ingrese su fecha de nacimiento</div>
                                        <div id="Fecha_nacimiento"> 
                                            <table width="80%" border="0">
                                                <tr>
                                                    <td width="25"><img src="ctm_entorno/img/usuario.png" style="width: 25px; height: 30px"/></td>
                                                    <td align="center"><input type="text" id="fecha_nacimiento" name="fecha_nacimiento" placeholder="DDMMAAAA"/></td>
                                                    <td width="25"></td>
                                                </tr>
                                            </table>
                                        </div>
                                    <div id="Capcha"> 
                                        <table width="80%" border="0">
                                            <tr>
                                                <td width="100"><img src="ctm_entorno/conf/captcha/captcha.php"/></td>
                                                <td><input type="text" id="captcha" name="captcha" placeholder="Captcha"/></td>
                                            </tr>  
                                        </table>
                                    </div>
                                        <input  id="id" name="id" type="hidden" value="<?php echo $id?>">  
                                        <input  id="msj" name="msj" type="hidden" value="<?php echo $msj?>">    
                                        <input class="btn btn-pna btn-block" id="ingresar" name="ingresar" type="submit" value="Ingresar">
                                    </div>
                            </form>
            </div>
                               
<?php
}else{
            //echo $captcha." == ".$_SESSION['cap_code'];
            //$capcha_post = Enmascarar_post('captcha');
            $capcha_sesi = $_SESSION['cap_code'];
            if ($captcha == $capcha_sesi) {
    
                    $parametros = Array
                    (
                        'id_msg_ctrl'   => $id,
                        'fecha_nacimiento'   => $fecha_nacimiento,
                        'alertaid'  => $msj
                    );

                    //var_dump($parametros);
                    
                    $res = validacion_chequeo($parametros);

                    //var_dump($res);
                    
                    if($res['usuario']){
                            
                            crtl_msg($id, $msj, $usu, $hoy, '1');
                            
                            $sql="SELECT g.alertaid, 
                                         g.tipo_alertaid,
                                         ta.descripcion AS tipo,
                                         g.caudalid,
                                         c.descripcion AS caudal,
                                         g.descripcion,
                                         g.envia_fecha,
                                         g.estadoid, 
                                         g.auditoria_usu, 
                                         g.auditoria_fecha 
                                    FROM pade_alertas g,
                                         pade_tipos_alertas ta,
                                         pade_caudales c
                                   WHERE g.tipo_alertaid = ta.tipo_alertaid
                                     AND g.caudalid = c.caudalid
                                     AND g.alertaid = '$msj';";
                            //echo $sql;
                            $rowalert=sql_do_array(sql_do_query($sql,'1',$usu));
                             
                            
                            
                            
?>                            
  
        
            <br />
            <div class="text-homemain text-light text-center" style="margin-left: 0px; margin-right: -50px;">
                            <form id="form1" name="form1" method="post" action="chequeo_alertas.php">
                                    <div id="contenedor_mensaje">
                                        <div id="Titulo">Mensaje de Alarmas</div>
                                        <div id="Texto_Consejo">Lea con detenimiento</div>
                                        <div id="Tipos"> 
                                            <table width="100%" border="0">
                                                <tr>
                                                    <td width="25"></td>
                                                    <td align="left">Emisión: <strong><?php echo $rowalert['ENVIA_FECHA']; ?></strong></td>
                                                    <td width="25"></td>
                                                </tr>
                                                <tr>
                                                    <td width="25"></td>
                                                    <td align="left">Tipo: <strong><?php echo $rowalert['TIPO']; ?></strong></td>
                                                    <td width="25"></td>
                                                </tr>
                                                <tr>
                                                    <td width="25"></td>
                                                    <td align="left">Caudal: <strong><?php echo $rowalert['CAUDAL']; ?></strong></td>
                                                    <td width="25"></td>
                                                </tr>
                                            </table>
                                        </div>
                                    <div id="Alerta_descripcion">
                                        <table width="100%" border="0" cellpadding='5' cellspacing='5'>
                                                <tr>
                                                    <td><?php echo html_entity_decode($rowalert['DESCRIPCION'], ENT_HTML401, 'ISO-8859-1'); ?></td>
                                                </tr>
                                        </table>
                                        
                                        
                                    </div>
                                        <input  id="id" name="id" type="hidden" value="<?php echo $id?>">  
                                        <input  id="msj" name="msj" type="hidden" value="<?php echo $msj?>">    
                                        <input class="btn btn-pna btn-block" id="salir" name="salir" type="submit" value="Salir">
                                    </div>
                            </form>
            </div>
        <br />

<?php

                    }else{
                        
                                echo '<script>swal({
                                           title: "Algo está mal!",
                                           text: "!La consulta de usuario no entrega ningun resultado!",
                                           icon: "error",
                                           dangerMode: true,
                                           buttons: {
                                               confirm: {text: "Continuar",
                                                         value: true,
                                               },
                                           },
                                          })
                                           .then((value) => {
                                                window.location.replace("'.$servidor.'chequeo_alertas.php?id='.$id.'&msj='.$msj.'");
                                            })
                                      </script>';
                       /*
                        echo '<script type="text/javascript">
                                        alert("Error de acceso.");
                                        window.location = "'.$servidor.'chequeo_alertas.php?id='.$id.'&msj='.$msj.'";
                              </script>';*/
                         

                    }
                    
                }else{
                    
                    
                                echo '<script>swal({
                                           title: "Algo está mal!",
                                           text: "!Error de Captcha!",
                                           icon: "error",
                                           dangerMode: true,
                                           buttons: {
                                               confirm: {text: "Continuar",
                                                         value: true,
                                               },
                                           },
                                          })
                                           .then((value) => {
                                                window.location.replace("'.$servidor.'chequeo_alertas.php?id='.$id.'&msj='.$msj.'");
                                            })
                                      </script>';
                    
                        
                        /*
                        echo '<script type="text/javascript">
                                        alert("Error de Captcha.");
                                        window.location = "'.$servidor.'chequeo_alertas.php?id='.$id.'&msj='.$msj.'";
                              </script>';*/
                        
                     
                }
}
?>
        </body>
        </html>  