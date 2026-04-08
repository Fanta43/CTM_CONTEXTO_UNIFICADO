# CTM Presupuestos (CTM_055) - Documentacion de Archivos

> Generado: 2026-04-08 | Version: 2.0.0
> Gestion presupuestaria con integracion K2B.
> Ciclo: borrador (proyeccion multifactor) -> revision jerarquica (3 niveles) -> aprobacion -> ejecucion -> cierre.

---

## Estadisticas

| Tipo       | Cantidad | Ubicacion                          |
|------------|----------|------------------------------------|
| PHP        | 49       | config/, includes/, web/           |
| CSS        | 1        | assets/css/                        |
| JS         | 1        | assets/js/                         |
| SQL        | 12       | raiz                               |
| Markdown   | 1        | raiz                               |
| Config IA  | 3        | config/ia/                         |
| Libs       | 54+      | config/lib/ (PHPMailer, Twilio)    |
| Vendor     | 100+     | vendor/ (Composer: PhpWord, Twilio)|

---

## Estructura de Directorios

```
ctm_presupuestos/
├── index.php
├── popups_manager.php
├── README_MIGRACION.md
├── composer.json
├── composer.lock
├── sql_*.sql (12 scripts)
├── config/
│   ├── ia/
│   └── lib/
│       ├── PHPMailer/
│       └── twilio/
├── includes/
├── assets/
│   ├── css/
│   └── js/
├── web/
│   ├── int/
│   └── mod/
│       ├── solicitudes/
│       ├── presupuestos/
│       ├── configuracion/
│       ├── revision/
│       ├── reportes/
│       └── cierre/
├── img/
└── vendor/
    └── phpoffice/phpword/
```

---

## Archivos por Directorio

### Raiz - Puntos de entrada y scripts SQL

**Puntos de entrada:**

| Archivo                | Proposito                                                   |
|------------------------|-------------------------------------------------------------|
| `index.php`            | Router principal. Maneja parametro GET `estado`, sesion      |
| `popups_manager.php`   | Gestor de ventanas popup. Carga contenido desde ciclo_menus  |

**Documentacion:**

| Archivo                  | Proposito                                                  |
|--------------------------|--------------------------------------------------------------|
| `README_MIGRACION.md`    | Guia de migracion del sistema legacy a arquitectura modular  |
| `composer.json`          | Dependencias: phpoffice/phpword para generacion DOCX         |
| `composer.lock`          | Dependencias bloqueadas                                      |

**Scripts SQL (12):**

| Archivo                           | Proposito                                    |
|-----------------------------------|----------------------------------------------|
| `sql_crear_tablas.sql`            | Esquema de creacion de tablas core            |
| `sql_crear_ciclo_rol.sql`        | Configuracion de roles/ciclo                  |
| `sql_borrador_eett.sql`          | Tabla de borradores EETT (Especificaciones Tecnicas) |
| `sql_k2b_integracion.sql`       | Integracion con sistema K2B                   |
| `sql_k2b_matricula_categoria.sql`| Mapeo categoria producto/matricula K2B        |
| `sql_k2b_sync_presupuestos.sql` | Sincronizacion de presupuestos K2B            |
| `sql_matricula_eett.sql`        | Asociacion matricula-EETT                     |
| `sql_solicitudes_k2b.sql`       | Solicitudes de recursos K2B                   |
| `sql_sr_estado_log.sql`         | Log de estados de SR                          |
| `sql_sr_logs_eett.sql`          | Logs de EETT para SR                          |
| `sql_swap_tablas.sql`           | Utilidades de swap de tablas                  |

---

### config/ - Configuracion

| Archivo        | Proposito                                                                      |
|----------------|--------------------------------------------------------------------------------|
| `config.php`   | Constantes centrales (APP_SISTEMA='CTM_055', APP_VERSION='2.0.0'), rutas, URLs |
| `database.php` | Singleton Database con MySQLi. getDbConfig(), getInstance() multi-BD. Wrappers legacy |
| `session.php`  | Seguridad de sesion: anti-spoofing (IP, UA). Variables $usu, $nivel, $destino  |

### config/ia/ - Configuracion de Inteligencia Artificial (Ollama)

| Archivo                         | Proposito                                              |
|---------------------------------|--------------------------------------------------------|
| `system_prompt_eett.txt`        | System prompt para generacion de EETT via Ollama LLM   |
| `specs_referencia.md`           | Datos de referencia de especificaciones tecnicas        |
| `justificaciones_referencia.md` | Justificaciones de referencia para contexto IA          |

### config/lib/ - Librerias externas

| Directorio/Archivo                    | Proposito                                    |
|---------------------------------------|----------------------------------------------|
| `PHPMailer/` (54 archivos idioma + src/) | Libreria de email                         |
| `PHPMailer/src/PHPMailer.php`         | Clase principal de envio de email            |
| `PHPMailer/src/SMTP.php`             | Protocolo SMTP                               |
| `PHPMailer/src/OAuth.php`            | Autenticacion OAuth                          |
| `twilio/`                             | SDK de Twilio (SMS/voz - sin uso activo)     |
| `DigiCertGlobalRootCA.crt.pem`       | Certificado SSL para conexiones seguras      |

---

### includes/ - Funciones del modulo

| Archivo                | Proposito                                                            |
|------------------------|----------------------------------------------------------------------|
| `functions.php`        | Cargador principal: importa config, database, helpers, PHPMailer     |
| `helpers.php`          | UI helpers: SweetAlert2 (generarAlertJS, generarConfirmJS), generadores Bootstrap 5 |
| `ia_prompt_builder.php`| Constructor de prompts IA: carga config/ia, filtra specs, construye mensajes para Ollama |
| `header.php`           | HTML head con Bootstrap 5 CDN, SweetAlert2, Font-Awesome, CSS local  |
| `navbar.php`           | Navbar responsive Bootstrap 5. Menu dinamico desde tabla ciclo_menus. Breadcrumb |
| `footer.php`           | Footer sticky con Bootstrap JS Bundle                                |

---

### assets/ - Frontend

| Archivo            | Proposito                                                                  |
|--------------------|----------------------------------------------------------------------------|
| `css/styles.css`   | Estilos custom con integracion Bootstrap 5. Clase navbar-pade              |
| `js/app.js`        | Namespace JS PRES: SweetAlert2 wrappers, AJAX helpers, validacion de formularios, tooltips |

---

### web/int/ - Paginas internas

| Archivo              | Proposito                                                        |
|----------------------|------------------------------------------------------------------|
| `presentacion.php`   | Dashboard/presentacion con diagrama SVG circular del ciclo presupuestario |

---

### web/mod/solicitudes/ - Gestion de Solicitudes K2B (15 archivos)

**Archivos activos:**

| Archivo                              | Proposito                                                   |
|--------------------------------------|-------------------------------------------------------------|
| `listar.php`                         | Lista de SR (Solicitud de Recurso) K2B. Tabla filtrable con acciones |
| `borradores.php`                     | Gestion de borradores SR. Cambio de estado (borrador/enviada/descartada). Agrupados por periodo y centro de costo |
| `detalle_sr.php`                     | Vista detalle de SR individual con informacion completa      |
| `detalle_borrador.php`              | Detalle de borrador SR con entrada de EETT/justificacion     |
| `notificar.php`                      | Notificacion por email de SR seleccionada                    |
| `saldos.php`                         | Saldos/presupuesto remanente por item. Semaforo (colores)    |
| `imprimir_eett.php`                 | Impresion de documento EETT (formato HTML)                   |
| `descargar_eett_docx.php`           | Generacion DOCX via PhpWord. Tipos: sr o borrador            |
| `borrador_eett_ia_ajax.php`         | AJAX: Generacion IA de EETT para borradores via Ollama       |
| `borrador_eett_ia_masivo_ajax.php`  | AJAX: Generacion masiva de EETT via IA para multiples borradores |

**Archivos legacy (_old):**

| Archivo                    | Proposito (superseded)                |
|----------------------------|---------------------------------------|
| `aprobar_old.php`          | Flujo de aprobacion (reemplazado)     |
| `crear_old.php`            | Flujo de creacion (reemplazado)       |
| `ejecutar_old.php`         | Flujo de ejecucion (reemplazado)      |
| `mis_solicitudes_old.php`  | Vista mis solicitudes (reemplazado)   |
| `saldos_old.php`           | Vista de saldos (reemplazado)         |

---

### web/mod/presupuestos/ - Gestion de Presupuestos (7 archivos)

| Archivo                  | Proposito                                                              |
|--------------------------|------------------------------------------------------------------------|
| `listar.php`             | Lista principal de presupuestos. Tabla filtrable. Links a detalle, comparativo, acciones. Deteccion de importacion K2B |
| `detalle.php`            | Vista detalle del presupuesto con partidas (line items)                |
| `generar_borrador.php`   | Generacion de SR proyectadas. Compara periodos origen vs destino. Aplica factores de ajuste |
| `comparativo.php`        | Comparacion lado a lado (A vs B). Resalta variaciones >20% (naranja) >50% (rojo). Export CSV |
| `k2b_sr.php`             | Vista de integracion SR con K2B                                        |
| `ajax_importar_k2b.php`  | AJAX: Importacion de datos K2B a presupuestos                          |
| `notificar_sr.php`       | Notificacion a stakeholders sobre SR                                   |

---

### web/mod/configuracion/ - Configuracion del Sistema (13 archivos)

**CRUD de entidades:**

| Archivo                | Proposito                                                    |
|------------------------|--------------------------------------------------------------|
| `periodos.php`         | CRUD de periodos fiscales (tabla ciclo_periodo_fiscal)        |
| `roles.php`            | CRUD de roles con niveles jerarquicos                        |
| `unidades.php`         | CRUD de unidades organizacionales                            |
| `usuarios.php`         | CRUD de usuarios con asignacion de unidad y rol              |
| `items.php`            | CRUD de items presupuestarios (partidas)                     |
| `categorias.php`       | CRUD de categorias de gasto                                  |
| `factores.php`         | CRUD de factores de ajuste (para proyecciones)               |
| `umbrales.php`         | CRUD de umbrales de alerta                                   |
| `matriculas.php`       | Mapeo producto/matricula K2B a categorias presupuestarias    |
| `detalle_matricula.php`| Detalle de matricula individual con entrada EETT             |

**Integracion IA (AJAX):**

| Archivo                              | Proposito                                              |
|--------------------------------------|---------------------------------------------------------|
| `matricula_eett_ia_ajax.php`         | AJAX: Generacion IA de EETT para matriculas via Ollama  |
| `matricula_eett_ia_masivo_ajax.php`  | AJAX: Generacion masiva IA de EETT para matriculas      |
| `matriculas_ia_ajax.php`            | AJAX: Endpoint adicional de integracion IA               |

---

### web/mod/revision/ - Flujo de Aprobacion/Revision (4 archivos)

| Archivo                | Proposito                                                        |
|------------------------|------------------------------------------------------------------|
| `bandeja.php`          | Bandeja de revision. Muestra presupuestos pendientes segun nivel jerarquico del usuario |
| `revisar.php`          | Revision individual de presupuesto. Muestra detalle, permite aprobar/devolver |
| `aprobar_devolver.php` | Acciones de aprobacion o devolucion                              |
| `historial.php`        | Historial de revisiones / log de auditoria                       |

---

### web/mod/reportes/ - Reportes y Analitica (5 archivos)

| Archivo                  | Proposito                                                  |
|--------------------------|------------------------------------------------------------|
| `resumen.php`            | Reporte resumen presupuestario                             |
| `ejecucion.php`          | Reporte de ejecucion: presupuesto vs gasto real            |
| `alertas.php`            | Reporte de alertas por umbrales                            |
| `comparativo_anual.php`  | Comparacion presupuestaria interanual                      |
| `proyeccion_vs_real.php` | Performance: proyeccion vs ejecucion real                  |

---

### web/mod/cierre/ - Cierre de Periodo (4 archivos)

| Archivo          | Proposito                                                      |
|------------------|----------------------------------------------------------------|
| `iniciar.php`    | Inicio del cierre de periodo. Cambia estado a "en_cierre"      |
| `validar.php`    | Validacion de condiciones de cierre                            |
| `consolidar.php` | Consolidacion de datos del periodo                             |
| `cerrar.php`     | Cierre final del periodo                                       |

---

## Archivos Legacy / Obsoletos

| Archivo                                    | Tipo    | Nota                              |
|--------------------------------------------|---------|-----------------------------------|
| `web/mod/solicitudes/aprobar_old.php`      | _old    | Reemplazado por revision/         |
| `web/mod/solicitudes/crear_old.php`        | _old    | Reemplazado por borradores.php    |
| `web/mod/solicitudes/ejecutar_old.php`     | _old    | Reemplazado por presupuestos/     |
| `web/mod/solicitudes/mis_solicitudes_old.php` | _old | Reemplazado por listar.php        |
| `web/mod/solicitudes/saldos_old.php`       | _old    | Reemplazado por saldos.php        |

---

## Integraciones Externas

### K2B (Compras)
- Tablas: pres_k2b_* (4 tablas de sincronizacion)
- Archivos: ajax_importar_k2b.php, k2b_sr.php, notificar_sr.php
- Parsing: SUBSTRING_INDEX(esquema, '-', N) en multiples queries

### Ollama (IA Local)
- Config: config/ia/ (system prompt, specs referencia, justificaciones)
- Builder: includes/ia_prompt_builder.php
- Endpoints: borrador_eett_ia_ajax.php, borrador_eett_ia_masivo_ajax.php, matricula_eett_ia_ajax.php, matricula_eett_ia_masivo_ajax.php, matriculas_ia_ajax.php
- Sin fallback si el servicio cae

### PHPMailer (Email)
- Libreria en config/lib/PHPMailer/
- Usado por notificar.php y notificar_sr.php

### PhpWord (Documentos)
- Via Composer en vendor/phpoffice/phpword/
- Usado por descargar_eett_docx.php para generar DOCX

### Twilio (SMS - sin uso activo)
- SDK en config/lib/twilio/ y vendor/twilio/
- Disponible pero no utilizado activamente

---

## Modelo de Datos

**Tablas del ciclo presupuestario (20):** ciclo_*
**Tablas K2B (4):** pres_k2b_*
**Vistas (5):** vistas auxiliares para reportes
**Stored Procedure (1):** procedimiento almacenado

**Roles jerarquicos:** operador -> jefe -> gerente -> director -> admin
- Cada nivel ve y aprueba presupuestos segun su alcance

---

## Patrones Arquitectonicos

1. **Database Singleton** - Clase Database custom con wrappers backward-compatible
2. **Bootstrap 5 + SweetAlert2** - Frontend moderno con namespace JS PRES
3. **Integracion IA** - Ollama LLM para generacion de EETT y justificaciones
4. **Ciclo Presupuestario** - Flujo multi-paso: solicitudes -> presupuesto -> revision -> aprobacion -> ejecucion -> cierre
5. **Generacion de Documentos** - PhpWord para DOCX de EETT y justificaciones
6. **Menu Dinamico** - Tabla ciclo_menus alimenta navbar con niveles de acceso
7. **Comparativos** - Variaciones coloreadas por semaforo (>20% naranja, >50% rojo)
8. **Proyeccion Multifactor** - Factores de ajuste configurables para generar borradores proyectados
