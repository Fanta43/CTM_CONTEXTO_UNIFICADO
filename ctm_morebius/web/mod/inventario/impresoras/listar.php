<div class="h-100 w-100">
<?php
////ini_set( 'display_errors', 'on' );
/**
 * RECIBO LAS VARIABLES PARA OPERAR
**/

//var_dump($_SESSION);

$baja = Enmascarar_post('baja');
$buscar = Enmascarar_post('buscar');

?>
<div class="row flex-column align-items-start m-2">
    <strong class="fs-3">Impresoras</strong>
</div>
<div class="row flex-column m-2">
    <form id="form1" name="form1" method="post" action="">
        <input type="text" class="form-control" name="buscar" id="buscar" placeholder="Filtro de Busqueda" onchange="submit();" />
    </form>
</div>
<div class="row flex-column m-2 overflow-auto" style="height: 100vh;">
                    <table class="table table-bordered table-striped"  style="background-color: white;">
                    <thead>
                        <tr>
                            <th>Ord</th>
                            <th>Código</th>
                            <th>Numero Serie</th>
                            <th>Estado</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT a.impresoraid,
                                       a.codigo, 
                                       a.marca, 
                                       a.numeroserie,
                                       a.baja
                                  FROM more_impresoras a
                                 WHERE (UPPER(a.codigo) like UPPER('$buscar%') 
                                       OR UPPER(a.marca) like UPPER('$buscar%')
                                       OR UPPER(a.numeroserie) like UPPER('$buscar%'))
                              ORDER BY a.baja, a.impresoraid DESC";
                        //echo $sql;
                        $result = sql_do_query($sql, '1', $usu);
                        $n = 0;
                        while ($row = sql_do_array($result)) {
                            $n++;

                            if ($row['BAJA'] != '1') {
                                $img_estado = 'si.gif';
                            } else {
                                $img_estado = 'no.gif';
                            }
                        ?>
                            <tr>
                                <td align="center"><?php echo $n; ?>&nbsp;</td>
                                <td align="center"><?php echo $row['CODIGO']; ?>&nbsp;</td>
                                <td><?php echo $row['MARCA']; ?>&nbsp;</td>
                                <td align="center"><?php echo $row['NUMEROSERIE']; ?>&nbsp;</td>
                                <td align="center" width="30"><img src="img/ico/<?php echo $img_estado; ?>" width="30" height="30" /></td>
                                <td align="center">
                                    <?php
                                    $variables = array("impresoraid" => $row['IMPRESORAID']);
                                    $estados = array("28" => "Resumen");
                                    echo impSelectHerramienta($variables, $estados, $n);
                                    ?>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
<br />

