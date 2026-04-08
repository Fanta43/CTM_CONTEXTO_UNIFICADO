<div id="pres">

<?php
// ── Datos cross-module para contexto presupuestario ─────────────────────────
$integ_resumen = integ_dashboard_resumen('1', $usu);
$integ_alertas = integ_dashboard_alertas('1', $usu);
?>

<!-- ═══ SECCION CROSS-MODULE: contexto de inventario ═══ -->
<h6 class="text-muted mb-3"><i class="bi bi-link-45deg me-2"></i>Contexto de Inventario IT</h6>
<?php echo integ_render_cards_resumen($integ_resumen); ?>
<?php echo integ_render_alertas($integ_alertas); ?>

<!-- TÍTULO -->
<div class="text-center mb-4">
    <h3 class="fw-bold" style="color: var(--pade-primary, #418CBA);">
        <i class="bi bi-arrow-repeat me-2"></i>Ciclo Presupuestario
    </h3>
    <p class="text-muted">Gesti&oacute;n continua donde cada per&iacute;odo se alimenta de los resultados del anterior</p>
</div>

<!-- DIAGRAMA CIRCULAR SVG -->
<div class="d-flex justify-content-center mb-5">
    <svg viewBox="0 0 600 600" width="520" height="520" xmlns="http://www.w3.org/2000/svg" style="max-width:100%;">
        <defs>
            <marker id="arrowhead" markerWidth="8" markerHeight="6" refX="8" refY="3" orient="auto">
                <polygon points="0 0, 8 3, 0 6" fill="#6c757d"/>
            </marker>
            <filter id="shadow" x="-10%" y="-10%" width="130%" height="130%">
                <feDropShadow dx="2" dy="2" stdDeviation="4" flood-opacity="0.15"/>
            </filter>
        </defs>

        <!-- Flechas circulares -->
        <path d="M 300 80 A 220 220 0 0 1 520 300" fill="none" stroke="#dee2e6" stroke-width="3" stroke-dasharray="8,4" marker-end="url(#arrowhead)"/>
        <path d="M 520 300 A 220 220 0 0 1 300 520" fill="none" stroke="#dee2e6" stroke-width="3" stroke-dasharray="8,4" marker-end="url(#arrowhead)"/>
        <path d="M 300 520 A 220 220 0 0 1 80 300" fill="none" stroke="#dee2e6" stroke-width="3" stroke-dasharray="8,4" marker-end="url(#arrowhead)"/>
        <path d="M 80 300 A 220 220 0 0 1 300 80" fill="none" stroke="#dee2e6" stroke-width="3" stroke-dasharray="8,4" marker-end="url(#arrowhead)"/>

        <!-- Centro -->
        <circle cx="300" cy="300" r="72" fill="#418CBA" opacity="0.1" stroke="#418CBA" stroke-width="2"/>
        <text x="300" y="290" text-anchor="middle" font-size="13" font-weight="bold" fill="#418CBA">Ciclo</text>
        <text x="300" y="310" text-anchor="middle" font-size="13" font-weight="bold" fill="#418CBA">Presupuestario</text>

        <!-- FASE 1 - Arriba -->
        <g filter="url(#shadow)" style="cursor:pointer;" onclick="document.getElementById('fase1').scrollIntoView({behavior:'smooth'})">
            <rect x="210" y="20" width="180" height="80" rx="14" fill="#0d6efd"/>
            <text x="300" y="50" text-anchor="middle" font-size="11" fill="white" font-weight="600">FASE 1</text>
            <text x="300" y="68" text-anchor="middle" font-size="13" fill="white" font-weight="bold">Borrador</text>
            <text x="300" y="84" text-anchor="middle" font-size="10" fill="rgba(255,255,255,0.8)">Proyecci&#243;n autom&#225;tica</text>
        </g>

        <!-- FASE 2 - Derecha -->
        <g filter="url(#shadow)" style="cursor:pointer;" onclick="document.getElementById('fase2').scrollIntoView({behavior:'smooth'})">
            <rect x="430" y="260" width="160" height="80" rx="14" fill="#198754"/>
            <text x="510" y="290" text-anchor="middle" font-size="11" fill="white" font-weight="600">FASE 2</text>
            <text x="510" y="308" text-anchor="middle" font-size="13" fill="white" font-weight="bold">Revisi&#243;n</text>
            <text x="510" y="324" text-anchor="middle" font-size="10" fill="rgba(255,255,255,0.8)">Aprobaci&#243;n jer&#225;rquica</text>
        </g>

        <!-- FASE 3 - Abajo -->
        <g filter="url(#shadow)" style="cursor:pointer;" onclick="document.getElementById('fase3').scrollIntoView({behavior:'smooth'})">
            <rect x="210" y="500" width="180" height="80" rx="14" fill="#fd7e14"/>
            <text x="300" y="530" text-anchor="middle" font-size="11" fill="white" font-weight="600">FASE 3</text>
            <text x="300" y="548" text-anchor="middle" font-size="13" fill="white" font-weight="bold">Ejecuci&#243;n</text>
            <text x="300" y="564" text-anchor="middle" font-size="10" fill="rgba(255,255,255,0.8)">Solicitudes y consumo</text>
        </g>

        <!-- FASE 4 - Izquierda -->
        <g filter="url(#shadow)" style="cursor:pointer;" onclick="document.getElementById('fase4').scrollIntoView({behavior:'smooth'})">
            <rect x="10" y="260" width="160" height="80" rx="14" fill="#dc3545"/>
            <text x="90" y="290" text-anchor="middle" font-size="11" fill="white" font-weight="600">FASE 4</text>
            <text x="90" y="308" text-anchor="middle" font-size="13" fill="white" font-weight="bold">Cierre</text>
            <text x="90" y="324" text-anchor="middle" font-size="10" fill="rgba(255,255,255,0.8)">Retroalimentaci&#243;n</text>
        </g>

        <!-- Etiquetas de transici&#243;n -->
        <text x="440" y="155" text-anchor="middle" font-size="10" fill="#6c757d" transform="rotate(40, 440, 155)">Env&#237;o a revisi&#243;n</text>
        <text x="460" y="460" text-anchor="middle" font-size="10" fill="#6c757d" transform="rotate(-40, 460, 460)">Presupuesto aprobado</text>
        <text x="155" y="460" text-anchor="middle" font-size="10" fill="#6c757d" transform="rotate(40, 155, 460)">Fin del ejercicio</text>
        <text x="135" y="155" text-anchor="middle" font-size="10" fill="#6c757d" transform="rotate(-40, 135, 155)">Datos ejecutados</text>

        <!-- A&#241;o N / N+1 -->
        <text x="300" y="170" text-anchor="middle" font-size="10" fill="#418CBA" font-weight="bold">A&#241;o N+1</text>
        <text x="300" y="440" text-anchor="middle" font-size="10" fill="#418CBA" font-weight="bold">A&#241;o N</text>
    </svg>
</div>

<!-- L&#205;NEA DE FLUJO HORIZONTAL -->
<div class="card shadow-sm mb-5">
    <div class="card-body py-3">
        <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 small">
            <span class="badge bg-secondary">Resultados A&#241;o N</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-primary">Borrador N+1</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-success">Revisi&#243;n</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-success">Aprobaci&#243;n</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-warning text-dark">Ejecuci&#243;n N+1</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-danger">Cierre N+1</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-primary">Borrador N+2</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="text-muted">...</span>
        </div>
    </div>
</div>

<!-- DETALLE DE FASES -->
<div class="row g-4">

    <!-- FASE 1 -->
    <div class="col-md-6" id="fase1">
        <div class="card shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-1-circle me-2"></i>Fase 1 &#8212; Generaci&#243;n del Borrador</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Al cierre del ejercicio anterior, el sistema proyecta autom&#225;ticamente el presupuesto del pr&#243;ximo per&#237;odo.</p>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><i class="bi bi-check2 text-primary me-2"></i>Recopila montos <strong>ejecutados reales</strong> por &#237;tem y categor&#237;a del a&#241;o anterior.</li>
                    <li class="mb-2"><i class="bi bi-check2 text-primary me-2"></i>Aplica <strong>factores de ajuste</strong> configurables: inflaci&#243;n, crecimiento, recortes.</li>
                    <li class="mb-2"><i class="bi bi-check2 text-primary me-2"></i>Genera borrador respetando la jerarqu&#237;a <strong>Categor&#237;a &#8594; &#205;tem</strong>.</li>
                    <li><i class="bi bi-check2 text-primary me-2"></i>Cada l&#237;nea conserva <strong>trazabilidad</strong> hacia la partida de origen.</li>
                </ul>
                <div class="mt-3 text-end">
                    <span class="badge bg-primary bg-opacity-10 text-primary">Estado: Borrador</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FASE 2 -->
    <div class="col-md-6" id="fase2">
        <div class="card shadow-sm h-100 border-start border-4 border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-2-circle me-2"></i>Fase 2 &#8212; Revisi&#243;n y Aprobaci&#243;n</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">El borrador sube por la cadena jer&#225;rquica. Cada nivel puede modificar montos, observar o devolver.</p>
                <div class="small mb-0">
                    <div class="d-flex align-items-start mb-2">
                        <span class="badge bg-info text-dark me-2 mt-1">N1</span>
                        <div><strong>Jefe de Unidad:</strong> revisa &#237;tems, ajusta montos, justifica.</div>
                    </div>
                    <div class="d-flex align-items-start mb-2">
                        <span class="badge bg-warning text-dark me-2 mt-1">N2</span>
                        <div><strong>Gerencia:</strong> consolida unidades, redistribuye, valida coherencia.</div>
                    </div>
                    <div class="d-flex align-items-start mb-2">
                        <span class="badge bg-danger me-2 mt-1">N3</span>
                        <div><strong>Direcci&#243;n:</strong> aprueba el presupuesto final o devuelve con instrucciones.</div>
                    </div>
                </div>
                <div class="mt-2 text-end">
                    <span class="badge bg-success bg-opacity-10 text-success">Estado: En revisi&#243;n &#8594; Aprobado</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FASE 3 -->
    <div class="col-md-6" id="fase3">
        <div class="card shadow-sm h-100 border-start border-4" style="border-color: #fd7e14 !important;">
            <div class="card-header text-white" style="background-color: #fd7e14;">
                <h6 class="mb-0"><i class="bi bi-3-circle me-2"></i>Fase 3 &#8212; Ejecuci&#243;n</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Con el presupuesto aprobado, las unidades generan solicitudes que consumen &#237;tems del presupuesto vigente.</p>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><i class="bi bi-cart-check text-warning me-2"></i>Cada solicitud referencia &#237;tems y <strong>descuenta el monto</strong> correspondiente.</li>
                    <li class="mb-2"><i class="bi bi-arrow-left-right text-warning me-2"></i>Flujo: cotizaci&#243;n &#8594; OC &#8594; recepci&#243;n &#8594; pago.</li>
                    <li class="mb-2"><i class="bi bi-speedometer2 text-warning me-2"></i><strong>Saldo en tiempo real:</strong> aprobado &#8722; comprometido &#8722; ejecutado.</li>
                    <li><i class="bi bi-bell text-warning me-2"></i>Alertas autom&#225;ticas al superar <strong>umbrales configurables</strong>.</li>
                </ul>
                <div class="mt-3 text-end">
                    <span class="badge bg-warning bg-opacity-10 text-dark">Estado: Operativo</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FASE 4 -->
    <div class="col-md-6" id="fase4">
        <div class="card shadow-sm h-100 border-start border-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-4-circle me-2"></i>Fase 4 &#8212; Cierre y Retroalimentaci&#243;n</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Al finalizar el ejercicio, los montos ejecutados alimentan la Fase 1 del siguiente per&#237;odo.</p>
                <div class="small">
                    <div class="d-flex align-items-start mb-2">
                        <span class="badge bg-secondary me-2 mt-1">1</span>
                        <div><strong>Iniciar cierre:</strong> bloquea nuevas solicitudes.</div>
                    </div>
                    <div class="d-flex align-items-start mb-2">
                        <span class="badge bg-secondary me-2 mt-1">2</span>
                        <div><strong>Validar:</strong> resuelve solicitudes pendientes.</div>
                    </div>
                    <div class="d-flex align-items-start mb-2">
                        <span class="badge bg-secondary me-2 mt-1">3</span>
                        <div><strong>Consolidar:</strong> recalcula montos ejecutados.</div>
                    </div>
                    <div class="d-flex align-items-start">
                        <span class="badge bg-secondary me-2 mt-1">4</span>
                        <div><strong>Cerrar:</strong> per&#237;odo sellado &#8594; insumo para nuevo borrador.</div>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <span class="badge bg-danger bg-opacity-10 text-danger">Estado: Cerrado</span>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- PIE CON M&#211;DULOS -->
<div class="card shadow-sm mt-5 mb-3">
    <div class="card-header" style="background-color: var(--pade-primary, #418CBA); color: white;">
        <h6 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>M&#243;dulos del Sistema</h6>
    </div>
    <div class="card-body">
        <div class="row g-3 text-center">
            <div class="col-4 col-md-2">
                <div class="p-2 rounded bg-light">
                    <i class="bi bi-gear fs-4 text-primary d-block mb-1"></i>
                    <small class="fw-semibold">Configuraci&#243;n</small>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="p-2 rounded bg-light">
                    <i class="bi bi-journal-text fs-4 text-primary d-block mb-1"></i>
                    <small class="fw-semibold">Presupuestos</small>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="p-2 rounded bg-light">
                    <i class="bi bi-clipboard-check fs-4 text-success d-block mb-1"></i>
                    <small class="fw-semibold">Revisi&#243;n</small>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="p-2 rounded bg-light">
                    <i class="bi bi-cart-plus fs-4 text-warning d-block mb-1"></i>
                    <small class="fw-semibold">Solicitudes</small>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="p-2 rounded bg-light">
                    <i class="bi bi-bar-chart-line fs-4 text-info d-block mb-1"></i>
                    <small class="fw-semibold">Reportes</small>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="p-2 rounded bg-light">
                    <i class="bi bi-lock fs-4 text-danger d-block mb-1"></i>
                    <small class="fw-semibold">Cierre</small>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

