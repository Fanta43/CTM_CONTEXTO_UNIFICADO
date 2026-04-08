<?php
/**
 * Presentación - Dashboard de módulos disponibles
 * Usa config/modules.php para colores, iconos y estilos.
 * Icono: SVG dinámico (assets/icons/icon.php) con fallback a PNG legacy.
 */
$sistemas = getSistemasUsuario($usu);
?>

<div class="container-fluid py-4 px-4 fade-in">
    <div class="text-center mb-4">
        <h4 class="fw-bold text-dark"><?php echo APP_NAME; ?></h4>
        <p class="text-muted mb-0">Seleccione un módulo para comenzar</p>
    </div>
    
    <div class="row g-3 justify-content-center">
        <?php foreach ($sistemas as $sys):
            $codigo = $sys['CODIGO_SISTEMA'];
            $nombre = htmlspecialchars($sys['NOMBRE']);
            $style  = getModuleStyle($codigo);
            $color  = $style['color'];
            $color2 = $style['color2'];
            $grad   = (int)$style['gradient'];
            $styleNum = getModuleStyleNumber($codigo);

            $pngPath = 'img/picto/' . $codigo . '.png';
            $iconSrc = is_file($pngPath) ? $pngPath : getModuleIconUrl($codigo);
        ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <a href="index.php?estado=<?php echo $codigo; ?>" 
               class="system-card mod-style-<?php echo $styleNum; ?>"
               style="--card-color: <?php echo $color; ?>; --card-color2: <?php echo $color2; ?>; --card-grad: <?php echo $grad; ?>deg;">
                <div class="card-icon">
                    <img src="<?php echo $iconSrc; ?>" 
                         alt="<?php echo $nombre; ?>"
                         onerror="this.src='img/picto/home.png'">
                </div>
                <span class="card-label"><?php echo $nombre; ?></span>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
