<?php
/**
 * ============================================================================
 * Generador DOCX real de EETT y Justificación — PhpWord
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
    http_response_code(403);
    echo 'Sesión no válida.';
    exit;
}

// Autoload de Composer (PhpWord)
require_once APP_ROOT . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\TablePosition;

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
    $codigo    = addslashes($_GET['codigo'] ?? '');
    $mat       = addslashes($_GET['matricula'] ?? '');
    $lin       = (int)($_GET['lin'] ?? 0);
    $estado_sr = addslashes($_GET['estado'] ?? '');

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

    if (!$row) { echo 'No se encontraron EETT.'; exit; }

    $titulo_doc = 'Especificaciones Técnicas (EETT)';
    $subtitulo_doc = $row['EETT_DENOMINACION'] ?? $row['DESC_SR'] ?? '';
    $meta_info = [
        'SR'          => $_GET['codigo'] ?? '',
        'Matrícula'   => $_GET['matricula'] ?? '',
        'Línea'       => $lin,
        'Descripción' => $row['DESC_SR'] ?? '',
        'Categoría'   => $row['CAT_NOMBRE'] ?? '',
        'Ítem'        => ($row['ITEM_CODIGO'] ?? '') . ' — ' . ($row['ITEM_NOMBRE'] ?? ''),
        'Unidad'      => $row['UNIDAD_NOMBRE'] ?? '',
        'Cantidad'    => $row['CANT'] ?? '',
        'Solicitante' => $row['USUARIO_SR'] ?? '',
        'Fecha SR'    => $row['FECHA_SR'] ?? '',
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

    if (!$row) { echo 'No se encontraron EETT.'; exit; }

    $titulo_doc = 'Especificaciones Técnicas (EETT) — Borrador SR';
    $subtitulo_doc = $row['EETT_DENOMINACION'] ?? $row['DESC_BORR'] ?? '';
    $meta_info = [
        'Borrador #'  => $_GET['id'] ?? '',
        'Período'     => $row['ANIO_DESTINO'] ?? '',
        'Matrícula'   => $row['MAT_BORR'] ?? '',
        'Descripción' => $row['DESC_BORR'] ?? '',
        'Categoría'   => $row['CAT_NOMBRE'] ?? '',
        'Ítem'        => ($row['ITEM_CODIGO'] ?? '') . ' — ' . ($row['ITEM_NOMBRE'] ?? ''),
        'Unidad'      => $row['UNIDAD_NOMBRE'] ?? '',
        'Cantidad'    => $row['CANT'] ?? '',
        'SR Origen'   => $row['SR_ORIGEN'] ?? '',
        'Monto Est.'  => 'U$D ' . number_format((float)($row['MONTO'] ?? 0), 2, ',', '.'),
    ];
    $eett_data = $row;
    $justificacion = $row['JUSTIFICACION_COMPRA'] ?? '';

} else {
    echo 'Tipo no válido.';
    exit;
}

// ── Colores ──────────────────────────────────────────────────────────────────
$colorRojo    = 'c0392b';
$colorOscuro  = '2c3e50';
$colorGris    = '555555';
$colorLight   = 'f8f9fa';
$colorBorde   = 'cccccc';

// ── Crear documento Word ─────────────────────────────────────────────────────
$phpWord = new PhpWord();
$phpWord->getSettings()->setUpdateFields(true);

// Estilos globales
$phpWord->setDefaultFontName('Calibri');
$phpWord->setDefaultFontSize(11);

// Sección A4 con márgenes
$section = $phpWord->addSection([
    'pageSizeW'    => Converter::cmToTwip(21),
    'pageSizeH'    => Converter::cmToTwip(29.7),
    'marginTop'    => Converter::cmToTwip(1.5),
    'marginBottom' => Converter::cmToTwip(2),
    'marginLeft'   => Converter::cmToTwip(2),
    'marginRight'  => Converter::cmToTwip(2),
]);

// ── MEMBRETE ─────────────────────────────────────────────────────────────────
$logo_path = APP_ROOT . 'img' . DIRECTORY_SEPARATOR . 'logo_transparente.png';
if (file_exists($logo_path)) {
    $section->addImage($logo_path, [
        'width'     => Converter::cmToPoint(5),
        'alignment' => Jc::CENTER,
    ]);
}
$section->addText(
    'COMISIÓN TÉCNICA MIXTA DE SALTO GRANDE',
    ['size' => 8, 'color' => $colorGris, 'allCaps' => true],
    ['alignment' => Jc::CENTER, 'spaceAfter' => Converter::pointToTwip(6)]
);

// Línea separadora roja
$tblLinea = $section->addTable(['borderColor' => $colorRojo, 'borderSize' => 6]);
$tblLinea->addRow(1);
$tblLinea->addCell(Converter::cmToTwip(17), [
    'borderBottomSize' => 12,
    'borderBottomColor' => $colorRojo,
]);

$section->addTextBreak(1);

// ── TÍTULO ───────────────────────────────────────────────────────────────────
$section->addText(
    mb_strtoupper($titulo_doc),
    ['size' => 14, 'bold' => true, 'color' => '1a1a1a'],
    ['alignment' => Jc::CENTER, 'spaceAfter' => Converter::pointToTwip(2)]
);
if (!empty($subtitulo_doc)) {
    $section->addText(
        $subtitulo_doc,
        ['size' => 11, 'bold' => true, 'color' => '333333'],
        ['alignment' => Jc::CENTER, 'spaceAfter' => Converter::pointToTwip(2)]
    );
}
$section->addText(
    'Fecha de emisión: ' . $fecha_doc,
    ['size' => 8.5, 'color' => '888888'],
    ['alignment' => Jc::CENTER, 'spaceAfter' => Converter::pointToTwip(12)]
);

// ── DATOS META ───────────────────────────────────────────────────────────────
$tblMeta = $section->addTable([
    'borderSize'  => 4,
    'borderColor' => 'e0e0e0',
    'cellMargin'  => 60,
]);

$metaFiltered = array_filter($meta_info, function($v) { return !empty($v) && $v !== ' — '; });
$metaKeys = array_keys($metaFiltered);
$metaVals = array_values($metaFiltered);

for ($i = 0; $i < count($metaKeys); $i += 2) {
    $tblMeta->addRow();
    // Col 1
    $cell1 = $tblMeta->addCell(Converter::cmToTwip(8.5), ['bgColor' => $colorLight]);
    $run1 = $cell1->addTextRun();
    $run1->addText($metaKeys[$i] . ': ', ['bold' => true, 'size' => 9, 'color' => $colorGris]);
    $run1->addText((string)$metaVals[$i], ['size' => 9, 'color' => '1a1a1a']);
    // Col 2
    if (isset($metaKeys[$i + 1])) {
        $cell2 = $tblMeta->addCell(Converter::cmToTwip(8.5), ['bgColor' => $colorLight]);
        $run2 = $cell2->addTextRun();
        $run2->addText($metaKeys[$i + 1] . ': ', ['bold' => true, 'size' => 9, 'color' => $colorGris]);
        $run2->addText((string)$metaVals[$i + 1], ['size' => 9, 'color' => '1a1a1a']);
    } else {
        $tblMeta->addCell(Converter::cmToTwip(8.5), ['bgColor' => $colorLight]);
    }
}

$section->addTextBreak(1);

// ── SECCIÓN EETT ─────────────────────────────────────────────────────────────
$section->addText(
    'ESPECIFICACIONES TÉCNICAS',
    ['size' => 11, 'bold' => true, 'color' => $colorOscuro, 'allCaps' => true],
    ['spaceAfter' => Converter::pointToTwip(4), 'borderBottomSize' => 6, 'borderBottomColor' => $colorOscuro]
);

$tblEett = $section->addTable([
    'borderSize'  => 4,
    'borderColor' => $colorBorde,
    'cellMargin'  => 60,
]);

$thStyle  = ['bgColor' => $colorOscuro, 'valign' => 'top'];
$thFont   = ['bold' => true, 'size' => 9, 'color' => 'ffffff', 'allCaps' => true];
$tdStyle  = ['valign' => 'top'];
$tdFont   = ['size' => 10, 'color' => '1a1a1a'];
$emptyFont = ['size' => 10, 'color' => 'aaaaaa', 'italic' => true];

$rowNum = 0;
foreach ($eett_campos as $key => $label) {
    $val = trim($eett_data[strtoupper($key)] ?? '');
    $bgColor = ($rowNum % 2 === 1) ? 'f9fafb' : 'ffffff';

    $tblEett->addRow();
    $cellTh = $tblEett->addCell(Converter::cmToTwip(5), $thStyle);
    $cellTh->addText($label, $thFont);

    $cellTd = $tblEett->addCell(Converter::cmToTwip(12), array_merge($tdStyle, ['bgColor' => $bgColor]));
    if (!empty($val)) {
        // Manejar saltos de línea
        $lines = explode("\n", $val);
        foreach ($lines as $li => $line) {
            $cellTd->addText(trim($line), $tdFont, ['spaceAfter' => 0]);
        }
    } else {
        $cellTd->addText('—', $emptyFont);
    }
    $rowNum++;
}

// ── JUSTIFICACIÓN ────────────────────────────────────────────────────────────
if (!empty($justificacion)) {
    $section->addTextBreak(1);
    $section->addText(
        'JUSTIFICACIÓN DE COMPRA',
        ['size' => 11, 'bold' => true, 'color' => $colorOscuro, 'allCaps' => true],
        ['spaceAfter' => Converter::pointToTwip(4), 'borderBottomSize' => 6, 'borderBottomColor' => $colorOscuro]
    );

    $tblJustif = $section->addTable([
        'borderSize'  => 4,
        'borderColor' => 'd5d5d5',
        'cellMargin'  => 80,
    ]);
    $tblJustif->addRow();
    // Borde izquierdo grueso azul
    $cellJ = $tblJustif->addCell(Converter::cmToTwip(17), [
        'borderLeftSize'  => 18,
        'borderLeftColor' => $colorOscuro,
    ]);
    $jLines = explode("\n", $justificacion);
    foreach ($jLines as $jl) {
        $cellJ->addText(trim($jl), ['size' => 10, 'color' => '1a1a1a'], ['spaceAfter' => Converter::pointToTwip(3)]);
    }
}

// ── PIE ──────────────────────────────────────────────────────────────────────
$section->addTextBreak(2);

$tblPie = $section->addTable(['borderColor' => 'dddddd', 'borderSize' => 0]);
$tblPie->addRow();
$cellPie = $tblPie->addCell(Converter::cmToTwip(17), ['borderTopSize' => 4, 'borderTopColor' => 'dddddd']);
$cellPie->addText(
    'Mg. Gonzalo Raul Farinon — Jefe de Unidad Atención a Usuarios — Área de Informática y Comunicaciones',
    ['size' => 7.5, 'color' => '999999'],
    ['alignment' => Jc::CENTER, 'spaceBefore' => Converter::pointToTwip(6)]
);

// ── Enviar archivo ───────────────────────────────────────────────────────────
$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $subtitulo_doc ?: 'EETT') . '_' . date('Ymd') . '.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
