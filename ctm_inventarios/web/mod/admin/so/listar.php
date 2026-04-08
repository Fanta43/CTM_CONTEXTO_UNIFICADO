<div class="h-100 w-100">
    <?php
    // Configuraciones iniciales
    //ini_set( 'display_errors', 'on' );

    /**
     * RECIBO LAS VARIABLES PARA OPERAR
    **/
    // Aquí tu función Enmascarar_post o una alternativa segura
    $buscar = Enmascarar_post('buscar');

    ?>
    <div class="row flex-column align-items-start m-2">
        <strong class="fs-3">Sistemas Operativos</strong>
    </div>

    <div class="row flex-column m-2">
        <form id="form1" name="form1" method="post" action="">
            <input type="text" class="form-control" name="buscar" id="buscar" placeholder="Filtro de Búsqueda" onchange="submit();" />
        </form>
    </div>

    <div class="row flex-column m-2 overflow-auto" style="height: 100vh;">
        <table class="table table-bordered table-striped" style="background-color: white;">
            <thead>
                <tr>
                    <th>Ord</th>
                    <th>Id</th>
                    <th>Sistema Operativo</th>
                    <th>Estado</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = " SELECT sistemaoperativoid, 
                                sistema, 
                                estadoid, 
                                auditoria_usu, 
                                auditoria_fecha 
                           FROM inve_sistemaoperativo 
                          WHERE (UPPER(sistema) like UPPER('$buscar%') 
                             OR UPPER(auditoria_usu) like UPPER('$buscar%'))
                       ORDER BY estadoid DESC, sistema;";
                // Aquí tu función para ejecutar la consulta SQL
                //echo $sql;
                $result = sql_do_query($sql, '1', $usu);
                $n = 0;
                while ($row = sql_do_array($result)) {
                    $n++;

                    // Lógica para determinar el estado
                    if ($row['ESTADOID'] == '1') {
                        $img_estado = 'si.gif';
                    } else {
                        $img_estado = 'no.gif';
                    }
                ?>
                    <tr>
                        <td align="center"><?php echo $n; ?>&nbsp;</td>
                        <td align="center"><?php echo $row['SISTEMAOPERATIVOID']; ?>&nbsp;</td>
                        <td align="left"><?php echo $row['SISTEMA']; ?>&nbsp;</td>
                        <td align="center"><img src="img/ico/<?php echo $img_estado; ?>" width="30" height="30" /></td>
                        <td align="center">
                            <?php
                                        $variables = array("soid" => $row['SISTEMAOPERATIVOID']);
                                        $estados = array("37" => "Resumen");
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