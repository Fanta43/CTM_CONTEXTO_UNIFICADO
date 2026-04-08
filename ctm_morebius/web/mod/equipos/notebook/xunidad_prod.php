<style>
        body { padding: 20px; }
        .toggle { user-select: none; cursor: pointer; }
        .sector-node { font-weight: bold; background-color: #f8f9fa; }
        table tr:hover { background-color: #f5f5f5; }
</style>
<?php
//ini_set('display_errors', 'on');
// Obtener todos los nodos raíz (sectores sin padre o con padre inexistente)
function construirOrganica($inicio='S400', $db='2', $usu='99999999'){
    /// Desde aca los nodos de la organica principal:
                        $sql_mysql="
                                        WITH RECURSIVE organicas_cte (pkey, padre, nsector, legajo, persona, nivel, jefe, level) AS (
                                                SELECT o0.pkey, 
                                                       o0.padre,
                                                       o0.nsector,
                                                       o0.legajo,
                                                       o0.persona,
                                                       o0.nivel,
                                                       o0.jefe,
                                                       1 as level
                                                  FROM [PAYWEBCTM].[dbo].[ctm_arbol] o0
                                                 WHERE o0.pkey = 'S400'
                                             UNION ALL
                                                SELECT o.pkey, 
                                                       o.padre,
                                                       o.nsector,
                                                       o.legajo,
                                                       o.persona,
                                                       o.nivel, 
                                                       o.jefe,
                                                       level + 1 as level
                                                  FROM [PAYWEBCTM].[dbo].[ctm_arbol] o
                                                  JOIN organicas_cte cte ON o.padre = cte.pkey
                                             )
                                             SELECT oc.pkey, 
                                                    oc.padre,
                                                    oc.nsector,
                                                    oc.legajo,
                                                    oc.persona,
                                                    oc.nivel, 
                                                    oc.jefe, 
                                                    oc.level
                                             FROM organicas_cte oc
                                            WHERE oc.padre <> 'S1000' AND oc.pkey <> 'S1000'
                                         ORDER BY oc.padre, oc.nivel, oc.legajo;
                   ";

                        $sql_mssql="
                                    WITH organicas_cte (pkey, padre, nsector, legajo, persona, nivel, jefe, level) AS (
                                        SELECT 
                                            o0.pkey, 
                                            o0.padre,
                                            o0.nsector,
                                            o0.legajo,
                                            o0.persona,
                                            o0.nivel,
                                            o0.jefe,
                                            1 AS level
                                        FROM [PAYWEBCTM].[dbo].[ctm_arbol] o0
                                        WHERE o0.pkey = 'S400'
                                        
                                        UNION ALL
                                        
                                        SELECT 
                                            o.pkey, 
                                            o.padre,
                                            o.nsector,
                                            o.legajo,
                                            o.persona,
                                            o.nivel, 
                                            o.jefe,
                                            cte.level + 1 AS level
                                        FROM [PAYWEBCTM].[dbo].[ctm_arbol] o
                                        INNER JOIN organicas_cte cte ON o.padre = cte.pkey
                                    )
                                    SELECT 
                                        oc.pkey, 
                                        oc.padre,
                                        oc.nsector,
                                        oc.legajo,
                                        oc.persona,
                                        oc.nivel, 
                                        oc.jefe, 
                                        oc.level
                                    FROM organicas_cte oc
                                    WHERE 
                                        oc.padre <> 'S1000' 
                                        AND oc.pkey <> 'S1000'
                                    ORDER BY 
                                        oc.padre, 
                                        oc.nivel, 
                                        oc.legajo;
                   ";
                        
                          //echo $sql;
                          $n = 0;
                          $str_org = "";
                          // Estructuras para la representación en tabla
                          $nodes = [];
                          $children = [];
                          $result = sql_do_query($sql_mssql,$db,$usu);
                          while($row=sql_do_array($result,$db,$usu)) {
                              // Normalizar claves y cargar estructuras
                              $row = array_change_key_case($row, CASE_UPPER);
                              // Normalizar claves quitando espacios
                              $pkey = trim($row['PKEY']);
                              $padre = trim($row['PADRE']);
                              $row['PKEY'] = $pkey;
                              $row['PADRE'] = $padre;
                              $nodes[$pkey] = $row;
                              $children[$padre][] = $pkey;
                              // Evitar la lógica antigua de armado de string/imprimir*
                              // para que no oculte información ni haga consultas extra.
                              // La representación en tabla usará $nodes/$children.
                              continue;
                              if($row['PERSONA']!='1'){
                                                           $sql_cant = "SELECT COUNT(f.EmpLegajo) AS cant 
                                                                          FROM [PAYWEBCTM].[dbo].[ctm_plantel] f
                                                                         INNER JOIN [PAYWEBCTM].[dbo].[ctm_arbol] a ON f.EmpLegajo = a.legajo
                                                                         WHERE a.persona = '1' 
                                                                           AND a.padre = '".rtrim($row['PKEY'])."' 
                                                                           AND a.jefe = '1';";   

//echo $sql_cant;
                                                              $row_cant = sql_do_array(sql_do_query($sql_cant,$db,$usu));
                                                              //var_dump($row_cant);                                                              
                                                              $sql_sector = "SELECT connomabr FROM [PAYWEBCTM].[dbo].[ctm_centroscosto] a  WHERE a.ConIdcc='".$row['NSECTOR']."'";
                                                              $row_sector = sql_do_array(sql_do_query($sql_sector,$db,$usu));                                                              
                                                                if($row_cant['CANT']==1){
                                                                        $sql_jefe = "SELECT * FROM [PAYWEBCTM].[dbo].[ctm_plantel] f, [PAYWEBCTM].[dbo].[ctm_arbol] a WHERE f.EmpLegajo=a.legajo AND a.persona='1' AND a.padre = '".trim($row['PKEY'])."' AND a.jefe='1' ;";
                                                                        //if(trim($row['PKEY']) == 'S880') echo $sql_persona;
                                                                        /*$sql_persona = "SELECT empnombre, nivelcodext,  empcargonom, emplegajo, empnacionalidad, empemail
                                                                                        FROM [RRHH-DB].[CTMRHPRO].[dbo].[[PAYWEBCTM].[dbo].[ctm_plantel]] f
                                                                                  INNER JOIN [PAYWEBCTM].[dbo].[CTMARBOLNOF] a ON f.EmpLegajo = a.legajo
                                                                                       WHERE a.persona = '1' 
                                                                                         AND a.padre = '".rtrim($row['PKEY'])."' 
                                                                                         AND a.jefe = '1' 
                                                                                    ORDER BY f.emplegajo ASC;";*/
                                                                        
                                                                        $row_jefe = sql_do_array(sql_do_query($sql_jefe,$db,$usu));
                                                                        $str_org .= imprimirPersona($row, $row_jefe, $n, '0', $db,$usu);
                                                                }else{                                                                  
                                                                      if($row_cant['CANT']==0){  
                                                                          $str_org .= '{id: "'.trim($row['PKEY']).'", ';
                                                                            $str_org .= 'pid: "'.trim($row['PADRE']).'", ';                                                                        
                                                                            if($row['LEVEL']>'2'){
                                                                                $str_org .= 'Nombre: "VACANTE", Cargo: "'.rtrim($row_sector['CONNOMABR']).'" }, ';
                                                                            }else{
                                                                                $str_org .= 'Nombre: "BINACIONAL", Cargo: "'.rtrim($row_sector['CONNOMABR']).'" }, ';
                                                                            }                                                                      
                                                                      }else{
                                                                          
                                                                            $sql_jefe_cat = "SELECT MAX(nivelcodext) AS cat FROM [PAYWEBCTM].[dbo].[ctm_plantel] f, [PAYWEBCTM].[dbo].[ctm_arbol] a WHERE f.EmpLegajo=a.legajo AND a.persona='1' AND a.padre = '".$row['PKEY']."' AND a.jefe='1' ;";
                                                                            $row_jefe_cat = sql_do_array(sql_do_query($sql_jefe_cat,$db,$usu));
                                                                            $str_org .= '{id: "'.trim($row['PKEY']).'", ';
                                                                            $str_org .= 'pid: "'.trim($row['PADRE']).'", ';
                                                                            $str_org .= 'Nombre: "BINACIONAL", Cargo: "'.rtrim($row_sector['CONNOMABR']).'", ';
                                                                            $str_org .= ' tags: ["subLevels'.trim($row_jefe_cat['CAT']).'"]},';
                                                                            $str_org .= imprimirJefe($row, $n, $db, $usu);  
                                                                    }
                                                                }    
                             }else{                                   
                                   if($row['JEFE']!='1'){
                                                                      $sql_persona = "SELECT * FROM [PAYWEBCTM].[dbo].[ctm_plantel] f, [PAYWEBCTM].[dbo].[ctm_arbol] a WHERE f.EmpLegajo=a.legajo AND a.persona='1' AND a.pkey = '".rtrim($row['PKEY'])."' AND a.jefe='0' ;";
                                                                      $sql_persona = "SELECT empnombre, nivelcodext,  empcargonom, emplegajo, empnacionalidad, empemail
                                                                                        FROM [PAYWEBCTM].[dbo].[ctm_plantel] f
                                                                                  INNER JOIN [PAYWEBCTM].[dbo].[ctm_arbol] a ON f.EmpLegajo = a.legajo
                                                                                       WHERE a.persona = '1' 
                                                                                         AND a.pkey = '".rtrim($row['PKEY'])."' 
                                                                                         AND a.jefe = '0' 
                                                                                    ORDER BY f.emplegajo ASC;";

//echo $sql_persona;
                                                                      $row_persona = sql_do_array(sql_do_query($sql_persona,$db,$usu),$db,$usu);;                                                                      
                                                                      $str_org .= imprimirPersona($row, $row_persona, $n, '0', $db,$usu);
                                                 }
                                                                       
                               }
                              $n++;
                          }
                // Asegurar que el nodo inicial esté presente aunque tenga padre 'S1000'
                if (!isset($nodes[$inicio])) {
                    $sql_root = "SELECT pkey, padre, nsector, legajo, persona, nivel, jefe, 1 as level FROM [PAYWEBCTM].[dbo].[ctm_arbol] WHERE pkey = '" . addslashes($inicio) . "'";
                    $res_root = sql_do_query($sql_root, $db, $usu);
                    if ($res_root) {
                        if ($row_root = sql_do_array($res_root,$db,$usu)) {
                            $row_root = array_change_key_case($row_root, CASE_UPPER);
                            $nodes[$row_root['PKEY']] = $row_root;
                        }
                    }
                }
                // Devolver estructuras para renderizado en tabla
                return [
                    'nodes' => $nodes,
                    'children' => $children,
                ];
}

// Inicializar estructuras para renderizar manteniendo la representación actual
$inicio = 'S400';
$orgData = construirOrganica($inicio, '2', $usu);

//var_dump($orgData);
$allNodes = $orgData['nodes'];
$allChildren = $orgData['children'];
// Mantener la API de render existente
$rootSectors = [ ['PKEY' => $inicio] ];

// DEBUG: Mostrar información de los nodos y estructura antes del renderizado
// Puedes quitar esto cuando confirmes que todo funciona
//
// Conteo de nodos e hijos
// echo '<pre>NODOS: '.count($allNodes).' | HIJOS: '.count($allChildren)."\n";
// print_r(array_keys($allNodes));
// echo "\nROOT: ".json_encode($rootSectors)."</pre>\n";
//
// Para ver la estructura completa (solo para debug):
// echo '<pre>'.print_r($allNodes, true).'</pre>';
// echo '<pre>'.print_r($allChildren, true).'</pre>';


function renderNode($pkey, $nodes, $children, $level = 1, $parent = '') {
    global $usu;
    
    if (!isset($nodes[$pkey])) {
        return;
    }
    
    $row = $nodes[$pkey];
    $hasChildren = isset($children[$pkey]) && count($children[$pkey]) > 0;
    $indentPx = ($level - 1) * 20;
    
    // Obtener información del centro de costo
    $cc = getCentroCosto($row['NSECTOR']);
    
    // Solo obtener datos de plantel para empleados (legajo != 0)
    $usuarioPlantel = null;
    if ($row['LEGAJO'] !== '0') {
        $usuarioPlantel = obtenerUsuarioPlantel($row['LEGAJO'], '2', $usu);
    }
    
    echo '<tr data-level="' . $level . '" data-parent="' . $parent . '"' . ($level > 1 ? ' style="display:none;"' : '') . '>';
    echo '<td style="padding-left:' . $indentPx . 'px;">';
    
    // Mostrar ícono de expansión/colapsado si tiene hijos
    if ($hasChildren) {
        echo '<span class="toggle" data-pkey="' . $pkey . '" style="cursor:pointer; font-weight:bold;">►</span> ';
    }
    
    // Mostrar nombre del sector o empleado
    if ($row['LEGAJO'] !== '0') {
        // Nodo de empleado
        //$nombrePersona = !empty($row['PERSONA']) ? $row['PERSONA'] : 'Sin nombre';
        //echo htmlspecialchars($nombrePersona);
        echo '&nbsp;';
    } else {
        // Nodo de sector
        $nombreSector = !empty($cc['CENTROCOSTONOM']) ? $cc['CENTROCOSTONOM'] : 'Sector sin nombre';
        echo '<strong>' . htmlspecialchars($nombreSector) . '</strong>';
    }
    
    echo ' (' . htmlspecialchars($row['PKEY']) . ')';
    echo '</td>';
       
    // Columna Empleado (Legajo)
    if ($row['LEGAJO'] !== '0') {
        $empNombre = $usuarioPlantel['EMPNOMBRE'] ?? 'Sin nombre';
        echo '<td>' . htmlspecialchars($empNombre) . ' (' . htmlspecialchars($row['LEGAJO']) . ')</td>';
    } else {
        // Para sectores, mostrar que es un nodo estructural
        echo '<td>&nbsp;</td>';
    }
    
    // Columna Jefe
    echo '<td>' . ($row['JEFE'] === '1' ? 'Sí' : 'No') . '</td>';
    
    //echo $row['LEGAJO'].'<br>';
    //Columnas de Notebook a Cargo
    $notebooks = [];
    if ($row['LEGAJO'] !== '0') {
        $notebooks = obtenerNotebookMorebius($row['LEGAJO'], '2', $usu);
        //var_dump($notebooks);
    }
    // Renderizar notebooks (pueden ser varios)
    echo '<td>';
    if (!empty($notebooks) && is_array($notebooks)) {
        $hasEquipo = false;
        echo '<ul style="margin:0; padding-left:18px;">';
        foreach ($notebooks as $nb) {
            if (!is_array($nb) || empty($nb['NRO_ART'])) continue;
            $hasEquipo = true;
            $art = htmlspecialchars($nb['NRO_ART']);
            $detalle = htmlspecialchars($nb['DETALLE'] ?? '');
            $referencia = htmlspecialchars($nb['REFERENCIA'] ?? '');
            $fecha = htmlspecialchars($nb['FECHA'] ?? '');
            echo '<li><b>Art:</b> ' . $art . ' <b>Detalle:</b> ' . $detalle . ' <b>Referencia:</b> ' . $referencia . ' <b>Fecha:</b> ' . $fecha . '</li>';
        }
        echo '</ul>';
        if (!$hasEquipo) echo '-';
    } else {
        echo '-';
    }
    echo '</td>';

    // Columna Controles
            echo '<td>&nbsp;</td>';
    echo '</tr>';
    
    // Renderizar hijos recursivamente
    if ($hasChildren) {
        foreach ($children[$pkey] as $child) {
            renderNode($child, $nodes, $children, $level + 1, $pkey);
        }
    }
}


?>
    <div class="container-fluid">
        <h3>Notebook – Unidades Organicas</h3>
        <table class="table table-bordered table-striped" id="tablaOrganigrama">
            <thead class="table-light">
                <tr>
                    <th>Sector / Unidad</th>
                    <th>Empleado (Legajo)</th>
                    <th>Jefe</th>
                    <th>Notebook</th>
                    <th>Controles</th>
                </tr>
            </thead>
            <tbody>
<?php
// Renderizar todos los sectores raíz y sus subárboles
foreach ($rootSectors as $rootSector) {
    renderNode($rootSector['PKEY'], $allNodes, $allChildren, 1, '');
}
?>
            </tbody>
        </table>
    </div>
    <script>
    function closeAllDescendants(pkey, table) {
        const childRows = table.querySelectorAll('tr[data-parent="' + pkey + '"]');
        childRows.forEach(function(row) {
            row.style.display = 'none';
            const toggleSpan = row.querySelector('.toggle');
            if (toggleSpan) {
                toggleSpan.textContent = '►';
                const childPkey = toggleSpan.dataset.pkey;
                if (childPkey) {
                    closeAllDescendants(childPkey, table);
                }
            }
        });
    }
    
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('toggle')) return;

        const pkey = e.target.dataset.pkey;
        const table = document.getElementById('tablaOrganigrama');
        const rows = table.querySelectorAll('tr[data-parent="' + pkey + '"]');
        const isOpen = e.target.textContent === '▼';

        e.target.textContent = isOpen ? '►' : '▼';
        
        if (isOpen) {
            rows.forEach(function (row) {
                row.style.display = 'none';
            });
            closeAllDescendants(pkey, table);
        } else {
            rows.forEach(function (row) {
                row.style.display = '';
            });
        }
    });
    </script>