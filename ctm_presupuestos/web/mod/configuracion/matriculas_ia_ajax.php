<?php
/**
 * ============================================================================
 * AJAX: Auto-categorizar matrículas con IA (Ollama)
 * ============================================================================
 * Endpoint llamado vía fetch() desde matriculas.php.
 * Recibe filtro_item (opcional) y ejecuta matriculas_ia_categorizar().
 * Devuelve JSON con resultado.
 * ============================================================================
 */
header('Content-Type: application/json; charset=utf-8');

// Cargar infraestructura (sesión, DB, funciones)
include_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
include_once APP_CONFIG . 'session.php';

// Verificar sesión activa
if (empty($usu)) {
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida.']);
    exit;
}

$filtro_item = isset($_POST['filtro_item']) ? trim($_POST['filtro_item']) : null;
if ($filtro_item === '' || $filtro_item === 'null') $filtro_item = null;

$resultado = matriculas_ia_categorizar($filtro_item, '1', $usu);

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
