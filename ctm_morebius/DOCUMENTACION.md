# Morebius — Sistema de Inventario IT

## 1. Descripción General

**Morebius** es el módulo de gestión de inventario IT y asignación de equipos del sistema **Contexto Unificado**. Permite llevar un seguimiento completo de equipos informáticos (PCs, notebooks, monitores, impresoras), artículos de comunicación (celulares, líneas, módems) y su asignación al personal de la organización.

| Dato | Valor |
|------|-------|
| **Código de sistema** | `AICO_666` |
| **Variable de sesión** | `$_SESSION['AICO_666']` (nivel de acceso) |
| **Tabla de menú** | `more_menus` (en `contexto_unificado`) |
| **URL** | `/ctm/contexto_unificado/ctm_morebius/index.php` |
| **Tecnologías** | PHP 8.2, MariaDB 10.4, Bootstrap 5.3, SweetAlert2, Bootstrap Icons |

---

## 2. Arquitectura

```
ctm_morebius/
├── config/                      ← Configuración y conexión
│   ├── config.php               ← Constantes, paths, DB configs
│   ├── database.php             ← Singleton Database + wrappers legacy
│   └── session.php              ← Sesión segura + permisos
│
├── includes/                    ← Infraestructura compartida
│   ├── functions.php            ← Carga central (config→db→helpers) + utilidades
│   ├── helpers.php              ← Componentes UI (SweetAlert2, tablas, badges)
│   ├── header.php               ← <head> HTML con CDNs
│   ├── navbar.php               ← Menú dinámico desde more_menus + breadcrumb
│   └── footer.php               ← Footer sticky con info de usuario
│
├── assets/
│   ├── css/styles.css           ← Estilos unificados del módulo
│   └── js/app.js                ← JS (namespace MORE)
│
├── img/rrhh/                    ← Cache local de fotos de empleados
│
├── index.php                    ← Punto de entrada (ruteo por ?estado=N)
├── cron.php                     ← Tarea programada (sync plantel/inventario)
│
├── web/
│   ├── int/                     ← Archivos internos (ajax, presentación, menú legacy)
│   ├── cron/                    ← Scripts de importación (activos, externos)
│   └── mod/                     ← Páginas del módulo (ver sección 5)
│
├── sql_migracion_v2.sql         ← SQL de actualización de menús
├── README_MIGRACION.md          ← Guía de activación
└── DOCUMENTACION.md             ← Este archivo
```

### Flujo de carga

```
index.php
  └─ includes/functions.php
       ├─ config/config.php     (constantes, getDbConfig)
       ├─ config/database.php   (Singleton + wrappers)
       └─ includes/helpers.php  (UI components)
  └─ config/session.php         (validación + permisos)
  └─ includes/header.php        (HTML <head>)
  └─ includes/navbar.php        (menú dinámico)
  └─ web/mod/{pagina}.php       (contenido según ?estado=N)
  └─ includes/footer.php        (pie + JS)
```

---

## 3. Bases de Datos

El sistema utiliza **dos bases de datos**:

### 3.1. `morebius` (DB '2') — SOLO LECTURA

Base de datos principal del inventario. **No se modifica desde el código**; se alimenta por procesos de importación (CRON, RRHH).

#### `ctm_inventario` (3128 registros) — Equipos del inventario
Registro de cada equipo físico (PC, notebook, monitor, impresora, etc.).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int PK | ID interno |
| `codigo` | varchar(100) | Código patrimonial / etiqueta del equipo |
| `producto` | varchar(50) | Tipo general (PC, NB, MON, etc.) |
| `tipo` | varchar(50) | Subtipo descriptivo |
| `detalle` | varchar(500) | Descripción completa |
| `marca` | varchar(50) | Marca del equipo |
| `modelo` | varchar(50) | Modelo |
| `numeroserie` | varchar(50) | Número de serie |
| `legajo` | varchar(50) | Legajo del empleado asignado |
| `oc` | varchar(50) | Orden de compra |
| `sr` | varchar(50) | Solicitud de reparación |
| `pcc` | varchar(50) | Pedido de compra central |
| `fechacompra` | datetime | Fecha de compra |
| `fechavtogarantia` | datetime | Vencimiento de garantía |
| `baja` | bit(1) | 1 = dado de baja |
| `fch_baja` | datetime | Fecha de baja |
| `proveedor_nombre` | varchar(100) | Nombre del proveedor |
| `proveedor_direccion` | varchar(200) | Dirección |
| `proveedor_telefono` | varchar(50) | Teléfono |
| `proveedor_email1/2/3` | varchar(100) | Emails de contacto |

#### `articulos_con` (1768 registros) — Artículos asignados
Artículos individuales (celulares, líneas, módems, notebooks) asignados a personas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `nro_art` | int PK | Número de artículo |
| `fecha` | datetime | Fecha de asignación |
| `tipo_art` | smallint FK→articulos_tipos | Tipo de artículo |
| `detalle` | varchar(1024) | Descripción |
| `legajo_art` | int | Legajo del titular |
| `devuelto` | tinyint | 1 = devuelto |
| `DE_BAJA` | tinyint | 1 = dado de baja |
| `Nro_Linea` | varchar(20) | Número de línea telefónica |
| `MARCA` | smallint FK→artimarcas | Marca |
| `MODELO` | varchar(20) | Modelo |
| `NSERIE` | varchar(30) | Número de serie |
| `IMEI` | varchar(20) | IMEI del dispositivo |
| `UbicaId` | smallint FK→artcelubi | Ubicación |
| `TipCel` | smallint FK→artipcel | Tipo de dispositivo |
| `GARANTIA_VENCE` | datetime | Vencimiento garantía |

#### `articulos_his` (529 registros) — Historial de asignaciones
Cada vez que un artículo cambia de titular, se genera un registro histórico.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `ordinal` | int PK | ID |
| `nro_art` | int FK→articulos_con | Artículo |
| `fecha_des` / `fecha_has` | datetime | Período de asignación |
| `legajo_art` | int | Legajo del titular anterior |
| `nota_devolucion` | varchar(250) | Nota de devolución |

#### `ctm_plantel` (554 registros) — Personal
Datos de empleados importados desde RRHH.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `EmpLegajo` | int PK | Legajo |
| `EmpNombre` | varchar(50) | Nombre completo |
| `EmpCargoNom` | varchar(100) | Cargo |
| `EmpCentroCostoCod` | int | Código centro de costo |
| `EmpCentroCostoNom` | varchar(60) | Centro de costo |
| `EmpGerenciaNom` | varchar(60) | Gerencia |
| `EmpEmail` | varchar(100) | Email |
| `EmpFchAlta` | datetime | Fecha de alta |
| `Situacion_de_Revista` | varchar(60) | ACTIVO / BAJA / LICENCIA |
| `EmpDocDNI` | varchar(125) | DNI |
| `EmpSexo` | varchar(1) | M/F |

#### `ctm_arbol` (688 registros) — Organigrama
Estructura jerárquica de la organización (sectores, gerencias, personas).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `pkey` | varchar(8) PK | Clave del nodo |
| `padre` | varchar(8) | Clave del nodo padre |
| `nsector` | int | Código de sector |
| `persona` | int | 1 = es persona, 0 = es sector |
| `legajo` | int | Legajo (si es persona) |
| `nivel` | int | Profundidad en el árbol |
| `jefe` | int | 1 = es jefe del sector |

#### Tablas de referencia

| Tabla | Registros | Descripción |
|-------|-----------|-------------|
| `articulos_tipos` | 5 | Tipos: Celular, Notebook, Modem, Línea, Tablet |
| `artimarcas` | 13 | Marcas: Samsung, Apple, Motorola, etc. |
| `artipcel` | 6 | Tipos de dispositivo |
| `artcelubi` | 37 | Ubicaciones físicas |
| `artiplanes` | 3 | Planes de celular |
| `artiestaciones` | 83 | Estaciones telefónicas |
| `ctm_centroscosto` | 160 | Centros de costo / sectores |
| `ctm_cargos` | 473 | Cargos del personal |

---

### 3.2. `contexto_unificado` (DB '0'/'1') — LECTURA/ESCRITURA

Tablas del sistema de navegación, configuración y datos externos.

#### `more_menus` (57 registros) — Menú y navegación
Define la estructura de menú, permisos y ruteo de páginas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | decimal PK | ID del menú (= valor de `?estado=`) |
| `opcion` | varchar(20) | Texto del menú |
| `estado` | varchar(4) | '1' = activo, '0' = oculto/popup |
| `direccion` | varchar(100) | Ruta al archivo PHP |
| `submenu` | varchar(4) | ID del padre (NULL = top-level) |
| `menu` | varchar(4) | 1 = menú top, 2 = submenú, 3 = popup, 20 = tab |
| `nivacc` | decimal(2) | Nivel mínimo de acceso (1=consulta, 9=admin) |

#### `more_id` (12 registros) — Secuencias
Generador de IDs secuenciales por tabla.

#### Tablas de configuración

| Tabla | Registros | Descripción |
|-------|-----------|-------------|
| `more_estados` | 2 | Estados genéricos (Activo/Inactivo) |
| `more_tipos_equipos` | 6 | Tipos de equipos para reglas |
| `more_tipos_prestamos` | 5 | Tipos de préstamo |
| `more_gamas_equipos` | 65 | Gamas/categorías de equipos |
| `more_personal_pc` | 2 | Relación persona-PC |
| `more_personal_exceptuado` | 46 | Excepciones a reglas de asignación |
| `more_paises` | 2 | Países (AR, UY) |

#### Tablas externas (importadas por CRON)

| Tabla | Registros | Origen | Descripción |
|-------|-----------|--------|-------------|
| `more_v_all_users` | 4063 | Kaspersky | Usuarios del dominio |
| `more_v_hosts` | 1199 | Kaspersky | Equipos (hosts) registrados |
| `more_v_host_user_relation` | 6415 | Kaspersky | Relación host↔usuario |
| `more_v_hst_loggedin_users` | 801 | Kaspersky | Último logueo por host |
| `more_consulta_externa` | — | RRHH | Datos externos de personal |

---

## 4. Niveles de Acceso

| Nivel | Código | Descripción |
|-------|--------|-------------|
| 1 | CONSULTA | Solo lectura de listados y detalles |
| 4 | DCIA | Dirección — lectura ampliada |
| 6 | ABM | Alta/Baja/Modificación de artículos y equipos |
| 9 | ADMIN | Acceso total incluyendo administración y tablas externas |

El nivel se almacena en `$_SESSION['AICO_666']` al ingresar al módulo y se valida contra `more_menus.nivacc` para cada página.

---

## 5. Páginas del Módulo

### 5.1. Dashboard (`?estado=1`)
**Archivo:** `web/mod/inicio/dashboard.php`

Pantalla principal con:
- **4 tarjetas de estadísticas**: equipos en inventario, artículos activos, asignaciones vigentes, personal — cada una es link a su listado
- **Últimos movimientos**: 10 artículos más recientes con foto del titular
- **Distribución por tipo**: desglose de artículos por categoría

### 5.2. Inventario de Equipos (`?estado=11`)
**Archivo:** `web/mod/inventario/pc/listar_v2.php`

Lista completa de equipos del inventario (`ctm_inventario`) con:
- Búsqueda por código, marca, modelo, serie, tipo, legajo
- Badge de estado (Activo/Baja) y alerta de garantía vencida
- Foto del empleado asignado con link a su detalle
- Link en el código del equipo al detalle del equipo
- Botón "Resumen" por equipo

### 5.3. Detalle de Equipo (`?estado=24`)
**Archivo:** `web/mod/inventario/pc/resumen_v2.php`

Ficha completa de un equipo con:
- **Card izquierdo**: código, tipo, marca, modelo, serie, OC, SR, PCC, fechas, garantía, estado
- **Card derecho superior**: foto + datos del empleado asignado (con link a detalle persona)
- **Card derecho inferior**: datos del proveedor
- Botón volver

### 5.4. Artículos / Reportes (`?estado=2`)
**Archivo:** `web/mod/reportes/listar_v2.php`

Lista de artículos (`articulos_con`) — celulares, notebooks, módems, líneas:
- Filtro por tipo de artículo (combo) + búsqueda libre
- Columnas: número, tipo, detalle, marca/modelo, serie, línea, asignado a (con foto), estado
- Badges: activo/baja, devuelto/vigente

### 5.5. Personal / Plantel (`?estado=4`)
**Archivo:** `web/mod/personal/listar_plantel_v2.php`

Lista del personal con equipos:
- Búsqueda por nombre, legajo, cargo, centro de costo, email, DNI
- Columnas: foto, legajo, nombre, cargo, centro de costo, gerencia, email, equipos (badge con cantidad), fecha alta
- Click en foto/nombre/legajo/badge → detalle de persona

### 5.6. Detalle de Persona (`?estado=44`)
**Archivo:** `web/mod/personal/detalle_v2.php`

Ficha completa de un empleado:
- **Card izquierdo**: foto grande (80px), legajo, DNI, cargo, centro de costo, gerencia, email, situación, fecha alta
- **Resumen rápido**: 2 stat cards (artículos y equipos de inventario asignados)
- **Tabla de artículos**: todos los artículos asignados (`articulos_con`) con tipo, marca, serie, IMEI, fecha, estado
- **Tabla de equipos**: equipos de inventario (`ctm_inventario`) con código (link a detalle equipo), tipo, marca, serie, garantía, estado

### 5.7. Organigrama (`?estado=119`)
**Archivo:** `web/mod/equipos/organigrama_v2.php`

Organigrama jerárquico basado en `ctm_arbol`:
- Query recursiva (CTE) para recorrer el árbol
- Selector de nodo raíz para explorar ramas específicas
- Visualización indentada con iconos de sector/persona/jefe
- Muestra legajo y nombre de personas

### 5.8. Equipos por Unidad (`?estado=185`)
**Archivo:** `web/mod/equipos/xunidad_v2.php`

Artículos agrupados por centro de costo:
- Selector de centro de costo (filtro)
- Tabla resumen: top 15 unidades con más equipos
- Tabla detallada: artículos del CC seleccionado con foto del titular

### 5.9. Páginas de Administración (nivel 9)

| Estado | Archivo | Descripción |
|--------|---------|-------------|
| 30 | `admin/marcas/listar_v2.php` | Marcas de artículos (`artimarcas`) |
| 31 | `admin/so/listar_v2.php` | Tipos de artículo (`articulos_tipos`) |
| 32 | `admin/ubicacion/listar_v2.php` | Ubicaciones (`artcelubi`) |
| 33 | `admin/tipos_impresoras/listar_v2.php` | Tipos de dispositivo (`artipcel`) |
| 34 | `admin/tablas_externas/listar_v2.php` | Vista de tablas externas con conteo |
| 42 | `admin/sectores/listar_v2.php` | Centros de costo (`ctm_centroscosto`) |

---

## 6. Uso Normal — Flujos de Trabajo

### 6.1. Consultar equipos de una persona
1. Ir a **Personal** (menú)
2. Buscar al empleado por nombre o legajo
3. Click en su **nombre**, **legajo** o **badge de equipos**
4. Se abre el **Detalle de Persona** con la lista de artículos y equipos

### 6.2. Consultar detalle de un equipo
1. Ir a **Inventario** (desde menú o dashboard)
2. Buscar por código, marca o serie
3. Click en el **código del equipo**
4. Se abre el **Detalle del Equipo** con info completa, asignación y proveedor

### 6.3. Ver artículos (celulares, líneas, módems)
1. Ir a **Reportes** (menú)
2. Filtrar por tipo (Celular, Notebook, etc.) o buscar texto
3. La tabla muestra todos los artículos con su estado y titular
4. Click en el nombre del titular para ver su ficha completa

### 6.4. Ver el organigrama
1. Ir a **Equipos** → **Organigrama**
2. Seleccionar un nodo raíz (sector o gerencia)
3. El árbol se despliega mostrando la jerarquía con personas

### 6.5. Ver equipos por sector
1. Ir a **Equipos** → **Equipos x Unidad**
2. Seleccionar un centro de costo
3. Se muestra la tabla de artículos asignados a ese sector

### 6.6. Administración (nivel 9)
1. Ir a **Administración** → seleccionar tabla (Marcas, Ubicaciones, Tipos, etc.)
2. Se muestra la lista con búsqueda
3. Las tablas son de consulta (la base `morebius` es de solo lectura)

---

## 7. Fotos de Empleados

Las fotos se obtienen del servidor de imágenes RRHH:
- **URL base**: `https://intranet.saltogrande.org/imagenes/img_personal/`
- **Prioridad**: `hi_res/{legajo}.jpg` → `{legajo} - {nombre}.jpg` → `rrhh_default.png`
- **Cache local**: `img/rrhh/{legajo}.jpg` (se descarga una vez y se reutiliza)
- **Función**: `fotoEmpleado($legajo, $nombre, $servidor_img, $size)` genera el HTML

---

## 8. Procesos Automáticos (CRON)

| Archivo | Descripción |
|---------|-------------|
| `cron.php` | Orquestador principal |
| `web/cron/activos.php` | Importa plantel activo desde RRHH a `ctm_plantel` |
| `web/cron/activos_hs_justa.php` | Variante con horarios ajustados |
| `web/cron/externos.php` | Importa datos de fuentes externas (Kaspersky, RRHH extras) |

---

## 9. Conexiones a Base de Datos

Configuradas en `config/config.php` mediante `getDbConfig($db)`:

| Alias | Base | Uso |
|-------|------|-----|
| `'0'` | `contexto_unificado` | Menús, estados, tipos |
| `'1'` | `contexto_unificado` | Vistas Kaspersky, tablas more_* |
| `'2'` | `morebius` | Inventario, plantel, artículos (**read-only**) |
| `'3'` | `inventario` | Libro de guardia (servidor ODBC) |
| `'4'` | `gedb` | Base externa CTMGAG |

---

## 10. Navegación — Mapa de Menú

```
INICIO (estado=1) ─────────────── Dashboard
REPORTES (estado=2) ───────────── Artículos
  ├── Celulares ────────────────── Resumen celulares
  │   ├── Titulares ───────────── Titulares de celulares
  │   └── Entregados ──────────── Celulares entregados
  ├── Impresoras ──────────────── Listado impresoras
  └── Otros ───────────────────── Otros artículos
ADMINISTRACIÓN (estado=3) ─────── [solo nivel 9]
  ├── Marcas ──────────────────── Listado marcas
  ├── Tipos Artículo ──────────── Tipos de artículo
  ├── Ubicación ───────────────── Ubicaciones
  ├── Tipos Dispositivo ───────── Tipos de dispositivo
  ├── Tablas EXT ──────────────── Tablas externas (KASP, RRHH)
  │   ├── KASP Usuarios
  │   ├── KASP Logueos
  │   ├── KASP Equipos
  │   ├── KASP Ultimo Logueo
  │   └── RRHH Extras
  ├── Excepciones ─────────────── Personal exceptuado
  └── Sectores ────────────────── Centros de costo
PERSONAL (estado=4) ───────────── Plantel
  ├── Celulares ───────────────── Personal con celulares
  └── Plantel ─────────────────── Listado de personal
EQUIPOS (estado=5)
  ├── Notebook ────────────────── [presentación]
  │   └── Equipos x Unidad ────── Equipos por centro de costo
  └── Organigrama ─────────────── Organigrama jerárquico
UNIDADES (estado=8)
  ├── Notebook ────────────────── [presentación]
  ├── Organigrama ─────────────── Organigrama
  └── Plantel ─────────────────── Listado de personal
```

---

## 11. Dependencias Externas

| Recurso | Versión | CDN |
|---------|---------|-----|
| Bootstrap CSS | 5.3.2 | `cdn.jsdelivr.net/npm/bootstrap@5.3.2` |
| Bootstrap JS | 5.3.2 | `cdn.jsdelivr.net/npm/bootstrap@5.3.2` |
| Bootstrap Icons | 1.11.3 | `cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3` |
| SweetAlert2 | 11 | `cdn.jsdelivr.net/npm/sweetalert2@11` |

No se requiere `npm install` ni proceso de build. Todo se carga por CDN.
