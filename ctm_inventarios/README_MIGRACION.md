# Migración v2 - ctm_inventarios

## Descripción
Reingeniería completa del módulo de inventarios siguiendo los patrones de `herramientas_mejora` y la arquitectura de `ctm_morebius v2`.

## Cambios principales

### Arquitectura
- **Database Singleton** (`config/database.php`) con wrappers legacy `sql_do_query`, `sql_do_array`, `registros`
- **Sesión segura** (`config/session.php`) con validación IP/UA y control de permisos por menú
- **Configuración centralizada** (`config/config.php`) con `getDbConfig()` y constantes de la app
- **Funciones centrales** (`includes/functions.php`) como punto de entrada que carga toda la infraestructura
- **Helpers UI** (`includes/helpers.php`) para generación de componentes Bootstrap 5 + SweetAlert2

### UI / UX
- **Bootstrap 5** + **Bootstrap Icons** (CDN)
- **SweetAlert2** para alertas y confirmaciones
- **Navbar responsiva** con menú dinámico desde `inve_menus`
- **Breadcrumb** dinámico con jerarquía recursiva
- **Footer** con copyright (izquierda) y usuario + nivel (derecha)
- **Dashboard** con 6 stat-cards + últimos equipos + préstamos recientes
- **Tablas estilizadas** con clases `.table-pade`
- **Búsqueda integrada** en todos los listados

### Estructura actual del módulo
```
index.php               - Punto de entrada v2
README_MIGRACION.md     - Este archivo

config/
  config.php            - Configuración central y DB configs
  database.php          - Database Singleton + wrappers
  session.php           - Sesión segura CTM_062

includes/
  functions.php         - Funciones centrales (carga todo)
  helpers.php           - Helpers UI Bootstrap 5 + SweetAlert2
  header.php            - Head HTML con CDNs
  navbar.php            - Navbar dinámica desde inve_menus
  footer.php            - Footer con JS

assets/
  css/styles.css        - Estilos del módulo
  js/app.js             - JS namespace INVE

web/int/
  presentacion.php      - Página placeholder
  salir.php             - Logout

web/mod/inicio/
  dashboard.php                      - Dashboard principal
  prestamos/listar_v2.php            - Listado de préstamos
  prestamos/nuevo.php                - Nuevo préstamo
  prestamos/cargador.php             - Cargador POST préstamos
  reparaciones/listar_v2.php         - Listado de reparaciones
  reparaciones/nueva.php             - Nueva reparación
  reparaciones/cargador.php          - Cargador POST reparaciones

web/mod/inventario/
  pc/listar_v2.php                   - Listado de PCs
  pc/resumen_v2.php                  - Detalle/resumen de PC
  pc/cargador.php                    - Cargador POST PCs
  pc/nueva.php                       - Nuevo PC
  pc/resumen/obligatorios_v2.php     - Tab datos obligatorios
  pc/resumen/morebius.php            - Tab Morebius
  pc/resumen/ocs_inventory.php       - Tab OCS Inventory
  pc/resumen/comentarios.php         - Tab Comentarios
  impresoras/listar_v2.php           - Listado de impresoras
  impresoras/resumen.php             - Resumen impresora
  monitores/listar_v2.php            - Listado de monitores
  monitores/resumen.php              - Resumen monitor
  otros/listar_v2.php                - Listado de otros
  otros/resumen.php                  - Resumen otro dispositivo

web/mod/rrhh/personal/
  listar_v2.php                      - Listado de personal
  resumen.php                        - Resumen persona
  nueva.php                          - Nueva persona
  cargador.php                       - Cargador POST personal
  resumen/                           - Tabs de detalle persona

web/mod/admin/                       - Administración (proveedores, SO, zonas, etc.)
web/mod/acerca_de/                   - Info, contacto, migración
web/mod/popups/                      - Popups préstamos/reparaciones
web/cron/                            - Scripts de actualización
```

### Bases de datos
| DB ID | Base             | Uso                    |
|-------|------------------|------------------------|
| 0, 1  | contexto_unificado | Tablas inve_*, vistas |
| 2     | ctm_ocsweb       | OCS Inventory (RO)     |
| 3     | morebius         | Artículos (RO)         |
| 4     | inventario       | Legacy (no disponible) |

### Menús actualizados (inve_menus)
Todos los menús principales fueron actualizados vía `sql_migracion_v2.sql` para apuntar a los nuevos archivos `_v2.php`.

### Tabla de secuencias
Se creó `inve_id` para manejar IDs secuenciales (patrón `get_id()`), inicializada con los MAX de las tablas existentes.

### Archivos legacy eliminados
Se eliminaron todos los archivos legacy que fueron reemplazados por la versión v2:
- `conf/` completo (reemplazado por `config/` + `includes/`)
- `bkp/` (backups obsoletos)
- `web/int/` legacy: `encabezado.php`, `menu.php`, `pie.php`, `ariadna.php`, `script_manager.php`, `ajax/`, `cursos/`
- `web/mod/viejas/`, `web/mod/admin_old/`, `web/mod/inicio_old/` (carpetas explícitamente viejas)
- Archivos legacy individuales reemplazados: `listar.php`, `resumen.php`, `cargador_1.php`, `cargador_2.php`, `obligatorios.php`, `obligatorios_1.php`, `morebius_old.php`, `personal_1.php`, `ocs_inventory_1.php`, `cargador_1.php`
- `index_legacy.php`, `index_v2.php`, `demo.php`, `cron.php`, `popups_manager.php`, `sql_migracion_v2.sql`
- Tabs de PC no usados: `antecedentes.php`, `ariadna.php`, `bancos.php`, `detalle.php`, `documentacion.php`, `educacion.php`, `elementos.php`, `nombramientos.php`, `notas.php`
- Stubs vacíos: `detalles.php` en préstamos y reparaciones
- `web/arch/` (solo tenía Thumbs.db)

### Namespace JS
- Global: `INVE` (con métodos `alert`, `confirm`, `toast`, `ajax`, `openPopup`, `validateForm`)
