<?php
//ini_set('display_errors', '1');
//error_reporting(E_ALL);

// -----------------------------------------------------------
// CONFIGURACIÓN
// -----------------------------------------------------------
$TABLE = 'more_consulta_externa';
$usu = 1; // Usuario para auditoría

function limpiarTabla(string $tabla, int $usu): void {
    $sql = "TRUNCATE TABLE `{$tabla}`;";
    if (!sql_do_query($sql, '1', $usu)) {
        throw new Exception("Error al limpiar la tabla {$tabla}.");
    }
}

function procesarCSV(string $archivo): array {
    $rows = [];
    if (($handle = fopen($archivo, 'r')) === false) {
        throw new Exception("No se pudo abrir el archivo CSV.");
    }

    // Leer la primera línea para detectar el encoding general
    $firstLine = fgets($handle);
    $encoding = mb_detect_encoding($firstLine, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    if ($encoding === false) $encoding = 'UTF-8'; // Valor por defecto

    // Volver al inicio del archivo
    rewind($handle);

    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        // Convertir cada celda solo si el encoding no es UTF-8
        if (strtoupper($encoding) !== 'UTF-8') {
            $data = array_map(function ($value) use ($encoding) {
                return mb_convert_encoding($value, 'UTF-8', $encoding);
            }, $data);
        }
        $rows[] = $data;
    }
    fclose($handle);
    return $rows;
}

function normalizarColumna($col) {
    $col = trim($col);
    // Paso a mayúsculas
    $col = mb_strtoupper($col, 'UTF-8');
    // Caracteres extraños a letras correctas
    $col = strtr($col, [
        'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
        'Ü'=>'U', 'Ñ'=>'N', 'Ç'=>'C',
        'á'=>'A', 'é'=>'E', 'í'=>'I', 'ó'=>'O', 'ú'=>'U',
        'ü'=>'U', 'ñ'=>'N', 'ç'=>'C',
        '¡'=>'I', '¢'=>'O', '¨'=>'U', '´'=>'', '‘'=>'', '’'=>'', '`'=>'',
        '“'=>'', '”'=>'', '–'=>'-', '—'=>'-', 'µ'=>'U', '‚'=>'', '¢'=>'O'
    ]);
    // Todo lo que no sea letra, número o _ lo convierte en _
    $col = preg_replace('/[^A-Z0-9_]/', '_', $col);
    // Solo una _ entre palabras
    $col = preg_replace('/_+/', '_', $col);
    // Elimina _ al principio/final
    $col = trim($col, '_');
    return $col;
}



// Mapeo robusto, contempla variantes accidentales
$mapa_columnas = [
    'LEGAJO' => 'legajo',
    'NOMBRE' => 'nombre',
    'SEXO' => 'sexo',
    'NACIONALIDAD' => 'nacionalidad',
    'FECHANAC' => 'fechanac',
    'EDAD' => 'edad',
    'FECHAALTA' => 'fechaalta',
    'FECHAEGRESO' => 'fechaegreso',
    'CARGO_DETALLADO' => 'cargo_detallado',
    'CENTROS_DE_COSTO' => 'centros_de_costo',
    'AREA' => 'area',
    'GRUPOS_POR_GERENCIA' => 'grupos_por_gerencia',
    'REGIMEN_HORARIO' => 'regimen_horario',
    'SUCURSAL' => 'sucursal',
    'LUGAR_FISICO' => 'lugar_fisico',
    'CATEGORIA' => 'categoria',
    'CATEGORIA_SUBROGANTE' => 'categoria_subrogante',
    'NIVEL' => 'nivel',
    'PUNTAJE_EN_EL_CARGO' => 'puntaje_en_el_cargo',
    'PERMANENCIA' => 'permanencia',
    'PUNTAJE_TITULO' => 'puntaje_titulo',
    'PUNTAJE_T_TULO' => 'puntaje_titulo', // Variante extra por si acaso
    'PUNTAJE_TITULO' => 'puntaje_titulo',
    'PUNTAJE_DISPONIBILIDAD' => 'puntaje_disponibilidad',
    'PUNTAJE_ESPECIALIZACINN' => 'puntaje_especializacion',
    'PUNTAJE_ESENCIALIDAD' => 'puntaje_esencialidad',
    'DOC_DNI' => 'doc_dni',
    'DOC_CI' => 'doc_ci',
    'DOC_CUIL' => 'doc_cuil',
    'MONEDA_DE_COBRO' => 'moneda_de_cobro',
    'ESTADO_CIVIL' => 'estado_civil',
    'ACCESO_AL_CARGO' => 'acceso_al_cargo',
    'CONTRATO' => 'contrato',
    'ASIGNACION_RSG_II' => 'asignacion_rsg_ii',
    'TRABAJO_TENSION' => 'trabajo_tension'
];


function insertarRegistros(string $tabla, array $columnas, array $datos, int $usu): void {
    foreach ($datos as $fila) {
        $valores_sql = [];
        foreach ($columnas as $idx => $col) {
            $value = $fila[$idx] ?? null;
            // Procesar fechas dd/mm/yyyy a yyyy-mm-dd
            if ($value !== null && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {
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
                // Usar encabezados reales del CSV y mapearlos a los nombres de la tabla
                $headers = array_map('normalizarColumna', $rows[0]);
                foreach ($headers as $i => $col) {
                    if (isset($mapa_columnas[$col])) {
                        $headers[$i] = $mapa_columnas[$col];
                    }
                }
                $datosCsv = array_slice($rows, 1);

                limpiarTabla($TABLE, $usu);
                insertarRegistros($TABLE, $headers, $datosCsv, $usu);
                $importado = true;

                // Mostrar los registros recién insertados
                $sql_select = "SELECT * FROM {$TABLE} ORDER BY legajo;";
                $datos = [];
                $res = sql_do_query($sql_select, '1', $usu);
                while ($fila = sql_do_array($res)) {
                    $datos[] = $fila;
                }
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
            display: inline-block;
            width: auto;
            max-width: 100vw;
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
        <div style="overflow-x:auto;">
            <table style="min-width:400px;max-width:100%;">
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
        </div>
    <?php endif; ?>
</div>
</body>
</html>
<br />
