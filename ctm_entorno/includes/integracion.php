<?php
/**
 * ============================================================================
 * LIBRERIA DE INTEGRACION ENTRE MODULOS CTM
 * ============================================================================
 * Funciones compartidas para consultas cruzadas entre:
 *   - ctm_inventarios (inve_*)
 *   - ctm_morebius (more_*)
 *   - ctm_presupuestos (ciclo_*, pres_*)
 *
 * Usa las vistas vw_integ_* y la tabla integ_eventos.
 * Cada funcion recibe $db/$usu y usa los wrappers legacy (sql_do_query/sql_do_array)
 * que ya estan cargados por el modulo que incluye este archivo.
 *
 * Prefijo: integ_ para evitar colisiones de nombres.
 * ============================================================================
 */

// Evitar doble inclusion
if (defined('INTEG_LOADED')) return;
define('INTEG_LOADED', true);

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES DE CONSULTA CROSS-MODULE
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Resumen de equipos y presupuesto para un centro de costos
 * Retorna array con totales de equipos + datos presupuestarios BINV
 */
function integ_resumen_cc($cc, $db = '1', $usu = '99999999') {
    if (!$cc) return null;
    $cc_safe = addslashes($cc);
    $sql = "SELECT * FROM vw_integ_cc_equipos_presupuesto WHERE centro_costo = '$cc_safe' LIMIT 1;";
    $result = sql_do_query($sql, $db, $usu);
    if (!$result) return null;
    return sql_do_array($result, $db);
}

/**
 * Equipos y prestamos de una persona en ambos modulos
 * Retorna array con conteos de PCs, prestamos inventario y morebius
 */
function integ_equipos_persona($legajo, $db = '1', $usu = '99999999') {
    if (!$legajo) return null;
    $legajo_safe = addslashes($legajo);
    $sql = "SELECT * FROM vw_integ_personal_equipos WHERE usuarioid = '$legajo_safe' LIMIT 1;";
    $result = sql_do_query($sql, $db, $usu);
    if (!$result) return null;
    return sql_do_array($result, $db);
}

/**
 * Prestamos activos unificados de ambos modulos
 * Filtra opcionalmente por centro de costos
 * Retorna resultado iterable con sql_do_array
 */
function integ_prestamos_activos($db = '1', $usu = '99999999', $cc = null) {
    $sql = "SELECT * FROM vw_integ_prestamos_unificados WHERE devuelto = '0'";
    if ($cc) {
        $cc_safe = addslashes($cc);
        $sql .= " AND centrocosto = '$cc_safe'";
    }
    $sql .= " ORDER BY fechaprestamo DESC;";
    return sql_do_query($sql, $db, $usu);
}

/**
 * Reparaciones activas unificadas
 * Filtra opcionalmente por zona de inventario
 */
function integ_reparaciones_activas($db = '1', $usu = '99999999', $zona = null) {
    $sql = "SELECT * FROM vw_integ_reparaciones_unificadas WHERE reparado = '0'";
    if ($zona) {
        $zona_safe = addslashes($zona);
        $sql .= " AND id_zonas_inventario = '$zona_safe'";
    }
    $sql .= " ORDER BY fecha_envio DESC;";
    return sql_do_query($sql, $db, $usu);
}

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES DE VERIFICACION
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Verifica si un centro de costos tiene presupuesto BINV disponible para una compra
 * Retorna: ['disponible' => bool, 'saldo' => float, 'mensaje' => string]
 */
function integ_verificar_presupuesto_compra($cc, $monto, $db = '1', $usu = '99999999') {
    $resumen = integ_resumen_cc($cc, $db, $usu);

    // Si no hay datos presupuestarios, informar pero no bloquear
    if (!$resumen || !$resumen['PRESUPUESTO_ID']) {
        return [
            'disponible' => null,
            'saldo' => 0,
            'mensaje' => 'Sin presupuesto asignado para este centro de costos'
        ];
    }

    $saldo = floatval($resumen['BINV_DISPONIBLE']);
    $monto_float = floatval($monto);

    if ($monto_float <= 0) {
        return ['disponible' => true, 'saldo' => $saldo, 'mensaje' => ''];
    }

    if ($saldo >= $monto_float) {
        return [
            'disponible' => true,
            'saldo' => $saldo,
            'mensaje' => 'Presupuesto BINV disponible: $' . number_format($saldo, 2, ',', '.')
        ];
    }

    return [
        'disponible' => false,
        'saldo' => $saldo,
        'mensaje' => 'Presupuesto BINV insuficiente. Disponible: $' . number_format($saldo, 2, ',', '.')
            . ' - Requerido: $' . number_format($monto_float, 2, ',', '.')
    ];
}

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES DE EVENTOS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Registra un evento cross-module en integ_eventos
 * $datos = ['cc' => '', 'legajo' => '', 'referencia_id' => '', 'descripcion' => '', 'datos_json' => '']
 */
function integ_registrar_evento($modulo, $tipo, $datos = [], $db = '1', $usu = '99999999') {
    $modulo_safe = addslashes($modulo);
    $tipo_safe = addslashes($tipo);
    $cc = addslashes($datos['cc'] ?? '');
    $legajo = addslashes($datos['legajo'] ?? '');
    $ref_id = addslashes($datos['referencia_id'] ?? '');
    $desc = addslashes($datos['descripcion'] ?? '');
    $json = addslashes($datos['datos_json'] ?? '');
    $usu_safe = addslashes($usu);

    $sql = "INSERT INTO integ_eventos (modulo_origen, tipo, cc, legajo, referencia_id, descripcion, datos_json, auditoria_usu)
            VALUES ('$modulo_safe', '$tipo_safe', '$cc', '$legajo', '$ref_id', '$desc', '$json', '$usu_safe');";
    return sql_do_query($sql, $db, $usu);
}

/**
 * Obtiene eventos recientes, opcionalmente filtrados
 * $filtros = ['modulo' => '', 'tipo' => '', 'cc' => '', 'limite' => 10]
 */
function integ_obtener_eventos($filtros = [], $db = '1', $usu = '99999999') {
    $sql = "SELECT * FROM integ_eventos WHERE 1=1";

    if (!empty($filtros['modulo'])) {
        $sql .= " AND modulo_origen = '" . addslashes($filtros['modulo']) . "'";
    }
    if (!empty($filtros['tipo'])) {
        $sql .= " AND tipo = '" . addslashes($filtros['tipo']) . "'";
    }
    if (!empty($filtros['cc'])) {
        $sql .= " AND cc = '" . addslashes($filtros['cc']) . "'";
    }

    $limite = intval($filtros['limite'] ?? 10);
    $sql .= " ORDER BY auditoria_fecha DESC LIMIT $limite;";
    return sql_do_query($sql, $db, $usu);
}

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES DE DASHBOARD
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Resumen general cross-module para dashboards
 * Retorna array con totales de equipos, prestamos, reparaciones y presupuesto
 */
function integ_dashboard_resumen($db = '1', $usu = '99999999') {
    $resumen = [];

    // Total equipos inventario IT
    $sql = "SELECT COUNT(*) AS TOTAL FROM inve_pc WHERE estadoid='1' AND (baja='0' OR baja IS NULL);";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    $resumen['total_pc'] = $row['TOTAL'] ?? 0;

    $sql = "SELECT COUNT(*) AS TOTAL FROM inve_impresoras WHERE estadoid='1';";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    $resumen['total_impresoras'] = $row['TOTAL'] ?? 0;

    // Prestamos activos unificados
    $sql = "SELECT COUNT(*) AS TOTAL FROM vw_integ_prestamos_unificados WHERE devuelto='0';";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    $resumen['prestamos_activos'] = $row['TOTAL'] ?? 0;

    // Reparaciones activas unificadas
    $sql = "SELECT COUNT(*) AS TOTAL FROM vw_integ_reparaciones_unificadas WHERE reparado='0';";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    $resumen['reparaciones_activas'] = $row['TOTAL'] ?? 0;

    // Totales globales de monitores y otros (sin vinculo a CC)
    $sql = "SELECT COUNT(*) AS TOTAL FROM inve_monitores WHERE estadoid='1';";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    $resumen['total_monitores'] = $row['TOTAL'] ?? 0;

    $sql = "SELECT COUNT(*) AS TOTAL FROM inve_otros WHERE estadoid='1';";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    $resumen['total_otros'] = $row['TOTAL'] ?? 0;

    // Presupuesto BINV del periodo abierto (sumado)
    $sql = "SELECT SUM(binv_aprobado) AS TOTAL_APROBADO,
                   SUM(binv_ejecutado) AS TOTAL_EJECUTADO,
                   SUM(binv_disponible) AS TOTAL_DISPONIBLE
              FROM vw_integ_cc_equipos_presupuesto
             WHERE presupuesto_id IS NOT NULL;";
    $row = sql_do_array(sql_do_query($sql, $db, $usu));
    $resumen['binv_aprobado'] = $row['TOTAL_APROBADO'] ?? 0;
    $resumen['binv_ejecutado'] = $row['TOTAL_EJECUTADO'] ?? 0;
    $resumen['binv_disponible'] = $row['TOTAL_DISPONIBLE'] ?? 0;

    return $resumen;
}

/**
 * Alertas cross-module: prestamos vencidos, presupuesto excedido, reparaciones pendientes
 * Retorna array de alertas con tipo, mensaje y referencia
 */
function integ_dashboard_alertas($db = '1', $usu = '99999999') {
    $alertas = [];

    // Prestamos vencidos (devolucion_estimada < hoy y devuelto = 0)
    $hoy = date('Y-m-d');
    $sql = "SELECT origen, prestamoid, legajo, responsable, fechaprestamo, devolucion_estimada
              FROM vw_integ_prestamos_unificados
             WHERE devuelto = '0'
               AND devolucion_estimada IS NOT NULL
               AND devolucion_estimada < '$hoy'
             ORDER BY devolucion_estimada ASC
             LIMIT 10;";
    $result = sql_do_query($sql, $db, $usu);
    while ($row = sql_do_array($result, $db)) {
        $alertas[] = [
            'tipo' => 'prestamo_vencido',
            'icono' => 'bi-exclamation-triangle',
            'color' => 'warning',
            'mensaje' => 'Prestamo #' . $row['PRESTAMOID'] . ' (' . $row['ORIGEN'] . ') vencido desde ' . $row['DEVOLUCION_ESTIMADA'],
            'responsable' => $row['RESPONSABLE'] ?? ''
        ];
    }

    // Reparaciones con mas de 30 dias pendientes
    $hace30 = date('Y-m-d', strtotime('-30 days'));
    $sql = "SELECT origen, reparacionid, fecha_envio
              FROM vw_integ_reparaciones_unificadas
             WHERE reparado = '0'
               AND fecha_envio IS NOT NULL
               AND fecha_envio < '$hace30'
             ORDER BY fecha_envio ASC
             LIMIT 5;";
    $result = sql_do_query($sql, $db, $usu);
    while ($row = sql_do_array($result, $db)) {
        $alertas[] = [
            'tipo' => 'reparacion_demorada',
            'icono' => 'bi-tools',
            'color' => 'danger',
            'mensaje' => 'Reparacion #' . $row['REPARACIONID'] . ' (' . $row['ORIGEN'] . ') pendiente desde ' . $row['FECHA_ENVIO'],
            'responsable' => ''
        ];
    }

    // Presupuesto BINV excedido (disponible negativo o cercano a 0)
    $sql = "SELECT unidad_nombre, centro_costo, binv_aprobado, binv_ejecutado, binv_disponible
              FROM vw_integ_cc_equipos_presupuesto
             WHERE presupuesto_id IS NOT NULL
               AND binv_aprobado > 0
               AND (binv_disponible <= 0 OR (binv_ejecutado / binv_aprobado) >= 0.95)
             LIMIT 5;";
    $result = sql_do_query($sql, $db, $usu);
    while ($row = sql_do_array($result, $db)) {
        $pct = ($row['BINV_APROBADO'] > 0) ? round(($row['BINV_EJECUTADO'] / $row['BINV_APROBADO']) * 100) : 0;
        $alertas[] = [
            'tipo' => 'presupuesto_excedido',
            'icono' => 'bi-currency-dollar',
            'color' => 'danger',
            'mensaje' => $row['UNIDAD_NOMBRE'] . ' (CC ' . $row['CENTRO_COSTO'] . ') - BINV al ' . $pct . '% de ejecucion',
            'responsable' => ''
        ];
    }

    return $alertas;
}

/**
 * Genera el HTML de las cards de integracion para dashboards
 * Reutilizable desde cualquier modulo
 */
function integ_render_cards_resumen($resumen) {
    $total_equipos = intval($resumen['total_pc']) + intval($resumen['total_impresoras'])
                   + intval($resumen['total_monitores']) + intval($resumen['total_otros']);
    $binv_disp = floatval($resumen['binv_disponible']);
    $prestamos = intval($resumen['prestamos_activos']);
    $reparaciones = intval($resumen['reparaciones_activas']);

    $html = '<div class="row g-3 mb-4">';

    // Card equipos totales
    $html .= '<div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-start border-4 border-info h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Equipos IT (global)</div>
                        <div class="fs-4 fw-bold text-info">' . number_format($total_equipos) . '</div>
                    </div>
                    <i class="bi bi-pc-display fs-3 text-info opacity-50"></i>
                </div>
            </div>
        </div>
    </div>';

    // Card presupuesto BINV
    $color_binv = ($binv_disp > 0) ? 'success' : 'danger';
    $html .= '<div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-start border-4 border-' . $color_binv . ' h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Presupuesto BINV disponible</div>
                        <div class="fs-4 fw-bold text-' . $color_binv . '">$' . number_format($binv_disp, 0, ',', '.') . '</div>
                    </div>
                    <i class="bi bi-currency-dollar fs-3 text-' . $color_binv . ' opacity-50"></i>
                </div>
            </div>
        </div>
    </div>';

    // Card prestamos unificados
    $color_prest = ($prestamos > 0) ? 'warning' : 'secondary';
    $html .= '<div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-start border-4 border-' . $color_prest . ' h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Prestamos activos (global)</div>
                        <div class="fs-4 fw-bold text-' . $color_prest . '">' . $prestamos . '</div>
                    </div>
                    <i class="bi bi-arrow-left-right fs-3 text-' . $color_prest . ' opacity-50"></i>
                </div>
            </div>
        </div>
    </div>';

    // Card reparaciones unificadas
    $color_rep = ($reparaciones > 0) ? 'danger' : 'secondary';
    $html .= '<div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-start border-4 border-' . $color_rep . ' h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Reparaciones pendientes (global)</div>
                        <div class="fs-4 fw-bold text-' . $color_rep . '">' . $reparaciones . '</div>
                    </div>
                    <i class="bi bi-tools fs-3 text-' . $color_rep . ' opacity-50"></i>
                </div>
            </div>
        </div>
    </div>';

    $html .= '</div>';
    return $html;
}

/**
 * Genera el HTML de la tabla de alertas cross-module
 */
function integ_render_alertas($alertas) {
    if (empty($alertas)) {
        return '<div class="alert alert-success small mb-3">
                    <i class="bi bi-check-circle me-2"></i>Sin alertas cross-module
                </div>';
    }

    $html = '<div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-bell me-2"></i>Alertas Cross-Module (' . count($alertas) . ')</h6>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">';

    foreach ($alertas as $alerta) {
        $html .= '<div class="list-group-item list-group-item-' . $alerta['color'] . ' py-2 small">
            <i class="bi ' . $alerta['icono'] . ' me-2"></i>'
            . htmlspecialchars($alerta['mensaje']);
        if ($alerta['responsable']) {
            $html .= ' <span class="fw-semibold">(' . htmlspecialchars($alerta['responsable']) . ')</span>';
        }
        $html .= '</div>';
    }

    $html .= '</div></div></div>';
    return $html;
}

/**
 * Genera el HTML de la tabla de eventos recientes
 */
function integ_render_eventos_recientes($db = '1', $usu = '99999999', $limite = 5) {
    $result = integ_obtener_eventos(['limite' => $limite], $db, $usu);
    if (!$result) return '';

    $html = '<div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-activity me-2"></i>Actividad Reciente Cross-Module</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Modulo</th>
                        <th>Evento</th>
                        <th>Descripcion</th>
                        <th class="text-center">CC</th>
                        <th class="text-center">Fecha</th>
                    </tr>
                </thead>
                <tbody>';

    $n = 0;
    while ($row = sql_do_array($result, $db)) {
        $n++;
        // Colores por modulo
        $badge_color = 'secondary';
        $modulo_corto = $row['MODULO_ORIGEN'] ?? '';
        if (strpos($modulo_corto, 'inventarios') !== false) $badge_color = 'primary';
        elseif (strpos($modulo_corto, 'morebius') !== false) $badge_color = 'success';
        elseif (strpos($modulo_corto, 'presupuestos') !== false) $badge_color = 'warning';

        $html .= '<tr>
            <td><span class="badge bg-' . $badge_color . '">' . htmlspecialchars($modulo_corto) . '</span></td>
            <td class="small">' . htmlspecialchars($row['TIPO'] ?? '') . '</td>
            <td class="small">' . htmlspecialchars(mb_strimwidth($row['DESCRIPCION'] ?? '', 0, 60, '...')) . '</td>
            <td class="text-center small">' . htmlspecialchars($row['CC'] ?? '-') . '</td>
            <td class="text-center small">' . substr($row['AUDITORIA_FECHA'] ?? '', 0, 16) . '</td>
        </tr>';
    }

    if ($n == 0) {
        $html .= '<tr><td colspan="5" class="text-center text-muted py-3">Sin eventos registrados</td></tr>';
    }

    $html .= '</tbody></table></div></div>';
    return $html;
}
