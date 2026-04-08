<?php
session_start();
ini_set( 'display_errors', 'off' );
include_once('ctm_gastcomunes/conf/conf.php');
include_once('ctm_gastcomunes/conf/funcion.php');
$hoy = date("Y-m-d H:i:s");

$id = sani_input(substr($_GET['id'], 0, 15));
if(!$id) $id = sani_input(substr($_POST['id'], 0, 15));
$msj = sani_input(substr($_GET['msj'], 0, 15));
if(!$msj) $msj = sani_input(substr($_POST['msj'], 0, 15));

$sql="SELECT usuarioid
        FROM gast_msg_ctrl 
       WHERE id_msg_ctrl = '$id'
         AND gastoid = '$msj';";
//echo $sql;
$rowgast=sql_do_array(sql_do_query($sql,'1'));

$usu = $rowgast['USUARIOID'];


/*
    echo "<h1>Para GET</h1>";
    var_dump($_GET);
    echo "<h1>Para POST</h1>";
    var_dump($_POST);
*/

?>

        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//ES" "http://www.w3.org/TR/html4/loose.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <title>Sistema Avisos PADE</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv = "Content-Type" content = "text / html; charset = ISO-8859-1" />
        <link rel="shortcut icon" type="image/x-icon" href="ctm_entorno/img/ctm_favicon.ico"/>
        <script language='javascript' src="ctm_gastcomunes/conf/sweetalert.min.js"></script>
        <!--
            <link href="ahid_entorno/conf/estilos.css" rel="stylesheet" media="screen">
            <link rel="stylesheet" href="ahid_entorno/conf/css/login.css">
        -->    
                    <style type="text/css">
                    .html,body{
                        /* Para que funcione correctamente en Smartphones y Tablets */
                         height:100vh;
                    }
                    body {
                         /* Ruta relativa o completa a la imagen */
                         background-image: url(ctm_gastcomunes/img/fragmentos/fondo_login.jpg);
                         /* Centramos el fondo horizontal y verticalmente */
                         background-position: center center;
                         /* El fonde no se repite */
                         background-repeat: no-repeat;
                         /* Fijamos la imagen a la ventana para que no supere el alto de la ventana */
                         background-attachment: fixed;
                         /* El fonde se re-escala automáticamente */
                         background-size: cover;
                         /* Color de fondo si la imagen no se encuentra o mientras se está cargando */
                         background-color: #75aadb;
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
                    /*margin: auto;*/
                    margin-top: 30px;
                    width: 100%;
                    background-color: white;
                    color: black;
                    display:table;
                    justify-content: left;
                    align-items: center;
                    border-radius: 3px;
                    font: 16px "Open Sans", sans-serif;
                    background: rgba(255,255,255,255);
                    padding: 10px 0;
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

                      .small {
                          width: 100%;
                          text-align:left;
                          text-decoration: underline;
                          float: left;
                          /*background-color: red;*/
                      }

                      .large {
                        width: 100%;
                        float: left;
                        /*background-color: blue;*/
                      }
                    
            </style>
        </head>
<body>



<?php
$fecha_nacimiento = sani_input(substr($_POST['fecha_nacimiento'], 0, 8));
//if(!$fecha_nacimiento) $fecha_nacimiento = sani_input(substr($_GET['fecha_nacimiento'], 0, 8));
//var_dump($fecha_nacimiento);
if(!$fecha_nacimiento){
//var_dump($fecha_nacimiento);
    
    
    
?>
            <div class="text-homemain text-light text-center" style="margin-left: 0px; margin-right: -50px;">
                            <form id="form1" name="form1" method="post" action="">
                                    <div id="contenedor">

                                        <div id="Direccion">Gastos Vigentes</div>
                                        <div id="Contrasenia">Ingrese su fecha de nacimiento</div>
                                        <div id="Fecha_nacimiento"> 
                                            <table width="80%" border="0">
                                                <tr>
                                                    <td width="25"><img src="ctm_gastcomunes/img/usuario.png" style="width: 25px; height: 30px"/></td>
                                                    <td align="center"><input type="text" id="fecha_nacimiento" name="fecha_nacimiento" placeholder="DDMMAAAA"/></td>
                                                    <td width="25"></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <input type="hidden" id="id" name="id"  value="<?php echo $id?>">
                                        <input type="hidden" id="msj" name="msj" value="<?php echo $msj?>"> 
                                        <input class="btn btn-pna btn-block" id="ingresar" name="ingresar" type="submit" value="Ingresar">
                                    </div>
                            </form>
            </div>
                               
<?php
}else{
    

                $salir = sani_input(substr($_POST['salir'], 0, 6));
                
                if($salir){

                                                    echo '<script>swal({
                                                           title: "Gracias!",
                                                           text: "Usted se ha notificado del contenido de los Gastos!",
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

                }else{



                                    $parametros = Array
                                    (
                                        'id_msg_ctrl'   => $id,
                                        'fecha_nacimiento'   => $fecha_nacimiento,
                                        'gastoid'  => $msj
                                    );

                                    //var_dump($parametros);

                                    $res = validacion_chequeo($parametros);

                                    //var_dump($res);

                                    if($res['usuario']){

                                            crtl_msg($id, $msj, $usu, $hoy, '1');
                                            



                ?>                            

                            <br />
                            <div class="text-homemain text-light text-center" style="margin-left: 0px; margin-right: -50px;">
                                            <form id="form1" name="form1" method="post" action="chequeo.php">
                                                    <div id="contenedor_mensaje">
                                                    <div id="Alerta_descripcion" class="colores_alerta">
                                                            <table width="100%" border="0" cellpadding='5' cellspacing='5'>
                                                                    <tr>
                                                                        <td align="center"><strong>Gastos Comunes</strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center">Vigentes</td>
                                                                    </tr>
                                                            </table>
                                                    </div>

                                                        
                                                        
                                                        
                                                        
                                                        
                                                        
                                                        
                                                        
                                                        
<br />                                                        
<div class="container border bg-white mt-3">
    <?php
        	$sql=" SELECT ug.usuarioid,
                      p.apellido,
                      p.nombres
                 FROM gast_gastos g, 
                      gast_user_gastos ug,
                      vw_personas p
                WHERE g.gastoid = ug.gastoid
                  AND ug.usuarioid = p.usuarioid
                  AND ug.estadoid = '6'
                  AND g.estadoid NOT IN ('5','4') AND ug.usuarioid = '$usu'
             GROUP BY ug.usuarioid,
                      p.apellido,
                      p.nombres
             ORDER BY p.apellido,
                      p.nombres;";
        $result = sql_do_query($sql,'2',$usu);

        $n0 = 0;
        while($row=sql_do_array($result)) {
            $n0++;
            $estado_usu=null;
    ?>

    <div class="row">
        <div class="col-12"><strong><?php echo $row['APELLIDO'].', '.$row['NOMBRES']; ?></strong></div>
        <hr />
        <div class="col-12">
            <div class="container">
                <?php
                    $sql2="  SELECT g.gastoid, 
                                    g.nombre,
                                    g.descripcion,
                                    g.organizadorid,
                                    ug.usuarioid
                               FROM gast_gastos g, 
                                    gast_user_gastos ug
                              WHERE g.gastoid = ug.gastoid
                                AND ug.estadoid = '6'
                                AND g.estadoid NOT IN ('5','4')
                                AND ug.usuarioid = '".$row['USUARIOID']."';";
                    $result2 = sql_do_query($sql2,'1',$usu);
                    $n=0;
                    while($row2=sql_do_array($result2)){
                        $n++;                       
                        $estado_usu = ver_gasto_usuario($row2['GASTOID'], $row['USUARIOID'], '1', $usu);
                        
                        $sql3 = "SELECT * FROM vw_personas WHERE usuarioid =  '".$row2['ORGANIZADORID']."';";
                        //echo $sql;
                        $row3 = sql_do_array(sql_do_query($sql3,'1',$usu));

                        if($estado_usu['ESTADO']=='PAGADO'){
                            $str_estado =  '<strong style="font-size: 20px; color:green;">'.$estado_usu['ESTADO'].'</strong>';
                        }else{
                            $str_estado = '<strong style="font-size: 20px; color:red;">'.$estado_usu['ESTADO'].'</strong>';
                        }

                        $color_fila = ($n % 2 == 0) ? "bg-secondary" : "bg-light";
                ?>
                <div class="row <?php echo $color_fila; ?>">
                    <div class="col align-left">
                        <p style="font-size: 18px"><strong><?php echo $row2['NOMBRE'];?></strong></p>
                        <p style="font-size: 7px"><?php echo $row2['DESCRIPCION'];?></p>
                    </div>
                    <div class="col-2 text-center"><p style="font-size: 15px">Paga <strong style="font-size: 20px; color:red;"><?php echo $estado_usu['CUOTA_PARTE'];?></strong> $ a <?php echo $row3['APELLIDO'].', '.$row3['NOMBRES'];?></p></div>
                    <div class="col-3 text-center"><p><?php echo $str_estado;?></p></div>
                </div>
                <hr />
                <?php
                     if($row['USUARIOID']==$row2['ORGANIZADORID']){
                ?>
                <div class="row" >
                    <div class="col-3 text-left"><p>TE DEBEN:</p></div>
                <?php            
                            $sql5="  SELECT g.gastoid, 
                                            g.nombre,
                                            g.descripcion,
                                            g.organizadorid,
                                            ug.usuarioid
                                       FROM gast_gastos g, 
                                            gast_user_gastos ug
                                      WHERE g.gastoid = ug.gastoid
                                        AND ug.estadoid = '6'
                                        AND g.estadoid NOT IN ('5','4')
                                        AND g.gastoid = '".$row2['GASTOID']."'
                                        AND ug.usuarioid <> '".$row2['ORGANIZADORID']."';";
                            //echo $sql5;
                            $result5 = sql_do_query($sql5,'1',$usu);
                            while($row5=sql_do_array($result5)){
                                  $esta = ver_gasto_usuario($row2['GASTOID'], $row5['USUARIOID'], '1', $usu);
                                  //var_dump($esta);
                                  if($esta['ESTADO']=='PENDIENTE'){
                                        $sql6 = "SELECT * FROM vw_personas WHERE usuarioid = '".$row5['USUARIOID']."';";
                                        //echo $sql6;
                                        $row6 = sql_do_array(sql_do_query($sql6,'1',$usu));
                                        echo '<div class="col align-left"><p style="font-size: 15px; color:red;">'.$row6['APELLIDO'].', '.$row6['NOMBRES'].'</p></div>';
                                  }
                            }
                    }        
                            
                    }
                
                ?>
               </div>
            </div>
        </div>
        

        
        
    </div>
    <?php
        }
    ?>
</div>
                                                        
                                                        
                                                        
                                                        
                                                        
                                                        
                                                        
                                                        
                                                        
                                                        
                                                        

                                                        <input  id="id" name="id" type="hidden" value="<?php echo $id?>">  
                                                        <input  id="msj" name="msj" type="hidden" value="<?php echo $msj?>"> 
                                                        <input  id="fecha_nacimiento" name="fecha_nacimiento" type="hidden" value="<?php echo $fecha_nacimiento?>"> 
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
                                                                window.location.replace("'.$servidor.'chequeo.php?id='.$id.'&msj='.$msj.'");
                                                            })
                                                      </script>';


                                    }
                }
}
?>
        </body>
        </html>  