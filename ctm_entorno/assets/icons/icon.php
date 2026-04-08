<?php
/**
 * ============================================================================
 * Generador dinámico de iconos SVG
 * ============================================================================
 * Uso: icon.php?code=AHID_051
 * Genera un SVG único por módulo basado en config/modules.php
 * ============================================================================
 */

header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/modules.php';

$code = isset($_GET['code']) ? trim((string)$_GET['code']) : '';
$style = getModuleStyle($code);

$color  = htmlspecialchars($style['color']);
$color2 = htmlspecialchars($style['color2']);
$symbol = $style['symbol'];
$shape  = $style['shape'];
$grad   = (int)$style['gradient'];

$gradId = 'g' . md5($code);

// Coordenadas de formas (dentro de viewBox 0 0 80 80)
$shapes = [
    'circle'  => '<circle cx="40" cy="40" r="36" fill="url(#' . $gradId . ')" />',
    'square'  => '<rect x="6" y="6" width="68" height="68" rx="12" fill="url(#' . $gradId . ')" />',
    'hexagon' => '<polygon points="40,4 72,22 72,58 40,76 8,58 8,22" fill="url(#' . $gradId . ')" />',
    'diamond' => '<polygon points="40,2 76,40 40,78 4,40" fill="url(#' . $gradId . ')" />',
    'shield'  => '<path d="M40,4 L72,18 L72,48 Q72,68 40,78 Q8,68 8,48 L8,18 Z" fill="url(#' . $gradId . ')" />',
    'octagon' => '<polygon points="26,4 54,4 76,26 76,54 54,76 26,76 4,54 4,26" fill="url(#' . $gradId . ')" />',
];

$shapeSvg = $shapes[$shape] ?? $shapes['circle'];

// Gradiente con ángulo
$x1 = 50 - 50 * cos(deg2rad($grad));
$y1 = 50 - 50 * sin(deg2rad($grad));
$x2 = 50 + 50 * cos(deg2rad($grad));
$y2 = 50 + 50 * sin(deg2rad($grad));

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" width="80" height="80">
  <defs>
    <linearGradient id="<?php echo $gradId; ?>" x1="<?php echo round($x1); ?>%" y1="<?php echo round($y1); ?>%" x2="<?php echo round($x2); ?>%" y2="<?php echo round($y2); ?>%">
      <stop offset="0%" stop-color="<?php echo $color; ?>" />
      <stop offset="100%" stop-color="<?php echo $color2; ?>" />
    </linearGradient>
    <filter id="shadow" x="-10%" y="-10%" width="120%" height="120%">
      <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="rgba(0,0,0,0.2)" />
    </filter>
  </defs>
  <g filter="url(#shadow)">
    <?php echo $shapeSvg; ?>
  </g>
  <text x="40" y="48" text-anchor="middle" font-size="28" fill="white" style="filter:drop-shadow(0 1px 1px rgba(0,0,0,0.3))"><?php echo $symbol; ?></text>
</svg>
