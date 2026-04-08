<?php
// Configuración inicial
//ini_set('display_errors', 'on');
$buscar = Enmascarar_post('buscar');
?>
<style>
    :root {
        --color-fondo: #f5f5f5;
        --color-contenedor: #fff;
        --color-texto: #333;
        --color-borde: #ccc;
        --color-primario: #3498db;
        --color-primario-hover: #2980b9;
        --color-subtexto: #555;
        --color-descripcion: #888;
        --color-input-focus: #66afe9;
    }
    body {
        margin: 0;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        background: var(--color-fondo);
        color: var(--color-texto);
        line-height: 1.6;
    }
    .container {
        max-width: 800px;
        margin: 40px auto;
        background: var(--color-contenedor);
        border-radius: 8px;
        padding: 30px 40px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    h1.page-title {
        text-align: center;
        margin-bottom: 30px;
        font-size: 1.8em;
    }
    .form-group {
        margin-bottom: 20px;
    }
    label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
        color: var(--color-subtexto);
    }
    input[type="file"] {
        display: block;
        width: 100%;
        padding: 10px;
        border: 1px solid var(--color-borde);
        border-radius: 4px;
        font-size: 1em;
        box-sizing: border-box;
    }
    input[type="file"]:focus {
        border-color: var(--color-input-focus);
        outline: none;
    }
    .btn {
        display: block;
        width: 100%;
        background: var(--color-primario);
        color: #fff;
        padding: 12px;
        border: none;
        border-radius: 4px;
        font-size: 1.1em;
        cursor: pointer;
        text-align: center;
        transition: background 0.3s ease;
        margin-top: 20px;
    }
    .btn:hover {
        background: var(--color-primario-hover);
    }
    .message-ok {
        color: green;
        font-weight: bold;
        margin-top: 15px;
    }
    .message-error {
        color: red;
        font-weight: bold;
        margin-top: 15px;
    }
    .message-error ul {
        margin-top: 10px;
        padding-left: 20px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30px;
        font-size: 0.9em;
    }
    th, td {
        border: 1px solid var(--color-borde);
        padding: 10px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
        font-weight: bold;
    }
    .fecha-vencida {
        background: #FF9187 !important;
        color: white !important;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
    }
    .fecha-verde {
        background:rgb(73, 250, 87) !important;
        color: white !important;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
    }
    .fecha-media {
        background:rgb(237, 253, 3) !important;
        color: black !important;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
    }
</style>

<div class="h-100 w-100">
    <div class="row flex-column align-items-start m-2">
        <strong class="fs-3">Consulta Externa</strong>
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
                    <th rowspan="2" width="30">#</th>
                    <th colspan="7">Datos del Usuario</th>
                    <th colspan="2">Reglamento de Celulares</th>
                    <th colspan="5">Registro de entrega Morebius</th>
                    <th colspan="1" rowspan="2">Alertas</th>
                </tr>
                <tr>
                    <th width="50">Legajo</th>
                    <th width="200">Apellido y Nombre</th>
                    <th width="200">Email</th>
                    <th width="50">Cat.</th>
                    <th width="50">Punt.</th>
                    <th width="50">Disp.</th>
                    <th width="50">Esen.</th>
                    <th width="50">Motivo</th>
                    <th width="100">Gama</th>
                    <th width="100">Fecha</th>
                    <th width="100">Tipo</th>
                    <th width="100">Detalle</th>
                    <th width="100">Nro Art</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $buscar = isset($_POST['buscar']) ? trim($_POST['buscar']) : '';
                $sql = "
                    SELECT legajo, 
                           nacionalidad, 
                           nombre, 
                           nivel, 
                           area, 
                           puntaje_en_el_cargo, 
                           puntaje_esencialidad, 
                           puntaje_disponibilidad
                    FROM more_consulta_externa
                    WHERE legajo <> '0'
                       AND (UPPER(nombre) LIKE UPPER('" . addslashes($buscar) . "%')
                       OR UPPER(nacionalidad) LIKE UPPER('" . addslashes($buscar) . "%')
                       OR UPPER(nivel) LIKE UPPER('" . addslashes($buscar) . "%'))
                    ORDER BY legajo;
                ";

                $result = sql_do_query($sql, '1', $usu);
                $n = 0;

                while ($row = sql_do_array($result)) {
                    $n++;
                    $parametros_alertas = $row;
                    $parametros_alertas['a6']= $a6 = obtener6A($row['LEGAJO'], $row['NIVEL'], '2', $usu);    
                    $parametros_alertas['b6']= $b6 = obtener6B($row['NIVEL'], $row['PUNTAJE_ESENCIALIDAD']);
                    $parametros_alertas['c6']= $c6 = obtener6C($row['LEGAJO']);
                    $parametros_alertas['d6']= $d6 = obtener6D($row['NIVEL'], $row['PUNTAJE_ESENCIALIDAD']);
                    $parametros_alertas['e6']= $e6 = obtener6E($row['LEGAJO'], $row['NIVEL'], '2', $usu);  
                    $parametros_alertas['f6']= $f6 = obtener6F($row['NIVEL'], $row['PUNTAJE_ESENCIALIDAD']);
                    $parametros_alertas['tot6'] = $tot6 = $a6['txt'].$b6['txt'].$c6['txt'].$d6['txt'].$e6['txt'].$f6['txt']; 
                    $parametros_alertas['a71'] = $a71 = obtenerAltaGama($tot6, $row['LEGAJO'], $row['NIVEL'], $row['PUNTAJE_DISPONIBILIDAD'], $row['AREA'], $db, $usu);
                    $parametros_alertas['a71_str'] = $a71_str = $a71 ? $a71['tipo'] : '';
                    $parametros_alertas['a72'] = $a72 = obtenerGamaMedia($tot6, $a71);
                    $parametros_alertas['a72_str'] = $a72_str = $a72 ? $a72['tipo'] : '';
                    $parametros_alertas['datos_morebius'] = $datos_morebius = obtenerEquiposMorebius($row['LEGAJO'], '2', $usu);
                    $parametros_alertas['usuarioPlantel'] = $usuarioPlantel = obtenerUsuarioPlantel($row['LEGAJO'], '2', $usu);
                    $parametros_alertas['dias_desde_entrega'] = $dias_desde_entrega = diasEntreFechas(date("Y-m-d"), $datos_morebius[$m]['FECHA']);
                    $parametros_alertas['morebiusGama'] = $morebiusGama = morebiusGama($datos_morebius[0]['DETALLE'], $datos_morebius[0]['FECHA'], '1', $usu);


                    $alertas = alertas($parametros_alertas);

                    //var_dump($datos_morebius);
                    //echo "<br>";

                    // Corregido: recorre correctamente los registros de morebius
                    $cat_registros = isset($datos_morebius['cat_registros']) ? intval($datos_morebius['cat_registros']) : 0;
                    if ($cat_registros > 0) {
                        for ($m = 0; $m < $cat_registros; $m++) {
                            echo '<tr>';
                            if ($m == 0) {
                                // Solo en la primera fila se imprimen los datos con rowspan
                                ?>
                                <td rowspan="<?php echo $cat_registros; ?>" align="center"><?php echo $n; ?></td>
                                <td rowspan="<?php echo $cat_registros; ?>" align="center"><?php echo htmlspecialchars($row['LEGAJO']); ?></td>
                                <td rowspan="<?php echo $cat_registros; ?>" align="center"><?php echo htmlspecialchars($usuarioPlantel['EMPNOMBRE']); ?></td>
                                <td rowspan="<?php echo $cat_registros; ?>" align="center"><?php echo htmlspecialchars($usuarioPlantel['EMPEMAIL']); ?></td>
                                <td rowspan="<?php echo $cat_registros; ?>" align="center"><?php echo htmlspecialchars($row['NIVEL']); ?></td>
                                <td rowspan="<?php echo $cat_registros; ?>" align="center"><?php echo htmlspecialchars($row['PUNTAJE_EN_EL_CARGO']); ?></td>
                                <td rowspan="<?php echo $cat_registros; ?>" align="center"><?php echo htmlspecialchars($row['PUNTAJE_DISPONIBILIDAD']); ?></td>
                                <td rowspan="<?php echo $cat_registros; ?>" align="center"><?php echo htmlspecialchars($row['PUNTAJE_ESENCIALIDAD']); ?></td>
                                <td rowspan="<?php echo $cat_registros; ?>">
                                    <p title="<?php echo $a6['txt']; ?>"><?php echo $a6['img']; ?></p>
                                    <p title="<?php echo $b6['txt']; ?>"><?php echo $b6['img']; ?></p>
                                    <p title="<?php echo $c6['txt']; ?>"><?php echo $c6['img']; ?></p>
                                    <p title="<?php echo $d6['txt']; ?>"><?php echo $d6['img']; ?></p>
                                    <p title="<?php echo $e6['txt']; ?>"><?php echo $e6['img']; ?></p>
                                    <p title="<?php echo $f6['txt']; ?>"><?php echo $f6['img']; ?></p>
                                </td>
                                <td rowspan="<?php echo $cat_registros; ?>" align="center">
                                    <div class="motivo-item" title="<?= $a71['txt'].$a72['txt'] ?>">
                                        <?php 
                                            echo $a71_str;
                                            echo $a72_str;
                                        ?>
                                    </div>
                                </td>
                                <?php
                            }
                            ?>
                            <?php
                            $dias = diasEntreFechas(date("Y-m-d"), $datos_morebius[$m]['FECHA']);
                            if ($dias > 365*3) {
                                $clase_fecha = 'fecha-vencida';
                                $tooltip = 'Pasaron mas de 3 años desde la entrega en dias: '. $dias;
                            } elseif ($dias < 365*2) {
                                $clase_fecha = 'fecha-verde';
                                $tooltip = 'El equipo esta dentro de los 2 años';
                            } else {
                                $clase_fecha = 'fecha-media';
                                $tooltip = 'Pasaron mas de 2 años desde la entrega en dias: '. $dias;
                            }

                            $tipo_articulo = obtenerTipoArticulo($datos_morebius[$m]['TIPO_ART'], '2', $usu);
                            $tipo_celular = obtenerTipoCelular($datos_morebius[$m]['TIPCEL'], '2', $usu);
                            ?>
                            <td <?php if($tipo_celular['TIPCEL']=='2' or $tipo_articulo['TIPCEL']=='2') echo 'class="'.$clase_fecha.'"'; ?> title="<?= $tooltip ?>">
                                <div class="motivo-item">
                                    <?php 
                                        echo htmlspecialchars(enmascararFecha($datos_morebius[$m]['FECHA']) ?? ''); 
                                    ?>
                                </div>
                            </td>
                            <td>
                                <div class="motivo-item"><?php 
                                                            echo htmlspecialchars($tipo_articulo['DETALLE'] ?? '');
                                                            if($tipo_celular){
                                                                echo "<br />(".htmlspecialchars($tipo_celular['TIPCELDET'] ?? '').")";
                                                            }
                                                        ?>
                                </div>
                            </td>
                            <td>
                                <div class="motivo-item"><?php echo 'Detalle: '.htmlspecialchars($datos_morebius[$m]['DETALLE'] ?? '').'<br />Referencia:'.htmlspecialchars($datos_morebius[$m]['REFERENCIA'] ?? ''); ?></div>
                            </td>
                            <td>
                                <div class="motivo-item"><?php echo htmlspecialchars($datos_morebius[$m]['NRO_ART'] ?? ''); ?></div>
                            </td>
                            <td>
                                <div class="motivo-item">
                                    <?php 
                                        //echo $morebiusGama['descripcion']."<br />";
                                        //echo $morebiusGama['anio']."<br />";
                                        //echo $morebiusGama['codigo']."<br />";
                                        //echo $morebiusGama['nombre']."<br />";
                                        echo $morebiusGama['gama']."<br />";
                                    ?>
                                 </div>
                            </td>
                            <td><?php echo $alertas; ?></td>
                            <?php
                            echo '</tr>';
                        }
                    } else {
                        // Si no hay registros morebius, imprime una fila vacía
                        ?>
                        <tr>
                            <td align="center"><?php echo $n; ?></td>
                            <td align="center"><?php echo htmlspecialchars($row['LEGAJO']); ?></td>
                            <td align="center"><?php echo htmlspecialchars($usuarioPlantel['EMPNOMBRE']);  ?></td>
                            <td align="center"><?php echo htmlspecialchars($usuarioPlantel['EMPEMAIL']); ?></td>
                            <td align="center"><?php echo htmlspecialchars($row['NIVEL']); ?></td>
                            <td align="center"><?php echo htmlspecialchars($row['PUNTAJE_EN_EL_CARGO']); ?></td>
                            <td align="center"><?php echo htmlspecialchars($row['PUNTAJE_DISPONIBILIDAD']); ?></td>
                            <td align="center"><?php echo htmlspecialchars($row['PUNTAJE_ESENCIALIDAD']); ?></td>
                            <td>
                                <div class="motivo-item"><?= $a6['img'] ?></div>
                                <div class="motivo-item"><?= $b6['img'] ?></div>
                                <div class="motivo-item"><?= $c6['img'] ?></div>
                                <div class="motivo-item"><?= $d6['img'] ?></div>
                                <div class="motivo-item"><?= $e6['img'] ?></div>
                                <div class="motivo-item"><?= $f6['img'] ?></div>
                            </td>
                            <td align="center">
                                <div class="motivo-item">
                                    <?php 
                                        echo $a71_str;
                                        echo $a72_str;
                                    ?>
                                </div>
                            </td>
                            <td align="center"></td>
                            <td align="center"></td>
                            <td align="center"></td>
                            <td align="center"></td>
                            <td align="center"></td>
                            <td align="center"><?php echo $alertas; ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<br />