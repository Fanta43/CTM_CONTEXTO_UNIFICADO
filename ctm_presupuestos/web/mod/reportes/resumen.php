<?php
// Procesar las variables recibidas
//ini_set( 'display_errors', 'on' );
$baja = Enmascarar_post('baja');
$buscar = Enmascarar_post('buscar');
$presupuestoid = Enmascarar_post('presupuestoid');
$presupuesto_accion = Enmascarar_post('presupuesto_accion');

// Acción de cerrar presupuesto
if ($presupuesto_accion === 'Cerrar' && $presupuestoid) {
    $update = "UPDATE pres_old_presupuesto 
               SET estadoid = '2', 
                   auditoria_usu = '$usu', 
                   auditoria_fecha = '$hoy'  
               WHERE presupuestoid = '$presupuestoid';";
    sql_do_query($update, '1', $usu);
}

// ── Selección de año ─────────────────────────────────────────────────────────
$anio_sel = Enmascarar_post('anio_sel');
if (empty($anio_sel)) $anio_sel = Enmascarar_get('anio_sel');
if (empty($anio_sel)) $anio_sel = date('Y');

// Obtener años disponibles para navegación
$res_anios = sql_do_query("SELECT DISTINCT anio FROM pres_old_presupuesto ORDER BY anio;", '1', $usu);
$anios_disponibles = [];
while ($ra = sql_do_array($res_anios)) {
    $anios_disponibles[] = (int)$ra['ANIO'];
}
$anio_sel = (int)$anio_sel;
// Asegurar que el año seleccionado exista, sino usar el más reciente
if (!in_array($anio_sel, $anios_disponibles) && !empty($anios_disponibles)) {
    $anio_sel = end($anios_disponibles);
}
$idx = array_search($anio_sel, $anios_disponibles);
$anio_prev = ($idx > 0) ? $anios_disponibles[$idx - 1] : null;
$anio_next = ($idx < count($anios_disponibles) - 1) ? $anios_disponibles[$idx + 1] : null;

// Consultar presupuesto del año seleccionado
$sql_ppto = "SELECT p.presupuestoid, p.anio, p.nombre, p.estadoid 
             FROM pres_old_presupuesto p 
             WHERE p.anio = '$anio_sel' 
             LIMIT 1;";
$result_ppto = sql_do_query($sql_ppto, '1', $usu);
$ppto_row = sql_do_array($result_ppto);

function obtener_items_por_usuario($anio, $db = '1', $usu = '10433') {
    $anio_corto = substr($anio, -2);
    $sql = "SELECT cc, numero 
            FROM pres_old_items 
            WHERE itemid IN (
                SELECT itemid 
                FROM pres_old_items_usuarios 
                WHERE usuarioid = '$usu'
            );";
    $result = sql_do_query($sql, $db, $usu);
    
    $tabla = '<table class="table table-hover table-sm mb-0">';
    $tabla .= '<thead class="table-light">';
    $tabla .= '<tr>';
    $tabla .= '<th>Ítem</th>';
    $tabla .= '<th class="text-end">Ppto Autorizado</th>';
    $tabla .= '<th class="text-end">Total OC (USD)</th>';
    $tabla .= '<th class="text-end">Saldo</th>';
    $tabla .= '</tr>';
    $tabla .= '</thead>';
    $tabla .= '<tbody>';
    
    $total_planificado = 0;
    $total_oc          = 0;
    $total_saldo       = 0;

    while ($row = sql_do_array($result)) {
        $str_busqueda = $anio_corto."-".$row['CC']."-".$row['NUMERO']."-";
        $importes = obtener_importes_presupuesto($str_busqueda, $db, $usu);

        // Obtener total OC para este item desde SR logs
        // Por cada SR: si tiene monto_linea_oc_usd usa eso, sino usa importe_total
        $sql_oc = "SELECT COALESCE(SUM(sr_monto), 0) AS total_oc FROM (
                       SELECT codigo,
                              CASE WHEN SUM(monto_linea_oc_usd) > 0 THEN SUM(monto_linea_oc_usd)
                                   ELSE MAX(importe_total) END AS sr_monto
                       FROM pres_k2b_sr_logs
                       WHERE esquema LIKE '{$str_busqueda}%'
                         AND codigo IS NOT NULL AND codigo != ''
                         AND estado NOT IN ('RECHAZADA','FINALIZADA')
                       GROUP BY codigo
                   ) sr_totales";
        $res_oc = sql_do_query($sql_oc, $db, $usu);
        $row_oc = sql_do_array($res_oc);
        $oc_item = (float)($row_oc['TOTAL_OC'] ?? 0);

        $ppto = (float)$importes['IMPORTE_PLANIFICADO'];
        $saldo = $ppto - $oc_item;

        $total_planificado += $ppto;
        $total_oc          += $oc_item;
        $total_saldo       += $saldo;

        $saldo_color = ($saldo >= 0) ? '#198754' : '#dc3545';

        $tabla .= '<tr>';
        $tabla .= '<td class="fw-bold text-nowrap">' . htmlspecialchars($anio_corto . '-' . $row['CC'] . '-' . $row['NUMERO']) . '</td>';
        $tabla .= '<td class="text-end">$ ' . number_format($ppto, 2, ',', '.') . '</td>';
        $tabla .= '<td class="text-end">U$D ' . number_format($oc_item, 2, ',', '.') . '</td>';
        $tabla .= '<td class="text-end fw-bold" style="color:' . $saldo_color . '">U$D ' . number_format($saldo, 2, ',', '.') . '</td>';
        $tabla .= '</tr>';
    }

    // Fila de totales
    $tabla .= '<tr class="table-dark fw-bold">';
    $tabla .= '<td class="text-end">TOTAL</td>';
    $tabla .= '<td class="text-end">$ ' . number_format($total_planificado, 2, ',', '.') . '</td>';
    $tabla .= '<td class="text-end">U$D ' . number_format($total_oc, 2, ',', '.') . '</td>';
    $saldo_total_color = ($total_saldo >= 0) ? '#198754' : '#dc3545';
    $tabla .= '<td class="text-end" style="color:' . $saldo_total_color . '">U$D ' . number_format($total_saldo, 2, ',', '.') . '</td>';
    $tabla .= '</tr>';
    
    $tabla .= '</tbody>';
    $tabla .= '</table>';
    
    return $tabla;
}

function obtener_sr_items($anio, $nombre_presupuesto = '', $db = '1', $usu = '10433') {
    // Lista las SR asociadas a los recursos/item del usuario para el año dado.
    $anio_corto = substr($anio, -2);

    // 1) Obtener los recursos (cc, numero) asignados al usuario
    $sql = "SELECT cc, numero 
            FROM pres_old_items 
            WHERE itemid IN (
                SELECT itemid 
                FROM pres_old_items_usuarios 
                WHERE usuarioid = '$usu'
            );";
    $result = sql_do_query($sql, $db, $usu);
    


    // Formulario para seleccionar múltiples SR y enviarlas a notificar_sr.php
    $tabla  = '<form method="post" action="index.php?estado=1111">';
    $tabla .= '<table class="table table-hover table-sm mb-0">';
    $tabla .= '<thead class="table-light">';
    $tabla .= '<tr>';
    $tabla .= '<th>Recurso</th>';
    $tabla .= '<th>SR</th>';
    $tabla .= '<th class="text-center">Mes</th>';
    $tabla .= '<th class="text-center">Estado</th>';
    $tabla .= '<th class="text-end">Total OC (USD)</th>';
    $tabla .= '<th class="text-center">Sel.</th>';
    $tabla .= '</tr>';
    $tabla .= '</thead>';
    $tabla .= '<tbody>';

    $tiene_filas = false;
    $gran_total = 0;

    // 2) Por cada recurso, buscar sus SR en el log
    while ($row = sql_do_array($result)) {
        $str_busqueda = $anio_corto."-".$row['CC']."-".$row['NUMERO']."-";

        $sql_sr = "SELECT codigo, 
                          MONTH(MIN(fecha_entrega_sol)) AS mes_previsto
                    FROM pres_k2b_sr_logs 
                   WHERE esquema LIKE ('{$str_busqueda}%') 
                     AND codigo IS NOT NULL AND codigo != ''
                GROUP BY codigo 
                ORDER BY MIN(fecha_entrega_sol), codigo;";
        $result2 = sql_do_query($sql_sr, $db, $usu);

        $subtotal_recurso = 0;
        $tiene_sr_en_recurso = false;

        while ($row2 = sql_do_array($result2)) {
            $tiene_filas = true;
            $tiene_sr_en_recurso = true;
            
            // Calcular el total OC para esta SR: si tiene monto_linea_oc_usd usa eso, sino importe_total
            $sql_planificado = "SELECT SUM(monto_linea_oc_usd) AS total_oc, MAX(importe_total) AS importe_total
                                FROM pres_k2b_sr_logs 
                                WHERE codigo = '{$row2['CODIGO']}' AND codigo IS NOT NULL AND codigo != ''";
            $result3 = sql_do_query($sql_planificado, $db, $usu);
            $row3 = sql_do_array($result3);
            
            $sum_oc = $row3 ? (float)($row3['TOTAL_OC'] ?? 0) : 0;
            $total_planificado_num = ($sum_oc > 0) ? $sum_oc : (float)($row3['IMPORTE_TOTAL'] ?? 0);
            $estado_sr = maquina_estados_sol_recursos($row2['CODIGO'], $db, $usu);
            $resumen_sr = resumen_sr($row2['CODIGO'], $db, $usu);
            
            // Solo acumular en el subtotal si estado_suma == 1
            $imputa = ($estado_sr['estado_suma'] == 1);
            if ($imputa) {
                $subtotal_recurso += $total_planificado_num;
            }
            
            $oc_style = $imputa ? '' : ' style="text-decoration:line-through;color:#999;" title="No imputa al saldo (' . htmlspecialchars($estado_sr['estado']) . ')"';
            
            $tabla .= '<tr>';
            $tabla .= '<td class="small text-nowrap">' . htmlspecialchars(substr($str_busqueda, 0, 11)) . '</td>';
            
            // Celda con checkbox para seleccionar la SR y tooltip con el resumen
            $codigo_sr = htmlspecialchars($row2['CODIGO']);
            $title_sr  = htmlspecialchars($resumen_sr);
            $tabla .= '<td class="small" title="' . $title_sr . '">';
            $tabla .= '<span class="fw-semibold">' . $codigo_sr . '</span>';
            $tabla .= '</td>';
            
            $tabla .= '<td class="text-center small">' . htmlspecialchars($row2['MES_PREVISTO']) . '</td>';
            $tabla .= '<td class="text-center">';
            $tabla .= '<img title="' . htmlspecialchars($estado_sr['estado']) . '" src="img/ico/' . $estado_sr['estado_imagen'] . '.png" width="24" height="24" />';
            $tabla .= '</td>';
            $tabla .= '<td class="text-end small"' . $oc_style . '>U$D ' . number_format($total_planificado_num, 2, ',', '.') . '</td>';
            $tabla .= '<td class="text-center">';
            $tabla .= '<input class="form-check-input" type="checkbox" name="sr_codigos[]" value="' . $codigo_sr . '" />';
            $tabla .= '</td>';
            $tabla .= '</tr>';
        }

        // Agregar fila de subtotal si hubo SR para este recurso
        if ($tiene_sr_en_recurso) {
            $gran_total += $subtotal_recurso;
            $tabla .= '<tr class="table-light fw-bold">';
            $tabla .= '<td colspan="4" class="text-end small">SUBTOTAL ' . htmlspecialchars(substr($str_busqueda, 0, 11)) . '</td>';
            $tabla .= '<td class="text-end small">U$D ' . number_format($subtotal_recurso, 2, ',', '.') . '</td>';
            $tabla .= '<td></td>';
            $tabla .= '</tr>';
        }
    }

    if (!$tiene_filas) {
        $tabla .= '<tr><td colspan="6" class="text-center text-muted py-3">No se encontraron SR para este presupuesto.</td></tr>';
    }

    // Fila de gran total
    if ($tiene_filas) {
        $tabla .= '<tr class="table-dark fw-bold">';
        $tabla .= '<td colspan="4" class="text-end">TOTAL IMPUTADO</td>';
        $tabla .= '<td class="text-end">U$D ' . number_format($gran_total, 2, ',', '.') . '</td>';
        $tabla .= '<td></td>';
        $tabla .= '</tr>';
    }

    // Fila con botón para enviar las SR seleccionadas
    $tabla .= '<tr>';
    $tabla .= '<td colspan="6" class="text-end">';
    $tabla .= '<button type="submit" name="enviar_notificacion" class="btn btn-primary btn-sm">Notificar SR seleccionadas</button>';
    $tabla .= '</td>';
    $tabla .= '</tr>';

    $tabla .= '</tbody>';
    $tabla .= '</table>';
    $tabla .= '</form>';
    
    return $tabla;
}

function obtener_importes_presupuesto($prefijo_campo0, $db = '1', $usu = '9999') {
    // Obtiene importe_planificado e importe_disponible del registro más reciente (MAX(id))
    // para evitar duplicados generados por el scraper.

    $sql = "SELECT importe_planificado, importe_disponible
            FROM pres_k2b_presupuestos
            WHERE campo0 LIKE '{$prefijo_campo0}%'
              AND id IN (SELECT MAX(id) FROM pres_k2b_presupuestos GROUP BY campo0)
            LIMIT 1;";

    $result = sql_do_query($sql, $db, $usu);

    $row = sql_do_array($result);
    if ($row) {
        return [
            'IMPORTE_PLANIFICADO' => $row['IMPORTE_PLANIFICADO'],
            'IMPORTE_DISPONIBLE'  => $row['IMPORTE_DISPONIBLE'],
        ];
    }

    // Sin resultados: devolver 0 como valores por defecto
    return [
        'IMPORTE_PLANIFICADO' => 0,
        'IMPORTE_DISPONIBLE'  => 0,
    ];
}

function resumen_sr($sr, $db = '1', $usu = '9999') {
    // Ejecuta la consulta y devuelve una única fila con importe_planificado e importe_disponible.
    // Si hay varias filas, se toma la primera. Si no hay filas, se devuelven ceros.

    $sql = "SELECT codigo, MIN(fecha) AS fecha, MIN(fecha_entrega_sol) AS fecha_entrega_sol,
                   MIN(usuario) AS usuario, MIN(estado) AS estado, MIN(descripcion) AS descripcion,
                   MIN(precio_unitario) AS precio_unitario,
                   SUM(cantidad_solicitada) AS cantidad_solicitada,
                   SUM(cantidad_autorizada) AS cantidad_autorizada,
                   MIN(esquema) AS esquema, MIN(delegada) AS delegada,
                   MIN(pedido) AS pedido, MIN(comprador_pedido) AS comprador_pedido,
                   MIN(orden_compra) AS orden_compra,
                   MIN(proveedor) AS proveedor, MIN(fecha_entrega_fin) AS fecha_entrega_fin,
                   SUM(monto_linea_oc_usd) AS monto_linea_oc_usd, MIN(orden_stock) AS orden_stock
            FROM pres_k2b_sr_logs
            WHERE codigo = '$sr'
            GROUP BY codigo
            LIMIT 1;";

    $result = sql_do_query($sql, $db, $usu);
    $row = sql_do_array($result);

    if ($row) {
        // Construir un resumen legible para usar como tooltip
        $partes = [];

        if (!empty($row['CODIGO'])) {
            $partes[] = 'SR: ' . $row['CODIGO'];
        }
        if (!empty($row['ESTADO'])) {
            $partes[] = 'Estado: ' . $row['ESTADO'];
        }
        if (!empty($row['USUARIO'])) {
            $partes[] = 'Usuario: ' . $row['USUARIO'];
        }
        if (!empty($row['FECHA'])) {
            $partes[] = 'Fecha: ' . $row['FECHA'];
        }
        if (!empty($row['FECHA_ENTREGA_SOL'])) {
            $partes[] = 'Entrega prevista: ' . $row['FECHA_ENTREGA_SOL'];
        }
        if (!empty($row['DESCRIPCION'])) {
            $partes[] = 'Desc.: ' . $row['DESCRIPCION'];
        }
        if (!empty($row['CANTIDAD_SOLICITADA'])) {
            $partes[] = 'Cant. solicitada: ' . $row['CANTIDAD_SOLICITADA'];
        }
        if (!empty($row['CANTIDAD_AUTORIZADA'])) {
            $partes[] = 'Cant. autorizada: ' . $row['CANTIDAD_AUTORIZADA'];
        }
        if (!empty($row['PRECIO_UNITARIO'])) {
            $partes[] = 'P. Unit.: ' . $row['PRECIO_UNITARIO'];
        }
        if (!empty($row['PROVEEDOR'])) {
            // Normalizar espacios y tabs en el proveedor para evitar grandes huecos
            $proveedor_limpio = preg_replace('/\s+/', ' ', trim($row['PROVEEDOR']));
            $partes[] = 'Proveedor: ' . $proveedor_limpio;
        }
        if (!empty($row['ORDEN_COMPRA'])) {
            $partes[] = 'OC: ' . $row['ORDEN_COMPRA'];
        }
        if (!empty($row['PEDIDO'])) {
            $partes[] = 'Pedido: ' . $row['PEDIDO'];
        }
        if (!empty($row['ORDEN_STOCK'])) {
            $partes[] = 'Orden stock: ' . $row['ORDEN_STOCK'];
        }

        if (!empty($partes)) {
            // Unir con saltos de línea para que el tooltip sea más legible
            return implode("\n", $partes);
        }
    }

    // Sin resultados: devolver un texto por defecto
    return 'Sin datos de resumen para la SR ' . $sr;
}

function maquina_estados_sol_recursos($sr, $db = '1', $usu = '9999') {
    // Determina el estado visual y si la SR suma al presupuesto.
    // Agrupa por codigo para obtener datos consolidados de todas las líneas.

    $sql = "SELECT MIN(estado) AS estado,
                   GROUP_CONCAT(DISTINCT orden_stock SEPARATOR ',') AS orden_stock,
                   GROUP_CONCAT(DISTINCT orden_compra SEPARATOR ',') AS orden_compra,
                   SUM(monto_linea_oc_usd) AS total_pagado
            FROM pres_k2b_sr_logs
            WHERE codigo = '$sr'
            GROUP BY codigo;";

    $result = sql_do_query($sql, $db, $usu);
    $row = sql_do_array($result);

    if (!$row) {
        return ['estado' => '', 'estado_imagen' => '3', 'estado_suma' => 0];
    }

    $estado = $row['ESTADO'] ?? '';

    switch ($estado) {
        case 'FINALIZADA':
            $data = ['estado' => $estado, 'estado_imagen' => '2', 'estado_suma' => 0];
            break;
        case 'RECHAZADA':
            $data = ['estado' => $estado, 'estado_imagen' => '5', 'estado_suma' => 0];
            break;
        case 'CUMPLIDA':
        case 'CUMPLIMIENTOSTOCK':
            $data = ['estado' => $estado, 'estado_imagen' => '4', 'estado_suma' => 1];
            break;
        default:
            $data = ['estado' => $estado, 'estado_imagen' => '3', 'estado_suma' => 1];
            break;
    }

    if ($data['estado_imagen'] == '3' && (
        !empty($row['ORDEN_STOCK']) ||
        strpos($row['ORDEN_COMPRA'] ?? '', 'CC-') !== false ||
        (float)($row['TOTAL_PAGADO'] ?? 0) > 0
    )) {
        $data['estado_imagen'] = '4';
        $data['estado_suma'] = 1;
    }

    return $data;
}


?>

<div class="container-fluid mt-4">

    <!-- Navegador de año -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center">
                <?php if ($anio_prev): ?>
                    <a href="index.php?estado=<?php echo $estado; ?>&anio_sel=<?php echo $anio_prev; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-chevron-left me-1"></i><?php echo $anio_prev; ?>
                    </a>
                <?php else: ?>
                    <span class="btn btn-outline-secondary btn-sm disabled"><i class="bi bi-chevron-left"></i></span>
                <?php endif; ?>

                <div class="text-center">
                    <h5 class="mb-0 fw-bold"><?php echo $anio_sel; ?></h5>
                    <?php if ($ppto_row): ?>
                        <small class="text-muted"><?php echo htmlspecialchars($ppto_row['NOMBRE']); ?></small>
                    <?php endif; ?>
                </div>

                <?php if ($anio_next): ?>
                    <a href="index.php?estado=<?php echo $estado; ?>&anio_sel=<?php echo $anio_next; ?>" class="btn btn-outline-secondary btn-sm">
                        <?php echo $anio_next; ?><i class="bi bi-chevron-right ms-1"></i>
                    </a>
                <?php else: ?>
                    <span class="btn btn-outline-secondary btn-sm disabled"><i class="bi bi-chevron-right"></i></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($ppto_row): ?>
    <div class="row g-3">
        <!-- Asignado -->
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Ítems Asignados</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <?php echo obtener_items_por_usuario($ppto_row['ANIO'], '1', $usu); ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Resumen SR -->
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Solicitudes de Recurso</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <?php echo obtener_sr_items($ppto_row['ANIO'], $ppto_row['NOMBRE'], '1', $usu); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning text-center">
        <i class="bi bi-exclamation-triangle me-2"></i>No se encontró presupuesto para el año <?php echo $anio_sel; ?>.
    </div>
    <?php endif; ?>

</div>