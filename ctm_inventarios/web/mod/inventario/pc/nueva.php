<?php
//ini_set( 'display_errors', 'on' );
include('cargador.php');
?>

<style type="text/css">

#area #form1 .fondo-II img {
	margin: 5px;
}

</style>

<?php

$sql = "SELECT id_tabla FROM inve_id WHERE tabla = 'INVE_PC';";
$row = sql_do_array(sql_do_query($sql, '1', $usu));

$primerid = $row['ID_TABLA'];
$codigopc = "P".$primerid;

?>


<div class="area"><br />
    <div class="col-12">
        <div class="col-12 p-2 pb-0">
            <div class="col-12 row m-0 ">
                <div class="col-md-6 m-0">
                    <?php 
                        include_once $dir_raiz."resumen/ariadna.php";
                    ?>
                </div>
            </div>
            
    <div class='titulo_mediano_center_rojo'><?php echo $mensaje;?></div>
        <div class="row flex-column align-items-start" style="height: 40px;">
            <strong style="font-size: 25px;">Crear nuevo/s item/s de inventario</strong>
        </div>
    <br />
     <form id="form1" name="form1" method="post" action="<?php echo getCurrentUrlWithTab($tabValue, $codigopc); ?>">
    <div class="row flex-column align-items-start bg-white">
                <div class="p-3">
                       
                            <div class="row">
                                <div class="col-sm-6 col-md-3 p-1 d-flex justify-content-center align-items-center" style="height: 100px;">
                                    <div>
                                        <input type="checkbox" class="form-check-input" name="varias_si" id="varias_si" value="SI">
                                        <label for="varias_si" class=" m-0">Varias</label>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-3 p-1">
                                    <div class="mb-3">
                                        <label for="varias_numeros" class=" m-0">Numero de equipos</label>
                                        <input type="text" class="form-control" name="varias_numeros" id="varias_numeros" value="" placeholder="Cantidad de equipos">
                                    </div>
                                </div>
                            </div>
 
          </div>
    </div>
    
    <br />
    <div class="row flex-column align-items-start bg-white">
                <div class="p-3">
                        <div class="col-12">
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Codigo Pc</label>
                                                <input type="text" class="form-control fs-12" name="codigopc"' id="codigopc" value="<?php echo $codigopc; ?>" placeholder="Codigo Pc">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Fecha de Compra</label>
                                                <input type="date" class="form-control fs-12" name="fechacompra" id="fechacompra" value="<?php echo $hoy_completo; ?>" placeholder="Fecha Compra">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >SR</label>
                                                <input type="text" class="form-control fs-12" name="sr" id="sr" value="" placeholder="SR">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >OC</label>
                                                <input type="text" class="form-control fs-12" name="oc" id="oc" value="" placeholder="OC">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class=" m-0">Monto</label>
                                        <input type="text" class="form-control fs-12" name="monto" id="monto" value="" placeholder="Monto">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Vto de Garantia</label>
                                                <input type="date" class="form-control fs-12" name="fechavtogarantia" id="fechavtogarantia" value="<?php echo enmascararFecha($rowhanterior['FECHAVTOGARANTIA']); ?>" placeholder="Vto de Garantia">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="ubicacionSelect" class=" m-0">Ubicación</label>
                                        <select class="form-control" name="centrocosto" id="centrocosto">
                                            <?php
                                            $sql = "SELECT a.conidcc, a.connomcc FROM vw_accc a ORDER BY a.connomcc;";
                                            $result = sql_do_query($sql, '1', $usu);
                                            while ($row = sql_do_array($result)) {
                                                if($row['CONIDCC'] == $rowhanterior['FECHAVTOGARANTIA']){$sele = ' selected = selected ';}else{$sele = '';}
                                                echo '<option '.$sele.' value="' . htmlspecialchars($row['CONIDCC']) . '">' . htmlspecialchars($row['CONNOMCC']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div> 
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class=" m-0" >Legajo</label>
                                                <input type="text" class="form-control fs-12" name="legajo" id="legajo" value="<?php echo $rowhanterior['LEGAJO']; ?>" placeholder="Legajo">
                                        </div>
                                </div>

                                    <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                        <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                            <input name="obligatorio_insert" id="obligatorio_insert" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                        </div>
                                    </div>                      
                         </div>
                            
                        </form>
          </div>
    </div>
    
  </div>
<br />