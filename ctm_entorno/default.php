<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'includes/functions.php';
clearAuth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="img/ctm_favicon.ico"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="login-wrapper">
    <div class="login-card text-center">
        <img src="img/escudos/escudo_ctm.png" alt="CTM" style="height:80px;" class="mb-3">
        <h2>Hidrología</h2>
        <p class="text-muted mb-4">Ingrese sus credenciales para continuar</p>
        
        <form method="post" action="../intermedio_aico.php">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Usuario" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                </div>
            </div>
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text p-0">
                        <img src="assets/captcha/captcha.php" alt="Captcha" style="height:38px;border-radius:4px;">
                    </span>
                    <input type="text" class="form-control" id="captcha" name="captcha" placeholder="Captcha" required>
                </div>
            </div>
            <button type="submit" name="ingresar" value="1" class="btn btn-ento">
                <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
