# README MIGRACIÓN - ctm_presupuestos

## Resumen

Refactorización del módulo `ctm_presupuestos` siguiendo las directrices de `herramientas_mejora/PROMPT_REINGENIERIA.md`.

**Sistema:** CTM_061  
**Estilo visual:** #7 (ya registrado en `ctm_entorno/config/modules.php`)  
**Fecha:** 2026-03-16  

---

## Estructura nueva

```
ctm_presupuestos/
├── config/
│   ├── config.php          ← Configuración central (constantes, rutas, DB)
│   ├── database.php        ← Singleton Database + wrappers compatibilidad
│   └── session.php         ← Sesión segura (migrada de conf/seccion.php)
├── includes/
│   ├── functions.php       ← Punto de entrada: carga config + DB + helpers + funciones legacy
│   ├── helpers.php         ← UI helpers (SweetAlert2 + Bootstrap 5)
│   ├── header.php          ← <head> HTML con CDN BS5 + SA2 + CSS módulo
│   ├── navbar.php          ← Menú dinámico desde pres_menus + breadcrumb
│   └── footer.php          ← Footer sticky + JS Bundle
├── assets/
│   ├── css/styles.css      ← Estilos unificados (basado en styles_template.css)
│   └── js/app.js           ← JS unificado (namespace PRES, basado en app_template.js)
├── web/                    ← (SIN CAMBIOS) Módulos de contenido en web/mod/ y web/int/
│   ├── int/
│   └── mod/
├── conf/                   ← (PRESERVADO) Archivos legacy como respaldo
├── index.php               ← Refactorizado (usa nueva infraestructura)
├── popups_manager.php      ← Refactorizado (usa nueva infraestructura)
├── index_legacy.php        ← Backup del index.php original
├── popups_manager_legacy.php ← Backup del popups_manager.php original
└── README_MIGRACION.md     ← Este archivo
```

---

## Cambios realizados

### 1. Configuración unificada (`config/`)
- **config.php**: Constantes `APP_ROOT`, `APP_CONFIG`, `APP_INCLUDES`, `APP_ASSETS`, `APP_WEB`, `APP_URL`. Función `getDbConfig()` para múltiples entornos.
- **database.php**: Clase `Database` Singleton con MySQLi. Wrappers `sql_do_query()`, `sql_do_array()`, `registros()` con firma idéntica al código legacy.
- **session.php**: Validación de sesión con anti-spoofing (`REMOTE_ADDR`, `HTTP_USER_AGENT`). Usa SweetAlert2 en lugar de swal v1. Resuelve `$usu`, `$nivel`, `$destino`, `$est_estructuraid`, `$est_organicaid`.

### 2. Funciones e includes (`includes/`)
- **functions.php**: Bootstrap de la aplicación. Carga config → database → helpers → PHPMailer. Contiene funciones legacy migradas: `sani_input()`, `Enmascarar_post()`, `Enmascarar_get()`, `imp_combo_w()`, `imp_usuario()`, `ollama_chat()`, etc.
- **helpers.php**: Helpers UI desde `helpers_template.php`: `generarAlertJS()`, `generarConfirmJS()`, `generarIconoEstado()`, `generarTablaHeader()`, `generarBotonForm()`, `generarCampoBusqueda()`, `generarTituloSeccion()`, `generarCard()`, `generarWizardPasos()`.
- **header.php**: HTML5 doctype, Bootstrap 5.3.2 CDN, Bootstrap Icons, SweetAlert2 CDN (en `<head>`), CSS del módulo.
- **navbar.php**: Menú dinámico de 3 niveles desde `pres_menus`. Breadcrumb con CTE recursiva. Verifica permisos por `NIVACC` vs `$nivel`.
- **footer.php**: Footer sticky con info del usuario. Bootstrap JS Bundle + `app.js`.

### 3. Estilos (`assets/css/styles.css`)
- Variables CSS con colores del tema (`--app-primary: #418CBA`)
- Offset del marco padre: `--parent-header-height: 0px` (módulo dentro de iframe)
- Navbar, breadcrumb, cards, tablas, botones con estilo moderno
- Clases legacy compatibles: `.fondo-I`, `.fondo-II`, `.fondo-III`, `.subt_chico_color_fondo_cent`, etc.
- Botones: `.btn-pade` (sólido) y `.btn-pade-alt` (contorneado) con hover diagonal stripes
- Responsive breakpoints para móvil
- Scrollbar personalizada

### 4. JavaScript (`assets/js/app.js`)
- Namespace `PRES` con métodos: `alert()`, `confirm()`, `toast()`, `ajax()`, `openPopup()`, `openHelp()`, `submitForm()`, `validateForm()`
- Inicialización automática de tooltips Bootstrap en `DOMContentLoaded`
- Wrapper legacy `enviarEnt()` para compatibilidad

### 5. Puntos de entrada refactorizados
- **index.php**: Carga `includes/functions.php` → `config/session.php` → resuelve `$direcion` desde `pres_menus` → header → navbar → contenido → footer
- **popups_manager.php**: Mismo patrón simplificado para popups, sin navbar ni footer

---

## Compatibilidad

- **`web/mod/` y `web/int/`**: NO se modificaron. Los archivos de contenido siguen funcionando igual.
- **`conf/`**: Directorio legacy PRESERVADO. Disponible como fallback.
- **`pres_menus`**: Tabla de menú sin cambios. El navbar lee `ID`, `OPCION`, `ESTADO`, `DIRECCION`, `SUBMENU`, `NIVACC`, `MENU`.
- **Wrappers DB**: `sql_do_query()`, `sql_do_array()`, `registros()` mantienen firma idéntica.
- **SweetAlert**: Migrado de swal v1 → SweetAlert2. Los scripts legacy en `conf/sweetalert.min.js` siguen disponibles.

---

## Checklist de verificación

- [x] Estructura de directorios creada
- [x] Configuración unificada en `config/`
- [x] Database Singleton + wrappers
- [x] Sesión segura con anti-spoofing
- [x] Funciones legacy migradas
- [x] Helpers UI (SweetAlert2 + Bootstrap 5)
- [x] Header con CDN (BS5 + SA2 en `<head>`)
- [x] Navbar dinámica desde BD (3 niveles)
- [x] Breadcrumb recursivo
- [x] Footer sticky
- [x] CSS unificado con clases legacy compatibles
- [x] JS unificado con namespace PRES
- [x] index.php refactorizado
- [x] popups_manager.php refactorizado
- [x] Backups legacy creados
- [x] Estilo CTM_061 → #7 en modules.php (ya existía)
- [ ] Probar flujo completo (entorno → iframe → presupuestos)
- [ ] Verificar que el marco padre (60px) no se superponga
