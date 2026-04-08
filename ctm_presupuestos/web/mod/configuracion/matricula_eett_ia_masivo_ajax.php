<?php
/**
 * ============================================================================
 * AJAX: Listar matrículas con EETT pendientes
 * ============================================================================
 * Endpoint liviano que SOLO devuelve la lista de matrículas sin EETT.
 * El procesamiento IA lo hace el JS del cliente, una por una,
 * llamando a matricula_eett_ia_ajax.php para cada matrícula.
 * Recibe: filtro_item (opcional)
 * Devuelve JSON con array de matrículas pendientes.
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
$filtro_item = isset($_POST['filtro_item']) ? trim($_POST['filtro_item']) : null;
if ($filtro_item === '' || $filtro_item === 'null') $filtro_item = null;

$where = "sr.matricula IS NOT NULL AND sr.matricula != ''
          AND (mc.eett_denominacion IS NULL OR mc.eett_denominacion = '')";
if ($filtro_item) {
    $filtro_esc = addslashes($filtro_item);
    $where .= " AND ig.codigo = '$filtro_esc'";
}

$sql = "SELECT sr.matricula AS MATRICULA,
               MIN(sr.descripcion) AS DESCRIPCION
        FROM pres_k2b_sr_logs sr
        INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
        LEFT JOIN pres_k2b_matricula_categoria mc ON mc.matricula = sr.matricula
        WHERE $where
        GROUP BY sr.matricula
        ORDER BY sr.matricula
        LIMIT 50;";

$result = sql_do_query($sql, $db, $usu);
$pendientes = [];
while ($row = sql_do_array($result)) {
    $pendientes[] = ['matricula' => $row['MATRICULA'], 'descripcion' => mb_substr($row['DESCRIPCION'] ?? '', 0, 60)];
}

echo json_encode([
    'ok'         => true,
    'total'      => count($pendientes),
    'pendientes' => $pendientes,
], JSON_UNESCAPED_UNICODE);
