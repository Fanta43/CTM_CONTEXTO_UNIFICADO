<?php
/**
 * ============================================================================
 * Notificar SR - Vista previa y envío de correo
 * ============================================================================
 * Recibe SR seleccionadas desde listar.php via POST, muestra vista previa
 * del correo con descripciones prolijadas por Ollama (IA local), y permite
 * enviar el correo a los responsables.
 * ============================================================================
 */

$db  = '1';

// ═══════════════════════════════════════════════════════════════════════════════
// FUNCIONES AUXILIARES
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Clase CSS de badge según estado K2B
 */
function notificar_estado_badge_class($estado) {
    $estado = strtoupper(trim((string)$estado));
    switch ($estado) {
        case 'FINALIZADA':
        case 'RECHAZADA':
            return 'bg-secondary';
        case 'CUMPLIDA':
        case 'CUMPLIMIENTOSTOCK':
            return 'bg-success';
        case 'PENDIENTE':
        case 'ENPROCESOAUTORIZACION':
            return 'bg-warning text-dark';
        default:
            return 'bg-primary';
    }
}

/**
 * Formatear descripción de SR con Ollama (IA local)
 * Usa ollama_chat() para convertir la lista de productos en un párrafo prolijo.
 * Cache en memoria estática para evitar llamadas repetidas.
 */
function notificar_formatear_descripcion_ollama($texto, $model = 'phi3:14b', $host = 'http://localhost:11434') {
    static $cache = [];

    $texto = (string)$texto;
    $key = md5($model . '|' . $host . '|' . $texto);
    if (isset($cache[$key])) return $cache[$key];

    $texto_trim = trim($texto);
    if ($texto_trim === '') {
        $cache[$key] = '';
        return '';
    }

    $system_prompt = <<<'PROMPT'
Convierte esta lista de productos comprados en un informe conciso para gerencia. Genera UN SOLO párrafo en español con formato de albarán interno.

REGLAS ESTRICTAS:
1. Tono: OBJETIVO y ADMINISTRATIVO - como una nota interna de compras
2. Estilo: Directo, informativo, sin adornos
3. Formato: Texto corrido (un solo párrafo) - SIN viñetas, SIN saltos de línea, SIN títulos

INSTRUCCIONES:
- Menciona cada producto con: marca, modelo, código y cantidad
- Agrupa productos similares cuando tenga sentido
- Usa verbos neutrales: "se adquirió", "se compró", "incluye", "contiene"
- Si hay fechas relevantes, inclúyelas

FRASES PROHIBIDAS (NO USAR):
- "ofrecemos", "destacamos", "excelente", "calidad", "premium"
- "recomendamos", "ideal para", "perfecto"
- Cualquier lenguaje promocional o de ventas

FRASES PERMITIDAS (ejemplos):
- "La compra incluye..."
- "Se adquirieron..."
- "El pedido contiene..."
- "Se solicitaron..."

LISTA DE PRODUCTOS A PROCESAR:
[INSERTAR LISTA AQUÍ]

Recuerda: La gerencia solo necesita entender QUÉ se compró, no por qué es bueno o para qué sirve.
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user',   'content' => $texto_trim],
    ];

    if (!function_exists('ollama_chat')) {
        $cache[$key] = $texto_trim;
        return $texto_trim;
    }

    $r = ollama_chat($messages, $model, $host);
    if (isset($r['estado']) && (int)$r['estado'] === 1 && !empty($r['respuesta'])) {
        $resp = trim((string)$r['respuesta']);
        if ($resp !== '') {
            $cache[$key] = $resp;
            return $resp;
        }
    }

    $cache[$key] = $texto_trim;
    return $texto_trim;
}

/**
 * Obtener detalle de las SR seleccionadas
 */
function notificar_obtener_detalle_sr(array $sr_codigos, $db = '1', $usu = '9999') {
    if (empty($sr_codigos)) return [];

    $sr_codigos_limpios = [];
    foreach ($sr_codigos as $codigo) {
        $codigo_limpio = preg_replace('/[^0-9]/', '', (string)$codigo);
        if ($codigo_limpio !== '') $sr_codigos_limpios[] = $codigo_limpio;
    }
    if (empty($sr_codigos_limpios)) return [];

    $sr_lista = "'" . implode("','", $sr_codigos_limpios) . "'";

    sql_do_query("SET SESSION group_concat_max_len = 100000;", $db, $usu);

    $sql = "SELECT
                codigo,
                MAX(fecha) AS fecha,
                MIN(fecha_entrega_sol) AS fecha_entrega_sol,
                MAX(usuario) AS usuario,
                MAX(estado) AS estado,
                GROUP_CONCAT(DISTINCT descripcion ORDER BY descripcion SEPARATOR '\\n') AS descripcion
            FROM pres_k2b_sr_logs
            WHERE codigo IN ($sr_lista)
            GROUP BY codigo
            ORDER BY MIN(fecha_entrega_sol), codigo";

    $result = sql_do_query($sql, $db, $usu);
    $detalle = [];
    while ($row = sql_do_array($result)) {
        if ($row) {
            $row_upper = [];
            foreach ($row as $k => $v) {
                $row_upper[strtoupper((string)$k)] = $v;
            }
            $detalle[] = $row_upper;
        }
    }
    return $detalle;
}

/**
 * Construir HTML de tabla para vista previa y cuerpo de email
 */
function notificar_construir_html_sr(array $detalle, $usar_ollama = true) {
    if (empty($detalle)) {
        return '<p>No se encontraron datos para las SR seleccionadas.</p>';
    }

    $wrap_style = 'font-family: Arial, Helvetica, sans-serif; color: #111827;';
    $table_style = 'width: 100%; border-collapse: collapse; border-spacing: 0;';
    $th_style = 'background: #f3f4f6; color: #111827; text-align: left; font-weight: 700; font-size: 12px; border: 1px solid #d1d5db; padding: 8px; vertical-align: top;';
    $td_style = 'font-size: 12px; border: 1px solid #d1d5db; padding: 8px; vertical-align: top;';
    $nowrap_style = 'white-space: nowrap;';
    $desc_style = 'text-align: justify; white-space: normal; word-break: break-word; line-height: 1.35;';
    $badge_base_style = 'display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; color: #ffffff;';

    ob_start();
    ?>
    <div style="<?php echo $wrap_style; ?>">
    <div style="width: 100%; overflow-x: auto;">
    <table style="<?php echo $table_style; ?>">
        <thead>
            <tr>
                <th style="<?php echo $th_style . ' ' . $nowrap_style; ?>">SR</th>
                <th style="<?php echo $th_style . ' ' . $nowrap_style; ?>">Estado</th>
                <th style="<?php echo $th_style . ' ' . $nowrap_style; ?>">Usuario</th>
                <th style="<?php echo $th_style . ' ' . $nowrap_style; ?>">Fecha</th>
                <th style="<?php echo $th_style . ' ' . $nowrap_style; ?>">Entrega prevista</th>
                <th style="<?php echo $th_style; ?>">Descripción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($detalle as $row): ?>
            <?php
                $badge_bootstrap = notificar_estado_badge_class($row['ESTADO']);
                $badge_style = $badge_base_style . ' background: #2563eb;';
                if (strpos($badge_bootstrap, 'secondary') !== false) $badge_style = $badge_base_style . ' background: #6b7280;';
                if (strpos($badge_bootstrap, 'success') !== false) $badge_style = $badge_base_style . ' background: #16a34a;';
                if (strpos($badge_bootstrap, 'warning') !== false) $badge_style = $badge_base_style . ' background: #f59e0b; color: #111827;';

                $desc = $row['DESCRIPCION'] ?? '';
                if ($usar_ollama) {
                    $desc = notificar_formatear_descripcion_ollama($desc);
                }
            ?>
            <tr>
                <td style="<?php echo $td_style . ' ' . $nowrap_style; ?>"><strong><?php echo htmlspecialchars($row['CODIGO']); ?></strong></td>
                <td style="<?php echo $td_style . ' ' . $nowrap_style; ?>"><span style="<?php echo $badge_style; ?>"><?php echo htmlspecialchars($row['ESTADO']); ?></span></td>
                <td style="<?php echo $td_style . ' ' . $nowrap_style; ?>"><?php echo htmlspecialchars($row['USUARIO']); ?></td>
                <td style="<?php echo $td_style . ' ' . $nowrap_style; ?>"><?php echo htmlspecialchars($row['FECHA']); ?></td>
                <td style="<?php echo $td_style . ' ' . $nowrap_style; ?>"><?php echo htmlspecialchars($row['FECHA_ENTREGA_SOL']); ?></td>
                <td style="<?php echo $td_style . ' ' . $desc_style; ?>"><?php echo nl2br(htmlspecialchars($desc)); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Construir cuerpo completo del email
 */
function notificar_construir_mensaje_email(array $detalle, $usar_ollama = true) {
    $intro =
        "Estimados responsables de gestión de Solicitudes de Recursos (SR):<br /><br />" .
        "Se solicita conocer el estado de situación de las SR que se listan a continuación. " .
        "Agradecemos revisar su estado y actualizar la información correspondiente.<br /><br />";

    $tabla = notificar_construir_html_sr($detalle, $usar_ollama);

    $cierre =
        "<br />Si alguna de estas solicitudes ya fue gestionada y no se refleja en el sistema, " .
        "por favor sepa disculpar la consulta y en caso de necesitar asistencia, por favor comunicarse conmigo.<br /><br />" .
        "Atentamente,<br /><br />" .
        "Sistema de Presupuestos";

    return str_replace("'", '´', $intro . $tabla . $cierre);
}


// ═══════════════════════════════════════════════════════════════════════════════
// LÓGICA DE PÁGINA
// ═══════════════════════════════════════════════════════════════════════════════

$sr_codigos = isset($_POST['sr_codigos']) && is_array($_POST['sr_codigos'])
    ? array_filter($_POST['sr_codigos'])
    : [];

if (empty($sr_codigos)) {
    echo '<div class="alert alert-warning m-3"><i class="bi bi-exclamation-triangle me-2"></i>No se seleccionó ninguna SR. Vuelva al listado de Solicitudes y seleccione al menos una.</div>';
    return;
}

// Opción: usar Ollama o no
$usar_ollama = isset($_POST['usar_ollama']) ? (bool)$_POST['usar_ollama'] : true;

$detalle = notificar_obtener_detalle_sr($sr_codigos, $db, $usu);

// Construir HTML para vista previa
$tabla_html = notificar_construir_html_sr($detalle, $usar_ollama);

// Procesar envío de email
$resultado_envio = null;
$error_envio = '';
if (isset($_POST['enviar_email']) && $_POST['enviar_email'] === '1') {
    $destino_email = trim($_POST['destino_email'] ?? '');
    $cc_email = trim($_POST['cc_email'] ?? '');
    $asunto = trim($_POST['asunto'] ?? 'Seguimiento de Solicitudes de Recursos (SR)');

    $mensaje_email = notificar_construir_mensaje_email($detalle, $usar_ollama);
    $ok = presupuestos_enviar_email_sr($destino_email, [
        'from'    => 'presupuestos@saltogrande.org',
        'subject' => $asunto,
        'cc'      => $cc_email,
    ], $mensaje_email, $error_envio);
    $resultado_envio = $ok ? 1 : 0;
}
?>

<?php echo generarTituloSeccion('Notificar SR seleccionadas', 'bi bi-envelope-paper'); ?>

<?php if ($resultado_envio === 1): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Correo enviado correctamente.</div>
<?php elseif ($resultado_envio === 0): ?>
    <div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i>No se pudo enviar el correo. <?php echo htmlspecialchars($error_envio); ?></div>
<?php endif; ?>

<!-- Info -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-primary border-4">
            <div class="card-body py-2">
                <div class="text-muted small">SR seleccionadas</div>
                <div class="fs-4 fw-bold text-primary"><?php echo count($detalle); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-info border-4">
            <div class="card-body py-2">
                <div class="text-muted small">Ollama (IA)</div>
                <div class="fs-5 fw-bold <?php echo $usar_ollama ? 'text-success' : 'text-muted'; ?>">
                    <i class="bi <?php echo $usar_ollama ? 'bi-robot' : 'bi-robot'; ?> me-1"></i>
                    <?php echo $usar_ollama ? 'Activado' : 'Desactivado'; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-warning border-4">
            <div class="card-body py-2">
                <div class="text-muted small">Códigos</div>
                <div class="small fw-semibold"><?php echo htmlspecialchars(implode(', ', $sr_codigos)); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Formulario de envío -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white py-2">
        <h6 class="mb-0"><i class="bi bi-send me-2"></i>Configuración del envío</h6>
    </div>
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>&accion=notificar" id="frmEnvio">
            <?php foreach ($sr_codigos as $sr): ?>
                <input type="hidden" name="sr_codigos[]" value="<?php echo htmlspecialchars($sr); ?>" />
            <?php endforeach; ?>
            <input type="hidden" name="usar_ollama" value="<?php echo $usar_ollama ? '1' : '0'; ?>" />

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Destinatario (email)</label>
                    <input type="email" class="form-control form-control-sm" name="destino_email"
                           value="<?php echo htmlspecialchars($_POST['destino_email'] ?? ''); ?>"
                           placeholder="correo@ejemplo.com">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">CC (opcional)</label>
                    <input type="email" class="form-control form-control-sm" name="cc_email"
                           value="<?php echo htmlspecialchars($_POST['cc_email'] ?? ''); ?>"
                           placeholder="copia@ejemplo.com">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Asunto</label>
                    <input type="text" class="form-control form-control-sm" name="asunto"
                           value="<?php echo htmlspecialchars($_POST['asunto'] ?? 'Seguimiento de Solicitudes de Recursos (SR)'); ?>">
                </div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <button type="submit" name="enviar_email" value="1" class="btn btn-pade">
                    <i class="bi bi-envelope-check me-1"></i>Enviar correo
                </button>

                <div class="form-check form-switch ms-3">
                    <input class="form-check-input" type="checkbox" id="chkOllama" name="usar_ollama_toggle"
                           <?php echo $usar_ollama ? 'checked' : ''; ?>
                           onchange="document.getElementById('hidUO').value = this.checked ? '1' : '0'; document.getElementById('frmRegenerar').submit();">
                    <label class="form-check-label small" for="chkOllama">Prolijar descripciones con IA</label>
                </div>
            </div>
        </form>

        <!-- Form oculto para regenerar vista previa con/sin Ollama -->
        <form method="post" action="index.php?estado=<?php echo $estado; ?>&accion=notificar" id="frmRegenerar" class="d-none">
            <?php foreach ($sr_codigos as $sr): ?>
                <input type="hidden" name="sr_codigos[]" value="<?php echo htmlspecialchars($sr); ?>" />
            <?php endforeach; ?>
            <input type="hidden" name="usar_ollama" id="hidUO" value="<?php echo $usar_ollama ? '1' : '0'; ?>" />
        </form>
    </div>
</div>

<!-- Vista previa del correo -->
<div class="card shadow-sm">
    <div class="card-header bg-info text-dark py-2">
        <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Vista previa del correo</h6>
    </div>
    <div class="card-body p-0">
        <?php echo $tabla_html; ?>
    </div>
</div>
