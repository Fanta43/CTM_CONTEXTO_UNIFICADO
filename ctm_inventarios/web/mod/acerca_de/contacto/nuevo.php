<?php
//ini_set( 'display_errors', 'on' );
function conf_COM($tipo_com='E'){
   switch ($tipo_com)
   {
    case 'S':
        $data=array();
        $data['estado'] = 1;
        $data['sid'] = "AC91eff27803a79d62e017b58bb76a5f63";
        $data['token'] = "ed528238fddd12360999d8fa59c7a7f9";
        $data['twilio_number'] = "+19897472212";
    break;
    case 'W':
        $data=array();
        $data['estado'] = 1;
        $data['sid'] = "AC91eff27803a79d62e017b58bb76a5f63";
        $data['token'] = "ed528238fddd12360999d8fa59c7a7f9";
        $data['twilio_number'] = "+19897472212";
    break;
/*
    case 'E':
        $data=array();
 * $data['estado'] = 1;
        $data['host'] = 'smtp.gmail.com';
        $data['smtpauth'] = true;
        $data['username'] = 'qrmania@prefecturanaval.edu.ar';
        $data['useradmin'] = 'farinong@saltogrande.org';
        $data['password'] = 'P3p3v3r@2';
        $data['smtpsecure'] = 'ssl';
        $data['port'] = 465;
 */
    case 'E':
        $data=array();
        $data['estado'] = 0;
        $data['host'] = 'smtp.gmail.com';
        $data['smtpauth'] = true;
        $data['username'] = 'ctmhidrologia@gmail.com';
        $data['useradmin'] = 'ctmhidrologia@gmail.com';
        $data['password'] = 'xpafwyqdgzmdsqmm';
        $data['smtpsecure'] = 'tsl';
        $data['port'] = 465;
    break;
    case 'E2':
        $data=array();
        $data['estado'] = 1;
        $data['host'] = '172.16.1.98';
        //$data['host'] = 'relay.saltogrande.org';
        $data['smtpauth'] = false;
        $data['username'] = 'atencion_usuarios@saltogrande.org';
        $data['useradmin'] = 'farinong@saltogrande.org';
        $data['password'] = '';
        $data['smtpsecure'] = 'tsl';
        $data['port'] = 25;
    break;
    default:
        $data=array();
        $data['estado'] = 1;
        $data['host'] = '172.16.1.98';
        //$data['host'] = 'relay.saltogrande.org';
        $data['smtpauth'] = false;
        $data['username'] = 'atencion_usuarios@saltogrande.org';
        $data['useradmin'] = 'farinong@saltogrande.org';
        $data['password'] = '';
        $data['smtpsecure'] = 'tsl';
        $data['port'] = 25;
    }
Return $data;
}

$nombre = Enmascarar_post('nombre');
$email = Enmascarar_post('email');
$consulta = Enmascarar_post('consulta');

$mensaje = $nombre." - ".$email." - ".$consulta;

if($nombre && $email && $consulta){
    $envio = consultaEnviarEmail($email, $email_mesadeayuda, $mensaje); 
    if($envio){
        echo '<script>swal({
                       title: "Muchas Gracias por Contactarnos!",
                        text: "¡Un técnico de Atención a Usuarios lo contactará a la brevedad!",
                        icon: "success",
                  dangerMode: false,
                     buttons: {
                           confirm: {text: "Continuar",
                                     value: true,
                           },
                       },
                      })
                       .then((value) => {
                            window.location.replace("index.php?estado=1");
                           })    
             </script>';
    }
}    

?>

<div class="col-12 p-2 pb-0">
    <div class="text-center text-danger">
        <!-- Mostrar mensaje de respuesta -->
        <?php echo $mensaje; ?>
    </div>
    <div class="row my-3">
        <div class="col text-left">
            <strong style="font-size: 25px;">Consulta a Atenci&oacute;n a Usuarios </strong>
        </div>
    </div>

    <form id="formContacto" name="formContacto" method="post" action="">
        <div class="bg-white p-3">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Tu nombre">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Tu dirección de email">
            </div>
            <div class="mb-3">
                <label for="consulta" class="form-label">Consulta</label>
                <textarea class="form-control" id="consulta" name="consulta" rows="4" placeholder="Detalles de la consulta"></textarea>
            </div>
            <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                <input type="submit" id="enviarConsulta" name="enviarConsulta" class="btn btn-pna-oscuro" style="width: 200px;" value="Enviar">
            </div>
        </div>
    </form>
</div>
