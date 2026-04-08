<?php
/**
 * ============================================================================
 * CONFIGURACIÓN CENTRAL DE MÓDULOS - 50 estilos únicos
 * ============================================================================
 * Cada módulo tiene: color, gradiente, símbolo SVG, y forma del icono.
 * 
 * Para asignar un estilo a un código de sistema:
 *   1) Agregar entrada en $MODULE_STYLE_MAP (abajo) → código => número de estilo
 *   2) Los estilos van del 1 al 50, cada uno completamente distinto
 *
 * Ejemplo: 'AHID_051' => 1  →  Usa estilo #1 (verde menta, hexágono, rayo)
 * ============================================================================
 */

if (!defined('ENTO_APP')) {
    define('ENTO_APP', true);
}

// ═══════════════════════════════════════════════════════════════════════════════
// MAPA: código_sistema → número de estilo (1-50)
// ═══════════════════════════════════════════════════════════════════════════════
// Modificar aquí para asignar qué estilo visual le corresponde a cada módulo.

$MODULE_STYLE_MAP = [
    // Códigos nuevos (AHID_*)
    'AHID_001' =>  1,
    'AHID_002' =>  2,
    'AHID_003' =>  3,
    'AHID_004' =>  4,
    'AHID_006' =>  5,
    'AHID_007' =>  6,
    'AHID_008' =>  7,
    'AHID_009' =>  8,
    'AHID_010' =>  9,
    'AHID_011' => 10,
    'AHID_012' => 11,
    'AHID_013' => 12,
    'AHID_014' => 13,
    'AHID_015' => 14,
    'AHID_016' => 15,
    'AHID_017' => 16,
    'AHID_018' => 17,
    'AHID_019' => 18,
    'AHID_020' => 19,
    'AHID_051' => 20,
    'AHID_052' => 21,
    'AHID_053' => 22,
    'AHID_054' => 23,
    'AHID_055' => 24,
    // Códigos legacy (CTM_*, POLO_*, AICO_*)
    'CTM_006'  =>  5,
    'CTM_007'  =>  6,
    'CTM_011'  => 10,
    'CTM_012'  => 11,
    'CTM_013'  => 12,
    'CTM_014'  => 13,
    'CTM_015'  => 14,
    'CTM_016'  => 15,
    'CTM_017'  => 16,
    'CTM_018'  => 17,
    'CTM_019'  => 18,
    'CTM_043'  => 25,
    'CTM_051'  => 20,
    'CTM_052'  => 26,
    'CTM_053'  => 22,
    'CTM_054'  => 23,
    'CTM_055'  => 27,
    'CTM_056'  =>  4,
    'CTM_057'  => 24,
    'CTM_058'  =>  9,
    'CTM_060'  => 19,
    'CTM_061'  =>  7,
    'CTM_062'  =>  8,
    'POLO_001' =>  2,
    'AICO_666' =>  3,
    // Módulos DEDU
    'DEDU_002' => 28,
    'DEDU_003' => 29,
    'DEDU_004' => 30,
    'DEDU_005' => 31,
    'DEDU_006' => 32,
    'DEDU_007' => 33,
    'DEDU_008' => 34,
    'DEDU_009' => 35,
    'DEDU_051' => 36,
    'DEDU_053' => 37,
    'DEDU_054' => 38,
    // Módulos DICO
    'DICO_001' => 39,
    'DICO_002' => 40,
    'DICO_003' => 41,
    'DICO_004' => 42,
    'DICO_005' => 43,
    // Módulos OPR
    'OPR_001'  => 44,
    'OPR_004'  => 45,
    'OPR_005'  => 46,
    'OPR_006'  => 47,
    'OPR_008'  => 48,
    'OPR_010'  => 49,
    'OPR_011'  => 50,
    'OPR_012'  =>  3,
];

// ═══════════════════════════════════════════════════════════════════════════════
// 50 ESTILOS ÚNICOS - color, gradiente, símbolo, forma
// ═══════════════════════════════════════════════════════════════════════════════
// Cada estilo es completamente distinto en color y forma.
//   color     → color principal (hex)
//   color2    → color secundario para gradiente
//   symbol    → carácter/símbolo SVG del icono
//   shape     → forma: circle, square, hexagon, diamond, shield, octagon
//   gradient  → dirección: 135, 45, 180, 0, 90, 225

$MODULE_STYLES = [
    1  => ['color' => '#6EEE81', 'color2' => '#38b249', 'symbol' => '⚡', 'shape' => 'hexagon',  'gradient' => 135],
    2  => ['color' => '#319888', 'color2' => '#1a6b5f', 'symbol' => '🌊', 'shape' => 'circle',   'gradient' => 45],
    3  => ['color' => '#2CA58D', 'color2' => '#1b7a68', 'symbol' => '🔗', 'shape' => 'shield',   'gradient' => 180],
    4  => ['color' => '#FF61A8', 'color2' => '#d4357e', 'symbol' => '💎', 'shape' => 'diamond',  'gradient' => 225],
    5  => ['color' => '#c987ff', 'color2' => '#9b4dca', 'symbol' => '🔮', 'shape' => 'circle',   'gradient' => 135],
    6  => ['color' => '#D4BC5D', 'color2' => '#b09830', 'symbol' => '⭐', 'shape' => 'octagon',  'gradient' => 90],
    7  => ['color' => '#B8336A', 'color2' => '#8a1e4e', 'symbol' => '🎯', 'shape' => 'hexagon',  'gradient' => 0],
    8  => ['color' => '#6D454C', 'color2' => '#4a2a30', 'symbol' => '🏛️', 'shape' => 'square',   'gradient' => 135],
    9  => ['color' => '#5D5F71', 'color2' => '#3d3f4f', 'symbol' => '⚙️', 'shape' => 'circle',   'gradient' => 45],
    10 => ['color' => '#DBA7B0', 'color2' => '#c07d89', 'symbol' => '🩺', 'shape' => 'shield',   'gradient' => 180],
    11 => ['color' => '#EAD2AC', 'color2' => '#cdb07a', 'symbol' => '📋', 'shape' => 'square',   'gradient' => 90],
    12 => ['color' => '#ACACB1', 'color2' => '#87878e', 'symbol' => '📊', 'shape' => 'hexagon',  'gradient' => 135],
    13 => ['color' => '#96ADC8', 'color2' => '#6b8aac', 'symbol' => '🗂️', 'shape' => 'diamond',  'gradient' => 225],
    14 => ['color' => '#b87767', 'color2' => '#944e3e', 'symbol' => '🔧', 'shape' => 'octagon',  'gradient' => 0],
    15 => ['color' => '#AD8A64', 'color2' => '#8a6940', 'symbol' => '📦', 'shape' => 'shield',   'gradient' => 45],
    16 => ['color' => '#CDA6FD', 'color2' => '#a570e0', 'symbol' => '🎓', 'shape' => 'circle',   'gradient' => 135],
    17 => ['color' => '#D0D5A6', 'color2' => '#aeb47a', 'symbol' => '🌿', 'shape' => 'hexagon',  'gradient' => 180],
    18 => ['color' => '#B4A789', 'color2' => '#918262', 'symbol' => '📐', 'shape' => 'square',   'gradient' => 90],
    19 => ['color' => '#FFB861', 'color2' => '#e09530', 'symbol' => '🔔', 'shape' => 'diamond',  'gradient' => 45],
    20 => ['color' => '#4CAF50', 'color2' => '#2e7d32', 'symbol' => '💧', 'shape' => 'hexagon',  'gradient' => 135],
    21 => ['color' => '#FF6961', 'color2' => '#d4423a', 'symbol' => '🔥', 'shape' => 'circle',   'gradient' => 225],
    22 => ['color' => '#E74C3C', 'color2' => '#c0392b', 'symbol' => '📌', 'shape' => 'shield',   'gradient' => 0],
    23 => ['color' => '#E91E90', 'color2' => '#b5156e', 'symbol' => '🎀', 'shape' => 'diamond',  'gradient' => 135],
    24 => ['color' => '#E3D769', 'color2' => '#c4b940', 'symbol' => '☀️', 'shape' => 'octagon',  'gradient' => 90],
    25 => ['color' => '#66BB6A', 'color2' => '#43a047', 'symbol' => '🌱', 'shape' => 'circle',   'gradient' => 180],
    26 => ['color' => '#0F6961', 'color2' => '#094a44', 'symbol' => '🧭', 'shape' => 'hexagon',  'gradient' => 45],
    27 => ['color' => '#6AAA81', 'color2' => '#4a8a61', 'symbol' => '🍃', 'shape' => 'shield',   'gradient' => 135],
    28 => ['color' => '#5C6BC0', 'color2' => '#3949AB', 'symbol' => '📘', 'shape' => 'square',   'gradient' => 225],
    29 => ['color' => '#26A69A', 'color2' => '#00897B', 'symbol' => '📗', 'shape' => 'diamond',  'gradient' => 0],
    30 => ['color' => '#EF5350', 'color2' => '#E53935', 'symbol' => '📕', 'shape' => 'circle',   'gradient' => 135],
    31 => ['color' => '#AB47BC', 'color2' => '#8E24AA', 'symbol' => '📙', 'shape' => 'hexagon',  'gradient' => 90],
    32 => ['color' => '#42A5F5', 'color2' => '#1E88E5', 'symbol' => '📒', 'shape' => 'octagon',  'gradient' => 45],
    33 => ['color' => '#FFA726', 'color2' => '#FB8C00', 'symbol' => '📓', 'shape' => 'shield',   'gradient' => 180],
    34 => ['color' => '#78909C', 'color2' => '#546E7A', 'symbol' => '📔', 'shape' => 'square',   'gradient' => 135],
    35 => ['color' => '#EC407A', 'color2' => '#D81B60', 'symbol' => '🎒', 'shape' => 'diamond',  'gradient' => 225],
    36 => ['color' => '#7E57C2', 'color2' => '#5E35B1', 'symbol' => '🏫', 'shape' => 'circle',   'gradient' => 0],
    37 => ['color' => '#29B6F6', 'color2' => '#039BE5', 'symbol' => '✏️', 'shape' => 'hexagon',  'gradient' => 90],
    38 => ['color' => '#66BB6A', 'color2' => '#43A047', 'symbol' => '🎓', 'shape' => 'shield',   'gradient' => 135],
    39 => ['color' => '#3F51B5', 'color2' => '#283593', 'symbol' => '📡', 'shape' => 'hexagon',  'gradient' => 45],
    40 => ['color' => '#009688', 'color2' => '#00796B', 'symbol' => '📶', 'shape' => 'circle',   'gradient' => 180],
    41 => ['color' => '#FF7043', 'color2' => '#F4511E', 'symbol' => '🖥️', 'shape' => 'square',   'gradient' => 225],
    42 => ['color' => '#8D6E63', 'color2' => '#6D4C41', 'symbol' => '🔌', 'shape' => 'diamond',  'gradient' => 0],
    43 => ['color' => '#26C6DA', 'color2' => '#00ACC1', 'symbol' => '💻', 'shape' => 'octagon',  'gradient' => 135],
    44 => ['color' => '#9CCC65', 'color2' => '#7CB342', 'symbol' => '🏗️', 'shape' => 'hexagon',  'gradient' => 90],
    45 => ['color' => '#FFCA28', 'color2' => '#FFB300', 'symbol' => '⚠️', 'shape' => 'diamond',  'gradient' => 45],
    46 => ['color' => '#8BC34A', 'color2' => '#689F38', 'symbol' => '🔩', 'shape' => 'shield',   'gradient' => 180],
    47 => ['color' => '#FF8A65', 'color2' => '#FF7043', 'symbol' => '🛠️', 'shape' => 'circle',   'gradient' => 135],
    48 => ['color' => '#4DB6AC', 'color2' => '#26A69A', 'symbol' => '📏', 'shape' => 'square',   'gradient' => 225],
    49 => ['color' => '#7986CB', 'color2' => '#5C6BC0', 'symbol' => '🗃️', 'shape' => 'hexagon',  'gradient' => 0],
    50 => ['color' => '#A1887F', 'color2' => '#8D6E63', 'symbol' => '📎', 'shape' => 'octagon',  'gradient' => 90],
];

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES DE ACCESO
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Obtener el número de estilo asignado a un código de sistema.
 * Devuelve 0 si no tiene asignado (usa default gris).
 */
function getModuleStyleNumber($codigo) {
    global $MODULE_STYLE_MAP;
    return $MODULE_STYLE_MAP[$codigo] ?? 0;
}

/**
 * Obtener la configuración completa de estilo para un código de sistema.
 * Devuelve array con: color, color2, symbol, shape, gradient.
 */
function getModuleStyle($codigo) {
    global $MODULE_STYLES, $MODULE_STYLE_MAP;
    $num = $MODULE_STYLE_MAP[$codigo] ?? 0;
    if ($num > 0 && isset($MODULE_STYLES[$num])) {
        return $MODULE_STYLES[$num];
    }
    return ['color' => '#6c757d', 'color2' => '#495057', 'symbol' => '📄', 'shape' => 'circle', 'gradient' => 135];
}

/**
 * Obtener solo el color principal de un módulo.
 */
function getModuleColor($codigo) {
    $style = getModuleStyle($codigo);
    return $style['color'];
}

/**
 * Obtener la URL del icono SVG dinámico para un módulo.
 */
function getModuleIconUrl($codigo) {
    return 'assets/icons/icon.php?code=' . urlencode($codigo);
}
