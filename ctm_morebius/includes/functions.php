<?php
/**
 * ============================================================================
 * FUNCIONES CENTRALES - ctm_morebius
 * ============================================================================
 * Punto de entrada principal. Carga config, database, session y helpers.
 * Incluye funciones legacy necesarias para el módulo de inventario.
 * ============================================================================
 */

if (!defined('MORE_APP')) define('MORE_APP', true);

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
// FUNCIONES UTILITARIAS (migradas desde conf/funcion.php)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Generar ID secuencial para una tabla
 */
function get_id($tabla, $db = '1', $usu = '99999999') {
    $sql = "SELECT id_tabla AS MAXID FROM more_id WHERE tabla = '$tabla';";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    $nuevo = ($row['MAXID'] ?? 0) + 1;
    sql_do_query("UPDATE more_id SET id_tabla = '$nuevo' WHERE tabla = '$tabla';", $db, $usu);
    return $nuevo;
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
 * Imprimir nombre de empleado desde plantel (morebius DB)
 */
function imp_empleado($legajo, $db = '2', $usu = '99999999') {
    $sql = "SELECT EmpNombre FROM ctm_plantel WHERE EmpLegajo = '$legajo';";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    return $row['EMPNOMBRE'] ?? '';
}

/**
 * Imprimir centro de costos desde morebius DB
 */
function imp_centrocosto($cc, $db = '2', $usu = '99999999') {
    $sql = "SELECT CentroCostoNom FROM ctm_centroscosto WHERE CentroCosto = '$cc';";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    return $row['CENTROCOSTONOM'] ?? '';
}

/**
 * Select herramienta dinámico
 */
function impSelectHerramienta($variables, $estados, $n = 0) {
    $str = '<form id="detalle_permiso' . $n . '" name="detalle_permiso' . $n . '" method="post" action="">';
    foreach ($variables as $key => $value) {
        $str .= '<input type="hidden" name="' . $key . '" id="' . $key . $n . '" value="' . htmlspecialchars($value) . '"/>';
    }
    $str .= '<select class="form-select form-select-sm" name="estado' . $n . '" id="estado' . $n . '">
             <option value="" disabled selected>Acción</option>';
    foreach ($estados as $key => $value) {
        $str .= '<option value="' . $key . '">' . htmlspecialchars($value) . '</option>';
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

// ── Fotos de empleados ────────────────────────────────────────────────────────

/**
 * Verificar si una imagen remota existe (HTTP 200 + contenido)
 */
function imagenExiste($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $contenido = curl_exec($ch);
    if (curl_errno($ch)) { curl_close($ch); return false; }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode === 200 && !empty($contenido));
}

/**
 * Obtener la ruta de imagen de un empleado (cache local o descarga remota)
 */
function determinarImagen($emplegajo, $empnombre, $servidor_img) {
    $destDir = preg_replace('#^(.*ctm_morebius).*#', '$1', __DIR__) . '/img/rrhh/';
    if (!is_dir($destDir)) {
        @mkdir($destDir, 0777, true);
    }
    $nombreArchivoLocal = preg_replace('/[^a-zA-Z0-9_-]/', '_', $emplegajo) . '.jpg';
    $destino = $destDir . $nombreArchivoLocal;

    if (file_exists($destino)) {
        return 'img/rrhh/' . $nombreArchivoLocal;
    }

    $url_imagen1 = $servidor_img . 'hi_res/' . rawurlencode($emplegajo) . '.jpg';
    $url_imagen2 = $servidor_img . rawurlencode($emplegajo . ' - ' . $empnombre . '.jpg');

    if (imagenExiste($url_imagen1)) {
        $archivo_final = $url_imagen1;
    } elseif (imagenExiste($url_imagen2)) {
        $archivo_final = $url_imagen2;
    } else {
        return 'img/rrhh/rrhh_default.png';
    }

    $ch = curl_init($archivo_final);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $contenido = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $contenido === false) {
        return 'img/rrhh/rrhh_default.png';
    }
    @file_put_contents($destino, $contenido);
    return 'img/rrhh/' . $nombreArchivoLocal;
}

/**
 * Generar thumbnail circular de empleado (HTML)
 */
function fotoEmpleado($emplegajo, $empnombre, $servidor_img, $size = 36) {
    $src = determinarImagen($emplegajo, $empnombre, $servidor_img);
    return '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($empnombre) . '"'
         . ' width="' . $size . '" height="' . $size . '"'
         . ' class="rounded-circle object-fit-cover" style="object-fit:cover;"'
         . ' loading="lazy" onerror="this.src=\'img/rrhh/rrhh_default.png\'">';
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
 * Redirigir página via form submit
 */
function reenviar_pagina($url) {
    echo '<form id="form_redir" method="post" action="' . $url . '"><script>document.getElementById("form_redir").submit();</script></form>';
}
