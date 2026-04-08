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
    </style>
<?php
$buscar = Enmascarar_post('buscar');
$db = 2; // Definir explícitamente el db para que sea consistente

?>
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
                    <th width='30'>Ord</th>
                    <th width='200'>Usuario</th>
                    <th width='400'>Motivo de la asignación</th>
                    <th>Asignación en sistema</th>
                    <th width='200'>Opciones</th>
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
                      AND (
                          UPPER(nombre) LIKE UPPER('%" . addslashes($buscar) . "%')
                          OR UPPER(nacionalidad) LIKE UPPER('%" . addslashes($buscar) . "%')
                          OR UPPER(nivel) LIKE UPPER('%" . addslashes($buscar) . "%')
                      )
                    ORDER BY legajo;
                ";

                $result = sql_do_query($sql, '1', $usu);

                $n = 0;
                while ($row = sql_do_array($result)) {
                    $n++;

                    $a6 = obtener6A($row['LEGAJO'], $row['NIVEL'], '2', $usu);    
                    $b6 = obtener6B($row['NIVEL'], $row['PUNTAJE_ESENCIALIDAD']);
                    $c6 = obtener6C($row['LEGAJO']);
                    $d6 = obtener6D($row['NIVEL'], $row['PUNTAJE_ESENCIALIDAD']);
                    $e6 = obtener6E($row['LEGAJO'], $row['NIVEL'], '2', $usu);  
                    $f6 = obtener6F($row['NIVEL'], $row['PUNTAJE_ESENCIALIDAD']);

                    $tot6 = $a6['txt'].$b6['txt'].$c6['txt'].$d6['txt'].$e6['txt'].$f6['txt']; 
                    $a71 = obtenerAltaGama($tot6, $row['LEGAJO'], $row['NIVEL'], $row['PUNTAJE_DISPONIBILIDAD'], $row['AREA'], '2', $usu);
                    $a71_str = $a71 ? '('.$a71['tipo'].') '.$a71['txt'] : '';  
                    $a72 = obtenerGamaMedia($tot6, $a71);
                    $a72_str = $a72 ? '('.$a72['tipo'].') '.$a72['txt'] : ''; 
                    
                    

                    ?>
                    <tr>
                        <td align="center"><?php echo $n; ?></td>
                        <td align="center">
                        <?php 
                            echo "Legajo: ".$row['LEGAJO'] 
                                ."<br />Apellido y Nombres: ". $row['NOMBRE'] 
                                ."<br />Nacionalidad: ". $row['NACIONALIDAD'] 
                                ."<br />Categoría: ". $row['NIVEL'] 
                                ."<br />Puntaje del cargo: ". $row['PUNTAJE_EN_EL_CARGO'] 
                                ."<br />Disponibilidad: ". $row['PUNTAJE_DISPONIBILIDAD'] 
                                ."<br />Esencialidad: ". $row['PUNTAJE_ESENCIALIDAD']; 
                        ?>
                        </td>
                        <td>
                            <div class="motivo-item"><?= $a6['txt'] ?></div>
                            <div class="motivo-item"><?= $b6['txt'] ?></div>
                            <div class="motivo-item"><?= $c6['txt'] ?></div>
                            <div class="motivo-item"><?= $d6['txt'] ?></div>
                            <div class="motivo-item"><?= $e6['txt'] ?></div>
                            <div class="motivo-item"><?= $f6['txt'] ?></div>
                            <div class="motivo-item"><?= $a71_str ?></div>
                            <div class="motivo-item"><?= $a72_str ?></div>
                        </td> 
                        <td>
                            <div class="motivo-item">
                                <?php 
                                        $elementos = '';
                                        //echo "<h1>Equipos Morebius del Legajo:</h1>".$row['LEGAJO'];
                                        $equipos_morebius = obtenerEquiposMorebius($row['LEGAJO'], '2', $usu); 
                                        $elementos =  arrayToHtmlCards($equipos_morebius); 
                                        echo $elementos;
                                ?>
                            </div>
                        </td>
                        <td align="center">
                            <?php
                            $variables = array("legajo" => $row['LEGAJO']);
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