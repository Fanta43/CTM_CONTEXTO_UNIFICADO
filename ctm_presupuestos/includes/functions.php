<?php
/**
 * ============================================================================
 * FUNCIONES CENTRALES - ctm_presupuestos
 * ============================================================================
 * Punto de entrada principal. Carga config, database, session y helpers.
 * Incluye funciones legacy necesarias para el módulo presupuestos.
 * ============================================================================
 */

if (!defined('PRES_APP')) define('PRES_APP', true);

// ── Cargar infraestructura ───────────────────────────────────────────────────
if (!defined('APP_ROOT')) {
    require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
}
require_once APP_CONFIG . 'database.php';
require_once APP_INCLUDES . 'helpers.php';

// ── Integracion cross-module ────────────────────────────────────────────────
require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'ctm_entorno' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'integracion.php';

// ── PHPMailer ────────────────────────────────────────────────────────────────
$phpmailerPath = APP_CONFIG . 'lib' . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
if (file_exists($phpmailerPath . 'PHPMailer.php')) {
    require_once $phpmailerPath . 'Exception.php';
    require_once $phpmailerPath . 'PHPMailer.php';
    require_once $phpmailerPath . 'SMTP.php';
}

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES UTILITARIAS DEL MÓDULO (migradas desde conf/funcion.php)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Generar ID secuencial para una tabla
 */
function get_id($tabla, $db = '1', $usu = '99999999') {
    $sql = "SELECT MAX(id) AS MAXID FROM $tabla;";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    return ($row['MAXID'] ?? 0) + 1;
}

/**
 * Sanitizar input (prevención básica XSS/SQLi)
 */
function sani_input($txt) {
    $buscado = array("<?PHP", "?>", "SELECT ", "FROM", "WHERE", "INSERT", "INTO", "UPDATE", ";", "SET", "SCRIPT", "LINK", "META ", "SYSTEM", " AND ", " OR ",
                     "<?php", "?>", "select ", "from", "where", "insert", "into", "update", ";", "set", "script", "link", "meta ", "system", " And ", " Or ",
                     "<?Php", "?>", "Select ", "From", "Where", "Insert", "Into", "Update", ";", "Set", "Script", "Link", "Meta ", "System", " and ", " or ","'",'"');
    return str_replace($buscado, '', $txt);
}

/**
 * Enmascarar POST
 */
function Enmascarar_post($clave) {
    return sani_input($_POST[$clave] ?? '');
}

/**
 * Enmascarar GET
 */
function Enmascarar_get($clave) {
    return sani_input($_GET[$clave] ?? '');
}

/**
 * Enmascarar fecha (dd/mm/yyyy)
 */
function enmascararfecha($fecha) {
    if (!$fecha) return '';
    $d = substr($fecha, 8, 2);
    $m = substr($fecha, 5, 2);
    $y = substr($fecha, 0, 4);
    return "$d/$m/$y";
}

/**
 * Enmascarar mes
 */
function enmascararmes($mes) {
    $meses = array('1'=>'ENE','2'=>'FEB','3'=>'MAR','4'=>'ABR','5'=>'MAY','6'=>'JUN',
                   '7'=>'JUL','8'=>'AGO','9'=>'SEP','10'=>'OCT','11'=>'NOV','12'=>'DIC');
    return $meses[$mes] ?? '';
}

/**
 * Completar con ceros a la izquierda
 */
function completarCeros($number, $n) {
    return str_pad((int)$number, $n, "0", STR_PAD_LEFT);
}

/**
 * Combo desplegable genérico
 */
function imp_combo_w($nombrecombo, $query, $db, $usu, $actual = '') {
    $str = '<select class="form-select" name="' . $nombrecombo . '" id="' . $nombrecombo . '">';
    $campos = array('NOMBRE','ID');
    $result = sql_do_query($query, $db, $usu);
    $str .= '<option value="null">Seleccione</option>';
    while ($row = sql_do_array($result, $db)) {
        $sel = ($actual == $row[$campos['1']]) ? ' selected' : '';
        $str .= '<option value="' . $row[$campos['1']] . '"' . $sel . '>' . $row[$campos['0']] . '</option>';
    }
    $str .= '</select>';
    return $str;
}

/**
 * Combo con onchange submit
 */
function imp_combo_w_oc($nombrecombo, $query, $db, $usu, $actual) {
    $str = '<select class="form-select" name="' . $nombrecombo . '" id="' . $nombrecombo . '" onchange="this.form.submit()">';
    $campos = array('NOMBRE','ID');
    $result = sql_do_query($query, $db, $usu);
    $str .= '<option value="null">Seleccione</option>';
    while ($row = sql_do_array($result, $db)) {
        $sel = ($actual == $row[$campos['1']]) ? ' selected' : '';
        $str .= '<option value="' . $row[$campos['1']] . '"' . $sel . '>' . $row[$campos['0']] . '</option>';
    }
    $str .= '</select>';
    return $str;
}

/**
 * Combo font-size 12
 */
function imp_combo_w_fs12($nombrecombo, $query, $db, $usu, $actual) {
    $str = '<select class="form-select fs-12" name="' . $nombrecombo . '" id="' . $nombrecombo . '">';
    $campos = array('NOMBRE','ID');
    $result = sql_do_query($query, $db, $usu);
    $str .= '<option value="null">Seleccione</option>';
    while ($row = sql_do_array($result, $db)) {
        $sel = ($actual == $row[$campos['1']]) ? ' selected' : '';
        $str .= '<option value="' . $row[$campos['1']] . '"' . $sel . '>' . $row[$campos['0']] . '</option>';
    }
    $str .= '</select>';
    return $str;
}

/**
 * Imprimir nombre de usuario
 */
function imp_usuario($usuarioid, $db, $usu) {
    $sql = "SELECT a.EmpNombre, a.EmpLegajo FROM vw_ctm_plantel a WHERE a.EmpLegajo = '$usuarioid';";
    $rowpersona = sql_do_array(sql_do_query($sql, $db, $usu));
    return " " . ($rowpersona['EMPNOMBRE'] ?? '') . " (" . ($rowpersona['EMPLEGAJO'] ?? '') . ") ";
}

/**
 * Resolver centro de costos
 */
function imp_est_accc($usuarioid, $db, $usu) {
    $sql = "SELECT ConNomCc FROM vw_accc WHERE ConIdcc = '$usuarioid';";
    $rowpersona = sql_do_array(sql_do_query($sql, $db, $usu));
    return $rowpersona['CONNOMCC'] ?? '';
}

/**
 * Verificar existencia en tabla
 */
function existe($valor, $campo, $tabla, $usu) {
    $sql = "SELECT $campo FROM $tabla WHERE UPPER($campo) = UPPER('$valor');";
    $result = sql_do_query($sql, '1', $usu);
    return (sql_do_array($result)) ? "SI" : "NO";
}

/**
 * Registrar acceso de usuario
 */
function acceso_usuario($nombredeusuario, $descripcion, $usu, $codigo) {
    $id = get_id('USU_ACCESO_USUARIOS');
    if ($codigo == 0) $codigo = "SCOM_001";
    if ($nombredeusuario == 0) $nombredeusuario = '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $sql = "INSERT INTO usu_acceso_usuarios VALUES ($id, '$nombredeusuario', '$codigo', '$descripcion', SYSDATE, SYSDATE, '$ip', 12, $usu);";
    $result = sql_do_query($sql, '1', $usu);
    return $id;
}

/**
 * Redirigir página via form submit
 */
function reenviar_pagina($escuela) {
    $str = '<form id="form1" name="form1" method="post" action="' . $escuela . '"><script>form1.submit();</script></form>';
    echo $str;
    return $str;
}

/**
 * Resolver combo detallado
 */
function res_combo_detallados($nombrecombo, $tabla, $db, $usu, $id = "id", $nombre = "nombre", $id_valor = "1") {
    $campos = array($id, $nombre);
    $query = "SELECT * FROM $tabla WHERE $id='$id_valor'";
    $result = sql_do_query($query, $db, $usu);
    $str = '';
    while ($row = sql_do_array($result, $db)) {
        $str .= $row[$campos['1']] . " ";
    }
    return $str;
}

/**
 * Imprimir lista
 */
function imp_lista($array) {
    $str = '';
    if ($array) {
        foreach ($array as $destino1 => $destino2) {
            $str .= "$destino1" . ' ';
        }
    }
    return $str;
}

/**
 * Lista para SELECT IN
 */
function imp_lista_select($array) {
    $str = "";
    if ($array) {
        $n = 0;
        foreach ($array as $destino_terminal1 => $destino_terminal2) {
            if ($n != 0) $str .= " ,";
            $str .= "'" . "$destino_terminal1" . "'";
            $n++;
        }
    }
    return $str;
}

/**
 * Select herramienta dinámico
 */
function impSelectHerramienta($variables, $estados, $n = 0) {
    $str = '<form id="detalle_permiso' . $n . '" name="detalle_permiso' . $n . '" method="post" action="">';
    foreach ($variables as $key => $value) {
        $str .= '<input type="hidden" name="' . $key . '" id="' . $key . '" value="' . $value . '"/>';
    }
    $str .= '<select class="form-select" name="estado' . $n . '" id="estado' . $n . '">
             <option value="" disabled selected>Elige una opción</option>';
    foreach ($estados as $key => $value) {
        $str .= '<option value="' . $key . '">' . $value . '</option>';
    }
    $str .= '</select></form>';
    $str .= '<script>
        document.getElementById("estado' . $n . '").addEventListener("change", function() {
            var form = document.getElementById("detalle_permiso' . $n . '");
            form.action = "index.php?estado=" + this.value;
            form.submit();
        });
    </script>';
    return $str;
}

/**
 * URL con parámetro tab
 */
function getCurrentUrlWithTab($tabValue, $presupuestoid) {
    $params = $_GET;
    $params['tab'] = $tabValue;
    $params['presupuestoid'] = $presupuestoid;
    return basename($_SERVER['PHP_SELF']) . '?' . http_build_query($params);
}

function getCurrentUrlWithTabLegajo($tabValue, $legajo) {
    $params = $_GET;
    $params['tab'] = $tabValue;
    $params['legajo'] = $legajo;
    return basename($_SERVER['PHP_SELF']) . '?' . http_build_query($params);
}

/**
 * Herramienta pie de usuario
 */
function herramienta_pie($usuario, $nivel) {
    $niveles = [1 => 'CONSULTA', 4 => 'DCIA', 6 => 'ABM', 9 => 'ADMIN'];
    $niveltex = $niveles[$nivel] ?? 'CONSULTA';
    return '<div class="d-flex align-items-center gap-2 small text-muted">
                <i class="bi bi-person-circle"></i> <span>' . htmlspecialchars($usuario) . '</span>
                <span class="badge bg-secondary">' . $niveltex . '</span>
            </div>';
}

/**
 * Refresco automático de página
 */
function refrescoPagina($tiempo_refresco) {
    echo "<script>setInterval(function(){ location.reload(true); }, $tiempo_refresco);</script>";
}

/**
 * Ollama chat (AI local)
 */
function ollama_chat($messages, $model = 'phi3:14b', $host = 'http://localhost:11434', $options = [], $timeout = 60) {
    if (!is_array($messages) || empty($messages)) {
        return ['estado' => 0, 'respuesta' => '', 'error' => 'Mensajes inválidos para Ollama.'];
    }
    $url = rtrim($host, '/') . '/api/chat';
    $payload = ['model' => $model, 'messages' => $messages, 'stream' => false];
    if (is_array($options) && !empty($options)) $payload['options'] = $options;

    $ch = curl_init($url);
    if ($ch === false) return ['estado' => 0, 'respuesta' => '', 'error' => 'No se pudo inicializar cURL.'];
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) return ['estado' => 0, 'respuesta' => '', 'error' => 'Error cURL: ' . $err];
    $data = json_decode($raw, true);
    if (!is_array($data)) return ['estado' => 0, 'respuesta' => '', 'error' => 'Respuesta inválida. HTTP ' . $http];
    if ($http < 200 || $http >= 300) return ['estado' => 0, 'respuesta' => '', 'error' => $data['error'] ?? 'HTTP ' . $http];
    $respuesta = isset($data['message']['content']) ? (string)$data['message']['content'] : '';
    return ['estado' => 1, 'respuesta' => $respuesta, 'raw' => $data];
}

/**
 * Ollama generate (AI local, prompt simple sin roles)
 */
function ollama_generate($prompt, $model = 'llama3.1', $host = 'http://localhost:11434', $options = [], $timeout = 60) {
    $prompt = (string)$prompt;
    if ($prompt === '') {
        return ['estado' => 0, 'respuesta' => '', 'error' => 'Prompt vacío para Ollama.'];
    }
    $url = rtrim($host, '/') . '/api/generate';
    $payload = ['model' => $model, 'prompt' => $prompt, 'stream' => false];
    if (is_array($options) && !empty($options)) $payload['options'] = $options;

    $ch = curl_init($url);
    if ($ch === false) return ['estado' => 0, 'respuesta' => '', 'error' => 'No se pudo inicializar cURL.'];
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) return ['estado' => 0, 'respuesta' => '', 'error' => 'Error cURL: ' . $err];
    $data = json_decode($raw, true);
    if (!is_array($data)) return ['estado' => 0, 'respuesta' => '', 'error' => 'Respuesta inválida. HTTP ' . $http];
    if ($http < 200 || $http >= 300) return ['estado' => 0, 'respuesta' => '', 'error' => $data['error'] ?? 'HTTP ' . $http];
    $respuesta = isset($data['response']) ? (string)$data['response'] : '';
    return ['estado' => 1, 'respuesta' => $respuesta, 'raw' => $data];
}

/**
 * Auto-categorizar matrículas sin categoría usando Ollama (IA).
 *
 * Para cada matrícula sin categoría asignada dentro de un ítem, envía al LLM
 * la descripción del producto junto con la lista de categorías disponibles
 * para ese ítem y le pide que elija la más apropiada.
 *
 * @param string|null $filtro_item  Código de ítem (null = todos)
 * @param string      $db           ID de base de datos
 * @param string      $usu          ID de usuario auditoría
 * @param string      $model        Modelo Ollama
 * @param string      $host         Host Ollama
 * @return array      Resultado: ok, asignadas, errores, detalle[]
 */
function matriculas_ia_categorizar($filtro_item = null, $db = '1', $usu = '9999', $model = null, $host = null) {
    $model = $model ?? (defined('OLLAMA_MODEL') ? OLLAMA_MODEL : 'phi3:14b');
    $host  = $host  ?? (defined('OLLAMA_HOST')  ? OLLAMA_HOST  : 'http://localhost:11434');

    // 1. Obtener matrículas sin categoría
    $where = "sr.matricula IS NOT NULL AND sr.matricula != ''";
    if ($filtro_item && $filtro_item !== 'null') {
        $filtro_item_esc = addslashes($filtro_item);
        $where .= " AND ig.codigo = '$filtro_item_esc'";
    }

    $sql = "SELECT sr.matricula AS MATRICULA,
                   MIN(sr.descripcion) AS DESCRIPCION,
                   ig.codigo AS ITEM_CODIGO,
                   ig.id AS ITEM_ID
            FROM pres_k2b_sr_logs sr
            INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
            LEFT JOIN pres_k2b_matricula_categoria mc ON mc.matricula = sr.matricula
            WHERE $where AND mc.id_categoria IS NULL
            GROUP BY sr.matricula, ig.codigo, ig.id
            ORDER BY ig.codigo, sr.matricula";

    $result = sql_do_query($sql, $db, $usu);
    $pendientes = [];
    while ($row = sql_do_array($result)) {
        $pendientes[] = $row;
    }

    if (empty($pendientes)) {
        return ['ok' => true, 'asignadas' => 0, 'errores' => 0, 'detalle' => [], 'msg' => 'No hay matrículas sin categoría.'];
    }

    // 2. Obtener categorías agrupadas por ítem
    $sql_cats = "SELECT cg.id, cg.codigo, cg.nombre, ig.codigo AS item_codigo
                 FROM ciclo_categoria_gasto cg
                 JOIN ciclo_item_gasto ig ON cg.id_item = ig.id
                 WHERE cg.activo = 1
                 ORDER BY ig.codigo, cg.codigo";
    $res_cats = sql_do_query($sql_cats, $db, $usu);
    $cats_por_item = [];
    while ($cat = sql_do_array($res_cats)) {
        $cats_por_item[$cat['ITEM_CODIGO']][] = $cat;
    }

    // 3. Para cada matrícula sin categoría, consultar al LLM
    $asignadas = 0;
    $errores   = 0;
    $detalle   = [];

    foreach ($pendientes as $mat) {
        $item_cod    = $mat['ITEM_CODIGO'];
        $matricula   = $mat['MATRICULA'];
        $descripcion = $mat['DESCRIPCION'] ?? '';
        $cats        = $cats_por_item[$item_cod] ?? [];

        if (empty($cats)) {
            $detalle[] = ['matricula' => $matricula, 'status' => 'skip', 'msg' => 'Sin categorías disponibles para el ítem ' . $item_cod];
            continue;
        }

        // Construir lista de categorías para el prompt
        $lista_cats = '';
        $ids_validos = [];
        foreach ($cats as $c) {
            $lista_cats .= "ID:" . $c['ID'] . " | " . $c['CODIGO'] . " - " . $c['NOMBRE'] . "\n";
            $ids_validos[] = (int)$c['ID'];
        }

        $system_prompt = <<<PROMPT
Eres un clasificador de productos industriales y de mantenimiento. Tu tarea es asignar UNA categoría de gasto a un producto basándote en su descripción.

INSTRUCCIONES:
- Analiza la descripción del producto.
- De la lista de categorías disponibles, elige la que MEJOR se aproxime al producto.
- Responde ÚNICAMENTE con el número ID de la categoría elegida. Nada más.
- No agregues explicaciones, texto adicional ni puntuación.
- Si no puedes determinar la categoría, responde con el ID de la categoría más general o amplia.

CATEGORÍAS DISPONIBLES:
$lista_cats
PROMPT;

        $user_msg = "Producto: $matricula\nDescripción: $descripcion";

        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user',   'content' => $user_msg],
        ];

        $r = ollama_chat($messages, $model, $host, [], 30);

        if (!isset($r['estado']) || (int)$r['estado'] !== 1 || empty($r['respuesta'])) {
            $errores++;
            $detalle[] = ['matricula' => $matricula, 'status' => 'error', 'msg' => 'Ollama: ' . ($r['error'] ?? 'sin respuesta')];
            continue;
        }

        // Extraer ID numérico de la respuesta
        $respuesta = trim($r['respuesta']);
        preg_match('/(\d+)/', $respuesta, $matches);
        $id_sugerido = isset($matches[1]) ? (int)$matches[1] : 0;

        if (!in_array($id_sugerido, $ids_validos)) {
            $errores++;
            $detalle[] = ['matricula' => $matricula, 'status' => 'error', 'msg' => "ID $id_sugerido no válido (respuesta: $respuesta)"];
            continue;
        }

        // Buscar nombre de la categoría asignada
        $cat_nombre = '';
        foreach ($cats as $c) {
            if ((int)$c['ID'] === $id_sugerido) {
                $cat_nombre = $c['CODIGO'] . ' - ' . $c['NOMBRE'];
                break;
            }
        }

        // Guardar asignación
        $mat_esc = addslashes($matricula);
        $sql_upsert = "INSERT INTO pres_k2b_matricula_categoria (matricula, id_categoria, auditoria_usu)
                        VALUES ('$mat_esc', $id_sugerido, '$usu')
                        ON DUPLICATE KEY UPDATE id_categoria = $id_sugerido, auditoria_usu = '$usu'";
        sql_do_query($sql_upsert, $db, $usu);
        $asignadas++;
        $detalle[] = ['matricula' => $matricula, 'status' => 'ok', 'cat_id' => $id_sugerido, 'cat_nombre' => $cat_nombre];
    }

    return [
        'ok'        => true,
        'asignadas' => $asignadas,
        'errores'   => $errores,
        'total'     => count($pendientes),
        'detalle'   => $detalle,
    ];
}

/**
 * Enviar email via PHPMailer o mail()
 */
function EnviarEmail($parametros) {
    if (!$parametros) return "ERROR";
    return "LA OPERACION SE EFECTUO CORRECTAMENTE";
}

/**
 * Enviar correo de seguimiento de SR via PHPMailer + SMTP (conf_COM 'SG')
 *
 * @param string $destino      Correo principal de destino (opcional)
 * @param array  $mensajeArray Claves: from, subject, cc (opcional)
 * @param string $mensaje      Cuerpo HTML completo
 * @param string &$errorInfo   Referencia para capturar error
 * @return int                 1=OK, 0=error
 */
function presupuestos_enviar_email_sr($destino = '', array $mensajeArray = [], $mensaje = '', &$errorInfo = '') {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') && !class_exists('PHPMailer')) {
        $errorInfo = 'PHPMailer no disponible.';
        return 0;
    }
    $data = function_exists('conf_COM') ? conf_COM('SG') : [];
    if (empty($data) || ($data['estado'] ?? 0) != 1) {
        $errorInfo = 'Configuración SMTP no disponible (conf_COM SG).';
        return 0;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = $data['host'];
        $mail->SMTPAuth   = $data['smtpauth'];
        $mail->Username   = $data['username'];
        $mail->Password   = $data['password'];
        $mail->SMTPSecure = $data['smtpsecure'];
        $mail->Port       = $data['port'];
        $mail->CharSet    = 'UTF-8';

        $from = $mensajeArray['from'] ?? $data['username'];
        $mail->setFrom($from);
        $mail->addAddress($data['username']); // siempre al remitente

        if (!empty($destino)) $mail->addAddress($destino);
        if (!empty($mensajeArray['cc'])) $mail->addCC($mensajeArray['cc']);

        $mail->isHTML(true);
        $mail->Subject = $mensajeArray['subject'] ?? 'Seguimiento de Solicitudes de Recursos (SR)';
        $mail->Body    = $mensaje;

        if ($mail->send()) return 1;
        $errorInfo = $mail->ErrorInfo;
    } catch (Exception $e) {
        $errorInfo = $e->getMessage();
    }
    return 0;
}

/**
 * Limpiar caracteres especiales
 */
function limpiar_caracteres_raros($texto) {
    $texto = strtolower($texto);
    $texto = iconv(mb_detect_encoding($texto, mb_detect_order(), true), "UTF-8", $texto);
    $find = array('~', "'", '=', '\\\n', '\\', '<div>', '</div>', '  ', '     ', '   ', '    ', '</br>');
    $repl = array('', '', '', '', '', '', '', ' ', ' ', ' ', ' ', '');
    $texto = str_replace($find, $repl, $texto);
    return strtoupper($texto);
}

/**
 * Configuración de comunicaciones
 */
function conf_COM_legacy($tipo_com = 'BCRA') {
    return conf_COM($tipo_com);
}

// ═══════════════════════════════════════════════════════════════════════════════
// SYNC K2B → CICLO PRESUPUESTARIO (PHP puro, sin SP)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Sincronizar datos K2B con ciclo_presupuesto (PHP puro).
 * Crea/actualiza un presupuesto aprobado a partir de pres_k2b_presupuestos.
 * Usa MAX(id) por campo0 para dedup y match exacto del 3er segmento.
 *
 * @param int    $anio  Año fiscal (ej: 2025)
 * @param int    $cc    Centro de costos (ej: 880)
 * @param string $db    ID de base de datos
 * @param string $usu   ID de usuario auditoría
 * @return array        Resultado con presupuesto_id, monto_total, cant_partidas, etc.
 */
function sync_k2b_presupuesto($anio, $cc = 880, $db = '1', $usu = '9999') {
    $anio    = (int)$anio;
    $cc      = (int)$cc;
    $usu_int = (int)$usu;
    $now     = date('Y-m-d H:i:s');
    $yy      = substr((string)$anio, 2, 2); // ej: "25" para 2025

    try {
        // ── 1. Resolver período fiscal ──────────────────────────────────────
        $res = sql_do_query("SELECT id FROM ciclo_periodo_fiscal WHERE anio = $anio LIMIT 1", $db, $usu);
        $row = sql_do_array($res);
        if ($row) {
            $id_periodo = (int)$row['ID'];
        } else {
            sql_do_query("INSERT INTO ciclo_periodo_fiscal (anio, fecha_inicio, fecha_fin, estado, auditoria_usu)
                          VALUES ($anio, '$anio-01-01', '$anio-12-31', 'abierto', $usu_int)", $db, $usu);
            $res2 = sql_do_query("SELECT LAST_INSERT_ID() AS LID", $db, $usu);
            $row2 = sql_do_array($res2);
            $id_periodo = (int)$row2['LID'];
        }

        // ── 2. Resolver unidad organizativa por CC ──────────────────────────
        $res = sql_do_query("SELECT id, nivel_aprobacion FROM ciclo_unidad_organizativa WHERE cc = $cc LIMIT 1", $db, $usu);
        $row = sql_do_array($res);
        if (!$row) {
            return ['ok' => false, 'error' => "No existe unidad organizativa con CC=$cc"];
        }
        $id_unidad      = (int)$row['ID'];
        $nivel_max      = (int)($row['NIVEL_APROBACION'] ?? 1);

        // ── 3. Buscar/crear presupuesto ─────────────────────────────────────
        $es_nuevo = 0;
        $res = sql_do_query("SELECT id FROM ciclo_presupuesto
                             WHERE id_periodo = $id_periodo AND id_unidad = $id_unidad LIMIT 1", $db, $usu);
        $row = sql_do_array($res);

        if ($row) {
            $id_presupuesto = (int)$row['ID'];
            // Limpiar partidas previas y dependencias
            sql_do_query("DELETE rd FROM ciclo_revision_detalle rd
                          JOIN ciclo_partida pa ON rd.id_partida = pa.id
                          WHERE pa.id_presupuesto = $id_presupuesto", $db, $usu);
            sql_do_query("DELETE sp FROM ciclo_solicitud_partida sp
                          JOIN ciclo_partida pa ON sp.id_partida = pa.id
                          WHERE pa.id_presupuesto = $id_presupuesto", $db, $usu);
            sql_do_query("DELETE ac FROM ciclo_alerta_consumo ac
                          JOIN ciclo_partida pa ON ac.id_partida = pa.id
                          WHERE pa.id_presupuesto = $id_presupuesto", $db, $usu);
            sql_do_query("DELETE FROM ciclo_partida WHERE id_presupuesto = $id_presupuesto", $db, $usu);
            sql_do_query("DELETE FROM ciclo_revision WHERE id_presupuesto = $id_presupuesto
                          AND observaciones LIKE 'Aprobación automática desde K2B%'", $db, $usu);
        } else {
            sql_do_query("INSERT INTO ciclo_presupuesto
                          (id_periodo, id_unidad, estado, origen, monto_total, fecha_creacion, fecha_aprobacion, auditoria_usu, auditoria_fecha)
                          VALUES ($id_periodo, $id_unidad, 'aprobado', 'k2b', 0.00, '$now', '$now', $usu_int, '$now')", $db, $usu);
            $res2 = sql_do_query("SELECT LAST_INSERT_ID() AS LID", $db, $usu);
            $row2 = sql_do_array($res2);
            $id_presupuesto = (int)$row2['LID'];
            $es_nuevo = 1;
        }

        // ── 4. Insertar partidas (dedup MAX(id), match 3er segmento) ────────
        $sql_insert_partidas = "
            INSERT INTO ciclo_partida
                (id_presupuesto, id_item, id_categoria,
                 monto_proyectado, monto_aprobado, monto_comprometido, monto_ejecutado,
                 auditoria_usu, auditoria_fecha)
            SELECT
                $id_presupuesto,
                ig.id,
                NULL,
                SUM(k.importe_planificado),
                SUM(k.importe_planificado),
                SUM(k.importe_definitivo),
                SUM(k.importe_causado),
                $usu_int,
                '$now'
            FROM pres_k2b_presupuestos k
            JOIN (
                SELECT MAX(id) AS max_id FROM pres_k2b_presupuestos
                WHERE campo0 REGEXP '^[0-9]{2}-[0-9]+-[0-9]+-'
                GROUP BY campo0
            ) ult ON k.id = ult.max_id
            JOIN ciclo_item_gasto ig
                ON SUBSTRING_INDEX(SUBSTRING_INDEX(k.campo0, '-', 3), '-', -1) = ig.codigo
            WHERE SUBSTRING(k.campo0, 1, 2) = '$yy'
              AND CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(k.campo0, '-', 2), '-', -1) AS UNSIGNED) = $cc
            GROUP BY ig.id";
        sql_do_query($sql_insert_partidas, $db, $usu);

        // ── 5. Actualizar totales ───────────────────────────────────────────
        $res = sql_do_query("SELECT COALESCE(SUM(monto_aprobado),0) AS TOTAL,
                                    COUNT(*) AS CANT
                             FROM ciclo_partida WHERE id_presupuesto = $id_presupuesto", $db, $usu);
        $row = sql_do_array($res);
        $monto_total   = (float)($row['TOTAL'] ?? 0);
        $cant_partidas = (int)($row['CANT'] ?? 0);

        sql_do_query("UPDATE ciclo_presupuesto
                      SET monto_total = $monto_total, estado = 'aprobado', origen = 'k2b',
                          fecha_aprobacion = '$now', auditoria_usu = $usu_int, auditoria_fecha = '$now'
                      WHERE id = $id_presupuesto", $db, $usu);

        // ── 6. Trail de aprobación automática ───────────────────────────────
        // Usar id_usuario=1 (Administrador General) ya que el usu de sesión
        // puede no existir en ciclo_usuario (FK constraint).
        $res_cu = sql_do_query("SELECT id FROM ciclo_usuario ORDER BY id LIMIT 1", $db, $usu);
        $row_cu = sql_do_array($res_cu);
        $id_usuario_rev = $row_cu ? (int)$row_cu['ID'] : 1;

        for ($nivel = 1; $nivel <= $nivel_max; $nivel++) {
            sql_do_query("INSERT INTO ciclo_revision
                          (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                          VALUES ($id_presupuesto, $id_usuario_rev, $nivel, 'aprobar',
                                  'Aprobación automática desde K2B - Nivel $nivel', '$now')", $db, $usu);
        }

        return [
            'ok'             => true,
            'presupuesto_id' => $id_presupuesto,
            'periodo_id'     => $id_periodo,
            'unidad_id'      => $id_unidad,
            'monto_total'    => $monto_total,
            'es_nuevo'       => $es_nuevo,
            'cant_partidas'  => $cant_partidas,
        ];

    } catch (Exception $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Obtener items K2B sin mapeo a ciclo_item_gasto.
 * Devuelve valores de campo0 (formato directo) sin match en el 3er segmento.
 */
function k2b_items_sin_mapeo($anio = null, $db = '1', $usu = '9999') {
    $where_anio = '';
    if ($anio) {
        $yy = substr((string)(int)$anio, 2, 2);
        $where_anio = "AND k.campo0 LIKE '$yy-%'";
    }
    $sql = "SELECT DISTINCT k.campo0
            FROM pres_k2b_presupuestos k
            LEFT JOIN ciclo_item_gasto ig
                ON SUBSTRING_INDEX(SUBSTRING_INDEX(k.campo0, '-', 3), '-', -1) = ig.codigo
            WHERE ig.id IS NULL
              AND k.campo0 REGEXP '^[0-9]{2}-[0-9]+-[0-9]+-'
              $where_anio
            ORDER BY k.campo0;";
    $result = sql_do_query($sql, $db, $usu);
    $items = [];
    while ($row = sql_do_array($result)) {
        $items[] = $row['CAMPO0'];
    }
    return $items;
}
