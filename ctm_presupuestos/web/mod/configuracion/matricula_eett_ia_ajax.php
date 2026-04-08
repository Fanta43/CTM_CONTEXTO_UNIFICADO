<?php
/**
 * ============================================================================
 * AJAX: Auto-completar EETT de una matrícula con IA (Ollama)
 * ============================================================================
 * Endpoint llamado vía fetch() desde detalle_matricula.php.
 * Recibe: matricula (código de matrícula)
 * Devuelve JSON con los 11 campos EETT sugeridos.
 * ============================================================================
 */
// Garantizar respuesta JSON siempre
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

// Cargar infraestructura (sesión, DB, funciones)
include_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
include_once APP_CONFIG . 'session.php';

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Verificar sesión activa
if (empty($usu)) {
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida.']);
    exit;
}

$matricula = isset($_POST['matricula']) ? trim($_POST['matricula']) : '';
$descripcion_renglon = isset($_POST['descripcion_renglon']) ? trim($_POST['descripcion_renglon']) : '';
if ($matricula === '') {
    echo json_encode(['ok' => false, 'error' => 'No se especificó matrícula.']);
    exit;
}

$db = '1';
$mat_esc = addslashes($matricula);

// ── Obtener datos de la matrícula ───────────────────────────────────────────
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
             LIMIT 1;";
$res_info = sql_do_query($sql_info, $db, $usu);
$info = sql_do_array($res_info);

if (!$info) {
    echo json_encode(['ok' => false, 'error' => 'No se encontraron datos para la matrícula.']);
    exit;
}

// ── Obtener últimas descripciones (variantes) ───────────────────────────────
$sql_desc = "SELECT DISTINCT sr.descripcion
             FROM pres_k2b_sr_logs sr
             WHERE sr.matricula = '$mat_esc' AND sr.descripcion IS NOT NULL AND sr.descripcion != ''
             ORDER BY sr.fecha DESC LIMIT 5;";
$res_desc = sql_do_query($sql_desc, $db, $usu);
$descripciones = [];
while ($rd = sql_do_array($res_desc)) {
    $descripciones[] = $rd['DESCRIPCION'];
}

// ── Historial de compras (últimas 10 SR con OC) ────────────────────────────
$sql_compras = "SELECT sr.codigo AS SR, sr.fecha AS FECHA, sr.descripcion AS DESCRIPCION,
                       sr.estado AS ESTADO, sr.precio_unitario AS PU,
                       sr.cantidad_solicitada AS CANT_SOL, sr.cantidad_comprada AS CANT_COMP,
                       sr.monto_linea_oc_usd AS MONTO_OC,
                       sr.orden_compra AS OC, sr.proveedor AS PROVEEDOR,
                       sr.usuario AS USUARIO
                FROM pres_k2b_sr_logs sr
                WHERE sr.matricula = '$mat_esc'
                ORDER BY sr.fecha DESC, sr.codigo DESC
                LIMIT 10;";
$res_compras = sql_do_query($sql_compras, $db, $usu);
$compras = [];
while ($rc = sql_do_array($res_compras)) { $compras[] = $rc; }

// ── Construir prompt y llamar a IA ───────────────────────────────────────────
include_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ia_prompt_builder.php';

$model = defined('OLLAMA_MODEL') ? OLLAMA_MODEL : 'qwen3.5:9b';
$host  = defined('OLLAMA_HOST')  ? OLLAMA_HOST  : 'http://localhost:11434';

$historial_texto = ia_formatear_historial($compras);

$system_prompt = ia_build_system_prompt();
$user_msg = ia_build_user_message([
    'matricula'          => $matricula,
    'item_info'          => $info['ITEM_CODIGO'] . ' - ' . $info['ITEM_NOMBRE'],
    'descripcion_renglon' => $descripcion_renglon,
    'descripciones'      => implode("\n- ", $descripciones),
    'proveedores'        => $info['PROVEEDORES'] ?? 'No disponible',
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

// ── Parsear respuesta ───────────────────────────────────────────────────────
$parsed = ia_parse_response($r['respuesta']);
if (!$parsed['ok']) {
    echo json_encode(array_merge($parsed, ['raw' => $r['respuesta']]));
    exit;
}
$resultado = $parsed['campos'];
$campos_esperados = ia_campos_esperados();

// Si se pide guardar (modo masivo), persistir en BD
$guardar = isset($_POST['guardar']) && $_POST['guardar'] === '1';
if ($guardar) {
    $sets = [];
    $cols = [];
    $vals = [];
    foreach ($campos_esperados as $ce) {
        $v = addslashes($resultado[$ce]);
        $sets[] = "$ce = '$v'";
        $cols[] = $ce;
        $vals[] = "'$v'";
    }
    $sets[] = "auditoria_usu = '$usu'";
    $sets_sql = implode(', ', $sets);
    $cols_sql = implode(', ', $cols);
    $vals_sql = implode(', ', $vals);

    $sql_upsert = "INSERT INTO pres_k2b_matricula_categoria (matricula, $cols_sql, auditoria_usu)
                   VALUES ('$mat_esc', $vals_sql, '$usu')
                   ON DUPLICATE KEY UPDATE $sets_sql;";
    sql_do_query($sql_upsert, $db, $usu);
}

echo json_encode([
    'ok'     => true,
    'campos' => $resultado,
    'modelo' => $model,
], JSON_UNESCAPED_UNICODE);
