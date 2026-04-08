# CTM Morebius (AICO_666) - Documentacion de Archivos

> Generado: 2026-04-08 | Version: 2.0.0
> Asignacion de equipos al personal: 3,128+ equipos, 554+ empleados.
> Articulos (celulares, notebooks, modems), organigrama, equipos por unidad.

---

## Estadisticas

| Tipo       | Cantidad | Ubicacion                  |
|------------|----------|----------------------------|
| PHP        | 141      | config/, includes/, web/   |
| CSS        | 1        | assets/css/                |
| JS         | 1        | assets/js/                 |
| Imagenes   | 860+     | img/                       |
| SQL        | 1        | raiz                       |
| Markdown   | 2        | raiz                       |
| JSON       | 1        | web/int/ajax/              |

---

## Estructura de Directorios

```
ctm_morebius/
├── index.php
├── cron.php
├── DOCUMENTACION.md
├── README_MIGRACION.md
├── sql_migracion_v2.sql
├── config/
├── includes/
├── assets/
│   ├── css/
│   └── js/
├── web/
│   ├── int/
│   │   └── ajax/
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
│       ├── reportes/
│       │   └── celulares/
│       ├── personal/
│       ├── equipos/
│       │   ├── notebook/
│       │   └── xunidad/
│       ├── admin/
│       │   ├── marcas/
│       │   ├── ubicacion/
│       │   ├── so/
│       │   ├── sectores/
│       │   ├── tipos_impresoras/
│       │   ├── zonas/
│       │   ├── proveedores/
│       │   ├── tablas_externas/
│       │   ├── per_extras/
│       │   └── per_extras_old/
│       ├── rrhh/
│       │   └── personal/
│       └── popups/
└── img/
    ├── escudos/
    ├── grado_reserva/
    ├── ico/
    ├── icon/
    ├── orig/
    ├── prioridad/
    ├── rrhh/
    ├── so/
    └── tipo_equipo/
```

---

## Archivos por Directorio

### Raiz

| Archivo                  | Proposito                                                        |
|--------------------------|------------------------------------------------------------------|
| `index.php`              | Punto de entrada principal. Ruteo via parametro `?estado=N`      |
| `cron.php`               | Script de tareas programadas (importaciones, sincronizacion)     |
| `DOCUMENTACION.md`       | Documentacion completa del sistema (60+ lineas)                  |
| `README_MIGRACION.md`    | Guia de migracion para activacion v2.0                           |
| `sql_migracion_v2.sql`   | Script SQL de migracion de BD                                    |

### config/ - Configuracion

| Archivo        | Proposito                                                                      |
|----------------|--------------------------------------------------------------------------------|
| `config.php`   | Constantes (APP_SISTEMA='AICO_666'), rutas, URLs, tamanio de pagina            |
| `database.php` | Singleton Database con MySQLi + wrappers legacy para compatibilidad            |
| `session.php`  | Validacion de sesion (cookies seguras, verificacion IP/UA), timezone            |

### includes/ - Componentes compartidos

| Archivo          | Proposito                                                                    |
|------------------|------------------------------------------------------------------------------|
| `functions.php`  | Cargador central: importa config, database, helpers. Funciones utilitarias   |
| `helpers.php`    | Funciones UI: generarAlertJS(), generarConfirmJS(), generadores de tablas/badges |
| `header.php`     | HTML head con Bootstrap 5.3 CDN, Bootstrap Icons, SweetAlert2, CSS local     |
| `navbar.php`     | Navbar responsive con menu dinamico desde tabla more_menus                    |
| `footer.php`     | Footer sticky con copyright e info de usuario via herramienta_pie()          |

### assets/ - Frontend

| Archivo            | Proposito                                                                 |
|--------------------|---------------------------------------------------------------------------|
| `css/styles.css`   | CSS con variables Bootstrap (--app-primary: #418CBA), layout base         |
| `js/app.js`        | Namespace JS MORE: SweetAlert2 wrappers, tooltips, form helpers           |

### web/int/ - Paginas internas y utilidades

| Archivo                | Proposito                                          |
|------------------------|----------------------------------------------------|
| `encabezado.php`       | Componente de layout de encabezado                 |
| `menu.php`             | Estilos CSS dinamicos del menu                     |
| `pie.php`              | Template de pie de pagina                          |
| `presentacion.php`     | Capa de presentacion                               |
| `script_manager.php`   | Cargador de scripts con soporte legacy jQuery/DataTables |
| `salir.php`            | Handler de cierre de sesion                        |

### web/int/ajax/ - Endpoints AJAX (13 archivos)

| Archivo                    | Proposito                                            |
|----------------------------|------------------------------------------------------|
| `aj_listar_plantel.php`    | Lista de personal via AJAX                           |
| `listar_plantel.json`      | Lista de personal en formato JSON                    |
| `AlumnosCursos.php`        | Integracion modulo educativo - alumnos/cursos        |
| `DocCursos.php`            | Integracion modulo educativo - documentos/cursos     |
| `DocMaterias.php`          | Integracion modulo educativo - documentos/materias   |
| `consulta_alumnos.php`     | Consulta de alumnos                                  |
| `envio_msg.php`            | Envio de mensajes (usa vw_personas, celular/email)   |
| `gast_inversiones.php`     | Seguimiento de gastos/inversiones                    |
| `horasCursos.php`          | Gestion horas educativas - cursos                    |
| `horasMaterias.php`        | Gestion horas educativas - materias                  |
| `imagen_legajos.php`       | Subida de fotos de personal                          |
| `seg_ajax.php`             | Validacion de seguridad AJAX                         |

### web/cron/ - Tareas automatizadas

| Archivo                  | Proposito                                          |
|--------------------------|------------------------------------------------------|
| `activos.php`            | Sincronizacion de equipos activos con more_crontab   |
| `activos_hs_justa.php`   | Justificacion de horas de equipos                    |
| `externos.php`           | Importacion de activos externos                      |

### web/mod/inicio/ - Dashboard

| Archivo          | Proposito                                                              |
|------------------|------------------------------------------------------------------------|
| `dashboard.php`  | Dashboard con stats (total_pc, total_articulos, total_personal, total_asignados, movimientos recientes) |
| `listar.php`     | Listado general                                                        |

### web/mod/inicio/prestamos/ - Gestion de Prestamos

| Archivo          | Proposito                                    |
|------------------|----------------------------------------------|
| `listar.php`     | Lista de prestamos                           |
| `nuevo.php`      | Alta de prestamo                             |
| `detalles.php`   | Vista detalle de prestamo                    |
| `cargador.php`   | Handler POST para prestamos                  |

### web/mod/inicio/reparaciones/ - Gestion de Reparaciones

| Archivo          | Proposito                                    |
|------------------|----------------------------------------------|
| `listar.php`     | Lista de reparaciones                        |
| `nueva.php`      | Alta de reparacion                           |
| `detalles.php`   | Vista detalle de reparacion                  |
| `cargador.php`   | Handler POST para reparaciones               |

### web/mod/inventario/pc/ - Inventario de PCs

| Archivo            | Proposito                                              |
|--------------------|--------------------------------------------------------|
| `listar.php`       | Listado de PCs (version original)                      |
| `listar_v2.php`    | Listado de PCs (v2 - UI moderna)                       |
| `nueva.php`        | Alta de PC                                             |
| `cargador.php`     | Handler POST para datos de PC                          |
| `cargador_1.php`   | Handler POST alternativo                               |
| `cargador_2.php`   | Handler POST alternativo                               |
| `resumen.php`      | Vista detalle de PC (version original)                 |
| `resumen_v2.php`   | Vista detalle de PC (v2 - UI moderna)                  |

### web/mod/inventario/pc/resumen/ - Tabs de detalle PC

| Archivo              | Proposito                                       |
|----------------------|-------------------------------------------------|
| `detalle.php`        | Detalles basicos                                |
| `morebius.php`       | Datos de BD Morebius                            |
| `elementos.php`      | Especificaciones del equipo                     |
| `ocs_inventory.php`  | Integracion OCS Inventory                       |
| `obligatorios.php`   | Campos requeridos                               |
| `comentarios.php`    | Notas y comentarios                             |
| `ariadna.php`        | Datos institucionales                           |
| `bancos.php`         | Datos bancarios                                 |
| `antecedentes.php`   | Antecedentes                                    |
| `educacion.php`      | Registros educativos                            |
| `documentacion.php`  | Documentacion                                   |
| `nombramientos.php`  | Nombramientos                                   |

### web/mod/inventario/ - Otros equipos

**Impresoras:**

| Archivo        | Proposito                          |
|----------------|------------------------------------|
| `listar.php`   | Listado de impresoras              |
| `resumen.php`  | Vista detalle de impresora         |

**Monitores:**

| Archivo        | Proposito                          |
|----------------|------------------------------------|
| `listar.php`   | Listado de monitores               |
| `resumen.php`  | Vista detalle de monitor           |

**Otros:**

| Archivo        | Proposito                          |
|----------------|------------------------------------|
| `listar.php`   | Listado de otros dispositivos      |
| `resumen.php`  | Vista detalle                      |

### web/mod/reportes/ - Reportes

| Archivo                      | Proposito                              |
|------------------------------|----------------------------------------|
| `listar.php`                 | Listado de reportes (original)         |
| `listar_v2.php`              | Listado de reportes (v2)               |
| `celulares/entregados.php`   | Reporte de celulares entregados        |
| `celulares/titulares.php`    | Reporte de celulares por titular       |

### web/mod/personal/ - Gestion de Personal

| Archivo                  | Proposito                                    |
|--------------------------|----------------------------------------------|
| `listar_plantel.php`     | Lista de personal (original)                 |
| `listar_plantel_v2.php`  | Lista de personal (v2)                       |
| `detalle_v2.php`         | Detalle de empleado (v2)                     |
| `resumen.php`            | Vista resumen del empleado                   |
| `editar.php`             | Edicion de datos del empleado                |

### web/mod/equipos/ - Equipos y Organigrama

| Archivo               | Proposito                                              |
|-----------------------|--------------------------------------------------------|
| `organigrama.php`     | Organigrama (query CTE recursiva) - original           |
| `organigrama_v2.php`  | Organigrama (v2)                                       |
| `xunidad_v2.php`      | Equipos por unidad (v2)                                |

**Notebook:**

| Archivo            | Proposito                              |
|--------------------|----------------------------------------|
| `xunidad.php`      | Notebooks por unidad                   |
| `xunidad_prod.php` | Notebooks por unidad (produccion)      |

**xunidad:**

| Archivo          | Proposito                              |
|------------------|----------------------------------------|
| `listar.php`     | Listado de equipos por unidad          |

### web/mod/admin/ - Administracion

**Marcas:**

| Archivo          | Proposito                              |
|------------------|----------------------------------------|
| `listar.php`     | Lista de marcas (original)             |
| `listar_v2.php`  | Lista de marcas (v2)                   |
| `nuevo.php`      | Alta de marca                          |

**Ubicacion:**

| Archivo          | Proposito                              |
|------------------|----------------------------------------|
| `listar.php`     | Lista de ubicaciones (original)        |
| `listar_v2.php`  | Lista de ubicaciones (v2)              |
| `nuevo.php`      | Alta de ubicacion                      |

**Sistemas Operativos (so/):**

| Archivo          | Proposito                              |
|------------------|----------------------------------------|
| `listar.php`     | Lista de SO (original)                 |
| `listar_v2.php`  | Lista de SO (v2)                       |
| `nuevo.php`      | Alta de SO                             |

**Sectores:**

| Archivo          | Proposito                              |
|------------------|----------------------------------------|
| `listar.php`     | Lista de sectores (original)           |
| `listar_v2.php`  | Lista de sectores (v2)                 |

**Tipos de Impresoras:**

| Archivo          | Proposito                              |
|------------------|----------------------------------------|
| `listar.php`     | Lista de tipos (original)              |
| `listar_v2.php`  | Lista de tipos (v2)                    |
| `nueva.php`      | Alta de tipo de impresora              |

**Zonas:**

| Archivo          | Proposito                              |
|------------------|----------------------------------------|
| `listar.php`     | Lista de zonas                         |
| `nueva.php`      | Alta de zona                           |

**Proveedores:**

| Archivo          | Proposito                              |
|------------------|----------------------------------------|
| `listar.php`     | Lista de proveedores                   |
| `nuevo.php`      | Alta de proveedor                      |

**Tablas Externas:**

| Archivo                      | Proposito                                    |
|------------------------------|----------------------------------------------|
| `listar.php`                 | Listado principal (original)                 |
| `listar_v2.php`              | Listado principal (v2)                       |
| `excepciones.php`            | Gestion de excepciones                       |
| `excepciones_listar.php`     | Listado de excepciones                       |
| `rrhh_extras.php`            | Extras de RRHH                               |
| `v_all_users.php`            | Vista de todos los usuarios                  |
| `v_host_user_relation.php`   | Vista relacion host-usuario                  |
| `v_hosts.php`                | Vista de hosts                               |
| `v_hst_loggedin_users.php`   | Vista usuarios logueados por host            |

**Per Extras (Extras de Personal):**

| Archivo                  | Proposito                              |
|--------------------------|----------------------------------------|
| `listar.php`             | Listado de extras                      |
| `actualizar.php`         | Actualizar extras                      |
| `excepciones.php`        | Gestion de excepciones                 |
| `excepciones_listar.php` | Listado de excepciones                 |

### web/mod/rrhh/personal/ - RRHH

| Archivo                  | Proposito                              |
|--------------------------|----------------------------------------|
| `listar.php`             | Listado de personal con busqueda       |
| `resumen.php`            | Resumen del empleado                   |
| `editar.php`             | Edicion de datos                       |
| `actualizar.php`         | Handler de actualizacion               |
| `excepciones.php`        | Gestion de excepciones                 |
| `excepciones_listar.php` | Listado de excepciones                 |

### web/mod/popups/ - Modales

| Archivo                  | Proposito                              |
|--------------------------|----------------------------------------|
| `ver_articulo.php`       | Ver detalle de articulo                |
| `editar_articulo.php`    | Editar articulo (con obtener_parametro)|
| `editar_devolucion.php`  | Editar devolucion                      |
| `const_prestamo.php`     | Confirmacion de prestamo               |
| `const_reparacion.php`   | Confirmacion de reparacion             |

---

## Archivos Legacy / Obsoletos

### Archivos _old (4):

| Archivo                                           | Tipo    |
|---------------------------------------------------|---------|
| `web/mod/inventario/pc/resumen/morebius_old.php`  | _old    |
| `web/mod/popups/editar_articulo_old.php`          | _old    |
| `img/ico/editar_arc_old.jpg`                      | _old    |
| `img/MEMBRETEPNA_OLD.png`                         | _old    |

### Archivos copy (8):

| Archivo                                              | Tipo    |
|------------------------------------------------------|---------|
| `web/mod/admin/per_extras/actualizar copy.php`       | copy    |
| `web/mod/admin/tablas_externas/actualizar copy.php`  | copy    |
| `web/mod/equipos/xunidad/listar copy.php`            | copy    |
| `web/mod/equipos/xunidad/organigrama copy.php`       | copy    |
| `web/mod/popups/editar_articulo copy.php`            | copy    |
| `web/mod/popups/ver_articulo copy.php`               | copy    |
| `web/mod/rrhh/personal/actualizar copy.php`          | copy    |

### Directorio legacy completo (1):

| Directorio                          | Contenido                    |
|-------------------------------------|------------------------------|
| `web/mod/admin/per_extras_old/`     | 5 archivos - version legacy completa del modulo per_extras |

### Archivos v2 (coexisten con version original) (13):

| Archivo                                        | Modulo           |
|------------------------------------------------|------------------|
| `web/mod/admin/marcas/listar_v2.php`           | Administracion   |
| `web/mod/admin/sectores/listar_v2.php`         | Administracion   |
| `web/mod/admin/so/listar_v2.php`               | Administracion   |
| `web/mod/admin/tipos_impresoras/listar_v2.php` | Administracion   |
| `web/mod/admin/ubicacion/listar_v2.php`        | Administracion   |
| `web/mod/admin/tablas_externas/listar_v2.php`  | Administracion   |
| `web/mod/equipos/organigrama_v2.php`           | Equipos          |
| `web/mod/equipos/xunidad_v2.php`               | Equipos          |
| `web/mod/inventario/pc/listar_v2.php`          | Inventario       |
| `web/mod/inventario/pc/resumen_v2.php`         | Inventario       |
| `web/mod/personal/detalle_v2.php`              | Personal         |
| `web/mod/personal/listar_plantel_v2.php`       | Personal         |
| `web/mod/reportes/listar_v2.php`               | Reportes         |

---

## Imagenes por Directorio

| Directorio          | Cantidad | Contenido                              |
|---------------------|----------|----------------------------------------|
| `img/rrhh/`         | 566      | Cache de fotos de empleados            |
| `img/ico/`          | 66       | Iconos de estado y acciones            |
| `img/icon/`         | 38       | Iconos de UI                           |
| `img/so/`           | 54       | Logos de sistemas operativos           |
| `img/prioridad/`    | 20       | Indicadores de prioridad               |
| `img/escudos/`      | 21       | Escudos institucionales                |
| `img/orig/`         | 22       | Archivos originales                    |
| `img/tipo_equipo/`  | 12       | Iconos de tipos de equipo              |
| `img/grado_reserva/`| 10       | Imagenes de grados                     |
| `img/` (raiz)       | 58       | Fondos, encabezados, banners           |

---

## Patrones Arquitectonicos

1. **Database Singleton** - Patron con fallback a wrappers legacy (sql_do_query, sql_do_array)
2. **Bootstrap 5.3** - CDN + SweetAlert2 para UX moderna
3. **Migracion v2** - Archivos v2 paralelos a versiones legacy para modernizacion gradual
4. **Modelo de Sesion** - Usa `$_SESSION['AICO_666']` para nivel de permiso
5. **Ruteo por Estado** - index.php usa parametro `?estado=N` para cargar modulos
6. **Coexistencia Legacy** - Directorio per_extras_old/ intacto junto a version nueva
7. **13 Endpoints AJAX** - En web/int/ajax/ para operaciones asincronas
8. **Menu Dinamico** - Tabla more_menus alimenta el navbar con niveles de acceso (nivacc)
