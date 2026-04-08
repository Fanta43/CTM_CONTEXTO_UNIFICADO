<?php
// Configuración inicial
//ini_set('display_errors', 'on');

// Asegúrate de que la función Enmascarar_post esté definida
$buscar = Enmascarar_post('buscar');

// Asegurarse de que $usu esté definido
if (!isset($usu)) {
    $usu = ''; // O asigna el valor que corresponda según tu lógica
}
?>

<div class="h-100 w-100">
    <div class="row flex-column align-items-start m-2">
        <strong class="fs-3">Ubicaciones</strong>
    </div>

    <div class="row flex-column m-2">
        <form id="form1" name="form1" method="post" action="">
            <input type="text" class="form-control" name="buscar" id="buscar" placeholder="Filtro de Búsqueda" onchange="submit();" value="<?php echo htmlspecialchars($buscar); ?>" />
        </form>
    </div>

    <div class="row flex-column m-2 overflow-auto" style="height: 100vh;">
        <table class="table table-bordered table-striped" style="background-color: white;">
            <thead>
                <tr>
                    <th>Ord</th>
                    <th>Id</th>
                    <th>Detalle</th>
                    <th>Estado</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Ajustar a tu forma de conexión y ejecución
                $sql = "SELECT UbicaId, UbicaDet, Ubica_Activa 
                          FROM artcelubi 
                         WHERE UPPER(UbicaDet) LIKE UPPER('{$buscar}%') 
                         ORDER BY Ubica_Activa DESC, UbicaDet";

                $result = sql_do_query($sql, '2', $usu); // Asegúrate que $usu esté definido
                $n = 0;
                // Se corrige el ciclo para recuperar cada fila
                while ($row = sql_do_array($result, '2', $usu)) {
                    $n++;
                    $img_estado = ($row['UBICA_ACTIVA'] == '1') ? 'si.gif' : 'no.gif';
                ?>
                <tr>
                    <td align="center"><?php echo $n; ?>&nbsp;</td>
                    <td align="center"><?php echo $row['UBICAID']; ?>&nbsp;</td>
                    <td align="left"><?php echo $row['UBICADET']; ?>&nbsp;</td>
                    <td align="center"><img src="img/ico/<?php echo $img_estado; ?>" width="30" height="30" /></td>
                    <td align="center">
                        <?php
                        $variables = array("ubicaid" => $row['UBICAID']);
                        $estados = array("37" => "Resumen");
                        echo impSelectHerramienta($variables, $estados, $n);
                        ?>
                    </td>
                </tr>
                <?php } // Fin del while ?>
            </tbody>
        </table>
    </div>
</div>
<br />
