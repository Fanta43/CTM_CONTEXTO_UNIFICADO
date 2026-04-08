<?php
/**
 * AJAX endpoint para importar presupuesto K2B.
 * Recibe anio y cc via POST, ejecuta sync_k2b_presupuesto() y devuelve JSON.
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

// functions.php carga config.php, database.php y helpers.php internamente
require_once __DIR__ . '/../../../includes/functions.php';

$usu = $_SESSION['us'] ?? '9999';

$anio = (int)($_POST['anio'] ?? 0);
$cc   = (int)($_POST['cc']   ?? 0);

if ($anio <= 0 || $cc <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos (anio/cc).']);
    exit;
}

$res = sync_k2b_presupuesto($anio, $cc, '1', $usu);
echo json_encode($res, JSON_UNESCAPED_UNICODE);
