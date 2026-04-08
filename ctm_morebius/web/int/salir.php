<?php
/**
 * Salir / Cerrar sesión - ctm_morebius
 * Redirige al entorno principal.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["us"] = '0';
$_SESSION["nivel"] = '0';
header("location:../../ctm_entorno/default.php");
exit();
