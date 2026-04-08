<?php

/**
 * ============================================================================
 * Generar Borrador de Presupuesto - Proyeccion SR K2B
 * ============================================================================
 * ID menu: 201
 * Tablas: ciclo_presupuesto, ciclo_partida, ciclo_config_proyeccion,
 *         pres_k2b_sr_logs, pres_k2b_presupuestos, pres_sr_borrador
 *
 * Compara las SR del periodo origen con las del destino por item de gasto:
 * 1. Carga SR origen agrupadas por item+matricula con ultimo precio OC.
 * 2. Carga SR destino ya en proceso o con OC (no se duplican).
 * 3. Genera 1 SR proyectada por categoria dentro de cada item:
 *    - Suma precios ref de matriculas faltantes de la misma categoria
 *    - Aplica factor_ajuste (prioridad: item > categoria > global)
 *    - Suma acumulada SR (en curso + creadas) no excede presupuesto aprobado del item
 * 4. Muestra comparativo lado a lado con totales por item y % de similitud.
 * ============================================================================
 */

$db  = '1';
$msg = '';

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES AUXILIARES
// ═══════════════════════════════════════════════════════════════════════════════

function obtenerFactor($factores, $id_categoria, $id_item) {
    foreach ($factores as $f) {
        if (!empty($f['ID_ITEM']) && $f['ID_ITEM'] == $id_item) return $f;
    }
    foreach ($factores as $f) {
        if (!empty($f['ID_CATEGORIA']) && empty($f['ID_ITEM']) && $f['ID_CATEGORIA'] == $id_categoria) return $f;
    }
    foreach ($factores as $f) {
        if (empty($f['ID_CATEGORIA']) && empty($f['ID_ITEM'])) return $f;
    }
    return null;
}

function cargar_sr_por_item($anio, $cc, $db, $usu) {
    $result = [];
    $sql = "SELECT sr.esquema_gastoid, sr.matricula, sr.descripcion, sr.codigo,
                   sr.estado, sr.orden_compra, sr.precio_unitario, sr.cantidad_autorizada,
                   sr.monto_linea_oc_usd, sr.importe_total, sr.fecha,
                   mc.id_categoria AS CAT_ID,
                   COALESCE(cg.codigo, '') AS CAT_CODIGO,
                   COALESCE(cg.nombre, 'Sin categoria') AS CAT_NOMBRE,
                   (SELECT ult.precio_unitario
                    FROM pres_k2b_sr_logs ult
                    WHERE ult.matricula = sr.matricula
                      AND ult.esquema_gastoid = sr.esquema_gastoid
                      AND ult.monto_linea_oc_usd > 0
                    ORDER BY ult.fecha DESC, ult.codigo DESC LIMIT 1) AS ULTIMO_PU_OC,
                   (SELECT ult2.precio_unitario
                    FROM pres_k2b_sr_logs ult2
                    WHERE ult2.matricula = sr.matricula
                      AND ult2.esquema_gastoid = sr.esquema_gastoid
                      AND ult2.precio_unitario > 0
                    ORDER BY ult2.fecha DESC, ult2.codigo DESC LIMIT 1) AS ULTIMO_PU_SOL
            FROM pres_k2b_sr_logs sr
            INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
            LEFT JOIN pres_k2b_matricula_categoria mc ON mc.matricula = sr.matricula
            LEFT JOIN ciclo_categoria_gasto cg ON cg.id = mc.id_categoria
            WHERE sr.esquema_anio = '$anio' AND sr.esquema_cc = '$cc'
              AND sr.codigo IS NOT NULL AND sr.codigo != ''
            ORDER BY sr.esquema_gastoid, COALESCE(cg.codigo, 'ZZZ'), sr.codigo, sr.matricula";
    $res = sql_do_query($sql, $db, $usu);
    while ($r = sql_do_array($res)) {
        $gid = $r['ESQUEMA_GASTOID'];
        if (!isset($result[$gid])) $result[$gid] = [];
        $result[$gid][] = [
            'matricula'   => $r['MATRICULA'],
            'descripcion' => $r['DESCRIPCION'],
            'codigo'      => $r['CODIGO'],
            'estado'      => $r['ESTADO'],
            'oc'          => $r['ORDEN_COMPRA'],
            'pu'          => (float)$r['PRECIO_UNITARIO'],
            'cant'        => (float)$r['CANTIDAD_AUTORIZADA'],
            'oc_usd'      => (float)$r['MONTO_LINEA_OC_USD'],
            'importe'     => (float)$r['IMPORTE_TOTAL'],
            'fecha'       => $r['FECHA'],
            'cat_id'      => $r['CAT_ID'],
            'cat_codigo'  => $r['CAT_CODIGO'],
            'cat_nombre'  => $r['CAT_NOMBRE'],
            'pu_oc'       => (float)($r['ULTIMO_PU_OC'] ?? 0),
            'pu_sol'      => (float)($r['ULTIMO_PU_SOL'] ?? 0),
        ];
    }
    return $result;
}

function agrupar_sr_por_categoria($sr_lines) {
    $cats = [];
    foreach ($sr_lines as $line) {
        $cat_key = $line['cat_id'] ?? '0';
        if (!isset($cats[$cat_key])) {
            $cats[$cat_key] = [
                'cat_id'     => $line['cat_id'],
                'cat_codigo' => $line['cat_codigo'] ?? '',
                'cat_nombre' => $line['cat_nombre'] ?? 'Sin categoria',
                'lines'      => [],
            ];
        }
        $cats[$cat_key]['lines'][] = $line;
    }
    ksort($cats);
    return $cats;
}

function render_sr_cats($cats, $modo, $gid_r) {
    if (empty($cats)) { echo '<p class="text-muted small py-2 px-3">Sin SR en este periodo.</p>'; return; }

    if ($modo === 'nuevas'):
        // Modo nuevas: 1 SR por categoria — tabla resumen
?>
        <table class="table table-sm table-bordered mb-0" style="font-size:0.78rem;">
            <thead class="table-success">
                <tr>
                    <th>Categoria</th>
                    <th class="text-center" style="width:60px;">Matr.</th>
                    <th class="text-end" style="width:100px;">P.Ref. Total</th>
                    <th class="text-center" style="width:55px;">Factor</th>
                    <th class="text-end" style="width:110px;">Monto Proy.</th>
                </tr>
            </thead>
            <tbody>
<?php
        $grand_total = 0;
        foreach ($cats as $ck => $cat):
            foreach ($cat['lines'] as $ln):
                $grand_total += $ln['precio_proy'];
                $cat_label = !empty($ln['cat_codigo']) ? $ln['cat_codigo'] . ' — ' . $ln['cat_nombre'] : ($ln['cat_nombre'] ?? 'Sin categoria');
                $cant_mat = $ln['cant_mat'] ?? 1;
?>
                <tr>
                    <td>
                        <i class="bi bi-tag-fill text-success me-1"></i>
                        <span class="fw-semibold"><?php echo htmlspecialchars($cat_label); ?></span>
                    </td>
                    <td class="text-center"><span class="badge bg-dark"><?php echo $cant_mat; ?></span></td>
                    <td class="text-end">U$D <?php echo number_format($ln['precio_ref'], 2, ',', '.'); ?></td>
                    <td class="text-center">×<?php echo number_format($ln['factor'], 4); ?></td>
                    <td class="text-end fw-bold">U$D <?php echo number_format($ln['precio_proy'], 2, ',', '.'); ?></td>
                </tr>
<?php       endforeach;
        endforeach; ?>
                <tr class="fw-bold" style="background:#d1e7dd;">
                    <td colspan="4" class="text-end">Total SR Nuevas</td>
                    <td class="text-end">U$D <?php echo number_format($grand_total, 2, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>
<?php
    else:
        // Modo origen/destino: tabla con lineas individuales agrupadas por categoria
        foreach ($cats as $ck => $cat):
            $cat_label = !empty($cat['cat_codigo']) ? $cat['cat_codigo'] . ' — ' . $cat['cat_nombre'] : $cat['cat_nombre'];
?>
        <div class="mb-2">
            <div class="small fw-bold px-2 py-1" style="background:<?php echo $modo === 'destino' ? '#cff4fc' : '#e2e3e5'; ?>;">
                <i class="bi bi-tag-fill me-1"></i><?php echo htmlspecialchars($cat_label); ?>
                <span class="badge bg-dark ms-1"><?php echo count($cat['lines']); ?></span>
            </div>
            <table class="table table-sm table-bordered mb-0" style="font-size:0.78rem;">
                <thead>
                    <tr class="table-light">
                        <th style="width:80px;">Matricula</th>
                        <th>Descripcion</th>
                        <th class="text-center" style="width:70px;">SR</th>
                        <th class="text-center" style="width:55px;">Estado</th>
                        <th class="text-end" style="width:70px;">P.Unit.</th>
                        <th class="text-center" style="width:40px;">Cant.</th>
                        <th class="text-end" style="width:90px;">OC USD</th>
                    </tr>
                </thead>
                <tbody>
<?php
            $cat_total = 0;
            foreach ($cat['lines'] as $ln):
                $cat_total += ($ln['oc_usd'] > 0 ? $ln['oc_usd'] : ($ln['pu'] * max($ln['cant'], 1)));
?>
                    <tr>
                        <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($ln['matricula']); ?></td>
                        <td title="<?php echo htmlspecialchars($ln['descripcion'] ?? ''); ?>"><?php echo htmlspecialchars(mb_substr($ln['descripcion'] ?? '', 0, 55)); ?><?php echo mb_strlen($ln['descripcion'] ?? '') > 55 ? '…' : ''; ?></td>
                        <td class="text-center"><small><?php echo htmlspecialchars($ln['codigo'] ?? ''); ?></small></td>
                        <td class="text-center">
<?php
                            $est_c = strtoupper($ln['estado'] ?? '');
                            $badge_cl = match(true) {
                                str_contains($est_c, 'FINAL') => 'bg-success',
                                str_contains($est_c, 'RECHAZ') => 'bg-danger',
                                str_contains($est_c, 'COMPRA') => 'bg-info text-dark',
                                default => 'bg-secondary',
                            };
?>
                            <span class="badge <?php echo $badge_cl; ?>" style="font-size:0.6rem;"><?php echo htmlspecialchars(mb_substr($ln['estado'], 0, 12)); ?></span>
                        </td>
                        <td class="text-end"><?php echo number_format($ln['pu'], 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo number_format($ln['cant'], 0); ?></td>
                        <td class="text-end"><?php echo $ln['oc_usd'] > 0 ? 'U$D ' . number_format($ln['oc_usd'], 2, ',', '.') : '<span class="text-muted">—</span>'; ?></td>
                    </tr>
<?php endforeach; ?>
                    <tr class="fw-bold" style="background:#f8f9fa;">
                        <td colspan="5" class="text-end small">Subtotal <?php echo htmlspecialchars($cat_label); ?></td>
                        <td class="text-end small" colspan="2">U$D <?php echo number_format($cat_total, 2, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
<?php   endforeach;
    endif;
}

function cargar_oc_total_por_item($anio, $cc, $db, $usu) {
    $result = [];
    $sql = "SELECT esquema_gastoid, SUM(sr_monto) AS total_oc FROM (
                SELECT sr.esquema_gastoid, sr.codigo,
                       CASE WHEN SUM(sr.monto_linea_oc_usd) > 0 THEN SUM(sr.monto_linea_oc_usd)
                            ELSE MAX(sr.importe_total) END AS sr_monto
                FROM pres_k2b_sr_logs sr
                INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
                WHERE sr.esquema_anio = '$anio' AND sr.esquema_cc = '$cc'
                      AND sr.codigo IS NOT NULL AND sr.codigo != ''
                GROUP BY sr.esquema_gastoid, sr.codigo
            ) sr_oc GROUP BY esquema_gastoid";
    $res = sql_do_query($sql, $db, $usu);
    while ($r = sql_do_array($res)) {
        $result[$r['ESQUEMA_GASTOID']] = (float)$r['TOTAL_OC'];
    }
    return $result;
}

function cargar_ppto_item($anio, $db, $usu) {
    $result = [];
    $anio_corto = substr($anio, -2);
    $sql = "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(p.campo0, '-', 3), '-', -1) AS item_code,
                   p.importe_planificado AS PPTO
            FROM pres_k2b_presupuestos p
            INNER JOIN ciclo_item_gasto ig ON SUBSTRING_INDEX(SUBSTRING_INDEX(p.campo0, '-', 3), '-', -1) = ig.codigo
            WHERE p.campo0 REGEXP '^[0-9]{2}-[0-9]+-[0-9]+-'
              AND SUBSTRING_INDEX(p.campo0, '-', 1) = '$anio_corto'
              AND p.id IN (SELECT MAX(id) FROM pres_k2b_presupuestos GROUP BY campo0)";
    $res = sql_do_query($sql, $db, $usu);
    while ($r = sql_do_array($res)) {
        $result[$r['ITEM_CODE']] = (float)$r['PPTO'];
    }
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
$vista_previa_data = null;
$periodo_origen_id  = '';
$periodo_destino_id = '';
$cc_sel = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post        = Enmascarar_post('accion_post');
    $periodo_origen_id  = Enmascarar_post('periodo_origen');
    $periodo_destino_id = Enmascarar_post('periodo_destino');
    $cc_sel             = Enmascarar_post('cc_sel');

    $row_origen = sql_do_array(sql_do_query(
        "SELECT * FROM ciclo_periodo_fiscal WHERE id = '$periodo_origen_id';", $db, $usu));
    $row_destino = sql_do_array(sql_do_query(
        "SELECT * FROM ciclo_periodo_fiscal WHERE id = '$periodo_destino_id';", $db, $usu));

    if (!$row_origen) {
        $msg = generarAlertJS('Error', 'Seleccione un periodo origen valido.', 'error');
    } elseif (!$row_destino) {
        $msg = generarAlertJS('Error', 'Seleccione un periodo destino valido.', 'error');
    } elseif (empty($cc_sel)) {
        $msg = generarAlertJS('Error', 'Seleccione un centro de costos.', 'error');
    } else {
        $anio_origen  = $row_origen['ANIO'];
        $anio_destino = $row_destino['ANIO'];

        // Cargar factores de proyeccion
        $res_factores = sql_do_query(
            "SELECT * FROM ciclo_config_proyeccion WHERE id_periodo_destino = '$periodo_destino_id'
             ORDER BY CASE WHEN id_item IS NOT NULL THEN 1 WHEN id_categoria IS NOT NULL THEN 2 ELSE 3 END;", $db, $usu);
        $factores = [];
        while ($rf = sql_do_array($res_factores)) $factores[] = $rf;

        // Factor global por defecto si no hay configuracion
        $factor_global = 1.0000;
        if (!empty($factores)) {
            foreach ($factores as $f) {
                if (empty($f['ID_CATEGORIA']) && empty($f['ID_ITEM'])) {
                    $factor_global = (float)$f['FACTOR_AJUSTE'];
                    break;
                }
            }
        }

        // Cargar items del presupuesto (de ambos periodos)
        $sql_items = "SELECT DISTINCT ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE, ig.id AS ITEM_ID
                      FROM ciclo_partida pa
                      JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                      JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                      JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                      WHERE pr.id_periodo IN ('$periodo_origen_id', '$periodo_destino_id')
                        AND uo.cc = '$cc_sel'
                      ORDER BY ig.codigo";
        $res_items = sql_do_query($sql_items, $db, $usu);
        $items_pres = [];
        while ($ri = sql_do_array($res_items)) {
            $items_pres[$ri['ITEM_CODIGO']] = $ri;
        }

        // Presupuesto aprobado por item (destino)
        $ppto_dest = cargar_ppto_item($anio_destino, $db, $usu);

        // Presupuesto aprobado del ciclo (destino)
        $sql_apr_dest = "SELECT ig.codigo AS IC, SUM(pa.monto_aprobado) AS APR
                         FROM ciclo_partida pa
                         JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                         JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                         JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                         WHERE pr.id_periodo = '$periodo_destino_id' AND uo.cc = '$cc_sel'
                         GROUP BY ig.codigo";
        $res_apr = sql_do_query($sql_apr_dest, $db, $usu);
        $aprobado_dest = [];
        while ($ra = sql_do_array($res_apr)) {
            $aprobado_dest[$ra['IC']] = (float)$ra['APR'];
        }

        // Cargar SR origen y destino
        $sr_origen  = cargar_sr_por_item($anio_origen, $cc_sel, $db, $usu);
        $sr_destino = cargar_sr_por_item($anio_destino, $cc_sel, $db, $usu);

        // OC totales por item (patron k2b_sr.php: agrupar por SR, fallback importe_total)
        $oc_origen  = cargar_oc_total_por_item($anio_origen, $cc_sel, $db, $usu);
        $oc_destino = cargar_oc_total_por_item($anio_destino, $cc_sel, $db, $usu);

        // Matriculas ya existentes en destino (en proceso o con OC)
        $mat_dest_existentes = [];
        foreach ($sr_destino as $gid => $lines) {
            foreach ($lines as $sr) {
                $est = strtoupper($sr['estado']);
                if (!in_array($est, ['RECHAZADA', 'FINALIZADA'])) {
                    $mat_dest_existentes[$gid . '|' . $sr['matricula']] = true;
                }
            }
        }

        // Generar SR proyectadas por item
        $proyeccion_items = [];
        $all_item_codes = array_unique(array_merge(array_keys($sr_origen), array_keys($sr_destino), array_keys($items_pres)));
        sort($all_item_codes);

        foreach ($all_item_codes as $gid) {
            $item_info = $items_pres[$gid] ?? null;
            $item_nombre = $item_info ? $item_info['ITEM_NOMBRE'] : "Item $gid";
            $item_id = $item_info ? $item_info['ITEM_ID'] : null;

            // Factor para este item
            $factor_row = $item_id ? obtenerFactor($factores, null, $item_id) : null;
            $factor_val = $factor_row ? (float)$factor_row['FACTOR_AJUSTE'] : $factor_global;

            // Presupuesto aprobado para este item (ciclo_partida prioritario, K2B fallback)
            // Mismo criterio que detalle.php: ciclo_partida.monto_aprobado es la fuente principal
            $ppto_ciclo = $aprobado_dest[$gid] ?? 0;
            $ppto_k2b   = $ppto_dest[$gid] ?? 0;
            $ppto_item  = $ppto_ciclo > 0 ? $ppto_ciclo : $ppto_k2b;
            $ppto_fuente = $ppto_ciclo > 0 ? 'ciclo' : ($ppto_k2b > 0 ? 'k2b' : 'none');

            // OC ya comprometido en destino
            $oc_dest_item = $oc_destino[$gid] ?? 0;
            $disponible = $ppto_item - $oc_dest_item;

            // SR origen para este item
            $sr_or = $sr_origen[$gid] ?? [];
            // SR destino para este item
            $sr_de = $sr_destino[$gid] ?? [];

            // SR destino activas (excluir rechazadas/finalizadas)
            $sr_dest_activas = [];
            foreach ($sr_de as $sr) {
                $est = strtoupper($sr['estado']);
                if (!in_array($est, ['RECHAZADA', 'FINALIZADA'])) {
                    $sr_dest_activas[] = $sr;
                }
            }

            // Total OC origen y destino (de cargar_oc_total_por_item, mismo calculo que detalle.php)
            $total_oc_origen = $oc_origen[$gid] ?? 0;
            $total_oc_dest   = $oc_destino[$gid] ?? 0;

            // Deduplicar matriculas del origen: tomar la linea mas reciente por matricula
            $mat_origen_unico = [];
            foreach ($sr_or as $sr) {
                $mat = $sr['matricula'];
                // Sobreescribir siempre: como la query ordena por codigo ASC,
                // la ultima iteracion sera la SR mas reciente para esta matricula
                $mat_origen_unico[$mat] = $sr;
            }

            // Agrupar matriculas faltantes por categoria → 1 SR por categoria
            $cat_acum = []; // cat_key => [precio_ref_sum, matriculas[], ...]
            foreach ($mat_origen_unico as $mat => $sr) {
                $mat_key = $gid . '|' . $mat;
                if (isset($mat_dest_existentes[$mat_key])) continue;

                // Precio ref: precio_unitario del ultimo OC, fallback precio_unitario solicitado (mismo criterio que matriculas.php)
                $pu_ref = $sr['pu_oc'] > 0 ? $sr['pu_oc'] : ($sr['pu_sol'] > 0 ? $sr['pu_sol'] : $sr['pu']);
                $precio_ref = $pu_ref * max($sr['cant'], 1);
                $cat_key = $sr['cat_id'] ?? '0';

                if (!isset($cat_acum[$cat_key])) {
                    $cat_acum[$cat_key] = [
                        'cat_id'      => $sr['cat_id'],
                        'cat_codigo'  => $sr['cat_codigo'],
                        'cat_nombre'  => $sr['cat_nombre'],
                        'precio_ref'  => 0,
                        'matriculas'  => [],
                        'sr_refs'     => [],
                    ];
                }
                $cat_acum[$cat_key]['precio_ref'] += $precio_ref;
                $cat_acum[$cat_key]['matriculas'][] = $mat;
                if (!empty($sr['codigo'])) $cat_acum[$cat_key]['sr_refs'][] = $sr['codigo'];
            }

            // Generar 1 SR nueva por categoria
            $sr_nuevas = [];
            $acum_nuevas = 0;
            foreach ($cat_acum as $cat_key => $ca) {
                $precio_proy = round($ca['precio_ref'] * $factor_val, 2);
                // Verificar que no exceda disponible
                if (($total_oc_dest + $acum_nuevas + $precio_proy) > $ppto_item && $ppto_item > 0) {
                    $restante = $ppto_item - $total_oc_dest - $acum_nuevas;
                    if ($restante <= 0) continue;
                    $precio_proy = min($precio_proy, round($restante, 2));
                }

                $cat_label = !empty($ca['cat_codigo']) ? $ca['cat_codigo'] . ' — ' . $ca['cat_nombre'] : ($ca['cat_nombre'] ?? 'Sin categoria');
                $sr_nuevas[] = [
                    'matricula'   => implode(', ', array_slice($ca['matriculas'], 0, 5)) . (count($ca['matriculas']) > 5 ? '... +' . (count($ca['matriculas']) - 5) : ''),
                    'descripcion' => $cat_label . ' (' . count($ca['matriculas']) . ' matriculas)',
                    'sr_ref'      => implode(', ', array_unique(array_slice($ca['sr_refs'], 0, 3))),
                    'oc_ref'      => '',
                    'precio_ref'  => $ca['precio_ref'],
                    'factor'      => $factor_val,
                    'precio_proy' => $precio_proy,
                    'budget_cut'  => false,
                    'cat_id'      => $ca['cat_id'],
                    'cat_codigo'  => $ca['cat_codigo'],
                    'cat_nombre'  => $ca['cat_nombre'],
                    'cant_mat'    => count($ca['matriculas']),
                    'matriculas_lista' => $ca['matriculas'],
                    'sr_refs_lista'    => array_unique($ca['sr_refs']),
                ];
                $acum_nuevas += $precio_proy;
            }

            // Agrupar por categoria para visualizacion
            $cats_origen  = agrupar_sr_por_categoria($sr_or);
            $cats_destino = agrupar_sr_por_categoria($sr_dest_activas);
            $cats_nuevas  = agrupar_sr_por_categoria($sr_nuevas);

            $proyeccion_items[$gid] = [
                'codigo'        => $gid,
                'nombre'        => $item_nombre,
                'ppto'          => $ppto_item,
                'ppto_fuente'   => $ppto_fuente,
                'ppto_ciclo'    => $ppto_ciclo,
                'ppto_k2b'      => $ppto_k2b,
                'factor'        => $factor_val,
                'sr_origen'     => $sr_or,
                'sr_destino'    => $sr_dest_activas,
                'sr_nuevas'     => $sr_nuevas,
                'cats_origen'   => $cats_origen,
                'cats_destino'  => $cats_destino,
                'cats_nuevas'   => $cats_nuevas,
                'total_oc_orig' => $total_oc_origen,
                'total_oc_dest' => $total_oc_dest,
                'total_nuevas'  => $acum_nuevas,
                'disponible'    => $disponible,
            ];
        }

        // ── VISTA PREVIA ─────────────────────────────────────────────────────
        if ($accion_post === 'vista_previa') {
            if (empty($proyeccion_items)) {
                $msg = generarAlertJS('Sin datos', 'No se encontraron SR en el periodo origen.', 'info');
            } else {
                $vista_previa_data = $proyeccion_items;
            }
        }

        // ── CONFIRMAR GENERACION ─────────────────────────────────────────────
        if ($accion_post === 'confirmar') {
            // Limpiar borradores anteriores del mismo destino+cc
            sql_do_query("DELETE FROM pres_sr_borrador
                          WHERE id_periodo_destino = '$periodo_destino_id' AND esquema_cc = '$cc_sel';", $db, $usu);

            $total_insertadas = 0;
            foreach ($proyeccion_items as $gid => $item) {
                foreach ($item['sr_nuevas'] as $sr) {
                    $mat_e = addslashes($sr['matricula']);
                    $desc_e = addslashes($sr['descripcion']);
                    $sr_ref_e = addslashes($sr['sr_ref']);
                    $oc_ref_e = addslashes($sr['oc_ref'] ?? '');
                    sql_do_query("INSERT INTO pres_sr_borrador
                        (id_periodo_destino, esquema_cc, esquema_gastoid, matricula, descripcion,
                         precio_unitario_ref, factor_aplicado, precio_unitario_proy, cantidad, monto_estimado,
                         sr_origen_codigo, oc_origen, estado, auditoria_usu)
                        VALUES ('$periodo_destino_id', '$cc_sel', '$gid', '$mat_e', '$desc_e',
                         '{$sr['precio_ref']}', '{$sr['factor']}', '{$sr['precio_proy']}', 1, '{$sr['precio_proy']}',
                         '$sr_ref_e', '$oc_ref_e', 'borrador', '$usu');", $db, $usu);

                    // Arrastrar EETT al borrador: primero desde renglón SR, luego desde matrícula
                    $row_lid = sql_do_array(sql_do_query("SELECT LAST_INSERT_ID() AS LID", $db, $usu));
                    $new_bid = (int)($row_lid['LID'] ?? 0);
                    if ($new_bid > 0) {
                        $first_mat = trim(($sr['matriculas_lista'][0] ?? ''));
                        if (empty($first_mat)) {
                            $first_mat = trim(explode(',', preg_replace('/\.\.\.\s*\+\d+$/', '', $sr['matricula']))[0]);
                        }
                        $first_mat_esc = addslashes($first_mat);
                        $eett_copied = false;

                        // 1) Intentar desde pres_k2b_sr_logs_eett (EETT del renglón SR original)
                        $sr_refs = $sr['sr_refs_lista'] ?? [];
                        if (!empty($sr_refs) && !empty($first_mat_esc)) {
                            $sr_refs_sql = array_map(function($c) { return "'" . addslashes(trim($c)) . "'"; }, array_slice($sr_refs, 0, 5));
                            $res_eett_sr = sql_do_query("SELECT * FROM pres_k2b_sr_logs_eett
                                WHERE codigo IN (" . implode(',', $sr_refs_sql) . ")
                                  AND matricula = '$first_mat_esc'
                                  AND (eett_denominacion IS NOT NULL AND eett_denominacion != '')
                                ORDER BY codigo DESC
                                LIMIT 1", $db, $usu);
                            $row_eett_sr = sql_do_array($res_eett_sr);
                            if ($row_eett_sr) {
                                $eett_cols = ['eett_denominacion','eett_caracteristicas','eett_representacion_grafica',
                                              'eett_normas_fabricacion','eett_ensayo_recepcion','eett_responsable_recepcion',
                                              'eett_marcas','eett_aplicacion','eett_forma_entrega','eett_observaciones',
                                              'eett_proveedores','justificacion_compra'];
                                $ins_vals = ["$new_bid"];
                                foreach ($eett_cols as $ec) $ins_vals[] = "'" . addslashes($row_eett_sr[strtoupper($ec)] ?? '') . "'";
                                $ins_vals[] = "'$usu'";
                                sql_do_query("INSERT INTO pres_sr_borrador_eett
                                    (id_borrador, " . implode(', ', $eett_cols) . ", auditoria_usu)
                                    VALUES (" . implode(', ', $ins_vals) . ")", $db, $usu);
                                $eett_copied = true;
                            }
                        }

                        // 2) Fallback: desde pres_k2b_matricula_categoria
                        if (!$eett_copied && !empty($first_mat_esc)) {
                            sql_do_query("INSERT INTO pres_sr_borrador_eett
                                (id_borrador, eett_denominacion, eett_caracteristicas, eett_representacion_grafica,
                                 eett_normas_fabricacion, eett_ensayo_recepcion, eett_responsable_recepcion,
                                 eett_marcas, eett_aplicacion, eett_forma_entrega, eett_observaciones,
                                 eett_proveedores, justificacion_compra, auditoria_usu)
                                SELECT $new_bid, eett_denominacion, eett_caracteristicas, eett_representacion_grafica,
                                       eett_normas_fabricacion, eett_ensayo_recepcion, eett_responsable_recepcion,
                                       eett_marcas, eett_aplicacion, eett_forma_entrega, eett_observaciones,
                                       eett_proveedores, justificacion_compra, '$usu'
                                FROM pres_k2b_matricula_categoria
                                WHERE matricula = '$first_mat_esc'
                                  AND (eett_denominacion IS NOT NULL AND eett_denominacion != '')
                                LIMIT 1;", $db, $usu);
                        }
                    }

                    $total_insertadas++;
                }
            }

            $total_monto = 0;
            foreach ($proyeccion_items as $item) $total_monto += $item['total_nuevas'];

            $msg = generarAlertJS('SR Borrador generado',
                "Se crearon $total_insertadas SR proyectadas por U\$D " . number_format($total_monto, 2, ',', '.') .
                " para el periodo {$row_destino['ANIO']}.",
                'success', "index.php?estado=$estado");
        }
    }
}

echo $msg;

// ═══════════════════════════════════════════════════════════════════════════════
// VISTA
// ═══════════════════════════════════════════════════════════════════════════════

// Combos
$sql_origen  = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_destino = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_cc      = "SELECT DISTINCT uo.cc AS ID, CONCAT(uo.cc, ' - ', uo.nombre) AS NOMBRE
                FROM ciclo_unidad_organizativa uo
                WHERE uo.cc IS NOT NULL AND uo.cc != ''
                ORDER BY uo.cc;";
?>

<?php echo generarTituloSeccion('Generar Borrador SR - Proyeccion K2B', 'bi bi-magic'); ?>

<!-- FORMULARIO SELECCION -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Parametros de Proyeccion SR</h6>
    </div>
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" id="formBorrador">
            <input type="hidden" name="accion_post" id="accion_post" value="vista_previa">
            <input type="hidden" name="periodo_origen" id="h_periodo_origen" value="<?php echo $periodo_origen_id; ?>">
            <input type="hidden" name="periodo_destino" id="h_periodo_destino" value="<?php echo $periodo_destino_id; ?>">
            <input type="hidden" name="cc_sel" id="h_cc_sel" value="<?php echo $cc_sel; ?>">

            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Periodo Origen <span class="text-danger">*</span></label>
                    <?php echo imp_combo_w('sel_periodo_origen', $sql_origen, $db, $usu, $periodo_origen_id); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Periodo Destino <span class="text-danger">*</span></label>
                    <?php echo imp_combo_w('sel_periodo_destino', $sql_destino, $db, $usu, $periodo_destino_id); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Centro de Costos <span class="text-danger">*</span></label>
                    <?php echo imp_combo_w('sel_cc', $sql_cc, $db, $usu, $cc_sel); ?>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-pade w-100" onclick="prepararForm('vista_previa')">
                        <i class="bi bi-eye me-1"></i>Vista Previa SR
                    </button>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Compara SR del origen con destino. Genera SR nuevas para matriculas faltantes (respetando presupuesto aprobado).
                </small>
            </div>
        </form>
    </div>
</div>

<script>
function prepararForm(accion) {
    document.getElementById('accion_post').value = accion;
    document.getElementById('h_periodo_origen').value = document.getElementById('sel_periodo_origen').value;
    document.getElementById('h_periodo_destino').value = document.getElementById('sel_periodo_destino').value;
    document.getElementById('h_cc_sel').value = document.getElementById('sel_cc').value;
}
</script>

<?php
// ═══════════════════════════════════════════════════════════════════════════════
// VISTA PREVIA - COMPARATIVO SR
// ═══════════════════════════════════════════════════════════════════════════════
if ($vista_previa_data):
    // Totales globales
    $gt_or = 0; $gt_dest = 0; $gt_nuevas = 0; $gt_ppto = 0;
    $gt_sr_or = 0; $gt_sr_dest = 0; $gt_sr_new = 0;
    foreach ($vista_previa_data as $item) {
        $gt_or     += $item['total_oc_orig'];
        $gt_dest   += $item['total_oc_dest'];
        $gt_nuevas += $item['total_nuevas'];
        $gt_ppto   += $item['ppto'];
        $gt_sr_or  += count($item['sr_origen']);
        $gt_sr_dest+= count($item['sr_destino']);
        $gt_sr_new += count($item['sr_nuevas']);
    }
    $gt_dest_total = $gt_dest + $gt_nuevas;
    $pct_similitud = ($gt_or > 0) ? round(($gt_dest_total / $gt_or) * 100, 1) : 0;
?>

<!-- RESUMEN CARDS -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-secondary">
            <div class="card-body py-2">
                <div class="text-muted small">Origen (<?php echo $anio_origen; ?>)</div>
                <div class="fs-5 fw-bold">U$D <?php echo number_format($gt_or, 2, ',', '.'); ?></div>
                <small class="text-muted"><?php echo $gt_sr_or; ?> lineas SR</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-info">
            <div class="card-body py-2">
                <div class="text-muted small">Destino en curso (<?php echo $anio_destino; ?>)</div>
                <div class="fs-5 fw-bold text-info">U$D <?php echo number_format($gt_dest, 2, ',', '.'); ?></div>
                <small class="text-muted"><?php echo $gt_sr_dest; ?> lineas activas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-success">
            <div class="card-body py-2">
                <div class="text-muted small">SR Nuevas a crear</div>
                <div class="fs-5 fw-bold text-success">U$D <?php echo number_format($gt_nuevas, 2, ',', '.'); ?></div>
                <small class="text-muted"><?php echo $gt_sr_new; ?> lineas proyectadas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-primary">
            <div class="card-body py-2">
                <div class="text-muted small">Similitud dest/origen</div>
                <div class="fs-5 fw-bold <?php echo ($pct_similitud >= 80) ? 'text-success' : (($pct_similitud >= 50) ? 'text-warning' : 'text-danger'); ?>">
                    <?php echo number_format($pct_similitud, 1); ?>%
                </div>
                <small class="text-muted">
                    U$D <?php echo number_format($gt_dest_total, 2, ',', '.'); ?> vs <?php echo number_format($gt_or, 2, ',', '.'); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- TABLA COMPARATIVA POR ITEM -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light py-2">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>Comparativo SR por Item de Gasto</h6>
            <span class="badge bg-warning text-dark">Factor global: <?php echo number_format($factor_global, 4); ?></span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr class="table-light">
                        <th style="width:30px;"></th>
                        <th>Item de Gasto</th>
                        <th class="text-end">Ppto Aprob.</th>
                        <th class="text-center">SR Orig.</th>
                        <th class="text-end">OC Origen</th>
                        <th class="text-center">SR Dest.</th>
                        <th class="text-end">OC Destino</th>
                        <th class="text-center text-success">SR Nuevas</th>
                        <th class="text-end text-success">Monto Nuevas</th>
                        <th class="text-end">Total Dest.</th>
                        <th style="width:90px;">% Simil.</th>
                    </tr>
                </thead>
                <tbody>
<?php
    $n = 0;
    foreach ($vista_previa_data as $gid => $item):
        $n++;
        $total_dest_item = $item['total_oc_dest'] + $item['total_nuevas'];
        $pct_item = ($item['total_oc_orig'] > 0) ? round(($total_dest_item / $item['total_oc_orig']) * 100, 1) : 0;
        $pct_class = ($pct_item >= 80) ? 'bg-success' : (($pct_item >= 50) ? 'bg-warning' : 'bg-danger');
        $pct_uso = ($item['ppto'] > 0) ? round(($total_dest_item / $item['ppto']) * 100, 1) : 0;
        $has_detail = !empty($item['sr_origen']) || !empty($item['sr_destino']) || !empty($item['sr_nuevas']);
        $row_id = 'detail_' . $gid;
?>
                    <tr style="cursor:<?php echo $has_detail ? 'pointer' : 'default'; ?>;" <?php if ($has_detail): ?>onclick="toggleDetail('<?php echo $row_id; ?>')"<?php endif; ?>>
                        <td class="text-center">
                            <?php if ($has_detail): ?>
                                <i class="bi bi-chevron-right" id="ico_<?php echo $row_id; ?>" style="transition:transform 0.2s;"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="fw-bold"><?php echo htmlspecialchars($gid); ?></span>
                            <span class="text-muted mx-1">—</span>
                            <span class="small"><?php echo htmlspecialchars($item['nombre']); ?></span>
                            <?php if ($item['factor'] != $factor_global): ?>
                                <span class="badge bg-warning text-dark ms-1" style="font-size:0.6rem;">×<?php echo number_format($item['factor'], 2); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end small">
                            U$D <?php echo number_format($item['ppto'], 0, ',', '.'); ?>
                            <?php if ($item['ppto_fuente'] === 'k2b'): ?>
                                <span class="badge bg-info text-dark" style="font-size:0.55rem;" title="Fuente: K2B">K2B</span>
                            <?php elseif ($item['ppto_fuente'] === 'none'): ?>
                                <span class="badge bg-danger" style="font-size:0.55rem;" title="Sin presupuesto asignado">!</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><span class="badge bg-secondary"><?php echo count($item['sr_origen']); ?></span></td>
                        <td class="text-end small">U$D <?php echo number_format($item['total_oc_orig'], 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="badge bg-info text-dark"><?php echo count($item['sr_destino']); ?></span></td>
                        <td class="text-end small">U$D <?php echo number_format($item['total_oc_dest'], 2, ',', '.'); ?></td>
                        <td class="text-center">
                            <?php if (count($item['sr_nuevas']) > 0): ?>
                                <span class="badge bg-success"><?php echo count($item['sr_nuevas']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end small fw-semibold text-success">
                            <?php echo $item['total_nuevas'] > 0 ? 'U$D ' . number_format($item['total_nuevas'], 2, ',', '.') : '—'; ?>
                        </td>
                        <td class="text-end small fw-bold">U$D <?php echo number_format($total_dest_item, 2, ',', '.'); ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1" style="height:6px;">
                                    <div class="progress-bar <?php echo $pct_class; ?>" style="width:<?php echo min($pct_item, 100); ?>%;"></div>
                                </div>
                                <span class="small" style="min-width:30px;text-align:right;"><?php echo number_format($pct_item, 0); ?>%</span>
                            </div>
                        </td>
                    </tr>
<?php if ($has_detail):
    $tab_id = 'tabs_' . $gid;
?>
                    <tr id="<?php echo $row_id; ?>" style="display:none;">
                        <td colspan="11" class="p-0">
                            <!-- Tabs: Origen / Destino / Nuevas -->
                            <ul class="nav nav-tabs nav-fill small" style="font-size:0.8rem;">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#<?php echo $tab_id; ?>_or">
                                        <i class="bi bi-arrow-left-circle me-1"></i>Origen
                                        <span class="badge bg-secondary ms-1"><?php echo count($item['sr_origen']); ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#<?php echo $tab_id; ?>_dest">
                                        <i class="bi bi-arrow-right-circle me-1 text-info"></i>Destino
                                        <span class="badge bg-info text-dark ms-1"><?php echo count($item['sr_destino']); ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#<?php echo $tab_id; ?>_new">
                                        <i class="bi bi-plus-circle me-1 text-success"></i>Nuevas
                                        <span class="badge bg-success ms-1"><?php echo count($item['sr_nuevas']); ?></span>
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active p-2" id="<?php echo $tab_id; ?>_or" style="border-left:4px solid #6c757d;">
                                    <?php render_sr_cats($item['cats_origen'], 'origen', $gid); ?>
                                </div>
                                <div class="tab-pane fade p-2" id="<?php echo $tab_id; ?>_dest" style="border-left:4px solid #0dcaf0;">
                                    <?php render_sr_cats($item['cats_destino'], 'destino', $gid); ?>
                                </div>
                                <div class="tab-pane fade p-2" id="<?php echo $tab_id; ?>_new" style="border-left:4px solid #198754;">
                                    <?php render_sr_cats($item['cats_nuevas'], 'nuevas', $gid); ?>
                                </div>
                            </div>
                            <div class="small text-muted px-3 py-1" style="background:#f8f9fa; border-top:1px solid #dee2e6;">
                                Ppto aprobado: U$D <?php echo number_format($item['ppto'], 0, ',', '.'); ?>
                                · OC en curso: U$D <?php echo number_format($item['total_oc_dest'], 2, ',', '.'); ?>
                                · Uso total: U$D <?php echo number_format($total_dest_item, 2, ',', '.'); ?>
                                (<?php echo number_format($pct_uso, 1); ?>%)
                                <?php if ($pct_uso > 100): ?>
                                    <span class="badge bg-danger">EXCEDE PPTO</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
<?php endif; ?>
<?php endforeach; ?>

                    <!-- TOTALES -->
                    <tr class="table-dark fw-bold">
                        <td></td>
                        <td>TOTALES</td>
                        <td class="text-end">U$D <?php echo number_format($gt_ppto, 0, ',', '.'); ?></td>
                        <td class="text-center"><?php echo $gt_sr_or; ?></td>
                        <td class="text-end">U$D <?php echo number_format($gt_or, 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo $gt_sr_dest; ?></td>
                        <td class="text-end">U$D <?php echo number_format($gt_dest, 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo $gt_sr_new; ?></td>
                        <td class="text-end">U$D <?php echo number_format($gt_nuevas, 2, ',', '.'); ?></td>
                        <td class="text-end">U$D <?php echo number_format($gt_dest_total, 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo number_format($pct_similitud, 0); ?>%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- CONFIRMAR -->
    <div class="card-footer">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="confirmar">
            <input type="hidden" name="periodo_origen" value="<?php echo $periodo_origen_id; ?>">
            <input type="hidden" name="periodo_destino" value="<?php echo $periodo_destino_id; ?>">
            <input type="hidden" name="cc_sel" value="<?php echo $cc_sel; ?>">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    <?php echo $gt_sr_new; ?> SR nuevas por U$D <?php echo number_format($gt_nuevas, 2, ',', '.'); ?>
                    · Similitud: <?php echo number_format($pct_similitud, 1); ?>%
                </span>
                <?php if ($gt_sr_new > 0): ?>
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-check2-all me-1"></i>Confirmar y Crear <?php echo $gt_sr_new; ?> SR Borrador
                </button>
                <?php else: ?>
                <span class="badge bg-info text-dark fs-6 py-2 px-3">No hay SR nuevas para crear - destino ya completo</span>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
function toggleDetail(id) {
    var row = document.getElementById(id);
    var ico = document.getElementById('ico_' + id);
    if (row.style.display === 'none') {
        row.style.display = '';
        if (ico) ico.style.transform = 'rotate(90deg)';
    } else {
        row.style.display = 'none';
        if (ico) ico.style.transform = '';
    }
}
</script>

<?php endif; ?>
