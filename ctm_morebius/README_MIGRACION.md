# Migración Morebius v2.0

## Resumen
Reingeniería completa del módulo `ctm_morebius` siguiendo los patrones de `herramientas_mejora/`.

**Base "morebius" es INTOCABLE** — todos los cambios están en `contexto_unificado` y en el código PHP.

## Estructura Nueva

```
ctm_morebius/
├── config/
│   ├── config.php          ← Configuración central (AICO_666, DB configs)
│   ├── database.php        ← Singleton Database + wrappers legacy
│   └── session.php         ← Sesión segura + validación permisos
├── includes/
│   ├── functions.php       ← Punto de entrada (carga config+db+helpers)
│   ├── helpers.php         ← Componentes UI (SweetAlert2, tablas, badges)
│   ├── header.php          ← <head> con Bootstrap 5 CDN + SweetAlert2
│   ├── navbar.php          ← Navbar dinámico desde more_menus + breadcrumb
│   └── footer.php          ← Footer sticky con info usuario
├── assets/
│   ├── css/styles.css      ← Estilos unificados
│   └── js/app.js           ← JS unificado (MORE namespace)
├── index_v2.php            ← Nuevo punto de entrada
├── sql_migracion_v2.sql    ← SQL para actualizar more_menus
├── web/mod/
│   ├── inicio/dashboard.php         ← Dashboard con stats
│   ├── inventario/pc/listar_v2.php  ← Listado equipos inventario
│   ├── inventario/pc/resumen_v2.php ← Detalle equipo
│   ├── reportes/listar_v2.php       ← Artículos (celulares, etc.)
│   ├── personal/listar_plantel_v2.php ← Personal con equipos
│   ├── equipos/organigrama_v2.php   ← Organigrama jerárquico
│   ├── equipos/xunidad_v2.php       ← Equipos por unidad
│   ├── admin/marcas/listar_v2.php   ← Marcas (consulta)
│   ├── admin/ubicacion/listar_v2.php ← Ubicaciones
│   ├── admin/so/listar_v2.php       ← Tipos artículo
│   ├── admin/tipos_impresoras/listar_v2.php ← Tipos dispositivo
│   ├── admin/sectores/listar_v2.php ← Centros de costo
│   └── admin/tablas_externas/listar_v2.php ← Tablas externas
└── (archivos legacy preservados sin modificar)
```

## Pasos de Activación

### 1. Ejecutar SQL
```sql
-- En phpMyAdmin o consola, contra la base contexto_unificado:
SOURCE sql_migracion_v2.sql;
```

### 2. Activar el nuevo index
```bash
# Backup del original
copy index.php index_legacy.php
# Activar v2
copy index_v2.php index.php
```

### 3. Verificar
- Navegar a `http://localhost/ctm/contexto_unificado/ctm_morebius/index.php?estado=1`
- Verificar que el dashboard carga correctamente
- Verificar navegación por menú

## Bases de Datos

| DB | Alias | Uso |
|----|-------|-----|
| `contexto_unificado` | DB '0', '1' | Menús, tablas more_*, sesiones, tablas externas |
| `morebius` | DB '2' | **READ-ONLY**: inventario, plantel, árbol, artículos |

## Tecnologías
- Bootstrap 5.3.2 (CDN)
- Bootstrap Icons 1.11.3 (CDN)
- SweetAlert2 v11 (CDN)
- PHP 8.2 + MariaDB 10.4

## Compatibilidad
- Archivos legacy (`conf/`, `web/int/menu.php`, etc.) se preservan intactos
- Funciones legacy (`sql_do_query`, `sql_do_array`, `Enmascarar_post`, etc.) siguen funcionando via wrappers en `config/database.php` e `includes/functions.php`
- El `index.php` original se guarda como `index_legacy.php`
