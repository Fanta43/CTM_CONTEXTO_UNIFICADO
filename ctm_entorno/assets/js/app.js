/**
 * ============================================================================
 * APP.JS - Sistema de Tres Vías
 * ============================================================================
 */

var ENTO = ENTO || {};

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(el) { new bootstrap.Tooltip(el); });
});

// Abrir popup
ENTO.openPopup = function(url, name, w, h) {
    var left = (screen.width - w) / 2;
    var top = (screen.height - h) / 2;
    window.open(url, name, 'width=' + w + ',height=' + h + ',left=' + left + ',top=' + top + ',scrollbars=yes,resizable=yes');
};
