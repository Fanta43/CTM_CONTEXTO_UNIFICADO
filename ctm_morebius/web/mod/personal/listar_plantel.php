<?php
// Configuración inicial
//ini_set('display_errors', 'on');
session_start();
$usu = isset($_SESSION['usu']) ? $_SESSION['usu'] : '99999999';

$data_json = file_get_contents('web/int/ajax/listar_plantel.json');
$data = json_decode($data_json, true);
//$data = construirPlantel('2', $usu);

?>
<style>
.custom-tooltip {
    position: relative;
    cursor: pointer;
}
.custom-tooltip .tooltip-text {
    visibility: hidden;
    width: 260px;
    background-color: #333;
    color: #fff;
    text-align: left;
    border-radius: 6px;
    padding: 10px;
    position: absolute;
    z-index: 9999;
    bottom: 125%;
    left: 50%;
    margin-left: -130px;
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 0.95em;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    white-space: pre-line;
}
.custom-tooltip:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}

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
    <div class="row flex-column align-items-center mt-4 mb-4">
        <h1 class="page-title">Distribuci&oacute;n de equipamiento en el plantel</h1>
    </div>
    <div class="row flex-column m-2 overflow-auto" style="height: 100vh;">
        <table class="table table-bordered table-striped" style="background-color: white;">
            <thead>
                <tr>
                    <th rowspan="2" width="30">#</th>
                    <th colspan="8">Datos del Usuario</th>
                    <th colspan="3">Celulares</th>
                    <th colspan="2">Notebook</th>
                    <th colspan="2">PC</th>
                    <th rowspan="2" width="250">Operaciones</th>
                </tr>
                <tr>
                    <th>Legajo</th>
                    <th>Apellido y Nombre</th>
                    <th>Email</th>
                    <th width="60">Cate.</th>
                    <th width="60">CCos.</th>
                    <th width="60">Carg.</th>
                    <th width="60">Disp.</th>
                    <th width="60">Esen.</th>
                    <th width="70">Equipo</th>
                    <th width="70">Linea</th>
                    <th width="70">Alerta</th>
                    <th width="70">Equipo</th>
                    <th width="70">Alerta</th>
                    <th width="70">Equipo</th>
                    <th width="70">Alerta</th>
                </tr>
            </thead>
            <tbody>
                <?php
                //$datos = json_decode($data, true);
                $datos = $data;
                $n = 0;
                if (is_array($datos)) {
                    foreach ($datos as $row) {
                        $row = array_change_key_case($row, CASE_UPPER);
                        $n++;

                        /*
                        if($row['EMPLEGAJO']=='10433' ){
                            var_dump($row);
                        }
                        */
                    ?>
                        <tr>
                            <td align="center"><?php echo $n; ?></td>
                            <td align="center"><?php echo htmlspecialchars($row['EMPLEGAJO'] ?? ''); ?></td>
                            <td align="center"><?php echo htmlspecialchars($row['EMPNOMBRE'] ?? ''); ?></td>
                            <td align="center"><?php echo htmlspecialchars($row['EMPEMAIL'] ?? ''); ?></td>
                            <td align="center"><?php echo htmlspecialchars($row['EMPCATEGORIA'] ?? ''); ?></td>
                            <td align="center" title= "<?php echo htmlspecialchars($row['CENTROCOSTONOM'] ?? ''); ?>"><?php echo htmlspecialchars($row['CENTROCOSTO'] ?? ''); ?></td>
                            <td align="center" title= "<?php echo htmlspecialchars($row['CARGONOM'] ?? ''); ?>"><?php echo htmlspecialchars($row['EMPCARGOCOD'] ?? ''); ?></td>
                            <td align="center"><?php echo htmlspecialchars($row['EXTRAS']['PUNTAJE_DISPONIBILIDAD'] ?? ''); ?></td>
                            <td align="center"><?php echo htmlspecialchars($row['EXTRAS']['PUNTAJE_ESENCIALIDAD'] ?? ''); ?></td>
                    <?php
                            // Primer celular
                            $nro_art_cel = '';
                            if (isset($row['CELULARES'][0]['NRO_ART'])) {
                                $nro_art_cel = $row['CELULARES'][0]['NRO_ART'];
                                $nro_art_cel_titulo = 'Linea: '.$row['CELULARES'][0]['NRO_LINEA'].'<br />Detalle: '.$row['CELULARES'][0]['DETALLE'].'<br /> Referencia: '.$row['CELULARES'][0]['REFERENCIA'].'<br /> Fecha: '.enmascararFecha($row['CELULARES'][0]['FECHA']);
                            }else{
                                $nro_art_cel_titulo = '';
                                $nro_art_cel = '';
                            }
                            // Primer línea
                            $nro_art_linea = '';
                            if (isset($row['LINEAS'][0]['NRO_ART'])) {
                                $nro_art_linea = $row['LINEAS'][0]['NRO_ART'];
                                $nro_art_linea_titulo =  'Linea: '.$row['LINEAS'][0]['NRO_LINEA'].'<br />Detalle: '.$row['LINEAS'][0]['DETALLE'].'<br /> Referencia: '.$row['LINEAS'][0]['REFERENCIA'].'<br /> Fecha: '.enmascararFecha($row['LINEAS'][0]['FECHA']);
                            }else{
                                $nro_art_linea_titulo = '';
                                $nro_art_linea = '';
                            }
                            // Primer notebook
                            $nro_art_notebook = '';
                            if (isset($row['NOTEBOOKS'][0]['NRO_ART'])) {
                                $nro_art_notebook = $row['NOTEBOOKS'][0]['NRO_ART'];
                                $nro_art_notebook_titulo = 'Detalle: '.$row['NOTEBOOKS'][0]['DETALLE'].'<br /> Referencia: '.$row['NOTEBOOKS'][0]['REFERENCIA'].'<br /> Fecha: '.enmascararFecha($row['NOTEBOOKS'][0]['FECHA']);
                            }else{
                                $nro_art_notebook_titulo = '';
                                $nro_art_notebook = '';
                            }
                            ?>
                            <td class="custom-tooltip" align="center">
                                <?php echo htmlspecialchars($nro_art_cel); ?>
                                <?php if (!empty($nro_art_cel_titulo)) { ?>
                                    <span class="tooltip-text"><?php echo $nro_art_cel_titulo; ?></span>
                                <?php } ?>
                            </td>
                            <td class="custom-tooltip" align="center">
                                <?php echo htmlspecialchars($nro_art_linea); ?>
                                <?php if (!empty($nro_art_linea_titulo)) { ?>
                                    <span class="tooltip-text"><?php echo $nro_art_linea_titulo; ?></span>
                                <?php } ?>
                            </td>
                            <td align="center"></td>
                            <td class="custom-tooltip" align="center">
                                <?php echo htmlspecialchars($nro_art_notebook); ?>
                                <?php if (!empty($nro_art_notebook_titulo)) { ?>
                                    <span class="tooltip-text"><?php echo $nro_art_notebook_titulo; ?></span>
                                <?php } ?>
                            </td>
                            <td align="center"></td>
                            <td align="center">
                                <?php
                                if (isset($row['PC']) && is_array($row['PC'])) {
                                    $equipos = [];
                                    foreach ($row['PC'] as $item) {
                                        if (is_array($item) && isset($item['EQUIPO'])) {
                                            $equipos[] = $item['EQUIPO'];
                                        }
                                    }
                                    echo implode(' ', $equipos);
                                }
                                ?>
                            </td>
                            <td align="center"></td>
                            <td align="center">
                            <?php
                            $variables = array("legajo" => $row['EMPLEGAJO']);
                            $estados = array("26" => "Resumen", "261" => "Editar");
                            echo impSelectHerramienta($variables, $estados, $n);
                            ?>
                        </td>
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
<div id="ajax-result"></div>
<script>

$(document).ready(function() {
    $.ajax({
        url: 'web/int/ajax/aj_listar_plantel.php',
        type: 'GET',
        data: { usu: '<?php echo $usu ?? "99999999"; ?>' },
        success: function(data) {
            $('#ajax-result').html('<pre>' + data + '</pre>'); // Muestra la respuesta del PHP
            //location.reload(); // COMENTA esta línea para evitar el bucle
        },
        error: function(xhr, status, error) {
            $('#ajax-result').html('<b style="color:red">Error AJAX:</b> ' + error);
            console.log("Hubo un error al ejecutar la función construir_listar_plantel en segundo plano: ", error);
        }
    });
});
</script>