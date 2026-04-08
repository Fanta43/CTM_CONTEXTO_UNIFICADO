<?php
/**
 * ============================================================================
 * AJAX: Auto-completar EETT de un borrador con IA (Ollama)
 * ============================================================================
 * Recibe: id_borrador (ID del borrador), matricula (código de matrícula)
 * Llama a Ollama para generar EETT y guarda en pres_sr_borrador_eett.
 * Devuelve JSON con los campos EETT generados.
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

$id_borrador = isset($_POST['id_borrador']) ? trim($_POST['id_borrador']) : '';
$matricula   = isset($_POST['matricula'])   ? trim($_POST['matricula'])   : '';
$desc_renglon = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

if ($id_borrador === '') {
    echo json_encode(['ok' => false, 'error' => 'No se especificó id_borrador.']);
    exit;
}
if ($matricula === '') {
    echo json_encode(['ok' => false, 'error' => 'No se especificó matrícula.']);
    exit;
}

$db = '1';
$bid_esc = addslashes($id_borrador);
$mat_esc = addslashes($matricula);

// ── Verificar que el borrador exista y obtener su descripción ─────────────────────
$borr = sql_do_array(sql_do_query("SELECT id, descripcion, matricula FROM pres_sr_borrador WHERE id = '$bid_esc'", $db, $usu));
if (!$borr) {
    echo json_encode(['ok' => false, 'error' => "Borrador #$id_borrador no encontrado."]);
    exit;
}
// Usar la descripción del borrador si no se envió por POST
if (empty($desc_renglon)) {
    $desc_renglon = trim($borr['DESCRIPCION'] ?? '');
}

// ── Configuración Ollama ───────────────────────────────────────────────────
$host  = defined('OLLAMA_HOST')  ? OLLAMA_HOST  : 'http://localhost:11434';
$model = defined('OLLAMA_MODEL') ? OLLAMA_MODEL : 'qwen3:8b';

if (!function_exists('ollama_chat')) {
    echo json_encode(['ok' => false, 'error' => 'Función ollama_chat no disponible.']);
    exit;
}

// ── Datos de la matrícula ──────────────────────────────────────────────────
$sql_info = "SELECT sr.matricula AS MATRICULA,
                    MIN(sr.descripcion) AS DESCRIPCION,
                    ig.codigo AS ITEM_CODIGO,
                    ig.nombre AS ITEM_NOMBRE,
                    COUNT(DISTINCT sr.codigo) AS CANT_SR,
                    SUM(sr.monto_linea_oc_usd) AS TOTAL_USD,
                    GROUP_CONCAT(DISTINCT sr.proveedor ORDER BY sr.fecha DESC SEPARATOR ' | ') AS PROVEEDORES,
                    GROUP_CONCAT(DISTINCT sr.orden_compra ORDER BY sr.fecha DESC SEPARATOR ', ') AS OCS
             FROM pres_k2b_sr_logs sr
             INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
             WHERE sr.matricula = '$mat_esc'
             GROUP BY sr.matricula, ig.codigo, ig.nombre
             ORDER BY CANT_SR DESC LIMIT 1;";
$info = sql_do_array(sql_do_query($sql_info, $db, $usu));

if (!$info) {
    echo json_encode(['ok' => false, 'error' => "No se encontraron datos de SR para la matrícula $matricula."]);
    exit;
}

// ── Historial de compras ──────────────────────────────────────────────────
$sql_compras = "SELECT sr.codigo, sr.descripcion, sr.estado, sr.fecha,
                       sr.precio_unitario, sr.cantidad_solicitada, sr.cantidad_autorizada,
                       sr.orden_compra, sr.monto_linea_oc_usd, sr.proveedor
                FROM pres_k2b_sr_logs sr
                WHERE sr.matricula = '$mat_esc'
                ORDER BY sr.fecha DESC LIMIT 10;";
$res_compras = sql_do_query($sql_compras, $db, $usu);
$compras = [];
while ($rc = sql_do_array($res_compras)) $compras[] = $rc;

// ── Construir prompt y llamar a IA ───────────────────────────────────────────
include_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ia_prompt_builder.php';

$historial_texto = ia_formatear_historial($compras, 'compacto');

$system_prompt = ia_build_system_prompt();
$user_msg = ia_build_user_message([
    'matricula'          => $info['MATRICULA'],
    'item_info'          => ($info['ITEM_CODIGO'] ?? '') . ' - ' . ($info['ITEM_NOMBRE'] ?? ''),
    'descripcion_renglon' => $desc_renglon,
    'descripciones'      => $info['DESCRIPCION'] ?? '',
    'proveedores'        => $info['PROVEEDORES'] ?? 'Sin datos',
    'cant_sr'            => $info['CANT_SR'],
    'total_usd'          => (float)($info['TOTAL_USD'] ?? 0),
    'historial_texto'    => $historial_texto,
    'cant_compras'       => count($compras),
]);

$messages = [
    ['role' => 'system', 'content' => $system_prompt],
    ['role' => 'user',   'content' => $user_msg],
];

$r = ollama_chat($messages, $model, $host, ['temperature' => 0.3], 90);

if (!isset($r['estado']) || (int)$r['estado'] !== 1 || empty($r['respuesta'])) {
    echo json_encode(['ok' => false, 'error' => 'Ollama: ' . ($r['error'] ?? 'sin respuesta')]);
    exit;
}

// ── Parsear respuesta ────────────────────────────────────────────────────
$parsed = ia_parse_response($r['respuesta']);
if (!$parsed['ok']) {
    echo json_encode(array_merge($parsed, ['raw' => $r['respuesta']]));
    exit;
}
$resultado = $parsed['campos'];
$campos_esperados = ia_campos_esperados();

// ── Guardar en pres_sr_borrador_eett ──────────────────────────────────────
$sets = [];
$cols = ['id_borrador'];
$vals = ["'$bid_esc'"];
foreach ($campos_esperados as $ce) {
    $v = addslashes($resultado[$ce]);
    $sets[] = "$ce = '$v'";
    $cols[] = $ce;
    $vals[] = "'$v'";
}
$sets[] = "auditoria_usu = '$usu'";
$cols[] = 'auditoria_usu';
$vals[] = "'$usu'";

$cols_sql = implode(', ', $cols);
$vals_sql = implode(', ', $vals);
$sets_sql = implode(', ', $sets);

$sql_upsert = "INSERT INTO pres_sr_borrador_eett ($cols_sql)
               VALUES ($vals_sql)
               ON DUPLICATE KEY UPDATE $sets_sql;";
sql_do_query($sql_upsert, $db, $usu);

echo json_encode([
    'ok'          => true,
    'id_borrador' => $id_borrador,
    'campos'      => $resultado,
    'modelo'      => $model,
], JSON_UNESCAPED_UNICODE);
