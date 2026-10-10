# PROMPT PARA GENERAR EL CLAUDE.md DEL EQUIPO CTM

Copia todo este archivo como primer mensaje de una sesión de Claude Code abierta en la raíz del repositorio
`ctm_contexto_unificado`. El resultado es un `CLAUDE.md` en la raíz (y, si hace falta, uno corto por módulo)
que todo el equipo comparte para que las personas y Claude trabajen igual.

---

## 1. TU TAREA

Eres el arquitecto del sistema CTM (Contexto Unificado). Tu tarea es escribir el archivo `CLAUDE.md` que
guiará a todo el equipo y a Claude Code cuando trabajen en este repositorio.

El `CLAUDE.md` debe permitir tres cosas:

1. **Entender el sistema** en 5 minutos: qué hace, cómo está armado y qué reglas se respetan.
2. **Replicar un módulo o un dominio nuevo** copiando la estructura común sin tener que adivinar nada.
3. **Diseñar con DDD (Domain-Driven Design)** usando el estilo del equipo: PHP procedural, funciones simples, sin clases.

### Cómo trabajar

1. **Verifica antes de escribir.** Todo lo que aparece en las secciones 3 a 6 se tomó del código, pero puede haber cambiado. Abre cada archivo citado y confirma la información. Si algo no coincide, manda el código y anota la diferencia.
2. **Compara los cuatro módulos.** Para cada archivo común, revisa sus cuatro versiones (`ctm_entorno`, `ctm_inventarios`, `ctm_morebius`, `ctm_presupuestos`). Documenta la parte común como plantilla y las diferencias como variantes.
3. **No inventes.** Si un dato no está en el código ni en este prompt, escríbelo como pregunta abierta al final del `CLAUDE.md`.
4. **Nunca copies secretos.** Ni contraseñas, ni usuarios de base, ni cuentas de correo. En los ejemplos usa `'***'`.
5. **Escribe en español**, con frases cortas, tablas para comparar y bloques de código para las plantillas.
6. Al terminar, muestra el `CLAUDE.md` y lista las preguntas abiertas para que el equipo las responda.

---

## 2. ESTRUCTURA QUE DEBE TENER EL CLAUDE.md

Usa exactamente estas secciones, en este orden:

1. **Qué es CTM**: un párrafo y una tabla de módulos (código de sistema, prefijo de tablas, objeto JS, propósito).
2. **Reglas de oro**: entre 10 y 15 reglas cortas que nunca se rompen (ver sección 7 de este prompt).
3. **Stack y entorno**: versiones, rutas locales, URL de prueba, bases de datos.
4. **Anatomía de un módulo**: el árbol de carpetas y una tabla con cada archivo común (qué hace, qué contiene, qué se cambia al replicarlo).
5. **Flujo de una petición**: desde el login en `ctm_entorno` hasta la página incluida por `index.php?estado=N`.
6. **Convenciones**: nombres, prefijos, claves de fila en MAYÚSCULAS, auditoría, fechas, mensajes y comentarios.
7. **DDD en CTM**: los principios explicados y traducidos a funciones PHP (ver sección 6).
8. **Receta: crear un módulo nuevo** (checklist numerada).
9. **Receta: agregar un dominio o funcionalidad a un módulo existente** (checklist numerada).
10. **Integración entre módulos**: `integ_eventos`, vistas `vw_integ_*`, funciones `integ_*`.
11. **Anti-patrones que no se copian**: lo que existe en el código legacy pero no se reproduce.
12. **Glosario del negocio** (lenguaje ubicuo).
13. **Preguntas abiertas**.

Largo orientativo: entre 400 y 700 líneas. Si una sección crece demasiado, crea un `CLAUDE.md` corto dentro de ese módulo y enlázalo.

---

## 3. CONTEXTO DEL SISTEMA (verificar)

CTM es un sistema web en PHP que gestiona el ciclo completo del equipamiento IT de la organización:
presupuesto y compra, inventario, asignación al personal, préstamos y reparaciones.
Corre sobre XAMPP en `D:\xampp\htdocs\ctm\contexto_unificado\` (URL `http://localhost/ctm/contexto_unificado/`).

| Módulo | Código de sistema | Prefijo de tablas | Objeto JS | Propósito |
|---|---|---|---|---|
| `ctm_entorno` | — (marco) | `usu_*` | — | Login con captcha, autenticación SOAP con respaldo local, topbar y menú lateral de sistemas, iframe que contiene a los demás módulos, estilos de los 50 sistemas, librería de integración |
| `ctm_inventarios` | `CTM_062` | `inve_*` | `INVE` | Inventario de PCs, impresoras, monitores y otros; préstamos y reparaciones |
| `ctm_morebius` | `AICO_666` | `more_*` (+ base `morebius` de solo lectura) | `MORE` | Asignación de equipos al personal, organigrama, celulares, equipos por unidad |
| `ctm_presupuestos` | `CTM_055` o `CTM_061` (confirmar) | `ciclo_*`, `pres_*`, `pres_k2b_*` | `PRES` | Ciclo presupuestario borrador → revisión (3 niveles) → aprobación → ejecución → cierre; solicitudes K2B, EETT e IA local (Ollama) |

**Stack común**: PHP 8.2+ procedural, MariaDB 10.4 / MySQL (mysqli), Bootstrap 5.3.2, Bootstrap Icons 1.11.3,
SweetAlert2 11 (todo por CDN, en el `<head>`), PHPMailer, PHPWord (composer, solo presupuestos), Python con
Selenium + BeautifulSoup para los robots K2B.

**Bases de datos** (función `getDbConfig($db)` en `config/config.php`). Las claves cambian de un módulo a otro:

| Módulo | `'0'`, `'1'` | `'2'` | `'3'` | `'4'` |
|---|---|---|---|---|
| `ctm_inventarios` | `contexto_unificado` (lectura y escritura) | `ctm_ocsweb` (OCS, solo lectura) | `morebius` (solo lectura) | `inventario` (legacy) |
| `ctm_morebius` | `contexto_unificado` | `morebius` (solo lectura) | `inventario` | `gedb` |
| `ctm_entorno` | usa claves `default`, `db1`, `db2`, todas sobre `contexto_unificado` | | | |

El `CLAUDE.md` debe completar esta tabla para Presupuestos y proponer unificar las claves.

---

## 4. ANATOMÍA DE UN MÓDULO (archivos comunes)

Todos los módulos se migraron en 2026 desde una carpeta legacy `conf/` a esta estructura:

```
ctm_<modulo>/
├── index.php                 Punto de entrada: carga functions → session → header → navbar → página → footer
├── popups_manager.php        (opcional) igual que index.php pero sin navbar, para ventanas emergentes
├── config/
│   ├── config.php            Constantes APP_*, rutas, URL, getDbConfig(), conf_COM() (correo)
│   ├── database.php          Conexión mysqli única por base + wrappers sql_do_query / sql_do_array / registros
│   └── session.php           Validación de sesión, variables $usu, $nivel, $destino, $hoy; control de permiso por menú
├── includes/
│   ├── functions.php         Carga config + database + helpers + integracion.php; utilidades y combos
│   ├── helpers.php           Generadores de HTML: alertas SweetAlert2, tablas, botones, tarjetas, buscador
│   ├── header.php            <head> con los CDN y assets/css/styles.css
│   ├── navbar.php            Menú desde <prefijo>_menus filtrado por nivel + breadcrumb recursivo + resuelve $direcion
│   └── footer.php            Pie fijo (copyright, versión, usuario y nivel) + bootstrap.bundle + assets/js/app.js
├── assets/
│   ├── css/styles.css        Variables :root (--app-primary, --app-body-bg, --app-font-size...) y clases *-pade
│   └── js/app.js             Un objeto con el prefijo del módulo (INVE, MORE, PRES) con init, alert, confirm...
├── web/
│   ├── mod/<area>/<funcionalidad>/
│   │   ├── cargador.php      Funciones de consulta de esa funcionalidad (consultaPrestamo($parametros, $db, $usu))
│   │   ├── listar_v2.php     Listado con búsqueda
│   │   └── nuevo.php / nueva.php / detalles.php   Alta, edición, detalle
│   ├── int/                  Páginas internas: presentacion.php, salir.php
│   │   └── ajax/             Endpoints JSON: header JSON → functions.php → session.php → validar $usu → json_encode
│   └── cron/                 Tareas programadas (PHP o Python)
├── sql_*.sql                 Scripts de creación y migración (CREATE TABLE IF NOT EXISTS, vistas, menús)
├── README_MIGRACION.md       Qué cambió en la migración v2
└── DOCUMENTACION*.md         Documentación del módulo y de cada archivo
```

### Lo que hay que documentar de cada archivo común

Para cada archivo del árbol anterior, el `CLAUDE.md` debe tener:

- **Responsabilidad** en una línea.
- **Qué contiene** (funciones, variables o constantes que expone).
- **Qué se cambia al replicarlo** (por ejemplo: `APP_SISTEMA`, `APP_TITULO`, prefijo de tabla de menú, nombre del objeto JS, color).
- **Plantilla mínima** en un bloque de código para: `config.php`, `session.php`, `index.php`, un endpoint AJAX, un `cargador.php` y una página `listar`.

### Datos clave verificados en el código

- **Constantes de `config.php`**: `<PREFIJO>_APP`, `APP_SISTEMA`, `APP_TITULO`, `APP_NAME`, `APP_VERSION`, `APP_ROOT`, `APP_CONFIG`, `APP_INCLUDES`, `APP_ASSETS`, `APP_WEB`, `APP_URL`, más `$TAMANO_PAGINA = 20`. Todas con `if (!defined(...))`.
- **`database.php`**: la única clase del sistema (`Database::getInstance($db)`, una conexión por base). El resto del código usa los wrappers `sql_do_query($sql, $db, $usu)`, `sql_do_array($result)` y `registros($result)`. `sql_do_array` devuelve las claves en **MAYÚSCULAS** (`$row['NIVACC']`). `Database::execute()` ya soporta parámetros preparados, pero los wrappers no los usan.
- **`session.php`**: `session.use_only_cookies` y `cookie_httponly`; zona horaria `America/Argentina/Buenos_Aires`; invalida la sesión si cambian la IP o el User-Agent y redirige a `../ctm_entorno/default.php`; el nivel del usuario está en `$_SESSION[APP_SISTEMA]`.
- **Tabla de menú** (`inve_menus`, `more_menus`, `ciclo_menus`): `id`, `opcion`, `estado`, `direccion` (ruta del PHP a incluir), `submenu` (id del padre), `descripcion`, `menu` (`'1'` = principal, `'2'` = submenú), `nivacc` (nivel mínimo), `tipo`.
- **Ruteo**: `index.php?estado=N` → se busca `N` en la tabla de menú → se incluye `direccion`. En Morebius y Presupuestos `estado=10` cierra sesión; Inventarios no lo maneja en `index.php`.
- **Permisos**: `ctm_entorno` carga en `$_SESSION[<codigo_sistema>]` el `nro_priv` de cada sistema del usuario (`usu_user_sist`, `usu_sistemas`, `usu_sist_privilegio`). Cada módulo compara ese nivel con `nivacc`.
- **Identificadores**: `get_id($tabla)` usa una tabla de contadores (`inve_id`) en vez de `AUTO_INCREMENT`.
- **Auditoría**: las tablas de negocio tienen `auditoria_usu` y `auditoria_fecha`.
- **Integración**: `ctm_entorno/includes/integracion.php` (incluido desde `functions.php` de cada módulo) expone `integ_resumen_cc`, `integ_equipos_persona`, `integ_prestamos_activos`, `integ_reparaciones_activas`, `integ_verificar_presupuesto_compra`, `integ_registrar_evento`, `integ_obtener_eventos`, `integ_dashboard_resumen`, `integ_dashboard_alertas` e `integ_render_*`. Se apoya en la tabla `integ_eventos` y en 5 vistas `vw_integ_*` (`sql_integracion_modulos.sql`).
- **Estilo visual**: `ctm_entorno/config/modules.php` asigna a cada código de sistema uno de 50 estilos (color, gradiente, símbolo SVG, forma de icono). Un módulo nuevo necesita su entrada en `$MODULE_STYLE_MAP`.
- **IA**: `ctm_presupuestos/includes/ia_prompt_builder.php` y `config/ia/` (prompt de sistema y referencias en `.md`/`.txt`); funciones `ollama_chat()` y `ollama_generate()` contra `http://localhost:11434`.

### Diferencias entre módulos a revisar y documentar

- Orden de carga en `index.php`: Inventarios carga `session.php` **antes** de que `navbar.php` defina `$estado`, así que el control de `nivacc` de `session.php` no se ejecuta; Morebius y Presupuestos definen `$estado` antes. Confirmar y fijar un único orden en la plantilla.
- Dónde se resuelve `$direcion`: en `navbar.php` (Inventarios, Morebius) o en `index.php` (Presupuestos). Elegir uno para la plantilla.
- Qué hace cada módulo cuando no hay `estado`: va al dashboard (Inventarios) o redirige al login (Morebius, Presupuestos).
- Alertas: hay 37 archivos que todavía usan `swal()` (SweetAlert v1) aunque se carga SweetAlert2. La plantilla usa `Swal.fire` / `generarAlertJS()`.

---

## 5. CONVENCIONES QUE EL CLAUDE.md DEBE FIJAR

- Código PHP **procedural**: funciones con nombre en español y `snake_case` (`prestamo_crear`, `integ_registrar_evento`). La única clase permitida es `Database`.
- **Prefijo de módulo** en tablas, funciones de integración y objeto JS; nunca se escribe en tablas de otro prefijo.
- **Comentario breve** sobre cada función nueva; encabezado `/** ==== NOMBRE - modulo ==== */` en cada archivo, como los existentes.
- **Prepared statements siempre** que haya un parámetro (`query_segura()` de `PROMPT_MEJORA_CTM.md` o `Database::getInstance($db)->execute($sql, $params)`).
- **Token CSRF** en todo formulario (`campo_csrf()`, `validar_csrf()` de `PROMPT_MEJORA_CTM.md`).
- **Escapar la salida** con `htmlspecialchars()`.
- **Una transacción** (`begin_transaction` / `commit` / `rollback`) por operación de negocio que toque más de una tabla.
- **Fechas**: en la base `Y-m-d H:i:s`; en pantalla `dd/mm/aaaa` con `enmascararFecha()`.
- **No dejar** `echo $sql;`, `display_errors` en `on`, archivos `copy`, `_old`, `_1` ni `v1` junto a la versión vigente.
- **SQL de cambios**: un archivo `sql_<tema>.sql` idempotente (`IF NOT EXISTS`, `CREATE OR REPLACE VIEW`) por cada cambio de esquema o de menú.

---

## 6. PRINCIPIOS DDD QUE EL CLAUDE.md DEBE EXPLICAR (y cómo aplicarlos con funciones)

Explica cada principio en 3 a 5 líneas, con un ejemplo de CTM y su forma en PHP procedural.
No se usan clases: un "objeto" de DDD se convierte en un archivo de funciones más un array asociativo.

### 6.1 Diseño estratégico

| Principio | Qué significa | En CTM |
|---|---|---|
| **Dominio y subdominios** | El problema del negocio dividido en partes. Se distinguen el *núcleo* (lo que diferencia a la organización), los de *soporte* y los *genéricos* | Núcleo: presupuesto y asignación de equipamiento. Soporte: inventario, préstamos, reparaciones. Genérico: login, correo, menús |
| **Contexto delimitado** (bounded context) | Un límite dentro del cual un modelo y sus palabras tienen un único significado | Cada módulo `ctm_*`, con su código de sistema, su prefijo de tablas y su menú. "Préstamo" en Inventarios y en Morebius son modelos distintos |
| **Lenguaje ubicuo** | Las mismas palabras en el negocio, en el código, en la base y en la interfaz | legajo, centro de costos (CC), PA, EETT, SR, matrícula, borrador, revisión, cierre, préstamo, reparación, zona. El glosario es parte del `CLAUDE.md` |
| **Mapa de contextos** | Cómo se relacionan los contextos entre sí | `ctm_entorno` = **núcleo compartido** (shared kernel). K2B, OCS y RRHH = sistemas externos que entran por una **capa anticorrupción** (robots Python, cron, lectura de solo lectura). Los módulos se consultan entre sí mediante un **servicio de host abierto** (`integracion.php` + vistas `vw_integ_*`) |
| **Capa anticorrupción** | Traducir el modelo externo al propio para que no contamine el dominio | Los datos de K2B se guardan en `pres_k2b_*` y se traducen con funciones (`sync_k2b_presupuesto`); ninguna página parsea esquemas K2B por su cuenta |

### 6.2 Diseño táctico (con funciones)

| Principio | Qué significa | Forma en CTM |
|---|---|---|
| **Entidad** | Algo con identidad que cambia en el tiempo | Un array asociativo con su id (`$prestamo['PRESTAMOID']`), leído por una función `prestamo_obtener($id)` |
| **Objeto valor** | Algo definido solo por sus valores, sin identidad, que no cambia | Funciones puras que validan y normalizan: `cc_normalizar($cc)`, `legajo_validar($legajo)`, `monto_formatear($monto)` |
| **Agregado** | Grupo de datos que se modifica siempre junto y conserva sus reglas; tiene una raíz por la que se entra | Préstamo + su historial + el estado del equipo. Solo se cambia con funciones `prestamo_*`, y cada una usa **una transacción** |
| **Invariante** | Regla que siempre debe cumplirse dentro del agregado | "Un equipo no puede estar prestado dos veces", "una SR no supera el saldo del CC". Se verifica en `prestamo_validar()` antes de escribir |
| **Repositorio** | Lo único que sabe leer y guardar un agregado | Archivo `datos/<concepto>_sql.php` (evolución de `cargador.php`) con consultas preparadas |
| **Servicio de dominio** | Regla que involucra a varios agregados | `integ_verificar_presupuesto_compra($cc, $monto)` |
| **Servicio de aplicación** | Orquesta un caso de uso: valida, abre la transacción, llama al dominio y registra el evento | La página `nuevo.php` solo recibe el POST, valida el CSRF y llama a `prestamo_crear()` |
| **Evento de dominio** | Hecho del pasado que le interesa a otros contextos | `integ_registrar_evento('ctm_inventarios', 'nuevo_prestamo', [...])` en `integ_eventos`. Otro contexto lo consume (cron que lee `leido = 0`) en vez de leer tablas ajenas |
| **Modelo de lectura** | Consulta preparada para mostrar, separada de la escritura | Vistas `vw_integ_*` y funciones de listado con paginación |

### 6.3 Capas dentro de un módulo (propuesta a validar con el equipo)

```
web/mod/<area>/<funcionalidad>/pagina.php   ← presentación + caso de uso (recibe, valida CSRF, llama, muestra)
includes/dominio/<concepto>.php             ← reglas e invariantes: <concepto>_crear, _validar, _cerrar
includes/datos/<concepto>_sql.php           ← repositorio: consultas preparadas, sin reglas
ctm_entorno/includes/integracion.php        ← contratos entre contextos (lo único que otro módulo puede llamar)
```

Ejemplo que debe aparecer en el `CLAUDE.md`:

```php
// Registra un préstamo respetando las reglas del negocio
function prestamo_crear($con, $datos, $usu) {
    $error = prestamo_validar($con, $datos);
    if ($error) return ['ok' => false, 'mensaje' => $error];

    $con->begin_transaction();
    try {
        $id = prestamo_insertar($con, $datos, $usu);          // datos/prestamos_sql.php
        equipo_marcar_prestado($con, $datos['id_pc'], $usu);  // datos/equipos_sql.php
        $con->commit();
    } catch (Throwable $e) {
        $con->rollback();
        error_log('prestamo_crear: ' . $e->getMessage());
        return ['ok' => false, 'mensaje' => 'No se pudo registrar el préstamo'];
    }

    integ_registrar_evento('ctm_inventarios', 'nuevo_prestamo',
        ['legajo' => $datos['legajo'], 'cc' => $datos['centrocosto'], 'referencia_id' => $id], '1', $usu);
    return ['ok' => true, 'id' => $id];
}
```

### 6.4 Dónde CTM se aparta hoy de DDD (para la sección de anti-patrones)

- Los cuatro módulos escriben en la misma base, y las vistas `vw_integ_*` leen tablas de otros contextos.
- 203 de las 223 páginas de `web/mod` ejecutan SQL directamente; las reglas quedan repartidas en las páginas.
- `nuevo.php` de préstamos inserta en dos tablas sin transacción y deja `echo $sql;`.
- `config/`, `includes/` y los altas de préstamos y reparaciones están copiados, con diferencias, entre Inventarios y Morebius.
- Los eventos de `integ_eventos` se registran y se muestran, pero ningún contexto reacciona a ellos.

---

## 7. REGLAS DE ORO (base para la sección 2 del CLAUDE.md)

1. Funciones, no clases (salvo `Database`).
2. Cada módulo es un contexto: escribe solo en sus tablas.
3. Para hablar con otro módulo, usa `integ_*` o un evento en `integ_eventos`.
4. Toda consulta con parámetros es preparada.
5. Todo formulario lleva token CSRF; toda salida, `htmlspecialchars()`.
6. Toda operación que toca más de una tabla usa una transacción.
7. Las reglas del negocio viven en `includes/dominio/`, no en la página.
8. Las palabras del glosario se usan igual en tablas, funciones e interfaz.
9. Nada de secretos en el código: credenciales fuera del repositorio (por ejemplo, un `config.local.php` ignorado por git).
10. Cada cambio de esquema o de menú viene con su `sql_*.sql` idempotente.
11. Lee el archivo completo antes de modificarlo; cambios pequeños y probados en `localhost`.
12. No se crean copias `copy`, `_old` o `v1`: el historial está en git.

---

## 8. RECETAS QUE EL CLAUDE.md DEBE INCLUIR

### 8.1 Crear un módulo nuevo `ctm_<nombre>`

Escribe la checklist completa, verificada contra el código. Debe cubrir al menos:

1. Elegir el código de sistema, el prefijo de tablas y el nombre del objeto JS.
2. Copiar la estructura de la sección 4 desde el módulo de referencia (indicar cuál) y cambiar `APP_SISTEMA`, `APP_TITULO`, `APP_NAME`, `<PREFIJO>_APP`, la tabla de menú en `navbar.php`/`session.php` y el objeto JS.
3. Crear `sql_crear_tablas.sql` con la tabla `<prefijo>_menus` y los menús iniciales (incluido `estado=10` para salir).
4. Registrar el sistema en `usu_sistemas` y los privilegios en `usu_sist_privilegio` / `usu_user_sist` (documentar columnas).
5. Agregar el código a `$MODULE_STYLE_MAP` en `ctm_entorno/config/modules.php`.
6. Incluir `integracion.php` desde `includes/functions.php`.
7. Escribir el glosario del nuevo contexto y su lugar en el mapa de contextos.
8. Probar el login, el menú, los permisos por `nivacc` y el cierre de sesión.

### 8.2 Agregar un dominio o funcionalidad a un módulo existente

1. Definir el agregado, sus invariantes y su vocabulario.
2. Crear `includes/datos/<concepto>_sql.php` y `includes/dominio/<concepto>.php`.
3. Crear las páginas en `web/mod/<area>/<concepto>/` (listar, nuevo, detalle) y, si hace falta, `web/int/ajax/`.
4. Agregar las filas de menú con `direccion` y `nivacc` en un `sql_*.sql`.
5. Registrar los eventos que interesan a otros módulos.
6. Documentar el archivo en `DOCUMENTACION_ARCHIVOS.md` del módulo.

---

## 9. PREGUNTAS PARA EL EQUIPO (si no se resuelven leyendo el código)

- ¿El código de Presupuestos es `CTM_055` o `CTM_061`?
- ¿Cuál es el módulo de referencia para copiar la plantilla?
- ¿Se adopta la estructura `includes/dominio/` + `includes/datos/` para el código nuevo?
- ¿Dónde se guardan las credenciales fuera del repositorio?
- ¿Quién consume los eventos de `integ_eventos` y con qué frecuencia corre ese proceso?
- ¿El `CLAUDE.md` va solo en la raíz o también uno corto por módulo?
