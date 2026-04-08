<?php
session_start();
if (!isset($_SESSION["us"]) and !isset($_SESSION["destino"])){
    $_SESSION["us"]='0';
    $_SESSION["nivel"]='0';
    echo  "<script type='text/javascript'>window.close();</script>";
}else{
    $hoy = date("Y-m-d H:i:s");
    $sistema='AICO_666';
    $usu=$_SESSION["us"];
    $nivel=$_SESSION[$sistema];
    $destino=$_SESSION["destino"];
}