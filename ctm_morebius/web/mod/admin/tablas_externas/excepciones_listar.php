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
        <strong class="fs-3">Personal Exceptuado</strong>
    </div>

    <div class="row flex-column m-2">
        <form id="form1" name="form1" method="post" action="">
            <input type="text" class="form-control" name="buscar" id="buscar" placeholder="Buscar por apellido o legajo" onchange="submit();" value="<?php echo htmlspecialchars($buscar); ?>" />
        </form>
    </div>

    <div class="row flex-column m-2 overflow-auto" style="height: 100vh;">
        <table class="table table-bordered table-striped" style="background-color: white;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Legajo</th>
                    <th>Cargo</th>
                    <th>Centro Costo</th>
                    <th>Antecedentes</th>
                    <th>Tipo Artículo</th>
                    <th>Tipo Equipo</th>
                    <th>Operaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = " SELECT excepcionesid, 
                                legajo, 
                                empcargocod, 
                                centrocosto, 
                                antecedentes, 
                                tipo_articuloid, 
                                tipo_equipoid, 
                                estadoid, 
                                auditoria_usu, 
                                auditoria_fecha 
                           FROM more_personal_exceptuado 
                          WHERE estadoid = 1 
                            AND legajo LIKE '{$buscar}%'
                       ORDER BY legajo";

                $result = sql_do_query($sql, '1', $usu);
                $n = 0;
                while ($row = sql_do_array($result, '1', $usu)) {
                    $n++;
                ?>
                <tr>
                    <td align="center"><?php echo $n; ?></td>
                    <td align="center"><?php echo $row['LEGAJO']; ?></td>
                    <td align="left"><?php echo $row['EMPCARGOCOD']; ?></td>
                    <td align="left"><?php echo $row['CENTROCOSTO']; ?></td>
                    <td align="left"><?php echo $row['ANTECEDENTES']; ?></td>
                    <td align="center"><?php echo $row['TIPO_ARTICULOID']; ?></td>
                    <td align="center"><?php echo $row['TIPO_EQUIPOID']; ?></td>
                    <td align="center">
                        <?php
                        $variables = array("excepcionesid" => $row['EXCEPCIONESID']);
                        $estados = array("391" => "Editar"); 
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
