<?php
/*Incluyo las funciones*/
//include_once('conf/funcion.php');

/* Estas Son Variables de Transporte de estado las uso en el if para cambiar el estilo del boton */
$estado=$_GET['estado'];
if(!$estado)$estado='1';

/**
* Aca armo el menu con las listas y el resultado del siguiente select select
* Elegir el numero para el menu que corresponde
**/

// Esta primera ejecucion de Select Arma el Menu Principal:
$sql="select * from usu_menus where menu='1' and estado ='1' order by 'opcion';";
$result = sql_do_query($sql,'0',$usu);

?>
<ul>
<?php
/**
* Este WHILE arma los botones 
* var1 "Estado" es la marca para funcionar:
* var2 "Opcion" es el texto
* var3 "id" es la marca de boton activo que manejo con los CSS
* En el siguiente bloque de codigo estoy buscando la variable $mnuprinc ella abre la puerta para el armado de los submenus
* en el caso que alguno este seleccionado; Cuando no hay ninguno abierto enonces se encarga la  variable estado por si misma 
* Abriendo el mismo sin seleccionar ninguna opcion.
**/
/*
$sql4="select * from usu_menus where id='$estado' and estado ='1';";
$result4 = sql_do_query($sql4,'1',$usu);
$row4=sql_do_array($result4);

$mnuprinc=$row4['SUBMENU'];
*/
?><div id="mnu1">
<?php
while($row=sql_do_array($result)) {
        $var1 = $row['ID'];
        $var2 = $row['OPCION'];
        $var3 = $row['NIVACC'];
        if($var3<=$nivel)echo "<li><a id='a1' href='index.php?estado=".$var1."'>".$var2."</a></li>";
}
?>
</div>
<div id="mnu2">
<?php
    /**
     * Armo los Submenus
     * El If es la llave que me muestra los menus al estar seleccionados en la primera opcion 
     * o al no haber ninguno seleccionado en la segunda.
    **/
    
        if($estado){
            
        $sql2="select * from usu_menus where submenu='$estado' and estado ='1' order by 'opcion';";
        $result2=sql_do_query($sql2,'1',$usu);
        
                while($row2=sql_do_array($result2)) {
                    $var5=$row2['ID'];
                    $var6=$row2['OPCION'];
                    $var7=$row2['NIVACC'];
                    
                    if($estado==$var5){
                        $var4='id="activo"';
                    }else{
                        $var4='';
                    }
                    if($var7<=$nivel) echo "<li><a ".$var4." href='index.php?estado=".$var5."'>".$var6."</a></li>";
                }
        }
?>
</div><?php
/**
 * Estas son las funciones que arman el includ de la pagina principal;
 * Para el includ es sensillo traigo la pagina grabada con el ID.
**/

$sql3="select * from usu_menus where id='".$estado."';";
$result3 = sql_do_query($sql3,'1',$usu);
$row3=sql_do_array($result3);
$direcion=$row3['DIRECCION'];

?>
</ul>