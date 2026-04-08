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
    <strong class="fs-3">Monitores</strong>
</div>
<div class="row flex-column m-2">
    <form id="form1" name="form1" method="post" action="">
        <input type="text" class="form-control" name="buscar" id="buscar" placeholder="Filtro de Busqueda" onchange="submit();" />
    </form>
</div>
<div class="row flex-column m-2 overflow-auto" style="height: 100vh;">
                    <table class="table table-bordered table-striped" style="background-color: white;">
                        <thead>
                            <tr>
                                <th>Ord</th>
                                <th>Código</th>
                                <th>Marca</th>
                                <th>Número de Serie</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT a.monitorid, 
                                           a.codigo, 
                                           a.marca, 
                                           a.numeroserie, 
                                           a.descripcion,
                                           a.estadoid,
                                           a.baja
                                      FROM more_monitores a
                                     WHERE (UPPER(a.codigo) LIKE UPPER('$buscar%') 
                                            OR UPPER(a.monitorid) LIKE UPPER('$buscar%')
                                            OR UPPER(a.marca) LIKE UPPER('$buscar%')
                                            OR UPPER(a.numeroserie) LIKE UPPER('$buscar%')
                                            OR UPPER(a.descripcion) LIKE UPPER('$buscar%'))
                                  ORDER BY a.monitorid DESC";
                            //echo $sql;
                            $result = sql_do_query($sql, '1', $usu);
                            $n = 0;
                            while ($row = sql_do_array($result)) {
                                $n++;
                                
                            if ($row['ESTADOID'] == '1') {
                                $img_estado = 'si.gif';
                            } else {
                                $img_estado = 'no.gif';
                            }
                            ?>
                                <tr>
                                    <td align="center"><?php echo $n; ?></td>
                                    <td align="center"><?php echo $row['CODIGO']; ?></td>
                                    <td><?php echo $row['MARCA']; ?></td>
                                    <td><?php echo $row['NUMEROSERIE']; ?></td>
                                    <td><?php echo $row['DESCRIPCION']; ?></td>
                                    <td align="center" width="30"><img src="img/ico/<?php echo $img_estado; ?>" width="30" height="30" /></td>
                                    <td align="center">
                                        <?php
                                        $variables = array("monitorid" => $row['MONITORID']);
                                        $estados = array("27" => "Resumen");
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

