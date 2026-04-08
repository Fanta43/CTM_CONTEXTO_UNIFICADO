<?php
/**
 * Organigrama - Vista jerárquica de la organización
 * Usa CTE recursiva sobre ctm_arbol (morebius DB) para construir el árbol.
 * Muestra sectores con sus responsables y personal asignado.
 */

$inicio = Enmascarar_get('inicio') ?: 'S400';

// CTE recursiva para obtener toda la jerarquía
$sql = "WITH RECURSIVE organicas_cte (pkey, padre, nsector, legajo, persona, nivel, jefe, level) AS (
            SELECT o0.pkey, o0.padre, o0.nsector, o0.legajo, o0.persona, o0.nivel, o0.jefe, 1 as level
              FROM ctm_arbol o0
             WHERE o0.pkey = '$inicio'
         UNION ALL
            SELECT o.pkey, o.padre, o.nsector, o.legajo, o.persona, o.nivel, o.jefe, level + 1 as level
              FROM ctm_arbol o
              JOIN organicas_cte cte ON o.padre = cte.pkey
        )
        SELECT oc.pkey, oc.padre, oc.nsector, oc.legajo, oc.persona, oc.nivel, oc.jefe, oc.level
          FROM organicas_cte oc
         WHERE oc.padre <> 'S1000' AND oc.pkey <> 'S1000'
         ORDER BY oc.padre, oc.nivel, oc.legajo;";

$result = sql_do_query($sql, '2', $usu);

// Construir estructuras para renderizado
$nodes = [];
$children = [];
while ($row = sql_do_array($result)) {
    $pkey  = trim($row['PKEY']);
    $padre = trim($row['PADRE']);
    $row['PKEY']  = $pkey;
    $row['PADRE'] = $padre;
    $nodes[$pkey] = $row;
    $children[$padre][] = $pkey;
}

// Cache de nombres de sectores
$sectores_cache = [];
function getNombreSector($nsector, $usu) {
    global $sectores_cache;
    if (!$nsector) return '';
    if (isset($sectores_cache[$nsector])) return $sectores_cache[$nsector];
    $sql = "SELECT CentroCostoNom FROM ctm_centroscosto WHERE CentroCosto = '$nsector';";
    $row = sql_do_array(sql_do_query($sql, '2', $usu));
    $sectores_cache[$nsector] = $row['CENTROCOSTONOM'] ?? '';
    return $sectores_cache[$nsector];
}

// Cache de nombres de personas
$personas_cache = [];
function getNombrePersona($legajo, $usu) {
    global $personas_cache;
    if (!$legajo) return '';
    if (isset($personas_cache[$legajo])) return $personas_cache[$legajo];
    $sql = "SELECT EmpNombre, EmpCargoNom FROM ctm_plantel WHERE EmpLegajo = '$legajo';";
    $row = sql_do_array(sql_do_query($sql, '2', $usu));
    $personas_cache[$legajo] = $row ? ['nombre' => $row['EMPNOMBRE'] ?? '', 'cargo' => $row['EMPCARGONOM'] ?? ''] : ['nombre' => '', 'cargo' => ''];
    return $personas_cache[$legajo];
}

// Función recursiva para renderizar el árbol
function renderNodo($pkey, &$nodes, &$children, $usu, $depth = 0) {
    if (!isset($nodes[$pkey])) return;
    $node = $nodes[$pkey];
    $isPersona = ($node['PERSONA'] == '1');
    $isJefe    = ($node['JEFE'] == '1');
    $indent    = min($depth * 20, 200);

    if (!$isPersona) {
        // Es un sector
        $sector_nombre = getNombreSector($node['NSECTOR'], $usu);
        echo '<div class="org-sector fade-in" style="margin-left:' . $indent . 'px;">';
        echo '<div class="org-sector-name">';
        echo '<i class="bi bi-diagram-3 me-1"></i>';
        echo htmlspecialchars($sector_nombre ?: $pkey);
        echo ' <small class="text-muted">(' . $pkey . ')</small>';
        echo '</div>';
    } else {
        // Es una persona
        $persona = getNombrePersona($node['LEGAJO'], $usu);
        $clase = $isJefe ? 'org-person org-jefe' : 'org-person';
        echo '<div class="' . $clase . '" style="margin-left:' . ($indent + 20) . 'px;">';
        echo htmlspecialchars($persona['nombre'] ?: 'Leg. ' . $node['LEGAJO']);
        if ($persona['cargo']) {
            echo ' <small class="text-muted">- ' . htmlspecialchars($persona['cargo']) . '</small>';
        }
        if ($isJefe) {
            echo ' <span class="badge bg-warning text-dark ms-1">Jefe</span>';
        }
        echo '</div>';
    }

    // Renderizar hijos
    if (isset($children[$pkey])) {
        foreach ($children[$pkey] as $child_pkey) {
            renderNodo($child_pkey, $nodes, $children, $usu, $depth + 1);
        }
    }

    if (!$isPersona) {
        echo '</div>';
    }
}
?>

<?php echo generarTituloSeccion('Organigrama', 'bi bi-diagram-3'); ?>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" action="" class="row g-2 align-items-end">
            <input type="hidden" name="estado" value="<?php echo $estado; ?>">
            <div class="col-md-4">
                <label class="form-label small mb-0">Nodo Inicial</label>
                <input type="text" class="form-control form-control-sm" name="inicio"
                       value="<?php echo htmlspecialchars($inicio); ?>" placeholder="Ej: S400">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-pade btn-sm w-100">
                    <i class="bi bi-search me-1"></i>Ver
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php
        if (isset($children[$inicio]) || isset($nodes[$inicio])) {
            renderNodo($inicio, $nodes, $children, $usu, 0);
        } else {
            echo '<div class="text-center text-muted py-4"><i class="bi bi-exclamation-circle me-2"></i>No se encontraron datos para el nodo "' . htmlspecialchars($inicio) . '"</div>';
        }
        ?>
    </div>
    <div class="card-footer bg-white text-end">
        <small class="text-muted"><?php echo count($nodes); ?> nodo(s) en el árbol</small>
    </div>
</div>
