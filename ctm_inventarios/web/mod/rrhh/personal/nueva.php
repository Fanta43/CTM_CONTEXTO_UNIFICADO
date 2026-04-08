<?php
//ini_set( 'display_errors', 'on' );
include('cargador.php');
?>

<style type="text/css">

#area #form1 .fondo-II img {
	margin: 5px;
}

</style>

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
            <strong style="font-size: 25px;">Crear nuevo item de inventario</strong>
        </div>
    <br />

    <div class="separar1 p-0 float-start bg-white">
                <div class="p-3">
                        <form id="form1" name="form1" method="post" action="<?php echo getCurrentUrlWithTab($tabValue, $codigopc); ?>">
                        <div class="col-12">
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Codigo Pc</label>
                                                <input type="text" class="form-control fs-12" name="codigopc"' id="codigopc" value="<?php echo $rowhanterior['CODIGOPC']; ?>" placeholder="Codigo Pc">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Fecha de Compra</label>
                                                <input type="date" class="form-control fs-12" name="fechacompra" id="fechacompra" value="<?php echo enmascararFecha($rowhanterior['FECHACOMPRA']); ?>" placeholder="Fecha Compra">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >SR</label>
                                                <input type="text" class="form-control fs-12" name="sr" id="sr" value="<?php echo $rowhanterior['SR']; ?>" placeholder="SR">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >OC</label>
                                                <input type="text" class="form-control fs-12" name="oc" id="oc" value="<?php echo $rowhanterior['OC']; ?>" placeholder="OC">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label m-0">Monto</label>
                                        <input type="text" class="form-control fs-12" name="monto" id="monto" value="<?php echo $rowhanterior['MONTO']; ?>" placeholder="Monto">
                                    </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                        <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Vto de Garantia</label>
                                                <input type="date" class="form-control fs-12" name="fechavtogarantia" id="fechavtogarantia" value="<?php echo enmascararFecha($rowhanterior['FECHAVTOGARANTIA']); ?>" placeholder="Vto de Garantia">
                                        </div>
                                </div>
                                <div class="col-3 float-start p-1">
                                    <div class="mb-3">
                                        <label for="ubicacionSelect" class="form-label m-0">Ubicación</label>
                                        <select class="form-control" name="centrocosto" id="centrocosto">
                                            <?php
                                            $sql = "SELECT a.conidcc, a.connomcc FROM vw_accc a;";
                                            $result = sql_do_query($sql, '2', $usu);
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
                                                <label for="exampleFormControlInput1" class="form-label m-0" >Legajo</label>
                                                <input type="text" class="form-control fs-12" name="legajo" id="legajo" value="<?php echo $rowhanterior['LEGAJO']; ?>" placeholder="Legajo">
                                        </div>
                                </div>
                                <?php
                                if($_SESSION['HABILITA_EDICION'] == 'SI'){
                                ?>
                                    <div class="col-12 float-start p-1" style="display: flex; justify-content: center; align-items: center;">
                                        <div class="mb-3" style="width: 100%; display: flex; justify-content: right;">
                                            <input name="obligatorio_insert" id="obligatorio_insert" type="submit" class="btn btn-pna-oscuro" style="width: 200px;" value="Guardar" />
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>
                                
                         </div>
                            
                        </form>
          </div>
    </div>
</div>
<br />