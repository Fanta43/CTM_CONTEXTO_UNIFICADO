<!-- Footer sticky -->
<footer class="footer-pade">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="small">&copy; <?php echo date('Y'); ?> - <?php echo APP_TITULO; ?> v<?php echo APP_VERSION; ?></span>
        <?php echo herramienta_pie($usu ?? '', $nivel ?? 1); ?>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- JS del módulo -->
<script src="assets/js/app.js"></script>
</body>
</html>
