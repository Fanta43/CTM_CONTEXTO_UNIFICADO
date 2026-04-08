<?php
$sql=" SELECT notificacionid
           FROM ento_notificaciones 
          WHERE ndoc = '$usu';";

$row = sql_do_array(sql_do_query($sql,'1',$usu));

if($row['NOTIFICACIONID']){
    echo '<img src="img/picto/con_notificaciones.png" width="50" height="50" />';
}else{
    echo '<img src="img/picto/notificaciones.png" width="50" height="50" />';
}

?>