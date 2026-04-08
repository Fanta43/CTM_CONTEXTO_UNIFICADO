<?php
/**
 * ============================================================================
 * FUNCIONES CENTRALES - ctm_inventarios
 * ============================================================================
 * Punto de entrada principal. Carga config, database, session y helpers.
 * Incluye funciones legacy migradas del módulo de inventario.
 * ============================================================================
 */

if (!defined('INVE_APP')) define('INVE_APP', true);

// ── Cargar infraestructura ───────────────────────────────────────────────────
if (!defined('APP_ROOT')) {
    require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
}
require_once APP_CONFIG . 'database.php';
require_once APP_INCLUDES . 'helpers.php';

// ── Integracion cross-module ────────────────────────────────────────────────
require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'ctm_entorno' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'integracion.php';

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES UTILITARIAS
// ═══════════════════════════════════════════════════════════════════════════════

function get_id($tabla, $db = '1', $usu = '99999999') {
    $sql = "SELECT id_tabla AS MAXID FROM inve_id WHERE tabla = '$tabla';";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    $nuevo = ($row['MAXID'] ?? 0) + 1;
    sql_do_query("UPDATE inve_id SET id_tabla = '$nuevo' WHERE tabla = '$tabla';", $db, $usu);
    return $nuevo;
}

function sani_input($txt) {
    $buscado = array("<?PHP", "?>", "SELECT ", "FROM", "WHERE", "INSERT", "INTO", "UPDATE", ";", "SET", "SCRIPT", "LINK", "META ", "SYSTEM", " AND ", " OR ",
                     "<?php", "?>", "select ", "from", "where", "insert", "into", "update", ";", "set", "script", "link", "meta ", "system", " And ", " Or ",
                     "<?Php", "?>", "Select ", "From", "Where", "Insert", "Into", "Update", ";", "Set", "Script", "Link", "Meta ", "System", " and ", " or ","'",'"');
    return str_replace($buscado, '', $txt);
}

function Enmascarar_post($clave) {
    return sani_input($_POST[$clave] ?? '');
}

function Enmascarar_get($clave) {
    return sani_input($_GET[$clave] ?? '');
}

function enmascararFecha($fecha) {
    if (!$fecha) return '';
    $d = substr($fecha, 8, 2);
    $m = substr($fecha, 5, 2);
    $y = substr($fecha, 0, 4);
    return "$d/$m/$y";
}

function enmascararmes($mes) {
    $meses = array('1'=>'ENE','2'=>'FEB','3'=>'MAR','4'=>'ABR','5'=>'MAY','6'=>'JUN',
                   '7'=>'JUL','8'=>'AGO','9'=>'SEP','10'=>'OCT','11'=>'NOV','12'=>'DIC');
    return $meses[$mes] ?? '';
}

function completarCeros($number, $n) {
    return str_pad((int)$number, $n, "0", STR_PAD_LEFT);
}

// ═══════════════════════════════════════════════════════════════════════════════
// COMBOS DESPLEGABLES
// ═══════════════════════════════════════════════════════════════════════════════

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

function imp_combo_w_fs12($nombrecombo, $query, $db, $usu, $actual) {
    $str = '<select class="form-select form-select-sm" name="' . $nombrecombo . '" id="' . $nombrecombo . '">';
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

// ═══════════════════════════════════════════════════════════════════════════════
// CONSULTAS PRINCIPALES
// ═══════════════════════════════════════════════════════════════════════════════

function consultaInventario($codigopc = '', $db = '1', $usu = '999999') {
    $sql = "SELECT pcid, codigopc, legajo, centrocosto, marca, tipoprocesador, tipoequipo,
                   velocidadprocesador, memoriaram, observaciones, ip, mac, numeroserie,
                   id_monitores, id_proveedores, pcc, fechavtogarantia, sr, oc, monto,
                   fechacompra, baja, fechabaja, motivobaja, id_zonas_inventario,
                   id_sistemaoperativo, clave1, clave2, clave3, clave4, clave5,
                   numoblea, discoscod, disco, estadoid, auditoria_usu, auditoria_fecha
              FROM inve_pc WHERE UPPER(codigopc) LIKE UPPER('$codigopc%');";
    return sql_do_array(sql_do_query($sql, $db, $usu), $db);
}

function consultaPersona($legajo = '', $db = '1', $usu = '999999') {
    $sql = "SELECT a.usuarioid, a.nombredeusuario, a.contrasenia, a.apellido, a.nombres,
                   a.telefono, a.celular, a.email, a.cargo, a.cargocod, a.ndoc,
                   a.fecha_nacimiento, a.lugar_domicilio, a.localidadid, a.organicaid,
                   a.estadoid, a.auditoria_usu, a.auditoria_fecha
              FROM vw_personas a WHERE a.usuarioid = '$legajo' ORDER BY a.usuarioid ASC;";
    return sql_do_array(sql_do_query($sql, $db, $usu), $db);
}

function getCentroCosto($id, $db = '1', $usu = '99999999') {
    $query = "SELECT cc.hldid, cc.hldnivel, cc.hldnom AS nombre
                FROM vw_accc cc WHERE cc.conidcc = '$id';";
    $row = sql_do_array(sql_do_query($query, $db, $usu));
    return $row['NOMBRE'] ?? '';
}

function getPc($id, $db = '1', $usu = '99999999') {
    $query = "SELECT * FROM inve_pc WHERE pcid = '$id';";
    $row = sql_do_array(sql_do_query($query, $db, $usu));
    return $row ?: ['NOMBRE_STR' => 'NO ENCONTRADO'];
}

function getImpresoras($id, $db = '1', $usu = '99999999') {
    $query = "SELECT * FROM inve_impresoras WHERE impresoraid = '$id';";
    $row = sql_do_array(sql_do_query($query, $db, $usu));
    return $row ?: ['NOMBRE_STR' => 'NO ENCONTRADO'];
}

function getMonitores($id, $db = '1', $usu = '99999999') {
    $query = "SELECT * FROM inve_monitores WHERE monitorid = '$id';";
    $row = sql_do_array(sql_do_query($query, $db, $usu));
    return $row ?: ['NOMBRE_STR' => 'NO ENCONTRADO'];
}

function getOtros($id, $db = '1', $usu = '99999999') {
    $query = "SELECT * FROM inve_otros WHERE otroid = '$id';";
    $row = sql_do_array(sql_do_query($query, $db, $usu));
    return $row ?: ['NOMBRE_STR' => 'NO ENCONTRADO'];
}

function getProveedor($id, $db = '1', $usu = '99999999') {
    $query = "SELECT * FROM inve_proveedores WHERE proveedorid = '$id';";
    $row = sql_do_array(sql_do_query($query, $db, $usu));
    return $row ?: ['NOMBRE_STR' => 'NO ENCONTRADO'];
}

// ═══════════════════════════════════════════════════════════════════════════════
// OPERACIONES CRUD (escriben solo en contexto_unificado)
// ═══════════════════════════════════════════════════════════════════════════════

function actualizarDatosInventario($pcid, $parametros, $db = '1', $usu = '999999') {
    $sql = "SELECT * FROM inve_pc WHERE pcid = '$pcid'";
    $row = sql_do_array(sql_do_query($sql, $db, $usu), $db);
    if (!$row) return 0;

    $hoy = date("Y-m-d H:i:s");
    $campos = ['codigopc','legajo','centrocosto','marca','tipoprocesador','tipoequipo',
               'velocidadprocesador','memoriaram','observaciones','ip','mac','numeroserie',
               'id_monitores','id_proveedores','pcc','sr','oc','monto','numoblea',
               'id_zonas_inventario','id_sistemaoperativo','clave1','clave2','clave3',
               'clave4','clave5','discoscod','disco'];
    $sets = [];
    foreach ($campos as $c) {
        $v = isset($parametros[$c]) ? $parametros[$c] : $row[strtoupper($c)];
        $sets[] = "$c = '" . addslashes($v) . "'";
    }
    $fvg = isset($parametros['fechavtogarantia']) ? substr($parametros['fechavtogarantia'],0,10) : substr($row['FECHAVTOGARANTIA'],0,10);
    $fc  = isset($parametros['fechacompra']) ? substr($parametros['fechacompra'],0,10) : substr($row['FECHACOMPRA'],0,10);
    $sets[] = "fechavtogarantia = '$fvg'";
    $sets[] = "fechacompra = '$fc'";
    $sets[] = "estadoid = '1'";
    $sets[] = "auditoria_usu = '$usu'";
    $sets[] = "auditoria_fecha = '$hoy'";

    $upd = "UPDATE inve_pc SET " . implode(', ', $sets) . " WHERE pcid = '$pcid';";
    $result = sql_do_query($upd, $db, $usu);
    return $result ? 1 : 0;
}

function insertarDatosInventario($parametros, $db = '1', $usu = '999999') {
    $obligatorio_id = get_id('INVE_PC');
    $codigopc = 'P' . $obligatorio_id;
    $hoy = date("Y-m-d H:i:s");
    $legajo = $parametros['legajo'] ?? '0';
    $centrocosto = $parametros['centrocosto'] ?? '882';
    $marca = $parametros['marca'] ?? 'S/E';
    $observaciones = $parametros['observaciones'] ?? 'Ingreso Inicial';
    $id_zonas_inventario = $parametros['id_zonas_inventario'] ?? '3';

    // ── Verificar presupuesto BINV (advertencia, no bloquea) ────────────────
    $monto_compra = $parametros['monto'] ?? '0';
    $check_pres = integ_verificar_presupuesto_compra($centrocosto, $monto_compra, $db, $usu);
    // El resultado se puede consultar en $check_pres['disponible'] / $check_pres['mensaje']

    $sql = "INSERT INTO inve_pc (pcid, codigopc, legajo, centrocosto, marca, tipoprocesador, tipoequipo,
                velocidadprocesador, memoriaram, observaciones, ip, mac, numeroserie,
                id_monitores, id_proveedores, pcc, fechavtogarantia, sr, oc, monto, fechacompra,
                baja, id_zonas_inventario, id_sistemaoperativo, numoblea, discoscod, disco,
                estadoid, auditoria_usu, auditoria_fecha
            ) VALUES (
                '$obligatorio_id', '$codigopc', '$legajo', '$centrocosto', '$marca',
                '" . ($parametros['tipoprocesador'] ?? 'S/E') . "',
                '" . ($parametros['tipoequipo'] ?? '1') . "',
                '" . ($parametros['velocidadprocesador'] ?? '0') . "',
                '" . ($parametros['memoriaram'] ?? '0') . "',
                '$observaciones',
                '" . ($parametros['ip'] ?? 'S/E') . "',
                '" . ($parametros['mac'] ?? 'S/E') . "',
                '" . ($parametros['numeroserie'] ?? 'S/E') . "',
                '" . ($parametros['id_monitores'] ?? 'S/E') . "',
                '" . ($parametros['id_proveedores'] ?? 'S/E') . "',
                '" . ($parametros['pcc'] ?? 'S/E') . "',
                '" . ($parametros['fechavtogarantia'] ?? date("Y-m-d", strtotime('+365 days'))) . "',
                '" . ($parametros['sr'] ?? '0') . "',
                '" . ($parametros['oc'] ?? '0') . "',
                '" . ($parametros['monto'] ?? '0') . "',
                '" . ($parametros['fechacompra'] ?? $hoy) . "',
                '0', '$id_zonas_inventario',
                '" . ($parametros['id_sistemaoperativo'] ?? '1') . "',
                '" . ($parametros['numoblea'] ?? 'S/E') . "',
                '" . ($parametros['discoscod'] ?? 'S/E') . "',
                '" . ($parametros['disco'] ?? 'S/E') . "',
                '1', '$usu', '$hoy');";
    $result = sql_do_query($sql, $db, $usu);

    // ── Registrar evento cross-module ───────────────────────────────────────
    if ($result) {
        integ_registrar_evento('ctm_inventarios', 'nueva_compra', [
            'cc' => $centrocosto,
            'legajo' => $legajo,
            'referencia_id' => $codigopc,
            'descripcion' => "Alta PC $codigopc - $marca (CC: $centrocosto)",
            'datos_json' => json_encode(['monto' => $monto_compra, 'presupuesto_check' => $check_pres['mensaje'] ?? ''])
        ], $db, $usu);
    }

    return $result;
}

function registroSinopticoContinuo($parametros, $db = '1', $usu = '999999') {
    $reg_id = get_id('INVE_REGISTRO_SINOPTICO_CONTINUO');
    $hoy = date("Y-m-d H:i:s");
    $sql = "INSERT INTO inve_registro_sinoptico_continuo (reg_sinapticoid, legajo, codigopc, cargocod,
                tipo_inventarioid, tipo_registroid, fecha_inicio, observaciones, estadoid,
                auditoria_usu, auditoria_fecha)
            VALUES ('$reg_id', '" . ($parametros['legajo'] ?? '') . "', '" . ($parametros['codigopc'] ?? '') . "',
                '" . ($parametros['cargocod'] ?? '') . "', '" . ($parametros['tipo_inventarioid'] ?? '') . "',
                '" . ($parametros['tipo_registroid'] ?? '') . "', '$hoy',
                '" . htmlspecialchars($parametros['observaciones'] ?? '') . "', '1', '$usu', '$hoy');";
    $result = sql_do_query($sql, $db, $usu);
    return $result ? 1 : 0;
}

// ═══════════════════════════════════════════════════════════════════════════════
// HERRAMIENTAS UI
// ═══════════════════════════════════════════════════════════════════════════════

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

function herramienta_pie($usuario, $nivel) {
    $niveles = [1 => 'CONSULTA', 4 => 'DCIA', 6 => 'ABM', 9 => 'ADMIN'];
    $niveltex = $niveles[$nivel] ?? 'CONSULTA';
    return '<div class="d-flex align-items-center gap-2 small text-muted">
                <i class="bi bi-person-circle"></i> <span>' . htmlspecialchars($usuario) . '</span>
                <span class="badge bg-secondary">' . $niveltex . '</span>
            </div>';
}

function getCurrentUrlWithTab($tabValue, $codigopc) {
    $params = $_GET;
    $params['tab'] = $tabValue;
    $params['codigopc'] = $codigopc;
    return basename($_SERVER['PHP_SELF']) . '?' . http_build_query($params);
}

function getCurrentUrlWithTabLegajo($tabValue, $legajo) {
    $params = $_GET;
    $params['tab'] = $tabValue;
    $params['legajo'] = $legajo;
    return basename($_SERVER['PHP_SELF']) . '?' . http_build_query($params);
}

// ── Fotos de empleados ────────────────────────────────────────────────────────

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

function determinarImagen($emplegajo, $empnombre, $servidor_img) {
    $destDir = preg_replace('#^(.*ctm_inventarios).*#', '$1', __DIR__) . '/img/rrhh/';
    if (!is_dir($destDir)) @mkdir($destDir, 0777, true);
    $nombreArchivoLocal = preg_replace('/[^a-zA-Z0-9_-]/', '_', $emplegajo) . '.jpg';
    $destino = $destDir . $nombreArchivoLocal;
    if (file_exists($destino)) return 'img/rrhh/' . $nombreArchivoLocal;

    $url1 = $servidor_img . 'hi_res/' . rawurlencode($emplegajo) . '.jpg';
    $url2 = $servidor_img . rawurlencode($emplegajo . ' - ' . $empnombre . '.jpg');
    if (imagenExiste($url1)) { $archivo_final = $url1; }
    elseif (imagenExiste($url2)) { $archivo_final = $url2; }
    else { return 'img/rrhh/rrhh_default.png'; }

    $ch = curl_init($archivo_final);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $contenido = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200 || $contenido === false) return 'img/rrhh/rrhh_default.png';
    @file_put_contents($destino, $contenido);
    return 'img/rrhh/' . $nombreArchivoLocal;
}

function fotoEmpleado($emplegajo, $empnombre, $servidor_img, $size = 36) {
    $src = determinarImagen($emplegajo, $empnombre, $servidor_img);
    return '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($empnombre) . '"'
         . ' width="' . $size . '" height="' . $size . '"'
         . ' class="rounded-circle object-fit-cover" style="object-fit:cover;"'
         . ' loading="lazy" onerror="this.src=\'img/rrhh/rrhh_default.png\'">';
}

function existe($valor, $campo, $tabla, $usu) {
    $sql = "SELECT $campo FROM $tabla WHERE UPPER($campo) = UPPER('$valor');";
    $result = sql_do_query($sql, '1', $usu);
    return (sql_do_array($result)) ? "SI" : "NO";
}

function reenviar_pagina($url) {
    echo '<form id="form_redir" method="post" action="' . $url . '"><script>document.getElementById("form_redir").submit();</script></form>';
}
