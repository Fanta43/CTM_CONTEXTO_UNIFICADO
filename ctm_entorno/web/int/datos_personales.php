<?php
    //if(!$_SESSION["nombres"] && !$_SESSION["apellido"] ){
    
        $sql="SELECT nombres, apellido, ndoc FROM vw_personas WHERE usuarioid = '".$usu."';";
        $row = sql_do_array(sql_do_query($sql,'1',$usu));
        $_SESSION["nombres"] = $row["NOMBRES"];
        $_SESSION["apellido"] = $row["APELLIDO"];
        $_SESSION["ndoc"] = $row["NDOC"];
    //}
    
    if(is_file("web/mod/personal/scripts/img_personalpna/img/$usu.jpg")){
        $foto = "web/mod/personal/scripts/img_personalpna/img/$usu.jpg";
    }else{
        $foto = "img/sin_imagen.jpg";
    }  
?>

<div style="height: 50px; width: 60px; float: left; margin: 5px 11px  5px 5px; padding: 0; line-height: 20px;"><img src="<?php echo $foto; ?>" style="border-radius: 50%;" alt="Foto del Cursante" width="50" height="50" /></div>

<div style="font-size: 140%; margin: 0; padding-top: 6px; height: 20px; text-align: left; vertical-align: top; width: 300px; line-height: 20px;"><strong><?php echo html_entity_decode($_SESSION["apellido"], ENT_HTML401, 'ISO-8859-1'); ?></strong></div>
<div style="font-size: 100%; font-family: Arial; margin: 0; padding-top: 5px; height: 20px; text-align: left; vertical-align: top; width: 300px; line-height: 20px;"><?php echo html_entity_decode($_SESSION["nombres"], ENT_HTML401, 'ISO-8859-1'); ?></div>
<div style="font-size: 80%;  margin: 0; padding: 0; height: 20px; text-align: left; vertical-align: top; width: 300px; line-height: 20px;"><?php echo $_SESSION["ndoc"]; ?></div>
