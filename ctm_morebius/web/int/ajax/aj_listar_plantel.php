<?php
    ini_set( 'display_errors', 'off' );
    include_once('seg_ajax.php');
    include_once('../../../conf/conf.php');
    include_once('../../../conf/funcion.php');
    $usu = $_GET['usu'];
    $str_org = construirPlantel('2', $usu);
    file_put_contents('listar_plantel.json', json_encode($str_org, JSON_UNESCAPED_UNICODE));
    echo "JSON generado correctamente<br>";
    echo "Ruta: " . realpath('listar_plantel.json');
?>