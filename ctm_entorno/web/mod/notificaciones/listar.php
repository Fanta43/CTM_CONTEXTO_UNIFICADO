<br /><br /><br />
<?php
  $sql=" SELECT notificacionid, 
                codigo_sistema, 
                ndoc, 
                fecha, 
                novedad, 
                tipo_notificacionid, 
                tipo_urgenciaid, 
                link, 
                lectura_usu, 
                lectura_fecha, 
                estadoid, 
                auditoria_usu, 
                auditoria_fecha 
           FROM ento_notificaciones 
          WHERE ndoc = '$usu';";
//echo $sql;
$n = 1;
$result = sql_do_query($sql,'1',$usu);
while($row=sql_do_array($result))  { 
    
    
    if(is_file("img/urgencia/".$row['TIPO_URGENCIAID'].'.png')){
    	$foto = "img/urgencia/".$row['TIPO_URGENCIAID'].'.png';
	}else{
	    $foto = "web/urgencia/sin_imagen.jpg";
    }

?>
				
                <div class="container" style="padding-top: 25px; padding-bottom:25px; border-bottom: 1px solid rgba(0,0,0,0.3);">
					<div class="row">
						<div class="text-center d-flex align-items-center justify-content-center" style="width: 30px;">
						  <h4><?php echo $n;?></h4>	
			            </div>
						<div class="col-lg-2 text-center d-flex align-items-center justify-content-center">
							<img class="img-fluid" style="height:100%; width:100%; padding-bottom: 20px;" src="<?php echo $foto;?>">
			            </div>
						<div class="col-lg-6">
                            <p class="text-justify"><?php echo "<strong>Sistema: </strong>".$row['CODIGO_SISTEMA'];?></p>
                            <p class="text-justify"><?php echo "<strong>Fecha: </strong>".$row['FECHA'];?></p>
                            <p class="text-justify"><?php echo "<strong>Notificaci&oacute;n: </strong>".$row['NOVEDAD'];?></p>
						</div>
						<div class="col-lg-3 text-center d-flex align-items-center justify-content-center">
                        
                        <table width="100%" border="0">
                          <tr>
                            <td>
                        		<form id="form1" name="form1" method="post" action="<?php echo $row['LINK'];?>">
                        			<button type="submit" class="btn btn-pna" style="width: 200px;" name="masinfo"  id="masinfo" value="Mas">Mas Informaci&oacute;n</button>
                        		</form>
                            </td>
                          </tr>
                        </table>
						</div>
					</div>
				</div>
<?php
$n++;
}
?>