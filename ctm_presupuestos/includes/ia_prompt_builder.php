<?php
/**
 * ============================================================================
 * Helper: Construcción de prompts IA para EETT y Justificaciones
 * ============================================================================
 * Centraliza la carga de archivos config/ia, filtrado inteligente de specs
 * de referencia, construcción de system prompt y user message.
 * Usado por matricula_eett_ia_ajax.php y borrador_eett_ia_ajax.php.
 * ============================================================================
 */

/**
 * Directorio de archivos de configuración IA.
 */
function ia_config_dir() {
    static $dir = null;
    if ($dir === null) {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'ia' . DIRECTORY_SEPARATOR;
    }
    return $dir;
}

/**
 * Carga un archivo de config/ia con cache estático por request.
 */
function ia_load_file($filename) {
    static $cache = [];
    if (!isset($cache[$filename])) {
        $path = ia_config_dir() . $filename;
        $cache[$filename] = @file_get_contents($path);
        if ($cache[$filename] === false) $cache[$filename] = '';
    }
    return $cache[$filename];
}

/**
 * Parsea specs_referencia.md en secciones indexadas por título de sección.
 * Retorna array: [ 'Notebook Alta Gama' => 'contenido...', 'Monitor' => '...' ]
 */
function ia_parse_specs_secciones() {
    static $secciones = null;
    if ($secciones !== null) return $secciones;

    $contenido = ia_load_file('specs_referencia.md');
    $secciones = [];
    if (empty($contenido)) return $secciones;

    $lineas = explode("\n", $contenido);
    $seccion_actual = null;
    $buffer = '';

    foreach ($lineas as $linea) {
        if (preg_match('/^## (.+)$/', $linea, $m)) {
            if ($seccion_actual !== null && trim($buffer) !== '') {
                $secciones[$seccion_actual] = trim($buffer);
            }
            $seccion_actual = trim($m[1]);
            $buffer = '';
        } elseif ($seccion_actual !== null) {
            $buffer .= $linea . "\n";
        }
    }
    if ($seccion_actual !== null && trim($buffer) !== '') {
        $secciones[$seccion_actual] = trim($buffer);
    }

    return $secciones;
}

/**
 * Filtra las secciones de specs relevantes para una descripción de producto.
 * Usa coincidencia de palabras clave entre la descripción y los títulos de sección.
 * Retorna el texto de las secciones relevantes (máx $max_chars).
 */
function ia_filtrar_specs_relevantes($descripcion, $max_chars = 6000) {
    $secciones = ia_parse_specs_secciones();
    if (empty($secciones) || empty($descripcion)) {
        // Sin descripción, devolver todo truncado
        $todo = ia_load_file('specs_referencia.md');
        return !empty($todo) ? mb_substr($todo, 0, $max_chars) : '';
    }

    $desc_lower = mb_strtolower($descripcion);

    // Mapa de keywords → secciones para matching más inteligente
    $keyword_map = [
        'notebook'      => ['Notebook Alta Gama', 'Notebook Estándar'],
        'laptop'        => ['Notebook Alta Gama', 'Notebook Estándar'],
        'portatil'      => ['Notebook Alta Gama', 'Notebook Estándar'],
        'portátil'      => ['Notebook Alta Gama', 'Notebook Estándar'],
        'alta gama'     => ['Notebook Alta Gama', 'PC Gran Capacidad'],
        'monitor'       => ['Monitor', 'Monitor con Docking', 'Monitor Camioneta', 'Monitor Peaje'],
        'pantalla'      => ['Monitor', 'Monitor con Docking'],
        'impresora'     => ['Impresora Multifunción B&N A3', 'Impresora Multifunción Color', 'Impresora Simple / Láser B&N', 'Impresora de Etiquetas'],
        'laser'         => ['Impresora Simple / Láser B&N', 'Impresora Multifunción B&N A3'],
        'láser'         => ['Impresora Simple / Láser B&N', 'Impresora Multifunción B&N A3'],
        'multifuncion'  => ['Impresora Multifunción B&N A3', 'Impresora Multifunción Color'],
        'multifunción'  => ['Impresora Multifunción B&N A3', 'Impresora Multifunción Color'],
        'pc '           => ['PC Escritorio', 'PC Gran Capacidad'],
        'escritorio'    => ['PC Escritorio', 'PC Gran Capacidad'],
        'desktop'       => ['PC Escritorio', 'PC Gran Capacidad'],
        'computadora'   => ['PC Escritorio', 'PC Gran Capacidad', 'PC All-in-One'],
        'docking'       => ['Docking Station', 'Monitor con Docking'],
        'dock'          => ['Docking Station', 'Monitor con Docking'],
        'auricular'     => ['Auriculares'],
        'headset'       => ['Auriculares'],
        'mouse'         => ['Mouse'],
        'teclado'       => ['Teclado'],
        'kvm'           => ['KVM'],
        'switch'        => ['KVM', 'Switch Red'],
        'videoconferencia' => ['Equipo Videoconferencia'],
        'webcam'        => ['Webcam'],
        'camara'        => ['Cámara PTZ', 'Webcam'],
        'cámara'        => ['Cámara PTZ', 'Webcam'],
        'scanner'       => ['Scanner'],
        'escaner'       => ['Scanner'],
        'escáner'       => ['Scanner'],
        'ups'           => ['UPS'],
        'cargador'      => ['Cargador'],
        'etiqueta'      => ['Impresora de Etiquetas'],
        'encoder'       => ['Encoder/Grabador Video'],
        'grabador'      => ['Encoder/Grabador Video'],
        'bim'           => ['PC BIM'],
        'mini'          => ['PC Mini / Industrial'],
        'industrial'    => ['PC Mini / Industrial'],
        'all-in-one'    => ['PC All-in-One'],
        'allinone'      => ['PC All-in-One'],
        'gran capacidad' => ['PC Gran Capacidad'],
        'tablet'        => ['Tablet'],
        'pendrive'      => ['Pendrive'],
        'usb'           => ['Pendrive'],
        'disco'         => ['Storage'],
        'storage'       => ['Storage'],
        'servidor'      => ['Servidor'],
        'server'        => ['Servidor'],
        'peaje'         => ['Monitor Peaje'],
        'camioneta'     => ['Monitor Camioneta'],
        'vehiculo'      => ['Monitor Camioneta'],
        'vehículo'      => ['Monitor Camioneta'],
        'consola'       => ['Consola Audio/Video'],
        'streaming'     => ['Consola Audio/Video', 'Encoder/Grabador Video'],
        'bateria'       => ['Analizador de Batería'],
        'batería'       => ['Analizador de Batería'],
    ];

    // Buscar secciones que matchean
    $secciones_match = [];
    foreach ($keyword_map as $kw => $sec_nombres) {
        if (mb_strpos($desc_lower, $kw) !== false) {
            foreach ($sec_nombres as $sn) {
                $secciones_match[$sn] = true;
            }
        }
    }

    // También buscar coincidencia directa con títulos de sección
    foreach (array_keys($secciones) as $titulo) {
        $titulo_lower = mb_strtolower($titulo);
        // Buscar si alguna palabra significativa del título está en la descripción
        $palabras_titulo = preg_split('/[\s\/]+/', $titulo_lower);
        foreach ($palabras_titulo as $pt) {
            if (mb_strlen($pt) >= 4 && mb_strpos($desc_lower, $pt) !== false) {
                $secciones_match[$titulo] = true;
                break;
            }
        }
    }

    // Armar texto con secciones relevantes
    $resultado = '';
    $chars = 0;
    foreach ($secciones_match as $titulo => $_) {
        if (isset($secciones[$titulo])) {
            $bloque = "## {$titulo}\n{$secciones[$titulo]}\n\n";
            if ($chars + mb_strlen($bloque) > $max_chars) break;
            $resultado .= $bloque;
            $chars += mb_strlen($bloque);
        }
    }

    // Si no matcheó nada, enviar todo truncado
    if (empty($resultado)) {
        $todo = ia_load_file('specs_referencia.md');
        return !empty($todo) ? mb_substr($todo, 0, $max_chars) : '';
    }

    return trim($resultado);
}

/**
 * Lista de campos EETT esperados en la respuesta JSON.
 */
function ia_campos_esperados() {
    return [
        'eett_denominacion', 'eett_caracteristicas', 'eett_representacion_grafica',
        'eett_normas_fabricacion', 'eett_ensayo_recepcion', 'eett_responsable_recepcion',
        'eett_marcas', 'eett_aplicacion', 'eett_forma_entrega', 'eett_observaciones',
        'eett_proveedores', 'justificacion_compra'
    ];
}

/**
 * Construye el system prompt completo combinando EETT + Justificaciones.
 * @return string
 */
function ia_build_system_prompt() {
    $base = ia_load_file('system_prompt_eett.txt');
    if (empty($base)) {
        $base = 'Sos un especialista en Especificaciones Técnicas (EETT) para el Área Informática y Comunicaciones (AICO).';
    }

    return $base . '

FORMATO DE SALIDA:
Respondé SOLO con un JSON válido, sin texto adicional, sin markdown, sin bloques ```.
Las claves del JSON deben ser EXACTAMENTE las siguientes:
{
  "eett_denominacion": "NOMBRE DEL EQUIPO EN MAYÚSCULAS",
  "eett_caracteristicas": "Listado de specs con formato Característica: valor (mínimo/máximo)",
  "eett_representacion_grafica": "",
  "eett_normas_fabricacion": "Normas IRAM, ISO, IEC aplicables",
  "eett_ensayo_recepcion": "Verificar que los componentes ofertados por el proveedor sean los recibidos y se encuentren en perfecto estado y en la cantidad solicitada",
  "eett_responsable_recepcion": "AREA INFORMÁTICA Y COMUNICACIONES",
  "eett_marcas": "Marcas sugeridas",
  "eett_aplicacion": "Uso o aplicación",
  "eett_forma_entrega": "Forma de entrega",
  "eett_observaciones": "Se deberá especificar la marca, modelo y prestaciones de cada uno de los componentes acompañando de ser posible con folletería ilustrativa. La garantía del equipamiento se deberá especificar en la oferta, sin excepción.",
  "eett_proveedores": "Proveedores del historial",
  "justificacion_compra": "Justificación formal de compra directa"
}

REGLAS PARA EETT:
- Respondé ÚNICAMENTE con el JSON, sin explicaciones.
- Si hay DESCRIPCIÓN ESPECÍFICA DEL RENGLÓN, priorízala sobre las genéricas.
- Para "eett_proveedores", usá proveedores REALES del historial.
- Para "eett_marcas", priorizá marcas institucionales de las specs de referencia.
- Para "eett_caracteristicas", usá formato "Característica: valor (mínimo)." según specs de referencia.

REGLAS PARA "justificacion_compra" (Manual de Compras y Contrataciones CTM):
- Justificación formal para compra directa, tono institucional.
- Citar sección del Manual: 3.6.5.1 (Especialidad/Expertise), 3.6.5.2 (Distribuidor exclusivo), 3.7.1 (Urgencia) o 3.7.2 (Emergencia).
- Mínimo 3 argumentos sólidos.
- Incluir "Riesgo de no adquirir" con consecuencias.
- Si hay proveedores históricos, mencionar antecedentes.
- Estructura: Fundamento normativo → Argumentos → Riesgo → Conclusión.
- Área solicitante: AREA INFORMÁTICA Y COMUNICACIONES (AICO).
- NO inventar SR, OC ni montos; usar datos reales del historial.';
}

/**
 * Construye el user message con contexto filtrado.
 *
 * @param array $params Claves:
 *   - matricula (string)
 *   - item_info (string) "CODIGO - NOMBRE"
 *   - descripcion_renglon (string) descripción específica del renglón
 *   - descripciones (string) lista de descripciones registradas
 *   - proveedores (string)
 *   - cant_sr (int)
 *   - total_usd (float)
 *   - historial_texto (string) texto formateado del historial
 *   - cant_compras (int)
 * @return string
 */
function ia_build_user_message($params) {
    $matricula   = $params['matricula'] ?? '';
    $item_info   = $params['item_info'] ?? '';
    $desc_renglon = $params['descripcion_renglon'] ?? '';
    $descripciones = $params['descripciones'] ?? '';
    $proveedores = $params['proveedores'] ?? 'No disponible';
    $cant_sr     = $params['cant_sr'] ?? 0;
    $total_usd   = $params['total_usd'] ?? 0;
    $historial   = $params['historial_texto'] ?? '';
    $cant_compras = $params['cant_compras'] ?? 0;

    // Descripción para filtrado: priorizar renglón, luego descripciones generales
    $desc_para_filtro = !empty($desc_renglon) ? $desc_renglon : $descripciones;

    // Filtrar specs relevantes al producto
    $specs_ctx = ia_filtrar_specs_relevantes($desc_para_filtro);
    if (!empty($specs_ctx)) {
        $specs_ctx = "\n\nESPECIFICACIONES DE REFERENCIA (AICO):\n" . $specs_ctx;
    }

    // Justificaciones de referencia (siempre se incluyen, son compactas)
    $justif_ref = ia_load_file('justificaciones_referencia.md');
    $justif_ctx = '';
    if (!empty($justif_ref)) {
        $justif_ctx = "\n\nJUSTIFICACIONES DE REFERENCIA (ejemplos reales AICO):\n" . $justif_ref;
    }

    $renglon_txt = '';
    if (!empty($desc_renglon)) {
        $renglon_txt = "\n- **DESCRIPCIÓN ESPECÍFICA DEL RENGLÓN (PRIORIZAR)**: {$desc_renglon}";
    }

    return "PRODUCTO A CLASIFICAR:
- Matrícula: {$matricula}
- Ítem de gasto: {$item_info}{$renglon_txt}
- Descripciones registradas: {$descripciones}
- Proveedores históricos: {$proveedores}
- Cantidad de SR: {$cant_sr}
- Total comprado (USD): " . number_format((float)$total_usd, 2) . "

HISTORIAL DE COMPRAS (últimas {$cant_compras} SR):
{$historial}
{$specs_ctx}
{$justif_ctx}
Generá la ficha EETT completa siguiendo el formato institucional AICO.
Para la justificación, seguí el formato del Manual de Compras CTM citando la sección normativa que corresponda.";
}

/**
 * Parsea la respuesta de Ollama, limpia markdown, extrae JSON.
 * @param string $raw Respuesta cruda de ollama_chat
 * @return array ['ok' => bool, 'campos' => [...], 'error' => '...', 'raw' => '...']
 */
function ia_parse_response($raw) {
    $respuesta = trim($raw);
    $respuesta = preg_replace('/^```(?:json)?\s*/i', '', $respuesta);
    $respuesta = preg_replace('/\s*```\s*$/', '', $respuesta);

    if (preg_match('/\{[\s\S]*\}/', $respuesta, $m)) {
        $respuesta = $m[0];
    }

    $campos = json_decode($respuesta, true);
    if (!is_array($campos)) {
        return ['ok' => false, 'error' => 'No se pudo interpretar la respuesta de la IA.', 'raw' => $raw];
    }

    $esperados = ia_campos_esperados();
    $resultado = [];
    foreach ($esperados as $c) {
        $resultado[$c] = isset($campos[$c]) ? (string)$campos[$c] : '';
    }

    return ['ok' => true, 'campos' => $resultado];
}

/**
 * Formatea el historial de compras como texto para el prompt.
 * @param array $compras Filas de pres_k2b_sr_logs
 * @param string $formato 'detallado' o 'compacto'
 * @return string
 */
function ia_formatear_historial($compras, $formato = 'detallado') {
    if (empty($compras)) return "  No hay compras registradas.\n";

    $texto = '';
    foreach ($compras as $i => $c) {
        $n = $i + 1;
        $sr     = $c['SR'] ?? $c['CODIGO'] ?? $c['codigo'] ?? '?';
        $fecha  = $c['FECHA'] ?? $c['fecha'] ?? '';
        $desc   = $c['DESCRIPCION'] ?? $c['descripcion'] ?? '';
        $estado = $c['ESTADO'] ?? $c['estado'] ?? '';
        $pu     = (float)($c['PU'] ?? $c['PRECIO_UNITARIO'] ?? $c['precio_unitario'] ?? 0);
        $cant_sol  = $c['CANT_SOL'] ?? $c['CANTIDAD_SOLICITADA'] ?? $c['cantidad_solicitada'] ?? '';
        $cant_comp = $c['CANT_COMP'] ?? $c['CANTIDAD_AUTORIZADA'] ?? $c['cantidad_autorizada'] ?? $c['CANTIDAD_COMPRADA'] ?? '';
        $monto_oc  = (float)($c['MONTO_OC'] ?? $c['MONTO_LINEA_OC_USD'] ?? $c['monto_linea_oc_usd'] ?? 0);
        $oc     = $c['OC'] ?? $c['ORDEN_COMPRA'] ?? $c['orden_compra'] ?? '';
        $prov   = $c['PROVEEDOR'] ?? $c['proveedor'] ?? '';
        $usu    = $c['USUARIO'] ?? $c['usuario'] ?? '';

        $oc_txt    = !empty($oc) ? $oc : 'Sin OC';
        $prov_txt  = !empty($prov) ? $prov : 'Sin proveedor';
        $monto_txt = ($monto_oc > 0) ? 'U$D ' . number_format($monto_oc, 2) : 'Sin monto OC';

        if ($formato === 'compacto') {
            $texto .= "- SR {$sr}: {$desc} | {$estado} | PU: U\$D " . number_format($pu, 2) . " | OC: {$oc_txt} | {$prov_txt}\n";
        } else {
            $texto .= "  {$n}. SR {$sr} ({$fecha}) - Estado: {$estado}\n";
            $texto .= "     Desc: {$desc}\n";
            $texto .= "     P.U.: U\$D " . number_format($pu, 2) . " | Cant.Sol: {$cant_sol} | Cant.Comp: {$cant_comp}\n";
            $texto .= "     OC: {$oc_txt} | Monto OC: {$monto_txt}\n";
            $texto .= "     Proveedor: {$prov_txt}" . (!empty($usu) ? " | Solicitante: {$usu}" : '') . "\n";
        }
    }
    return $texto;
}
