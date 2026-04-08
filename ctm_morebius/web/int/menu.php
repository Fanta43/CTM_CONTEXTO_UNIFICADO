<style>
/* MENU DINAMICO */

#menu{
	width:100%;
	height: 70px;
	float: left;
        margin:auto;
}

#menu ul {
        margin-top: 0px;
}

#mnu1{
	width:100%;
	height: 30px;
	float: left;
}
#mnu2{
	width:100%;
	float: left;
        height: 20px;
}
#mnu3{
	width:100%;
        height: 20px;
	float: left;
        padding-left: 100px;
       /* background-color: #41BA9A; */
}

#mnu1 li{
	width:120px;
	height:30px;
	list-style-type:none;
	float:right;
	background-image:url(img/fondo_mnu1.png);
	background-repeat:repeat-x;
	text-align:center;
	font-style:normal;
	font-variant:small-caps;
	font-size:12px;
	font-family:Arial, Helvetica, sans-serif;
	font-weight:normal;
	margin-bottom:0px;
}

#mnu1 a{
	width:120px;
	height:30px;
	padding-top:7px;
	text-decoration:none;
	display:block;
}

#mnu1 a:link{
	color:#000;
}

#mnu1 a:hover{
	padding-top:9px;
}

#mnu1 a:active{
	color:#000;

}

.sel_mn1 {
    color: #fff; 
    background-color: #418CBA; 
    font-weight: bold; 
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.5); 
    transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out; 
}

.sel_mn1 a {
    color: #fff;
    color: inherit; 
    text-decoration: none;
    display: block; 
}

#mnu2 li{
	width:120px;
	height:20px;
	list-style-type:none;
	float:right;
	text-align:center;
	font-style:normal;
	font-variant:small-caps;
	font-size:11px;
	font-family:Arial, Helvetica, sans-serif;
	font-weight:normal;
	margin-bottom:0px;
}

#mnu2 a{
	width:120px;
	height:20px;
	padding-top:2px;
	text-decoration:none;
	display:block;
}

#mnu2 a:hover{
	padding-top:4px;
}

#mnu2 a:active{
	color:#000;
}

#mnu3 a{
	text-decoration:none;
}


</style>


<?php
/*Incluyo las funciones*/
include_once('conf/funcion.php');

/* Estas Son Variables de Transporte de estado las uso en el if para cambiar el estilo del boton */
$estado=$_GET['estado'];
if(!$estado)$estado='1';

?>
<div id="mnu3">
    <?php include_once('ariadna.php'); ?>
</div>
<?php

/**
* Aca armo el menu con las listas y el resultado del siguiente select select
* Elegir el numero para el menu que corresponde
**/

// Esta primera ejecucion de Select Arma el Menu Principal:
$sql="select * from more_menus where menu='1' and estado ='1' order by id DESC;";
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

//var_dump($var_ini);

$nro1a=0;
$nro1b=0;
while($row=sql_do_array($result)) {
        $var1 = $row['ID'];
        $var2 = $row['OPCION'];
        $var3 = $row['NIVACC'];
        if($estado == $var1 or $var_ini == $var1){
            $mnu1 = $row['ID'];
            $st='class="sel_mn1"';
            $nro1b=0;
        }else{
            $st='';
        }
        if($var3<=$nivel)echo "<li $st><a id='a1' href='index.php?estado=".$var1."'>".$var2."</a></li>";
        $nro1b++;
        $nro1a++;
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
            
        $sql2="select * from more_menus where submenu='$estado' and estado ='1' order by 'opcion';";
        $result2=sql_do_query($sql2,'1',$usu);
                $nro2a=0;
                while($row2=sql_do_array($result2)) {
                    $var5=$row2['ID'];
                    $var6=$row2['OPCION'];
                    $var7=$row2['NIVACC'];
                    
                    $st='class="sel_mn1"'; 
                    if($estado==$var5){
                        $var4='id="activo"';
                    }else{
                        $var4='';
                    }
                    if($var7<=$nivel) echo "<li $st><a ".$var4." href='index.php?estado=".$var5."'>".$var6."</a></li>";
                    $nro2a++;
                }
                
                while($nro1a!=$nro2a){
                    echo "<li $st></li>";
                    $nro2a++;
                }
                
                
        }
?>
</div>

<?php
/**
 * Estas son las funciones que arman el includ de la pagina principal;
 * Para el includ es sensillo traigo la pagina grabada con el ID.
**/
$sql3="select * from more_menus where id='".$estado."';";
$result3 = sql_do_query($sql3,'1',$usu);
$row3=sql_do_array($result3);
$direcion=$row3['DIRECCION'];

?>
</ul>