/**
 * ============================================================================
 * JAVASCRIPT - ctm_morebius
 * ============================================================================
 * Basado en herramientas_mejora/app_template.js
 * ============================================================================
 */

'use strict';

const MORE = {

    init: function() {
        var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(function(el) { new bootstrap.Tooltip(el); });
    },

    // ── Alertas SweetAlert2 ─────────────────────────────────────────────────
    alert: function(titulo, texto, icono, redirect) {
        Swal.fire({
            title: titulo, text: texto, icon: icono || 'info',
            confirmButtonText: 'Continuar', confirmButtonColor: '#418CBA'
        }).then(function() { if (redirect) window.location.replace(redirect); });
    },

    confirm: function(pregunta, urlSi, urlNo, opciones) {
        opciones = opciones || {};
        urlNo = urlNo || 'index.php?estado=1';
        Swal.fire({
            title: pregunta, icon: 'question', showCancelButton: true,
            confirmButtonText: opciones.textoSi || 'SI',
            cancelButtonText: opciones.textoNo || 'NO',
            confirmButtonColor: '#418CBA', cancelButtonColor: '#dc3545', reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                Swal.fire({ title: opciones.tituloExito || 'Buen Trabajo!',
                    text: opciones.textoExito || 'El cambio se ha realizado!',
                    icon: 'success', confirmButtonText: 'Continuar', confirmButtonColor: '#418CBA'
                }).then(function() { window.location.replace(urlSi); });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire({ title: opciones.tituloCancela || 'Operación interrumpida!',
                    text: opciones.textoCancela || 'Puede reanudar el proceso!',
                    icon: 'error', confirmButtonText: 'Continuar', confirmButtonColor: '#418CBA'
                }).then(function() { window.location.replace(urlNo); });
            }
        });
    },

    toast: function(mensaje, icono) {
        Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true })
            .fire({ icon: icono || 'info', title: mensaje });
    },

    // ── AJAX ────────────────────────────────────────────────────────────────
    ajax: function(url, datos, callback) {
        var body = datos ? Object.keys(datos).map(function(k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(datos[k]);
        }).join('&') : '';
        fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
            .then(function(r) { return r.text(); })
            .then(function(html) { if (typeof callback === 'function') callback(html); });
    },

    // ── Utilidades ──────────────────────────────────────────────────────────
    openPopup: function(url, nombre, ancho, alto) {
        ancho = ancho || 800; alto = alto || 600;
        var left = (screen.width - ancho) / 2, top = (screen.height - alto) / 2;
        window.open(url, nombre, 'width=' + ancho + ',height=' + alto + ',top=' + top + ',left=' + left +
            ',toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes');
    },

    submitForm: function(formId) { var f = document.getElementById(formId); if (f) f.submit(); },

    validateForm: function(form) {
        var inputs = form.querySelectorAll('[required]'), valid = true;
        inputs.forEach(function(input) {
            if (!input.value || input.value === 'null') { input.classList.add('is-invalid'); valid = false; }
            else { input.classList.remove('is-invalid'); input.classList.add('is-valid'); }
        });
        if (!valid) MORE.alert('Campos incompletos', 'Todos los campos obligatorios deben estar completos.', 'warning');
        return valid;
    }
};

// Compatibilidad legacy
function EnviarMsg(v1, v2, v3, v4) { MORE.ajax('web/int/ajax/envio_msg.php', {valorCaja1:v1, valorCaja2:v2, valorCaja3:v3, valorCaja4:v4}); }
function enviarEnt(form) { if (form) form.submit(); }

document.addEventListener('DOMContentLoaded', function() { MORE.init(); });
