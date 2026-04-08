<?php
//ini_set( 'display_errors', 'on' );

$legajo = $_POST['legajo'];
if(!$legajo) $legajo = $_GET['legajo'];
if(!$legajo) $legajo = '';

//echo "<h1>P1</h1>";
//var_dump($codigopc);
include('cargador.php');
//echo "<h1>P2</h1>";
//var_dump($codigopc);

$nombre_pag = 'Datos del Personal';
$tab_inicial = '210';
$dir_raiz = 'web/mod/rrhh/personal/';

$tab = $_GET['tab'];
if(!$tab) $tab = $tab_inicial;

//var_dump($ndoc);

?>
<style>
.custom-tab-container {
    width: 66.66%; 
    display: flex;
    flex-direction: column;
}

.custom-nav-tabs {
    display: flex;
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.custom-nav-item {
    flex: 1;
    height: 50px;
    display: flex; 
    align-items: center; 
    justify-content: center;
}

.custom-nav-link {
    display: flex; 
    align-items: center; 
    justify-content: center; 
    text-align: center;
    padding: 20px;
    text-decoration: none;
    color: gray; 
    font-size: 10px; 
    background-color: transparent; 
    width: 100%; 
}

.custom-nav-link:hover, .custom-nav-link:focus {
    color: #0489B7; 
}

.custom-nav-link.active {
    font-weight: bold;
    color: #0489B7; 
    background-color: white; 
    border-top-right-radius: 20px; 
    border-left: 3px solid #0489B7; 
    box-shadow: 3px -2.5px 3px 0 rgba(20, 20, 20, 0.3);
}

.custom-tab-content-inner {
    padding: 0px;
}

</style>
    <div class="col-12">
        <div class="col-12 p-2 pb-0">
            <div class="col-12 row m-0 ">
                <div class="col-md-6 m-0">
                    <?php 
                    
                        //var_dump($codigopc);
                        include_once $dir_raiz."resumen/ariadna.php";
                        //var_dump($codigopc);
                        
                    ?>
                    <form id="form1" name="form1" method="post" action="<?php echo getCurrentUrlWithTabLegajo($tabValue, $legajo); ?>">
                        <input type="hidden" name="habilitar_edicion" id="habilitar_edicion" value="<?php echo $hab_ed; ?>"/>
                        <input name="ir" type="submit" class="btn <?php echo $hab_styl; ?>" style="width:200px; margin: 2px auto;" id="ir" value="<?php echo $hab_ed_tx; ?>" />
                    </form>
                </div>
            </div>
            <div class="col-12 p-3 m-0">
                <div class="col-md-4 float-start">
                    <?php 
                        include_once $dir_raiz."resumen/detalle.php";
                        include_once $dir_raiz."resumen/notas.php";
                    ?>
                </div>
<div class="custom-tab-container">
        <div class="custom-tab-content" style="margin-left: 10px;">
            <ul class="custom-nav-tabs" id="myTabs" style="margin: 0px;">
                <?php
                $currentTab = $tab;
                $sql = "SELECT * FROM $EsquemaSQL.inve_menus WHERE menu ='20' AND submenu ='$estado' ORDER BY id;";
                $result = sql_do_query($sql, '0', $usu);
                while ($row = sql_do_array($result)) {
                    $var1 = $row['ID'];
                    $var2 = $row['OPCION'];
                    $isActive = ($var1 == $currentTab) ? 'active' : '';
                    echo "<li class='custom-nav-item'>";
                    echo "<a class='custom-nav-link $isActive' id='$var1' name='$var1' href='".getCurrentUrlWithTab($var1, $codigopc)."' style='margin: 0px;'>$var2</a>";
                    echo "</li>";
                }
                ?>
           </ul>
            <div class="custom-tab-content-inner">
                <?php
                
               
                $sql31="SELECT * FROM $EsquemaSQL.inve_menus WHERE id='".$tab."';";
                $result31 = sql_do_query($sql31,'1',$usu);
                $row31=sql_do_array($result31);
                $direcion1=$row31['DIRECCION'];
                if (file_exists($direcion1)) {
                    include_once $direcion1;
                } else {
                    echo "Pestaña no encontrada.";
                }
                ?>
            </div>
        </div>
    </div>
                <br>
        </div>
    </div>
</div>