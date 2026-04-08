<?php
/**
 * ============================================================================
 * HELPERS UI - ctm_presupuestos
 * ============================================================================
 * Copiado desde herramientas_mejora/helpers_template.php
 * Funciones reutilizables para generar HTML con Bootstrap 5 y SweetAlert2.
 * ============================================================================
 */

// ═══════════════════════════════════════════════════════════════════════════════
// SWEETALERT2
// ═══════════════════════════════════════════════════════════════════════════════

function generarAlertJS($titulo, $texto, $icono = 'info', $redirect = '') {
    $redireccion = $redirect ? ".then(function() { window.location.replace('" . addslashes($redirect) . "'); })" : '';
    return '<script>
        Swal.fire({ title: ' . json_encode($titulo) . ', text: ' . json_encode($texto) . ', icon: ' . json_encode($icono) . ',
            confirmButtonText: "Continuar", confirmButtonColor: "#418CBA" })' . $redireccion . ';
    </script>';
}

function generarConfirmJS($texto, $urlSi, $urlNo = 'index.php?estado=1',
                          $tituloExito = 'Buen Trabajo!', $textoExito = 'El cambio se ha realizado!',
                          $tituloCancela = 'Operación interrumpida!', $textoCancela = 'Puede reanudar el proceso desde SEGUIMIENTO!') {
    return '<script>
        Swal.fire({ title: ' . json_encode($texto) . ', icon: "question", showCancelButton: true,
            confirmButtonText: "SI", cancelButtonText: "NO", confirmButtonColor: "#418CBA",
            cancelButtonColor: "#dc3545", reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                Swal.fire({ title: ' . json_encode($tituloExito) . ', text: ' . json_encode($textoExito) . ',
                    icon: "success", confirmButtonText: "Continuar", confirmButtonColor: "#418CBA"
                }).then(function() { window.location.replace(' . json_encode($urlSi) . '); });
            } else {
                Swal.fire({ title: ' . json_encode($tituloCancela) . ', text: ' . json_encode($textoCancela) . ',
                    icon: "error", confirmButtonText: "Continuar", confirmButtonColor: "#418CBA"
                }).then(function() { window.location.replace(' . json_encode($urlNo) . '); });
            }
        });
    </script>';
}

function generarAccesoDenegadoJS() {
    return generarAlertJS('No permitido!', 'No posee los privilegios suficientes!', 'error', 'index.php?estado=1');
}

// ═══════════════════════════════════════════════════════════════════════════════
// COMPONENTES BOOTSTRAP 5
// ═══════════════════════════════════════════════════════════════════════════════

function generarIconoEstado($estadoId) {
    if ($estadoId == '1' || $estadoId == '21') {
        return '<span class="text-success"><i class="bi bi-check-circle-fill"></i></span>';
    }
    return '<span class="text-danger"><i class="bi bi-x-circle-fill"></i></span>';
}

function generarTablaHeader($columnas) {
    $html = '<thead class="table-primary"><tr>';
    foreach ($columnas as $col) {
        $html .= '<th class="text-center">' . htmlspecialchars($col) . '</th>';
    }
    return $html . '</tr></thead>';
}

function generarBotonForm($texto, $action, $hiddenFields = [], $clase = 'btn btn-primary btn-sm') {
    $html = '<form method="post" action="' . htmlspecialchars($action) . '" class="d-inline">';
    foreach ($hiddenFields as $name => $value) {
        $html .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '">';
    }
    $html .= '<button type="submit" class="' . $clase . '">' . $texto . '</button></form>';
    return $html;
}

function generarCampoBusqueda($valor = '', $placeholder = 'Buscar...') {
    return '<form method="post" action="" class="mb-3">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" name="buscar" value="' . htmlspecialchars($valor) . '"
                   placeholder="' . htmlspecialchars($placeholder) . '" onchange="this.form.submit();">
        </div>
    </form>';
}

function generarTituloSeccion($titulo, $icono = '') {
    $iconoHtml = $icono ? '<i class="' . $icono . ' me-2"></i>' : '';
    return '<div class="d-flex align-items-center mb-3"><h4 class="mb-0">' . $iconoHtml . htmlspecialchars($titulo) . '</h4></div>';
}

function generarCard($titulo, $contenido, $claseHeader = 'bg-primary text-white') {
    return '<div class="card shadow-sm mb-3"><div class="card-header ' . $claseHeader . '">
        <h6 class="mb-0">' . htmlspecialchars($titulo) . '</h6></div>
        <div class="card-body">' . $contenido . '</div></div>';
}

function generarWizardPasos($pasoActual, $totalPasos = 4, $etiquetas = []) {
    $defaultEtiquetas = ['Paso 1', 'Paso 2', 'Paso 3', 'Paso 4'];
    $etiquetas = !empty($etiquetas) ? $etiquetas : $defaultEtiquetas;
    $html = '<div class="d-flex justify-content-center mb-4">';
    for ($i = 1; $i <= $totalPasos; $i++) {
        $clase = ($i <= $pasoActual) ? 'bg-success text-white' : 'bg-light text-muted border';
        $html .= '<div class="d-flex align-items-center">';
        $html .= '<div class="rounded-circle d-flex align-items-center justify-content-center ' . $clase . '" style="width:36px;height:36px;font-weight:bold;">' . $i . '</div>';
        $html .= '<span class="ms-1 me-3 small fw-semibold">' . htmlspecialchars($etiquetas[$i - 1] ?? '') . '</span>';
        if ($i < $totalPasos) {
            $lineColor = ($i < $pasoActual) ? 'bg-success' : 'bg-secondary';
            $html .= '<div class="' . $lineColor . ' me-3" style="width:40px;height:2px;"></div>';
        }
        $html .= '</div>';
    }
    return $html . '</div>';
}

function claseFilaAlternada($n) {
    return ($n % 2 == 0) ? 'table-light' : '';
}
