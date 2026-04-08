<?php
/**
 * ============================================================================
 * Impresión de EETT y Justificación — Documento formal con membrete CTM
 * ============================================================================
 * Parámetros GET:
 *   tipo=sr       → requiere: codigo, matricula, lin, estado
 *   tipo=borrador → requiere: id
 * ============================================================================
 */
ini_set('display_errors', '0');
error_reporting(E_ALL);

include_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
include_once APP_CONFIG . 'session.php';

if (empty($usu)) {
    echo 'Sesión no válida.';
    exit;
}

$db   = '1';
$tipo = $_GET['tipo'] ?? '';

$eett_campos = [
    'eett_denominacion'           => 'Denominación del Material',
    'eett_caracteristicas'        => 'Características Nominales',
    'eett_representacion_grafica' => 'Representación Gráfica',
    'eett_normas_fabricacion'     => 'Normas de Fabricación',
    'eett_ensayo_recepcion'       => 'Ensayo para la Recepción',
    'eett_responsable_recepcion'  => 'Responsable de la Recepción',
    'eett_marcas'                 => 'Marcas',
    'eett_aplicacion'             => 'Aplicación',
    'eett_forma_entrega'          => 'Forma de Entrega',
    'eett_observaciones'          => 'Observaciones',
    'eett_proveedores'            => 'Proveedores',
];

$titulo_doc     = '';
$subtitulo_doc  = '';
$meta_info      = [];
$eett_data      = [];
$justificacion  = '';
$fecha_doc      = date('d/m/Y');

// ── Cargar datos según tipo ──────────────────────────────────────────────────
if ($tipo === 'sr') {
    $codigo   = addslashes($_GET['codigo'] ?? '');
    $mat      = addslashes($_GET['matricula'] ?? '');
    $lin      = (int)($_GET['lin'] ?? 0);
    $estado_sr = addslashes($_GET['estado'] ?? '');

    // EETT del renglón
    $sql = "SELECT e.*, sr.descripcion AS DESC_SR, sr.fecha AS FECHA_SR,
                   sr.usuario AS USUARIO_SR, sr.cantidad_solicitada AS CANT,
                   ig.nombre AS ITEM_NOMBRE, ig.codigo AS ITEM_CODIGO,
                   uo.nombre AS UNIDAD_NOMBRE,
                   COALESCE(cg.nombre, '') AS CAT_NOMBRE
            FROM pres_k2b_sr_logs_eett e
            INNER JOIN pres_k2b_sr_logs sr
                ON sr.codigo = e.codigo AND sr.matricula = e.matricula AND sr.lin = e.lin AND sr.estado = e.estado
            LEFT JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
            LEFT JOIN ciclo_unidad_organizativa uo ON sr.esquema_cc = uo.cc
            LEFT JOIN pres_k2b_matricula_categoria mc2 ON mc2.matricula = sr.matricula
            LEFT JOIN ciclo_categoria_gasto cg ON cg.id = mc2.id_categoria
            WHERE e.codigo = '$codigo' AND e.matricula = '$mat' AND e.lin = $lin AND e.estado = '$estado_sr'
            LIMIT 1";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));

    if (!$row) {
        // Fallback: matrícula
        $sql2 = "SELECT mc.*, sr.descripcion AS DESC_SR, sr.fecha AS FECHA_SR,
                        sr.usuario AS USUARIO_SR, sr.cantidad_solicitada AS CANT,
                        ig.nombre AS ITEM_NOMBRE, ig.codigo AS ITEM_CODIGO,
                        uo.nombre AS UNIDAD_NOMBRE,
                        COALESCE(cg.nombre, '') AS CAT_NOMBRE
                 FROM pres_k2b_matricula_categoria mc
                 INNER JOIN pres_k2b_sr_logs sr ON sr.matricula = mc.matricula
                    AND sr.codigo = '$codigo' AND sr.lin = $lin AND sr.estado = '$estado_sr'
                 LEFT JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
                 LEFT JOIN ciclo_unidad_organizativa uo ON sr.esquema_cc = uo.cc
                 LEFT JOIN ciclo_categoria_gasto cg ON cg.id = mc.id_categoria
                 WHERE mc.matricula = '$mat'
                 LIMIT 1";
        $row = sql_do_array(sql_do_query($sql2, $db, $usu));
    }

    if (!$row) {
        echo '<p>No se encontraron EETT para este renglón.</p>';
        exit;
    }

    $titulo_doc = 'Especificaciones Técnicas (EETT)';
    $subtitulo_doc = htmlspecialchars($row['EETT_DENOMINACION'] ?? $row['DESC_SR'] ?? '');
    $meta_info = [
        'SR'          => htmlspecialchars($_GET['codigo'] ?? ''),
        'Matrícula'   => htmlspecialchars($_GET['matricula'] ?? ''),
        'Línea'       => $lin,
        'Descripción' => htmlspecialchars($row['DESC_SR'] ?? ''),
        'Categoría'   => htmlspecialchars($row['CAT_NOMBRE'] ?? ''),
        'Ítem'        => htmlspecialchars(($row['ITEM_CODIGO'] ?? '') . ' — ' . ($row['ITEM_NOMBRE'] ?? '')),
        'Unidad'      => htmlspecialchars($row['UNIDAD_NOMBRE'] ?? ''),
        'Cantidad'    => $row['CANT'] ?? '',
        'Solicitante' => htmlspecialchars($row['USUARIO_SR'] ?? ''),
        'Fecha SR'    => htmlspecialchars($row['FECHA_SR'] ?? ''),
    ];

    $eett_data = $row;
    $justificacion = $row['JUSTIFICACION_COMPRA'] ?? '';

} elseif ($tipo === 'borrador') {
    $bid = addslashes($_GET['id'] ?? '');

    $sql = "SELECT be.*, b.descripcion AS DESC_BORR, b.matricula AS MAT_BORR,
                   b.sr_origen_codigo AS SR_ORIGEN, b.monto_estimado AS MONTO,
                   b.cantidad AS CANT,
                   ig.nombre AS ITEM_NOMBRE, ig.codigo AS ITEM_CODIGO,
                   uo.nombre AS UNIDAD_NOMBRE,
                   pf.anio AS ANIO_DESTINO,
                   COALESCE(cg.nombre, '') AS CAT_NOMBRE
            FROM pres_sr_borrador_eett be
            INNER JOIN pres_sr_borrador b ON b.id = be.id_borrador
            LEFT JOIN ciclo_item_gasto ig ON ig.codigo = b.esquema_gastoid
            LEFT JOIN ciclo_unidad_organizativa uo ON uo.cc = b.esquema_cc
            LEFT JOIN ciclo_periodo_fiscal pf ON pf.id = b.id_periodo_destino
            LEFT JOIN pres_k2b_matricula_categoria mc2 ON mc2.matricula = b.matricula
            LEFT JOIN ciclo_categoria_gasto cg ON cg.id = mc2.id_categoria
            WHERE be.id_borrador = '$bid'
            LIMIT 1";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));

    if (!$row) {
        echo '<p>No se encontraron EETT para este borrador.</p>';
        exit;
    }

    $titulo_doc = 'Especificaciones Técnicas (EETT) — Borrador SR';
    $subtitulo_doc = htmlspecialchars($row['EETT_DENOMINACION'] ?? $row['DESC_BORR'] ?? '');
    $meta_info = [
        'Borrador #'   => htmlspecialchars($_GET['id'] ?? ''),
        'Período'      => htmlspecialchars($row['ANIO_DESTINO'] ?? ''),
        'Matrícula'    => htmlspecialchars($row['MAT_BORR'] ?? ''),
        'Descripción'  => htmlspecialchars($row['DESC_BORR'] ?? ''),
        'Categoría'    => htmlspecialchars($row['CAT_NOMBRE'] ?? ''),
        'Ítem'         => htmlspecialchars(($row['ITEM_CODIGO'] ?? '') . ' — ' . ($row['ITEM_NOMBRE'] ?? '')),
        'Unidad'       => htmlspecialchars($row['UNIDAD_NOMBRE'] ?? ''),
        'Cantidad'     => $row['CANT'] ?? '',
        'SR Origen'    => htmlspecialchars($row['SR_ORIGEN'] ?? ''),
        'Monto Est.'   => 'U$D ' . number_format((float)($row['MONTO'] ?? 0), 2, ',', '.'),
    ];

    $eett_data = $row;
    $justificacion = $row['JUSTIFICACION_COMPRA'] ?? '';

} else {
    echo '<p>Tipo de documento no válido.</p>';
    exit;
}

$logo_url = 'http://localhost/ctm/contexto_unificado/ctm_presupuestos/img/logo_transparente.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_doc; ?> — <?php echo $subtitulo_doc; ?></title>
    <style>
        /* ── Reset y base ─────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 11pt;
            color: #1a1a1a;
            background: #fff;
            line-height: 1.5;
            padding: 0;
        }

        /* ── Botones solo pantalla ─────────────────────────────── */
        .no-print { display: flex; gap: 8px; justify-content: center; padding: 16px; background: #f5f5f5; border-bottom: 1px solid #ddd; }
        .no-print button {
            padding: 8px 20px; font-size: 10pt; border: 1px solid #ccc;
            border-radius: 6px; cursor: pointer; background: #fff;
        }
        .no-print .btn-print { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        .no-print .btn-print:hover { background: #0b5ed7; }
        .no-print .btn-docx { background: #198754; color: #fff; border-color: #198754; }
        .no-print .btn-docx:hover { background: #157347; }
        .no-print .btn-close-doc { background: #6c757d; color: #fff; border-color: #6c757d; }

        /* ── Contenedor del documento ───────────────────────────── */
        .documento {
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm 20mm 20mm 20mm;
        }

        /* ── Membrete ──────────────────────────────────────────── */
        .membrete {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 2px solid #c0392b;
            margin-bottom: 20px;
        }
        .membrete img { max-width: 180px; height: auto; margin-bottom: 4px; }
        .membrete .org-name { font-size: 8pt; color: #555; letter-spacing: 2px; text-transform: uppercase; }

        /* ── Título documento ───────────────────────────────────── */
        .doc-titulo {
            text-align: center;
            margin-bottom: 18px;
        }
        .doc-titulo h1 {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #1a1a1a;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .doc-titulo h2 {
            font-size: 11pt;
            font-weight: 600;
            color: #333;
        }
        .doc-titulo .fecha {
            font-size: 8.5pt;
            color: #888;
            margin-top: 4px;
        }

        /* ── Info meta ─────────────────────────────────────────── */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 24px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 20px;
            font-size: 9pt;
        }
        .meta-grid .meta-item { display: flex; gap: 6px; }
        .meta-grid .meta-label { font-weight: 600; color: #555; white-space: nowrap; min-width: 90px; }
        .meta-grid .meta-value { color: #1a1a1a; }

        /* ── Sección EETT ──────────────────────────────────────── */
        .seccion-titulo {
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #2c3e50;
            border-bottom: 1.5px solid #2c3e50;
            padding-bottom: 4px;
            margin: 20px 0 12px 0;
            letter-spacing: 0.5px;
        }

        .eett-tabla {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 10pt;
        }
        .eett-tabla th, .eett-tabla td {
            border: 1px solid #ccc;
            padding: 6px 10px;
            vertical-align: top;
            text-align: left;
        }
        .eett-tabla th {
            background: #2c3e50;
            color: #fff;
            font-weight: 600;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            width: 200px;
        }
        .eett-tabla td {
            background: #fff;
        }
        .eett-tabla tr:nth-child(even) td { background: #f9fafb; }
        .eett-tabla td.vacio { color: #aaa; font-style: italic; }

        /* ── Justificación ─────────────────────────────────────── */
        .justif-box {
            background: #fefefe;
            border: 1px solid #d5d5d5;
            border-left: 4px solid #2c3e50;
            border-radius: 4px;
            padding: 12px 16px;
            font-size: 10pt;
            line-height: 1.65;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* ── Pie de página ─────────────────────────────────────── */
        .pie {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 7.5pt;
            color: #999;
        }


        /* ── Print ──────────────────────────────────────────────── */
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: #fff; }
            .documento { padding: 10mm 15mm 15mm 15mm; max-width: none; }
            .membrete { border-bottom-color: #c0392b; }
            .eett-tabla th { background: #2c3e50 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .eett-tabla tr:nth-child(even) td { background: #f9fafb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .meta-grid { background: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4; margin: 10mm; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn-print" onclick="window.print();">&#128424; Imprimir / PDF</button>
    <button class="btn-docx" onclick="descargarDocx();">&#128196; Descargar DOCX</button>
    <button class="btn-close-doc" onclick="window.close();">Cerrar</button>
</div>

<div class="documento">

    <!-- MEMBRETE -->
    <div class="membrete">
        <img src="<?php echo $logo_url; ?>" alt="CTM Salto Grande">
        <div class="org-name">Comisión Técnica Mixta de Salto Grande</div>
    </div>

    <!-- TÍTULO -->
    <div class="doc-titulo">
        <h1><?php echo htmlspecialchars($titulo_doc); ?></h1>
        <h2><?php echo $subtitulo_doc; ?></h2>
        <div class="fecha">Fecha de emisión: <?php echo $fecha_doc; ?></div>
    </div>

    <!-- DATOS DEL DOCUMENTO -->
    <div class="meta-grid">
        <?php foreach ($meta_info as $label => $val): if (empty($val) || $val === ' — ') continue; ?>
        <div class="meta-item">
            <span class="meta-label"><?php echo $label; ?>:</span>
            <span class="meta-value"><?php echo $val; ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- EETT -->
    <div class="seccion-titulo">Especificaciones Técnicas</div>
    <table class="eett-tabla">
        <?php foreach ($eett_campos as $key => $label):
            $val = trim($eett_data[strtoupper($key)] ?? '');
        ?>
        <tr>
            <th><?php echo htmlspecialchars($label); ?></th>
            <td<?php echo empty($val) ? ' class="vacio"' : ''; ?>><?php echo empty($val) ? '—' : nl2br(htmlspecialchars($val)); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- JUSTIFICACIÓN -->
    <?php if (!empty($justificacion)): ?>
    <div class="seccion-titulo">Justificación de Compra</div>
    <div class="justif-box"><?php echo nl2br(htmlspecialchars($justificacion)); ?></div>
    <?php endif; ?>

    <!-- PIE -->
    <div class="pie">
        Mg. Gonzalo Raul Farinon — Jefe de Unidad Atención a Usuarios — Área de Informática y Comunicaciones
    </div>

</div>

<script>
function descargarDocx() {
    var url = '<?php echo APP_URL; ?>web/mod/solicitudes/descargar_eett_docx.php' + window.location.search;
    window.location.href = url;
}
</script>
</body>
</html>
