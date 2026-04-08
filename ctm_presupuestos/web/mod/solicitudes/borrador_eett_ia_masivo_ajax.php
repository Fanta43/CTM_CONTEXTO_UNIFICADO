<?php
/**
 * ============================================================================
 * AJAX: Listar borradores con EETT pendientes
 * ============================================================================
 * Devuelve JSON con array de borradores sin EETT completadas.
 * Cada item incluye id_borrador y la primera matrícula para llamar a la IA.
 * El JS del cliente procesa uno por uno llamando a borrador_eett_ia_ajax.php.
 * ============================================================================
 */
ini_set('display_errors', '0');
error_reporting(E_ALL);

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'PHP Fatal: ' . $err['message'] . ' en ' . $err['file'] . ':' . $err['line']]);
    }
});

ob_start();

include_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
include_once APP_CONFIG . 'session.php';

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (empty($usu)) {
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida.']);
    exit;
}

$db = '1';
$filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : null;
if ($filtro_periodo === '' || $filtro_periodo === 'null') $filtro_periodo = null;

$where = "WHERE (be.eett_denominacion IS NULL OR be.eett_denominacion = '')";
if ($filtro_periodo) {
    $fp_esc = addslashes($filtro_periodo);
    $where .= " AND b.id_periodo_destino = '$fp_esc'";
}

$sql = "SELECT b.id AS ID_BORRADOR,
               b.matricula AS MATRICULA,
               b.descripcion AS DESCRIPCION,
               b.esquema_gastoid AS ITEM_GASTO,
               ig.nombre AS ITEM_NOMBRE
        FROM pres_sr_borrador b
        LEFT JOIN pres_sr_borrador_eett be ON be.id_borrador = b.id
        LEFT JOIN ciclo_item_gasto ig ON ig.codigo = b.esquema_gastoid
        $where
        ORDER BY b.esquema_gastoid, b.id
        LIMIT 50;";

$result = sql_do_query($sql, $db, $usu);
$pendientes = [];
while ($r = sql_do_array($result)) {
    // Extraer la primera matrícula (puede ser lista separada por comas)
    $mat_raw = $r['MATRICULA'] ?? '';
    $mat_clean = preg_replace('/\.\.\.\s*\+\d+$/', '', $mat_raw);
    $mats = array_filter(array_map('trim', explode(',', $mat_clean)));
    $primera_mat = !empty($mats) ? $mats[0] : '';

    $pendientes[] = [
        'id_borrador' => $r['ID_BORRADOR'],
        'matricula'   => $primera_mat,
        'descripcion' => $r['DESCRIPCION'] ?? '',
        'item_gasto'  => $r['ITEM_GASTO'] ?? '',
        'item_nombre' => $r['ITEM_NOMBRE'] ?? '',
    ];
}

echo json_encode([
    'ok'         => true,
    'pendientes' => $pendientes,
    'total'      => count($pendientes),
], JSON_UNESCAPED_UNICODE);
