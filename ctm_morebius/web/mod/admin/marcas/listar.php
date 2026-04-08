<?php
// Configuración inicial
//ini_set('display_errors', 'on');

$buscar = Enmascarar_post('buscar');
?>

<div class="h-100 w-100">
    <div class="row flex-column align-items-start m-2">
        <strong class="fs-3">Marcas</strong>
    </div>

    <div class="row flex-column m-2">
        <form id="form1" name="form1" method="post" action="">
            <input type="text" class="form-control" name="buscar" id="buscar" placeholder="Filtro de Búsqueda" onchange="submit();" value="<?php echo $buscar; ?>" />
        </form>
    </div>

    <div class="row flex-column m-2 overflow-auto" style="height: 100vh;">
        <table class="table table-bordered table-striped" style="background-color: white;">
            <thead>
                <tr>
                    <th>Ord</th>
                    <th>Id</th>
                    <th>Detalle</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Ajusta a tu forma de conexión y ejecución
                // La búsqueda se realiza en el campo detmarca
                $sql = "
                    SELECT marcaid, detmarca 
                    FROM artimarcas
                    WHERE UPPER(detmarca) LIKE UPPER('$buscar%')
                    ORDER BY detmarca
                ";

                // Asegúrate de que $usu esté definido o reemplaza por tu variable/conexión concreta
                $result = sql_do_query($sql, '2', $usu);
                $n = 0;

                // Iteramos sobre los resultados
                while ($row = sql_do_array($result)) {
                    $n++;
                ?>
                <tr>
                    <td align="center"><?php echo $n; ?>&nbsp;</td>
                    <td align="center"><?php echo $row['MARCAID']; ?>&nbsp;</td>
                    <td align="left"><?php echo $row['DETMARCA']; ?>&nbsp;</td>
                    <td align="center">
                        <?php
                        // Ajusta las variables a enviar en la herramienta
                        $variables = array("marcaid" => $row['MARCAID']);
                        // Define las opciones o estados según tu necesidad
                        $estados = array("38" => "Editar");
                        echo impSelectHerramienta($variables, $estados, $n);
                        ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<br />
