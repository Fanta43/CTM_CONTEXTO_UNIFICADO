<ul class="gn-menu">
<li style="background-color:#222d32" "class="gn-icon">
    <a href="index.php?estado=6" title="Home">
        <table width="100%" border="0">
          <tr>
            <td width="10" align="middle" valign="middle"><img src="img/picto/home.png" width="52" height="52" style="margin-top: 0px;" /></td>
            <td width="10" align="left" valign="middle">&nbsp;</td>
            <td align="left" valign="middle">Home</td>
          </tr>
        </table>
    </a>
</li>
<?php

$sql="SELECT us.codigo_sistema, 
             s.nombre, 
             sp.nro_priv  
        FROM usu_user_sist us, 
             usu_sistemas s, 
             usu_sist_privilegio sp 
       WHERE us.priv_sist_id = sp.priv_sist_id 
         AND us.codigo_sistema = s.codigo 
         AND s.estadoid = '5'
         AND us.usuarioid = '".$usu."';";
//echo $sql ;
$result = sql_do_query($sql,'1',$usu);
while($row=sql_do_array($result)) {
        $var1 = $row['CODIGO_SISTEMA'];
        $var2 = $row['NOMBRE'];
        $var3 = $row['NRO_PRIV'];
        $_SESSION[$var1] = $var3;
        
        echo '<li style="background-color:#222d32" class="gn-icon">
                <a href="index.php?estado='.$var1.'" title="'.$var1.'">
                    <table width="100%" border="0">
                      <tr>
                        <td width="10" align="left" valign="middle"><img src="img/picto/'.$var1.'.png" width="52" height="52" style="margin-top: 0px;"/></td>
                        <td width="10" align="left" valign="middle">&nbsp;</td>
                        <td align="left" valign="middle">'.$var2.'</td>
                      </tr>
                    </table>
                </a>
              </li>';
}
?>
<li style="background-color:#222d32" class="gn-icon">
    <a href="index.php?estado=10" title="Salir">
        <table width="100%" border="0">
          <tr>
            <td width="10" align="left" valign="middle"><img src="img/picto/salir.png" width="52" height="52" style="margin-top: 0px;" /></td>
            <td width="10" align="left" valign="middle">&nbsp;</td>
            <td align="left" valign="middle" >Salir</td>
          </tr>
        </table>
    </a>
</li>
</ul>