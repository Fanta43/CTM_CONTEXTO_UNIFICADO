<!-- Footer sticky -->
<footer class="footer-pade">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="small">&copy; <?php echo date('Y'); ?> - <?php echo APP_TITULO; ?> v<?php echo APP_VERSION; ?></span>

        <div class="d-flex align-items-center gap-3">
            <?php echo herramienta_pie($usu ?? '', $nivel ?? 1); ?>

            <!-- Rol efectivo switcher -->
            <?php if (!empty($roles_disponibles) && count($roles_disponibles) > 1): ?>
            <form method="post" action="" class="d-inline-flex align-items-center gap-1 mb-0">
                <small class="text-muted text-nowrap"><i class="bi bi-shield-lock me-1"></i>Rol:</small>
                <select class="form-select form-select-sm py-0" name="cambiar_rol_efectivo"
                        onchange="this.form.submit()" style="width:auto; font-size:0.78rem;">
                    <?php foreach ($roles_disponibles as $rd): ?>
                    <option value="<?php echo $rd['CODIGO']; ?>" <?php echo ($rd['CODIGO'] === ($rol_usuario ?? '')) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($rd['NOMBRE']); ?> (<?php echo $rd['NIVEL_JERARQUICO']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php else: ?>
            <span class="small text-muted"><i class="bi bi-shield-lock me-1"></i><?php echo htmlspecialchars($rol_usuario_nombre ?? ''); ?></span>
            <?php endif; ?>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- JS del módulo -->
<script src="assets/js/app.js"></script>
</body>
</html>
