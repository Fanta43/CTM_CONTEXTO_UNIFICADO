<?php
//ini_set('display_errors', '1');
//error_reporting(E_ALL);

///echo "<h1>Actualizando datos de consulta externa (CSV)</h1>";

// -----------------------------------------------------------
// CONFIGURACIÓN
// -----------------------------------------------------------
$TABLE = 'more_consulta_externa';
$COLUMNS = [
    'legajo', 'nombre', 'sexo', 'nacionalidad', 'fechanac', 'edad',
    'fechaalta', 'fechaegreso', 'cargo_detallado', 'centros_de_costo',
    'área', 'grupos_por_gerencia', 'régimen_horario', 'sucursal', 'lugar_físico',
    'categoría', 'categoría_subrogante', 'nivel', 'puntaje_en_el_cargo',
    'permanencia', 'puntaje_título', 'puntaje_disponibilidad',
    'puntaje_especialización', 'puntaje_esencialidad', 'doc_dni', 'doc_ci',
    'doc_cuil', 'moneda_de_cobro', 'estado_civil', 'acceso_al_cargo', 'contrato',
    'asignacion_rsg_ii', 'trabajo_tensión'
];
$DATE_COLS = ['fechanac', 'fechaalta', 'fechaegreso'];
$usu = 1; // Usuario para auditoría

// -----------------------------------------------------------
// FUNCIONES
// -----------------------------------------------------------
function limpiarTabla(string $tabla, int $usu): void {
    $sql = "TRUNCATE TABLE `{$tabla}`;";
    if (!sql_do_query($sql, '1', $usu)) {
        throw new Exception("Error al limpiar la tabla {$tabla}.");
    }
}

function insertarRegistros(string $tabla, array $columnas, array $datos, array $dateCols, int $usu): void {
    foreach ($datos as $fila) {
        $valores_sql = [];
        foreach ($columnas as $idx => $col) {
            $value = $fila[$idx] ?? null;
            if ($value !== null && in_array($col, $dateCols, true)
                && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {
                $value = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            }
            $valores_sql[] = $value === '' || $value === null ? 'NULL' : "'" . addslashes($value) . "'";
        }
        $cols = implode('`,`', $columnas);
        $vals = implode(',', $valores_sql);
        $sql  = "INSERT INTO `{$tabla}` (`{$cols}`) VALUES ({$vals});";
        if (!sql_do_query($sql, '1', $usu)) {
            throw new Exception("Error al insertar registro en `{$tabla}`.");
        }
    }
}

function procesarCSV(string $archivo): array {
    $rows = [];
    if (($handle = fopen($archivo, 'r')) === false) {
        throw new Exception("No se pudo abrir el archivo CSV.");
    }
    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        $data = array_map(function ($value) {
            $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            return mb_convert_encoding($value, 'UTF-8', $encoding);
        }, $data);
        $rows[] = $data;
    }
    fclose($handle);
    return $rows;
}

// -----------------------------------------------------------
// PROCESAR SUBIDA
// -----------------------------------------------------------
$importado = false;
$errores = [];
$datos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['csv']['tmp_name']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
        $errores[] = 'No se seleccionó un archivo CSV válido.';
    } else {
        $tmpName = $_FILES['csv']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $errores[] = 'Formato no soportado: .' . $ext;
        } else {
            try {
                $rows = procesarCSV($tmpName);
                if (count($rows) < 2) {
                    throw new Exception('El CSV no contiene suficientes filas.');
                }
                $datosCsv = array_slice($rows, 1);

                limpiarTabla($TABLE, $usu);
                insertarRegistros($TABLE, $COLUMNS, $datosCsv, $DATE_COLS, $usu);
                $importado = true;

                $sql_select = "SELECT * FROM {$TABLE} ORDER BY legajo;";
                $datos = sql_do_array(sql_do_query($sql_select, '1', $usu));
            } catch (Exception $e) {
                $errores[] = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Importar Consulta Externa (CSV)</title>
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
</head>
<body>
<div class="container">
    <h1 class="page-title">Extraer de VISMA la Query e importar la "Consulta Externa" desde CSV</h1>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="csv">Selecciona el archivo (.csv):</label>
            <input type="file" name="csv" id="csv" accept=".csv" required>
        </div>
        <button type="submit" class="btn">Cargar e importar</button>
    </form>

    <?php if (!empty($errores)): ?>
        <div class="message-error">
            <strong>Se produjeron errores:</strong>
            <ul>
                <?php foreach ($errores as $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($importado): ?>
        <p class="message-ok">Importación completada (<?= count($datos) ?> registros).</p>
    <?php endif; ?>

    <?php if (!empty($datos)): ?>
        <h2>Registros recién insertados</h2>
        <table>
            <thead>
                <tr>
                    <?php foreach (array_keys($datos[0]) as $header): ?>
                        <th><?= htmlspecialchars($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $fila): ?>
                    <tr>
                        <?php foreach ($fila as $celda): ?>
                            <td><?= htmlspecialchars($celda ?? '') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>

<?php
$buscar = Enmascarar_post('buscar');
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
                    <th>Ord</th>
                    <th>Usuario</th>
                    <th>Motivo de la asignación</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $buscar = isset($_POST['buscar']) ? trim($_POST['buscar']) : '';
                $sql = "
                    SELECT legajo, nacionalidad, nombre, nivel, puntaje_en_el_cargo, puntaje_esencialidad
                    FROM more_consulta_externa
                    WHERE legajo <> '0'
                       AND (UPPER(nombre) LIKE UPPER('" . addslashes($buscar) . "%')
                       OR UPPER(nacionalidad) LIKE UPPER('" . addslashes($buscar) . "%')
                       OR UPPER(categoría) LIKE UPPER('" . addslashes($buscar) . "%'))
                    ORDER BY legajo;
                ";

                $result = sql_do_query($sql, '1', $usu);
                $n = 0;

                while ($row = sql_do_array($result)) {
                    $n++;
                    $a6 = obtener6A($row['NIVEL']);    
                    $b6 = obtener6B($row['NIVEL'], $row['PUNTAJE_ESENCIALIDAD']);
                    $c6 = obtener6C($row['NIVEL'], $row['PUNTAJE_ESENCIALIDAD']);
                    $d6 = obtener6D($row['NIVEL'], $row['PUNTAJE_ESENCIALIDAD']);
                    $e6 = obtener6E($row['NIVEL'], $row['PUNTAJE_ESENCIALIDAD']);
                    $f6 = obtener6F($row['NIVEL'], $row['PUNTAJE_ESENCIALIDAD']);
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
                    </td>                    
                    <td align="center">
                        <?php
                        $variables = array("legajo" => $row['legajo']);
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