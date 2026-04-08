# CTM Inventarios (CTM_062) - Documentacion de Archivos

> Generado: 2026-04-08 | Version: 2.0.0
> Seguimiento de inventario IT: PCs, impresoras, monitores, otros equipos.
> Gestiona prestamos y reparaciones.

---

## Estadisticas

| Tipo       | Cantidad | Ubicacion                  |
|------------|----------|----------------------------|
| PHP        | 81       | config/, includes/, web/   |
| CSS        | 1        | assets/css/                |
| JS         | 1        | assets/js/                 |
| Imagenes   | 254+     | img/                       |
| Markdown   | 1        | raiz                       |

---

## Estructura de Directorios

```
ctm_inventarios/
├── index.php
├── README_MIGRACION.md
├── config/
├── includes/
├── assets/
│   ├── css/
│   └── js/
├── web/
│   ├── int/
│   ├── cron/
│   └── mod/
│       ├── inicio/
│       │   ├── prestamos/
│       │   └── reparaciones/
│       ├── inventario/
│       │   ├── pc/
│       │   │   └── resumen/
│       │   ├── impresoras/
│       │   ├── monitores/
│       │   └── otros/
│       ├── rrhh/
│       │   └── personal/
│       │       └── resumen/
│       ├── admin/
│       │   ├── proveedores/
│       │   ├── so/
│       │   ├── tipos_impresoras/
│       │   ├── zonas/
│       │   └── sectores/
│       ├── acerca_de/
│       │   ├── contacto/
│       │   └── migracion/
│       │       └── script/
│       └── popups/
└── img/
    ├── escudos/
    ├── grado_reserva/
    ├── ico/
    ├── icon/
    ├── orig/
    ├── prioridad/
    ├── so/
    └── tipo_equipo/
```

---

## Archivos por Directorio

### Raiz

| Archivo                  | Proposito                                                         |
|--------------------------|-------------------------------------------------------------------|
| `index.php`              | Punto de entrada principal (v2). Rutea via parametro `estado`     |
| `README_MIGRACION.md`    | Documentacion de migracion a v2                                   |

### config/ - Configuracion

| Archivo        | Proposito                                                                      |
|----------------|--------------------------------------------------------------------------------|
| `config.php`   | Constantes (APP_SISTEMA='CTM_062', APP_VERSION='2.0.0'), rutas, URLs, BD       |
| `database.php` | Singleton Database con soporte multi-BD (contexto_unificado, ctm_ocsweb, morebius). Wrappers legacy: sql_do_query, sql_do_array, registros |
| `session.php`  | Validacion de sesion CTM_062: IP, User-Agent, nivel de acceso                  |

**Conexiones a BD:**

| ID BD | Base de Datos        | Acceso | Uso                              |
|-------|----------------------|--------|----------------------------------|
| 0, 1  | contexto_unificado   | RW     | Tablas inve_* principales        |
| 2     | ctm_ocsweb           | RO     | OCS Inventory (auditoria SW)     |
| 3     | morebius             | RO     | Catalogo articulos/equipos       |

### includes/ - Componentes compartidos

| Archivo          | Proposito                                                                    |
|------------------|------------------------------------------------------------------------------|
| `functions.php`  | Cargador central: importa config, database, helpers. Funciones utilitarias legacy (get_id para IDs secuenciales) |
| `helpers.php`    | Funciones UI Bootstrap 5: generarAlertJS(), generarConfirmJS() para SweetAlert2 |
| `header.php`     | Seccion HTML head: Bootstrap 5 CSS/JS, Bootstrap Icons, SweetAlert2 CDN      |
| `navbar.php`     | Navbar responsive con menus dinamicos desde tabla inve_menus                  |
| `footer.php`     | Footer con copyright e info de usuario                                       |

### assets/ - Frontend

| Archivo            | Proposito                                                                 |
|--------------------|---------------------------------------------------------------------------|
| `css/styles.css`   | CSS custom properties (--app-primary: #2196F3), fuentes, responsive       |
| `js/app.js`        | Namespace JS INVE: alert(), confirm(), toast(), ajax(), validateForm()    |

### web/int/ - Paginas internas

| Archivo              | Proposito                         |
|----------------------|-----------------------------------|
| `presentacion.php`   | Pagina de presentacion (placeholder) |
| `salir.php`          | Handler de cierre de sesion       |

### web/cron/ - Tareas automatizadas

| Archivo                  | Proposito                                               |
|--------------------------|----------------------------------------------------------|
| `activos.php`            | Cron de monitoreo de equipos activos. Usa inve_crontab   |
| `activos_hs_justa.php`   | Variante de activos para horario especifico              |
| `externos.php`           | Integracion con sistemas externos                        |

### web/mod/inicio/ - Dashboard

| Archivo            | Proposito                                                              |
|--------------------|------------------------------------------------------------------------|
| `dashboard.php`    | Dashboard principal: stat-cards (total PCs, impresoras, monitores, otros, prestamos activos, reparaciones pendientes) |

### web/mod/inicio/prestamos/ - Gestion de Prestamos

| Archivo          | Proposito                                                    |
|------------------|--------------------------------------------------------------|
| `listar_v2.php`  | Lista prestamos activos con busqueda/filtro por estado       |
| `nuevo.php`      | Formulario alta de prestamo. Genera IDs secuenciales         |
| `cargador.php`   | Handler POST para crear/actualizar prestamos                 |

### web/mod/inicio/reparaciones/ - Gestion de Reparaciones

| Archivo          | Proposito                                                   |
|------------------|--------------------------------------------------------------|
| `listar_v2.php`  | Lista reparaciones pendientes (reparado='0')                |
| `nueva.php`      | Formulario alta de reparacion                               |
| `cargador.php`   | Handler POST para operaciones de reparacion                 |

### web/mod/inventario/pc/ - Inventario de PCs

| Archivo          | Proposito                                                              |
|------------------|------------------------------------------------------------------------|
| `listar_v2.php`  | Listado de PCs con busqueda (codigo, marca, IP, serie) y filtro estado |
| `nueva.php`      | Formulario alta de PC con iconos por tipo de equipo                    |
| `cargador.php`   | Handler POST para datos de PC                                          |
| `resumen_v2.php` | Vista detalle de PC con interfaz multi-tab via parametro GET           |

### web/mod/inventario/pc/resumen/ - Tabs de detalle PC

| Archivo                | Proposito                                             |
|------------------------|-------------------------------------------------------|
| `obligatorios_v2.php`  | Tab: campos obligatorios (hardware, SO, usuario)      |
| `morebius.php`         | Tab: integracion con BD Morebius (articulos/partes)   |
| `ocs_inventory.php`    | Tab: integracion OCS Inventory (auditoria software)   |
| `comentarios.php`      | Tab: notas y comentarios                              |

### web/mod/inventario/impresoras/ - Inventario de Impresoras

| Archivo        | Proposito                          |
|----------------|------------------------------------|
| `listar_v2.php`| Listado de impresoras              |
| `resumen.php`  | Vista detalle de impresora         |

### web/mod/inventario/monitores/ - Inventario de Monitores

| Archivo        | Proposito                          |
|----------------|------------------------------------|
| `listar_v2.php`| Listado de monitores               |
| `resumen.php`  | Vista detalle de monitor           |

### web/mod/inventario/otros/ - Otros Equipos

| Archivo        | Proposito                          |
|----------------|------------------------------------|
| `listar_v2.php`| Listado de otros dispositivos      |
| `resumen.php`  | Vista detalle de dispositivo       |

### web/mod/rrhh/personal/ - Gestion de Personal

| Archivo          | Proposito                                               |
|------------------|---------------------------------------------------------|
| `listar_v2.php`  | Lista de personal con busqueda y filtros (estado, rol)  |
| `nueva.php`      | Formulario alta de personal                             |
| `cargador.php`   | Handler POST para datos de personal                     |
| `resumen.php`    | Vista detalle con interfaz multi-tab                    |

### web/mod/rrhh/personal/resumen/ - Tabs de detalle Personal

| Archivo              | Proposito                                    |
|----------------------|----------------------------------------------|
| `obligatorios.php`   | Datos personales obligatorios                |
| `detalle.php`        | Informacion detallada                        |
| `ariadna.php`        | Navegacion breadcrumb/jerarquia              |
| `cargo.php`          | Informacion de cargo/puesto                  |
| `documentacion.php`  | Gestion de documentos y enlaces              |
| `elementos.php`      | Equipos asignados a la persona               |
| `educacion.php`      | Registros de educacion/capacitacion          |
| `bancos.php`         | Informacion bancaria                         |
| `antecedentes.php`   | Historial/antecedentes                       |
| `nombramientos.php`  | Registros de nombramientos                   |
| `notas.php`          | Notas internas                               |
| `morebius.php`       | Integracion con BD Morebius                  |
| `ocs_inventory.php`  | Datos de OCS Inventory                       |

### web/mod/admin/ - Administracion

**Proveedores:**

| Archivo        | Proposito                              |
|----------------|----------------------------------------|
| `listar.php`   | Lista de proveedores con busqueda      |
| `nuevo.php`    | Formulario alta de proveedor           |

**Sistemas Operativos (so/):**

| Archivo        | Proposito                              |
|----------------|----------------------------------------|
| `listar.php`   | Lista de sistemas operativos           |
| `nuevo.php`    | Alta de SO                             |

**Tipos de Impresoras (tipos_impresoras/):**

| Archivo        | Proposito                              |
|----------------|----------------------------------------|
| `listar.php`   | Lista de modelos/tipos de impresora    |
| `nueva.php`    | Alta de tipo de impresora              |

**Zonas (zonas/):**

| Archivo        | Proposito                              |
|----------------|----------------------------------------|
| `listar.php`   | Lista de zonas geograficas/organizacionales |
| `nueva.php`    | Alta de zona                           |

**Sectores (sectores/):**

| Archivo        | Proposito                              |
|----------------|----------------------------------------|
| `listar.php`   | Lista de sectores/departamentos        |

### web/mod/acerca_de/ - Informacion y Utilidades

| Archivo               | Proposito                                    |
|-----------------------|----------------------------------------------|
| `info.php`            | Pagina de informacion del sistema            |
| `contacto/nuevo.php`  | Formulario de contacto                       |
| `migracion/listar.php`| Panel de control de migracion                |
| `migracion/script/101-109.php` | Scripts de migracion de BD para transicion v2 |
| `migracion/script/151-152.php` | Parches adicionales de migracion      |

### web/mod/popups/ - Modales

| Archivo                | Proposito                              |
|------------------------|----------------------------------------|
| `const_prestamo.php`   | Modal de confirmacion de prestamo      |
| `const_reparacion.php` | Modal de confirmacion de reparacion    |

---

## Archivos Legacy / Obsoletos

Archivos que podrian limpiarse en una futura revision:

| Archivo                                          | Tipo    |
|--------------------------------------------------|---------|
| `web/mod/rrhh/personal/resumen/morebius_old.php` | _old    |
| `web/mod/rrhh/personal/resumen/ocs_inventory_1.php` | Legacy |
| `web/mod/rrhh/personal/resumen/personal_1.php`   | Legacy  |
| `img/ico/editar_arc_old.jpg`                     | _old    |
| `img/MEMBRETEPNA_OLD.png`                        | _old    |
| `img/prioridad/*_old.png` (5 archivos)           | _old    |
| `img/_notes/*.mno` (16 archivos)                 | Dreamweaver metadata |
| `img/Thumbs.db`                                  | Cache Windows |

---

## Imagenes por Directorio

| Directorio          | Cantidad | Contenido                              |
|---------------------|----------|----------------------------------------|
| `img/` (raiz)       | 55       | Logos, fondos, encabezados             |
| `img/escudos/`      | 21       | Escudos de unidades/departamentos      |
| `img/grado_reserva/`| 10       | Iconos de grado militar/reserva        |
| `img/ico/`          | 41       | Iconos de acciones (editar, eliminar, etc.) |
| `img/icon/`         | 32       | Set de iconos modernos                 |
| `img/orig/`         | 22       | Versiones originales/backup de imagenes |
| `img/prioridad/`    | 20       | Indicadores de nivel de prioridad      |
| `img/so/`           | 54       | Logos de sistemas operativos           |
| `img/tipo_equipo/`  | 12       | Iconos de tipos de equipo              |

---

## Patrones Arquitectonicos

1. **Database Singleton** - Instancia unica por BD con carga lazy
2. **Wrappers Legacy** - sql_do_query(), sql_do_array() para compatibilidad
3. **Ruteo por Estado** - Parametro GET `estado` determina modulo en index.php
4. **Interfaz Multi-Tab** - PCs y personal usan parametro `tab` para vistas multiples
5. **IDs Secuenciales** - Tabla inve_id gestiona proximos IDs por tipo de entidad
6. **Seguridad por Sesion** - Validacion IP/UA, niveles de permiso
7. **Menus Dinamicos** - Tabla inve_menus alimenta el navbar
8. **UI Bootstrap 5** - Responsive con SweetAlert2 para modales
