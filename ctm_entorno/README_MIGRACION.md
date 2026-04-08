# Migración ahid_entorno - Sistema de Tres Vías

## Resumen de la Migración

Reingeniería del módulo marco (entorno) que contiene todos los módulos del sistema.
Se migró de la arquitectura legacy `conf/` a la nueva arquitectura `config/` + `includes/` + `assets/`.

---

## Nueva Arquitectura

```
ahid_entorno/
├── config/
│   ├── config.php          ← Configuración unificada
│   ├── database.php         ← Clase Database singleton + wrappers
│   └── session.php          ← Gestión de sesión segura
├── includes/
│   └── functions.php        ← Funciones unificadas + helpers
├── assets/
│   ├── css/styles.css       ← Estilos Bootstrap 5 centralizados
│   └── js/app.js            ← JavaScript unificado
├── index.php                ← Marco principal refactorizado
├── default.php              ← Login refactorizado
├── conf/                    ← Legacy (mantener captcha y funcion.php temporalmente)
└── web/
    └── int/
        ├── presentacion.php ← Dashboard dinámico (620→35 líneas)
        └── ...
```

---

## Cambios Realizados

### index.php
- **Antes**: gn-menu custom sidebar + legacy CSS/JS
- **Ahora**: Bootstrap 5 topbar + offcanvas sidebar
- Menú dinámico desde `usu_user_sist` / `usu_sistemas`
- User info, notificaciones y botón salir en topbar
- Contenido: include para estados numéricos, iframe para módulos

### default.php (Login)
- **Antes**: Tablas HTML + CSS legacy + formulario básico
- **Ahora**: Card centrada con gradient background, input groups con iconos Bootstrap
- Mantiene captcha funcional (`conf/captcha/captcha.php`)

### web/int/presentacion.php
- **Antes**: 620 líneas (566 de CSS inline para colores de sistemas)
- **Ahora**: 35 líneas con cards dinámicas usando `getColorSistema()`
- Grid responsivo Bootstrap 5 (4 columnas desktop, 3 tablet)

---

## Tecnologías

- **Bootstrap 5.3** via CDN
- **Bootstrap Icons** via CDN
- **SweetAlert2** via CDN
- **Database Singleton** con wrappers compatibles
- **Colores de sistemas** centralizados en `getColorSistema()` (functions.php)

---

## Tablas Utilizadas

- `ento_menus` - Menú interno del entorno
- `ento_notificaciones` - Notificaciones del usuario
- `usu_user_sist` - Relación usuario-sistema
- `usu_sistemas` - Catálogo de sistemas/módulos
- `usu_sist_privilegio` - Privilegios por sistema
- `vw_personas` - Vista de datos personales

---

## Backups Creados

- `index_legacy.php`
- `web/int/presentacion_legacy.php`

---

## Directorio `conf/` (Legacy) - ✅ ELIMINADO

Todas las dependencias fueron migradas:
- `conf/conf.php` → `config/config.php`
- `conf/funcion.php` → `config/database.php` + `includes/functions.php`
- `conf/seccion.php` → `config/session.php`
- `conf/estilos.css` → `assets/css/styles.css`
- `conf/captcha/*` → `assets/captcha/*`
- `conf/js/*` → CDN (Bootstrap 5, SweetAlert2)
- `conf/css/*` → CDN (Bootstrap 5)

**Estado**: ✅ **ELIMINADO** - Solo `index_legacy.php` y `web/int/menu.php` (comentado) lo referencian.

---

## Pendientes

- [ ] Migrar `web/mod/datos_personales/`
- [ ] Migrar `web/mod/notificaciones/`
- [ ] Verificar integración con `intermedio_aico.php` (login)
- [ ] Eliminar `conf/` cuando todo esté verificado
