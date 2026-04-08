<br /><br /><br /><br />
<?php
    $sql="  SELECT u.nombredeusuario,
                   u.password,
                   u.apellido,
                   u.nombres,
                   u.ndoc, 
                   u.nivacc, 
                   z.cuatrigrama AS destino
              FROM vw_personas u,
                   vw_tbl_zonas z
             WHERE u.destino = z.cuatrigrama  
               AND u.nombredeusuario = '".$usu."';";
           
    //echo $sql;        
    $row=sql_do_array(sql_do_query($sql,'1',$usu));
    
    if(is_file("web/mod/personal/scripts/img_personalpna/img/$usu.jpg")){
        $foto = "web/mod/personal/scripts/img_personalpna/img/$usu.jpg";
    }else{
        $foto = "img/sin_imagen.jpg";
    }  
?>
<div class="container" style="padding-top: 25px; padding-bottom:25px; border-bottom: 1px solid rgba(0,0,0,0.3);">
<div class="row">
      <table width="500" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td><strong>DATOS DEL PERSONAL:</strong></td>
        </tr>
      </table>
      <table width="500" border="0" cellpadding="0" cellspacing="0">
        <tr class="fondo-II">
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td width="30">&nbsp;</td>
        </tr>
        <tr class="fondo-I">
          <td width="20" height="40">&nbsp;</td>
          <td width="50" height="40" align="right"><strong>DNI:</strong></td>
          <td width="20" height="40">&nbsp;</td>
          <td width="200" height="40"><?php echo $row['NDOC'];?></td>
          <td rowspan="5" bgcolor="#FFFFFF"><img src="<?php echo $foto; ?>" alt="Foto del Cursante" width="200" height="266" /></td>
          <td width="30">&nbsp;</td>
        </tr>
        <tr class="fondo-II">
          <td width="20" height="40">&nbsp;</td>
          <td width="50" height="40"align="right"><strong>Apellido:</strong></td>
          <td width="20" height="40">&nbsp;</td>
          <td width="200" height="40"><?php echo $row['APELLIDO'];?></td>
          <td width="30">&nbsp;</td>
		</tr>
        <tr class="fondo-I">
          <td width="20" height="40">&nbsp;</td>
          <td width="50" height="40" align="right"><strong>Nombre:</strong></td>
          <td width="20" height="40">&nbsp;</td>
          <td width="200" height="40"><?php echo $row['NOMBRES'];?></td>
          <td width="30">&nbsp;</td>
        </tr>
        <tr class="fondo-II">
          <td width="20" height="40">&nbsp;</td>
          <td width="50" height="40" align="right"><strong>Destino:</strong></td>
          <td width="20" height="40">&nbsp;</td>
          <td width="200" height="40"><?php echo $destino_curso = $row['DESTINO'];?></td>
          <td width="30" height="40">&nbsp;</td>
        </tr>
        <tr class="fondo-I">
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td width="30">&nbsp;</td>
        </tr>
      </table>
   </div>
</div>
<div class="container" style="padding-top: 25px; padding-bottom:25px; border-bottom: 1px solid rgba(0,0,0,0.3);">
<div class="row">
      <table width="500" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td><strong>MODULOS Y NIVELES DEL SISTEMA:</strong></td>
        </tr>
      </table>
<?php 
              $sql= "SELECT b.nombre,a.codigo_sistema AS codigo, c.descripcion AS privilegio, d.descripcion AS estado, a.estado as estad
              		   FROM usu_user_sist a,usu_sistemas b, usu_sist_privilegio c, usu_estados d   
             	      WHERE a.codigo_sistema = b.codigo
             			AND a.priv_sist_id = c.priv_sist_id
             			AND a.estado = d.estadoid
                        AND d.descripcion <> 'BORRADO'
            			AND a.nombredeusuario = '$usu'
            			ORDER BY a.estado DESC;";
              $result = sql_do_query($sql,'1',$usu); ?>
                    <table width="600" border="0" cellpadding="0" cellspacing="0" bordercolor="#DFE4F9" >
                      <tr class="subt_chico_color_fondo_cent">
                         <td align="center"><strong>Modulo</strong></td>
                         <td align="center"><strong>Privilegio</strong></td>
                         <td align="center"><strong>Estado</strong></td>
                      </tr>
                      <?php while($row = sql_do_array($result)) { ?>
                      <tr class="fondo-I">
                         <td>&nbsp;<?php echo $row['NOMBRE'] ; ?></td>
                         <td align="center">&nbsp;<?php echo $row['PRIVILEGIO'] ; ?></td>
                         <td align="center">&nbsp;<?php echo $row['ESTADO'] ; ?></td>
                      </tr>
                      <?php
                            }
                        ?> 
                    </table>
   </div>
</div>
<div class="container" style="padding-top: 25px; padding-bottom:25px; border-bottom: 1px solid rgba(0,0,0,0.3);">
<div class="row">
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td><strong>MENSAJES VISTOS POR EL USUARIO EN EL ULTIMO MES:</strong></td>
        </tr>
      </table>
      <table width="500" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>MENSAJES ENVIADOS</td>
  </tr>
</table>
      <table width="600" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" width="100"><strong>GFH</strong></td>
        <td align="center" width="150"><strong>GR</strong></td>
        <td align="center" width="150"><strong>NP</strong></td>
        <td align="center"><strong>EJECUTIVO</strong></td>
    </tr>
              <?php 
             $sql= "SELECT m.id_mensaje,
                           m.origen, 
                           m.id_grado_reserva,
                           gr.nombre AS gr,
                           gr.bgcolor AS grcolor, 
                           m.id_nivel_prioridad, 
                           np.nombre AS np,
                           m.id_codigo_texto, 
                           m.gfh, 
                           m.mes, 
                           m.anio, 
                           m.anterior, 
                           m.estado, 
                           m.auditoria_usu, 
                           m.auditoria_fecha 
                      FROM sime_mensajes m,
                           sime_grados_reserva gr,
                           sime_niveles_prioridad np
                     WHERE m.auditoria_usu = '$usu' 
                       AND m.id_grado_reserva = gr.id_grado_reserva
                       AND m.id_nivel_prioridad = np.id_nivel_prioridad
                       AND m.estado = '2'
                       AND m.mes = '".date('n')."'
                  ORDER BY m.auditoria_fecha DESC;";
             $result=sql_do_query($sql,'1',$usu); ?>
                      <?php while($row = sql_do_array($result)) { ?>
                      <tr bgcolor="<?php echo $row['GRCOLOR'];?>">
                         <td align="center" height="40"><?php echo $row['GFH'];?></td>
                         <td align="center" height="40"><?php echo $row['GR'] ; ?></td>
                         <td align="center" height="40"><?php echo $row['NP'] ; ?></td>
                         <td align="center">
                         <?php 
             $sql2= "SELECT destinatario FROM sime_mostrar_destinatarios WHERE id_mensaje = '".$row['ID_MENSAJE']."';";
             //echo $sql2; 
             $result2=sql_do_query($sql2,'1',$usu); ?>
                      <?php
                          $destin = '';
                          while($row2 = sql_do_array($result2)) { 
                               $destin .= $row2['DESTINATARIO']. ' ';
                          }
                          echo $destin;
                      ?>
                         </td>
                      </tr>
                      <?php
                            }
                        ?> 
</table>
<br />
<br />
<table width="500" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>MENSAJES RECIBIDOS</td>
  </tr>
</table>
      <table width="600" border="0" cellspacing="0" cellpadding="0">
<tr>
        <td align="center" width="100"><strong>GFH</strong></td>
        <td align="center" width="150"><strong>GR</strong></td>
        <td align="center" width="150"><strong>NP</strong></td>
        <td align="center"><strong>EJECUTIVO</strong></td>
</tr>
              <?php 
             $sql= "SELECT m.id_mensaje,
                           m.origen, 
                           m.id_grado_reserva,
                           gr.nombre AS gr,
                           gr.bgcolor AS grcolor, 
                           m.id_nivel_prioridad, 
                           np.nombre AS np,
                           m.id_codigo_texto, 
                           m.gfh, 
                           m.mes, 
                           m.anio, 
                           m.anterior, 
                           m.estado, 
                           m.auditoria_usu, 
                           m.auditoria_fecha 
                      FROM sime_mensajes m,
                           sime_grados_reserva gr,
                           sime_niveles_prioridad np,
                           sime_real_destinatarios rd
                     WHERE m.id_mensaje = rd.id_mensaje
                       AND m.id_grado_reserva = gr.id_grado_reserva
                       AND m.id_nivel_prioridad = np.id_nivel_prioridad
                       AND m.estado = '2'
                       AND m.mes = '".date('n')."'
                       AND rd.auditoria_recibido_usu = '$usu' 
                  ORDER BY rd.auditoria_recibido_fecha DESC;";
             $result=sql_do_query($sql,'1',$usu); ?>
                      <?php while($row = sql_do_array($result)) { ?>
                      <tr bgcolor="<?php echo $row['GRCOLOR'];?>">
                         <td align="center" height="40"><?php echo $row['GFH'];?></td>
                         <td align="center" height="40"><?php echo $row['GR'] ; ?></td>
                         <td align="center" height="40"><?php echo $row['NP'] ; ?></td>
                         <td align="center" height="40"><?php echo $row['ORIGEN'] ; ?></td>
                      </tr>
                      <?php
                            }
                        ?> 
</table>
<br />
<br />
<table width="500" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>MENSAJES LEIDOS</td>
  </tr>
</table>
      <table width="600" border="0" cellspacing="0" cellpadding="0">
<tr>
        <td align="center" width="100"><strong>GFH</strong></td>
        <td align="center" width="150"><strong>GR</strong></td>
        <td align="center" width="150"><strong>NP</strong></td>
        <td align="center"><strong>EJECUTIVO</strong></td>
</tr>
              <?php 
             $sql= "SELECT m.id_mensaje,
                           m.origen, 
                           m.id_grado_reserva,
                           gr.nombre AS gr,
                           gr.bgcolor AS grcolor, 
                           m.id_nivel_prioridad, 
                           np.nombre AS np,
                           m.id_codigo_texto, 
                           m.gfh, 
                           m.mes, 
                           m.anio, 
                           m.anterior, 
                           m.estado, 
                           m.auditoria_usu, 
                           m.auditoria_fecha 
                      FROM sime_mensajes m,
                           sime_grados_reserva gr,
                           sime_niveles_prioridad np,
                           sime_ctrl_escaleras ce
                     WHERE m.id_mensaje = ce.id_mensaje
                       AND m.id_grado_reserva = gr.id_grado_reserva
                       AND m.id_nivel_prioridad = np.id_nivel_prioridad
                       AND m.estado = '2'
                       AND m.mes = '".date('n')."'
                       AND ce.auditoria_leido_usu = '$usu' 
                  ORDER BY ce.auditoria_leido_fecha DESC;";
             $result=sql_do_query($sql,'1',$usu); ?>
                      <?php while($row = sql_do_array($result)) { ?>
                      <tr bgcolor="<?php echo $row['GRCOLOR'];?>">
                         <td align="center" height="40"><?php echo $row['GFH'];?></td>
                         <td align="center" height="40"><?php echo $row['GR'] ; ?></td>
                         <td align="center" height="40"><?php echo $row['NP'] ; ?></td>
                         <td align="center" height="40"><?php echo $row['ORIGEN'] ; ?></td>
                      </tr>
                      <?php
                            }
                        ?> 
    </table>
   </div>
</div>
<br />