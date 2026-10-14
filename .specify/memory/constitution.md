# Constitución del proyecto — contexto_unificado

Copia de las secciones 1 y 2 de `CLAUDE.md` (raíz del repositorio). Es el documento contra el que spec-kit verifica cada `plan.md` (§7.1 de `CLAUDE.md`).

**Regla de mantenimiento**: no se edita acá. Si cambia una regla en `CLAUDE.md` (§1 o §2), se regenera este archivo en la misma spec, con `/speckit-constitution` o copiando las dos secciones de nuevo. Cualquier diferencia entre los dos archivos la resuelve `CLAUDE.md`.

**Versión**: 1.0.0 · **Ratificada**: 2026-10-10 (redacción de `CLAUDE.md`) · **Última copia**: 2026-10-14

---

## 1. Principios de DDD, desarrollados

### 1.1 Por qué DDD acá

DDD existe para un problema concreto: **cuando varios equipos o módulos comparten un solo
modelo (y una sola base), el modelo se degrada** —una misma palabra pasa a significar cosas
distintas, una tabla la escriben cinco programas con reglas distintas, y nadie puede cambiar
nada sin romper algo que no ve—. Es exactamente la situación de este repositorio (§9): 14
módulos sobre una base `contexto_unificado`, con `usu_usuarios` escrita por siete de ellos,
`pade_alertas` escrita por cuatro que no son PADE, 13 copias de la misma `funcion.php` y
códigos de sistema pisados.

La respuesta de DDD tiene dos mitades que acá se aplican enteras:

- **Diseño estratégico** (§1.2): partir el dominio en **contextos acotados** con dueño,
  modelo, lenguaje y **base de datos propios**, y hacer explícito en un **mapa de contextos**
  cómo se relaciona cada uno con los demás.
- **Diseño táctico** (§1.3): dentro de cada contexto, modelar con **agregados** que protegen
  sus invariantes, **repositorios** que los persisten, **servicios** que coordinan y
  **eventos** que cuentan lo que pasó.

Y una **arquitectura** (§1.4) que mantiene al dominio en el centro y a la base de datos, la
web y los otros contextos en la periferia.

Lo que **no** se adopta: frameworks, ORM, jerarquías de clases, inyección de dependencias.
El estilo de la casa es procedural en PHP, y DDD no exige objetos: exige **límites, lenguaje
e invariantes explícitos**. Cada concepto de la teoría tiene abajo su forma concreta en
funciones y tablas.

### 1.2 Diseño estratégico

#### Dominio y subdominios

El **dominio** es el problema que la organización resuelve: administrar la CTM. Se divide en
**subdominios**, y DDD pide clasificarlos porque no merecen el mismo esfuerzo:

| tipo | qué es | invertir en | en la CTM |
|---|---|---|---|
| **núcleo** (*core*) | lo que distingue a la organización; donde está la complejidad de negocio | el mejor diseño, el modelo más rico, el dueño más claro | **Concursos** (selección de personal), **Alertas hidrológicas (PADE)**, **Horas extras / Autogestión de trámites RRHH**, **Gastos comunes** |
| **de soporte** (*supporting*) | necesario, específico de la casa, pero no diferencial | diseño correcto, sin sobre-inversión | **Orgánica** (organigrama), **Monedas** (cotizaciones), **Reportes**, **Consola** |
| **genérico** | problemas resueltos en cualquier organización | lo más simple que funcione, o comprar | **Identidad y acceso** (usuarios, permisos, menús), **Geografía** (países/provincias/localidades), **Notificaciones**, **Mensajería** (SMS/WhatsApp/email) |

La clasificación cambia dónde se gasta el esfuerzo de esta guía: las reglas tácticas de §1.3
se exigen completas en el núcleo; en lo genérico alcanza con que esté aislado y publique una
interfaz estable.

#### Lenguaje ubicuo

Un **lenguaje ubicuo** es el vocabulario del negocio, acordado con quien lo opera, y usado
**igual** en la conversación, la documentación, las tablas, las funciones, las variables y
la interfaz. Es *ubicuo* porque está en todos lados, y es **por contexto**: la misma palabra
puede significar algo distinto en dos contextos (en Identidad "estado" es el `estadoid` de
una cuenta; en Concursos es el ciclo de vida del concurso; en la URL es el id del menú). Eso
no es un error: es la razón de tener contextos. Lo que sí es un error es que **dentro de un
mismo contexto** una cosa tenga dos nombres (`usuarioid` / `ndoc` / `us` / `legajo` para
"la persona"), o que el nombre del código no sea el del negocio (`aj_clasificar_postulantes`
para lo que RRHH llama *verificar requisitos*).

El glosario de §3 es el lenguaje ubicuo de este sistema. **Se mantiene antes que el código**:
una palabra nueva entra ahí primero.

#### Contexto acotado

Un **contexto acotado** (*bounded context*) es la frontera dentro de la cual un modelo y su
lenguaje son válidos y consistentes. Es la unidad de aislamiento de DDD y tiene cinco
propiedades, todas necesarias:

1. **Su propio modelo**: sus agregados, sus reglas, sus estados. Nadie de afuera los
   interpreta ni los modifica.
2. **Su propio lenguaje**: su sección del glosario.
3. **Su propio dueño**: una persona o equipo responsable de qué cambia y qué no.
4. **Su propio código**: una carpeta `ctm_<contexto>/`; nada de ese código se incluye desde
   otra carpeta salvo su interfaz publicada (§1.4).
5. **Su propia base de datos**: un esquema `ctm_<contexto>` con **su propio usuario de
   MariaDB**, con permisos sólo sobre ese esquema. **Ningún otro contexto puede hacer un
   `SELECT` ahí**, y el motor lo garantiza, no la buena voluntad.

La quinta es la que este repositorio no tiene y la que más cuesta: hoy todo vive en
`contexto_unificado` separado por prefijos, y el prefijo es una convención que ya se rompió
(§9.2). Un contexto que comparte base con otro **no está aislado**: cualquier `JOIN` lo acopla,
cualquier cambio de columna lo rompe, y la frontera se cruza con una línea de SQL. Por eso el
objetivo de este marco es **una base por contexto** (§4.4) y el camino para llegar (§6).

Aislamiento máximo, en concreto —lo que un contexto **no** puede hacer:

- Leer o escribir tablas de otro contexto, ni por `JOIN`, ni por `esquema.tabla`, ni por
  vista que apunte afuera.
- Incluir código de otro contexto que no sea su **interfaz publicada**.
- Compartir una secuencia, un catálogo de estados o una tabla de menús.
- Conocer el nombre de la base, el host o las credenciales de otro contexto.
- Depender de que otro contexto esté arriba para completar **su** transacción.

Y lo que **sí** puede:

- Llamar a las funciones publicadas de otro contexto (su *servicio de host abierto*, §1.2
  abajo) y recibir lo que ese contexto decidió publicar, en el formato que publicó.
- Emitir eventos de dominio y suscribirse a los de otros.
- Mantener **su propia copia** de los datos ajenos que necesita leer (un *modelo de lectura*
  o *read model*), alimentada por eventos o por la interfaz publicada, aceptando que pueda
  estar unos segundos atrasada (**consistencia eventual**).

#### Mapa de contextos y patrones de relación

El **mapa de contextos** es el dibujo de todos los contextos y de **qué tipo de relación**
tiene cada par. DDD nombra los tipos; cada uno implica obligaciones distintas y conviene
elegirlos a conciencia:

| patrón | qué significa | cuándo se usa acá |
|---|---|---|
| **Núcleo compartido** (*shared kernel*) | dos contextos comparten **un pedazo chico** de modelo y código, y lo cambian de acuerdo | sólo entre Entorno e Identidad (la sesión y su contrato). Es el patrón más acoplado: se usa lo menos posible |
| **Cliente–proveedor** (*customer–supplier*) | el proveedor publica algo que el cliente necesita; el cliente tiene voz en la agenda del proveedor | PADE (proveedor del envío de avisos) ↔ Horas extras, Autogestión, Gastos (clientes) |
| **Conformista** (*conformist*) | el cliente acepta el modelo del proveedor tal cual, sin negociarlo | los módulos frente a Identidad: usan `ndoc`, `nivacc`, códigos de sistema como Identidad los define |
| **Capa anticorrupción** (*anti-corruption layer*, ACL) | el cliente traduce el modelo ajeno al suyo en **una** capa, para que el modelo externo no "contamine" el propio | frente a los sistemas externos K2B, PayWeb, RHPro, Payroll, EAM (las réplicas `cp_*`) y frente a la intranet externa (`int_*` por ODBC) |
| **Servicio de host abierto** (*open host service*, OHS) | el proveedor publica una interfaz estable y documentada para que **cualquier** contexto la consuma | la forma en que cada contexto expone lo suyo: una biblioteca de funciones `<ctx>_api_*()` y sus eventos |
| **Lenguaje publicado** (*published language*) | el formato acordado en que viajan los datos entre contextos | el `datos_json` de los eventos y los arrays que devuelven las `<ctx>_api_*`; se documentan en §3 |
| **Caminos separados** (*separate ways*) | dos contextos que no necesitan integrarse, y se decide **no** integrarlos | Consola y Reportes respecto del resto; `aced_chatai` y `polo_ofertaadmin` respecto de todo (son de otros clientes) |
| **Socios** (*partnership*) | dos contextos que evolucionan juntos por decisión | hoy ninguno; se declara cuando dos equipos lo acuerden |
| **Gran bola de barro** (*big ball of mud*) | lo que no tiene fronteras: se dibuja como tal y se le pone una ACL alrededor hasta desarmarlo | **el estado actual** de los módulos `conf/` que escriben en tablas ajenas (§9.2) |

El mapa de este sistema está en §4.3, con el patrón de cada relación hoy y el patrón
objetivo.

### 1.3 Diseño táctico

Los bloques con los que se construye el modelo **dentro** de un contexto. Cada uno con su
definición y su forma en este código.

| bloque | definición | forma en PHP procedural acá |
|---|---|---|
| **Entidad** | cosa con **identidad** propia que persiste aunque cambien sus atributos (un concurso sigue siendo *ese* concurso aunque cambie de estado) | una fila con clave primaria; en código, un array asociativo con sus campos, construido y validado por `<entidad>_crear(array $in): array` en `includes/dominio/` |
| **Objeto de valor** | cosa que se define **sólo por sus atributos**, sin identidad, inmutable, autovalidada (un número de documento, un período, un puntaje, una cotización) | una función `vo_<nombre>(valor): valor` que valida y normaliza, y lanza excepción si no es válido. Se usa al construir la entidad: `'ndoc' => vo_ndoc($in['ndoc'])`. Nunca se guarda un valor sin pasar por su `vo_*` |
| **Agregado** | un grupo de entidades y objetos de valor que **cambian juntos** y tienen una **raíz** que es la única puerta de entrada. El agregado es la **unidad de consistencia**: sus **invariantes** (lo que siempre tiene que ser verdad) se cumplen al final de cada transacción | las tablas raíz + hijas de §4.5; una función de dominio por operación (`concurso_abrir($id, $usu)`), que carga el agregado entero, verifica invariantes, lo guarda **en una transacción** y emite eventos. Nadie toca una tabla hija sin pasar por la raíz |
| **Regla del agregado chico** | una transacción modifica **un** agregado. Si una operación necesita cambiar dos, el segundo se actualiza **después**, por un evento (consistencia eventual) | si una función de dominio abre dos transacciones o toca dos raíces, el modelo está mal cortado: se revisa §4.5 |
| **Referencia por identidad** | un agregado referencia a otro **por su id**, nunca cargándolo entero ni con `JOIN` | `postulaciones.id_concurso` guarda el id; para validar que el concurso está abierto se llama a `concurso_esta_abierto($id)` (misma base, mismo contexto) |
| **Repositorio** | la **única** puerta a la persistencia de un agregado: lo guarda y lo recupera entero, escondiendo el SQL | `includes/infraestructura/<agregado>_repo.php`: `repo_concurso_obtener($id)`, `repo_concurso_guardar(array $c)`, `repo_concurso_buscar(array $filtros)`. Una función por consulta, todas preparadas. **Ninguna página contiene SQL** |
| **Fábrica** | construye un agregado complejo garantizando que nace válido | `concurso_nuevo_desde_formulario(array $post, string $usu): array` en `dominio/` |
| **Servicio de dominio** | regla de negocio que **no pertenece a una sola entidad** (calcular el orden de prelación entre todas las postulaciones de un concurso) | función en `dominio/`, sin SQL, que recibe los agregados y devuelve un resultado |
| **Servicio de aplicación** (caso de uso) | orquesta **un** caso de uso: autoriza, abre la transacción, llama al dominio, persiste, emite eventos, devuelve el resultado. **No** tiene reglas de negocio | `includes/aplicacion/<caso_de_uso>.php`: `caso_abrir_concurso(int $id, string $usu): array`. Es lo que la página y el AJAX llaman |
| **Evento de dominio** | un hecho que **ya pasó** y que a otros les importa, nombrado en pasado en el lenguaje ubicuo (`concurso_abierto`, `postulacion_verificada`, `aviso_solicitado`) | fila en `integ_eventos` escrita por `evento_emitir($tipo, $datos)` **dentro** de la misma transacción que el cambio (tabla *outbox*), y despachada después por un consumidor (§5.6) |
| **Módulo** (en el sentido DDD: paquete) | agrupación de código por **concepto del dominio**, no por capa técnica | `web/mod/<area>/<agregado>/`, `dominio/<agregado>.php`, `infraestructura/<agregado>_repo.php`: el agregado da nombre a todo |
| **Especificación** | regla de negocio reutilizable expresada como predicado | `function postulacion_cumple_excluyentes(array $p): bool` |

#### Invariantes, estados y transiciones

La parte del modelo que más se rompe en este repo (§9.4) es el **ciclo de vida**: estados
que cambian a cualquier otro sin control. La regla: **cada agregado con estados tiene su
máquina de transiciones en una tabla PHP del dominio** y una sola función que la aplica:

```php
// includes/dominio/concurso.php
const CONCURSO_TRANSICIONES = [
    'borrador'      => ['abierto', 'cancelado'],
    'abierto'       => ['en_evaluacion', 'desierto', 'cancelado'],
    'en_evaluacion' => ['cerrado', 'desierto', 'cancelado'],
    'cerrado'       => [],
    'desierto'      => [],
    'cancelado'     => [],
];
function concurso_cambiar_estado(array $c, string $nuevo, string $usu): array {
    if (!in_array($nuevo, CONCURSO_TRANSICIONES[$c['estado']] ?? [], true)) {
        throw new DomainException("Un concurso {$c['estado']} no puede pasar a {$nuevo}.");
    }
    if ($nuevo === 'abierto' && count($c['etapas']) === 0) {
        throw new DomainException('No se puede abrir un concurso sin etapas.');
    }
    $c['estado'] = $nuevo;
    return $c;
}
```

Lo que **puede** hacerse en cada estado también es del dominio (`concurso_puede_editar_etapas($c)`),
y la página sólo pregunta.

### 1.4 Arquitectura

#### Las cuatro capas y la regla de dependencia

```
┌──────────────────────────────────────────────────────────────┐
│  INTERFAZ        web/mod/**/*.php · web/int/ajax/aj_*.php     │  recibe el request, valida formato,
│                  web/cron/*.php · includes/{header,navbar}    │  llama a UN caso de uso, pinta
├──────────────────────────────────────────────────────────────┤
│  APLICACIÓN      includes/aplicacion/caso_*.php              │  autoriza · transacción · dominio
│                                                              │  · repositorio · eventos
├──────────────────────────────────────────────────────────────┤
│  DOMINIO         includes/dominio/*.php                      │  entidades · VO · agregados ·
│                  (SIN SQL, SIN $_POST, SIN HTML)              │  invariantes · servicios · eventos
├──────────────────────────────────────────────────────────────┤
│  INFRAESTRUCTURA includes/infraestructura/*_repo.php         │  SQL preparado · Database ·
│                  config/ · adaptadores (ACL, mensajería)     │  conexiones · APIs externas
└──────────────────────────────────────────────────────────────┘
```

**Regla de dependencia**: las flechas van hacia adentro. La interfaz conoce a la aplicación;
la aplicación conoce al dominio y a la infraestructura; **el dominio no conoce a nadie**: ni
SQL, ni `$_POST`, ni HTML, ni `Database`. Si una función de `dominio/` necesita leer algo,
lo recibe por parámetro. Así el dominio se puede leer, probar y cambiar sin levantar la web
ni la base.

Es la **arquitectura hexagonal** (*puertos y adaptadores*) en su versión mínima: el dominio
en el centro; la web, la base, la mensajería y los otros contextos son adaptadores que se
enchufan afuera. Cambiar MariaDB por otra cosa, o la web por una API, no toca el dominio.

#### Una base por contexto

Cada contexto tiene su esquema `ctm_<contexto>` y su usuario `u_<contexto>` con
`GRANT … ON ctm_<contexto>.*` y nada más. Consecuencias que se aceptan a conciencia:

- **No hay `JOIN` entre contextos.** Si una pantalla de Concursos tiene que mostrar el
  nombre de la persona a partir del `ndoc`, o (a) llama a `identidad_api_persona($ndoc)` y
  compone en PHP, o (b) mantiene `conc_personas_rm` (un *read model* local con ndoc, apellido,
  nombres) que se actualiza con el evento `persona_modificada`. La (b) es la que escala; la
  (a) es la que se empieza.
- **No hay integridad referencial entre contextos.** `postulaciones.ndoc_interno` guarda el
  documento; que esa persona exista lo garantiza la validación al momento de crear, no una
  FK. Es el precio del aislamiento y es deliberado.
- **La consistencia entre contextos es eventual**: el aviso de PADE se envía *después* de
  que Horas extras confirmó la solicitud, no en la misma transacción. Si PADE está caído, el
  evento espera en `integ_eventos`; la solicitud no falla. **Un contexto nunca depende de que
  otro esté arriba para completar su propia transacción.**
- **Las tablas compartidas desaparecen**: `usu_estados` se vuelve `<prefijo>_estados` en cada
  contexto (o constantes PHP si el catálogo es fijo); `usu_id` también; `*_menus` siempre fue
  por contexto.

#### Integración: eventos y servicios de host abierto

Dos mecanismos, y sólo dos:

1. **Interfaz publicada** (`ctm_<ctx>/api/<ctx>_api.php`): funciones puras que otro
   contexto puede incluir. Reciben y devuelven **arrays simples del lenguaje publicado**,
   nunca filas crudas. Se versionan por nombre si cambian de forma incompatible
   (`identidad_api_persona_v2`). Son la **única** porción de código de un contexto que otro
   puede incluir, y la única que toca la base de ese contexto desde afuera de él.
2. **Eventos** (`integ_eventos` como *outbox* + un despachador por cron, §5.6): el emisor
   escribe el evento en la misma transacción que el cambio; el despachador lo entrega a los
   suscriptores (funciones `<ctx>_on_<evento>()` registradas en `api/<ctx>_suscripciones.php`),
   cada uno en su propia transacción, con reintento e idempotencia (si el mismo evento llega
   dos veces, el segundo no hace nada).

Lo que **no** es integración: incluir `conf/funcion.php` de otro módulo, leer `pade_menus`
desde Autogestión, insertar en `pade_alertas` desde Horas extras, hacer `SELECT … FROM
morebius.ctm_plantel` desde Orgánica.

#### Capa anticorrupción

Los sistemas externos (K2B, PayWeb, RHPro, Payroll, EAM, la intranet `int_*` por ODBC,
Azure OpenAI, Anthropic, Twilio) tienen **su** modelo, que no es el nuestro. Se les pone una
**capa anticorrupción**: un adaptador en `infraestructura/acl_<sistema>.php` que habla el
idioma del externo de un lado y devuelve objetos de nuestro lenguaje del otro. Las réplicas
`cp_*` son eso —una ACL materializada—, con dos correcciones: **las escribe un solo proceso**
(el sincronizador de Consola) y **se leen a través de una función** (`acl_payweb_bolsa($legajo)`),
no por `SELECT` directo desde las páginas.

#### Modelos de lectura (CQRS liviano)

Las pantallas de consulta (dashboards, listados, reportes) no necesitan agregados: necesitan
filas rápidas. Se permite que un contexto tenga **vistas y tablas de sólo lectura dentro de su
propio esquema**, desnormalizadas, alimentadas por sus propios eventos o por los de otros
(`conc_personas_rm`). La escritura **siempre** pasa por el agregado; la lectura puede ir por
el *read model*. Es CQRS sin infraestructura: dos caminos, una base.

### 1.5 Lo que esto cambia respecto del código actual

| hoy | con este marco |
|---|---|
| una base `contexto_unificado`, separación por prefijo | una base por contexto, con usuario y permisos propios |
| la página hace el `SELECT`, aplica la regla y pinta | la página llama a un caso de uso; el SQL está en el repositorio; la regla en el dominio |
| estados que cambian libremente | máquina de transiciones en el dominio, una función para cambiar |
| módulos que escriben en tablas de otros | funciones publicadas y eventos; el motor niega el acceso cruzado |
| 13 copias de `funcion.php`, 5 de `database.php` | una copia canónica de la infraestructura común, incluida, no copiada |
| `addslashes` + concatenación | consultas preparadas; objetos de valor que validan antes |
| sin transacciones | un agregado = una transacción; eventos en la misma transacción (*outbox*) |
| `integ_eventos` se escribe y nadie lo lee | despachador por cron y suscriptores por contexto |
| documentación copiada de otro módulo | glosario por contexto, mapa de contextos, un `DOCUMENTACION_ARCHIVOS.md` real |
| cambios directos sobre producción, sin plan ni rastro | spec-kit por cambio (`specs/NNN/`), Masterplan por sistema y por contexto, `HISTORIAL.md` incremental (§7) |

---

## 2. Reglas de oro

Derivadas de §1. Las 16 que no se negocian:

1. **Un contexto, una carpeta, una base, un dueño, un código de sistema.** Nunca dos
   módulos con el mismo `APP_SISTEMA`; nunca un módulo con dos.
2. **Un contexto sólo lee y escribe su propio esquema.** Lo garantiza el usuario de
   MariaDB, no la convención. Lo que necesite de otro contexto lo pide a su interfaz
   publicada o lo recibe por evento.
3. **Funciones, no clases** (la única clase es `Database`). Procedural, en español,
   `snake_case` para lo nuevo.
4. **Cuatro capas y la flecha hacia adentro**: `web/` → `aplicacion/` → `dominio/` ←
   `infraestructura/`. **El dominio no tiene SQL, ni `$_POST`, ni HTML.**
5. **Un agregado por transacción; un evento por hecho.** Lo que cruza agregados o contextos
   va por evento, con consistencia eventual.
6. **Toda regla de negocio vive en `includes/dominio/`.** Transiciones de estado en una tabla
   de transiciones y una sola función que la aplica. La página pregunta, no decide.
7. **Todo el SQL vive en `includes/infraestructura/*_repo.php`, preparado.** Ninguna página,
   ningún AJAX, ningún `cron` contiene `SELECT`. `sql_do_query()` con concatenación no se
   escribe más; se migra al tocar.
8. **Todo valor que entra pasa por su objeto de valor** (`vo_ndoc`, `vo_periodo`,
   `vo_puntaje`…) antes de llegar al dominio. `sani_input()` no es validación.
9. **Las palabras del glosario (§3) se usan igual en tablas, funciones, variables e
   interfaz**, y una palabra nueva entra primero al glosario.
10. **Toda salida pasa por `h()`; todo formulario y AJAX de escritura lleva token CSRF;
    toda autorización corta en el servidor.**
11. **Nada de secretos en el código.** `config/config.local.php` fuera del repositorio,
    permisos 600, un usuario de base **por contexto**; los secretos que hoy están en el
    código se sacan **y se rotan**.
12. **Cada cambio de esquema o de menú viene con su `sql/NNN_<que>.sql` idempotente**,
    aplicable en orden sobre una base vacía del contexto.
13. **La infraestructura común se incluye, no se copia.** Una `Database`, un `csrf`, un
    `evento_emitir`, en `ctm_entorno/includes/` (hasta que se extraigan a `comun/`).
14. **Leer el archivo completo antes de modificarlo; buscar antes de construir; probar en
    `localhost` antes de subir.** En este repo casi todo existe dos veces (§9.3).
15. **No se crean copias `_old`, `_1`, `copy`, `_v2`, carpetas `reportes_2/`: el historial
    está en git.** Lo primero es `git init` (§11).
16. **Ningún cambio sin spec; ninguna spec fuera del Masterplan.** Todo lo que no sea
    trivial (§7.3) nace como `specs/NNN-<slug>/spec.md`, se planifica contra esta
    constitución (`plan.md`), se corta en tareas (`tasks.md`) y recién ahí se implementa.
    Al cerrarse, actualiza `MASTERPLAN.md`, el `HISTORIAL.md` del contexto y
    `DOCUMENTACION_ARCHIVOS.md`. Un cambio sin rastro no ocurrió.

Dos reglas de método: **lo que se toca se deja mejor, pero sólo lo que se toca**; y **lo
que no está cubierto se escribe** (un invariante no implementado, un evento sin consumidor,
una tabla sin dueño van a la deuda del contexto en su `MASTERPLAN.md`, no se callan).

---

