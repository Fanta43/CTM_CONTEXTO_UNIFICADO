# contexto_unificado — marco único de trabajo (DDD)

Intranet de la CTM de Salto Grande: un conjunto de módulos PHP procedurales sobre MariaDB
que hoy comparten una sola base, el login, los usuarios, los permisos, la geografía y los
menús. Este archivo es **el contrato para trabajar acá**: los principios que gobiernan el
diseño, cómo se llama cada cosa, qué módulo es dueño de qué, cómo se arma un módulo y una
página, qué está prohibido y qué deuda ya se conoce para no repetirla.

Está escrito desde la teoría de *Domain-Driven Design* (Evans, 2003; Vernon, 2013) aplicada
al código tal como está en la raíz de este repositorio (última edición 2026-07-31, sin git) y
a los `.md` que ya existían dentro de los módulos. Lo que no se pudo verificar se dice.
Redacción: 2026-10-10.

**Cómo se trabaja con este archivo**: es la **constitución** del proyecto (en el sentido de
spec-kit: §1 y §2 son las reglas contra las que se verifica todo plan), el **Masterplan**
(`MASTERPLAN.md`) es la hoja de ruta que dice en qué fase está cada cosa, y **cada cambio
se hace con spec-kit** (`specs/NNN-<slug>/`: especificar → planificar → tareas →
implementar). El proceso completo está en §7.

---

## 0. Cómo leer este archivo

| § | qué responde |
|---|---|
| **1. Principios de DDD** | la teoría, desarrollada, y cómo se traduce a PHP procedural y MariaDB acá |
| **2. Reglas de oro** | las 16 reglas que salen de §1 y no se negocian |
| **3. Lenguaje ubicuo** | las palabras del negocio, con el significado que les da el código |
| **4. Mapa de contextos** | qué contexto es dueño de qué, cómo se relacionan, qué base le toca a cada uno |
| **5. Arquitectura de un módulo** | el molde en cuatro capas, el ciclo de una página, la capa de datos, la integración, **el estándar de menús** |
| **6. Camino de migración** | cómo se pasa de una base compartida a contextos aislados sin parar producción |
| **7. Proceso de trabajo** | Masterplan + spec-kit: cómo se planifica, ejecuta y documenta cada cambio; las recetas como specs |
| **8. Seguridad mínima** | los controles que todo módulo cumple |
| **9. Deuda conocida** | anti-patrones con ruta y línea, para no redescubrirlos |
| **10. Lo que NO está en el repo** · **11. Preguntas abiertas** | límites y decisiones pendientes |

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

## 3. Lenguaje ubicuo

Términos con el significado que tienen **en el código**; donde código y documentos no
coinciden se anota. Cada contexto tiene su sección; una misma palabra puede aparecer en dos
con significado distinto (es lo esperado, §1.2).

### 3.1 Identidad y acceso + Entorno (genérico; núcleo compartido entre ambos)

| término | significado | dónde vive |
|---|---|---|
| **persona** | ser humano del padrón institucional; se identifica por **ndoc** | `vw_personas` (vista en producción; tabla en algunas bases locales —§9.2) |
| **usuario** / **cuenta** | con qué entra una persona: `usuarioid`, `nombredeusuario`, `contrasenia` | `vw_personas` / `usu_usuarios` |
| **ndoc** | número de documento; identidad en toda la sesión (`$_SESSION['us']`) y en `auditoria_usu` | sesión, todas las tablas |
| **legajo** | número de empleado en RRHH; distinto del ndoc | `cp_rhpro_ctm_plantel`, `autog_*`, `hsex_*`, `org_*` |
| **sistema** | un contexto registrado, identificado por su **código** (`CTM_051`, `RRHH_060`…). El código es la clave de negocio | `usu_sistemas.codigo`, constante `APP_SISTEMA` |
| **privilegio** / **rol** | nivel que un sistema define (`nro_priv`, descripción en MAYÚSCULAS) | `usu_sist_privilegio` |
| **nivacc** | nivel de acceso: el privilegio que la persona tiene en un sistema (`$_SESSION['<CODIGO>']`) y el mínimo que exige una opción de menú (`*_menus.nivacc`). Se muestra y ejecuta si `nivel ≥ nivacc`. Escala única 0–5 (§5.8.3) | `usu_user_sist`, `*_menus` |
| **estado** (de URL/menú) | el `id` de la fila de `*_menus`: `index.php?estado=<id>`. `1` = inicio del contexto, `10` = salir en todos; `7` = bypass sin sesión **a eliminar** (§9.1). Estándar completo en §5.8 | `*_menus.id` |
| **estadoid** (de registro) | ciclo de vida de una fila, en el catálogo `(estadoid, descripcion, tabla)` | tablas con `estadoid` |
| **baja lógica** | cambiar `estadoid` al valor "baja"; nunca `DELETE` salvo deduplicación | convención |
| **orgánica** | nodo del árbol organizacional (`organicaid`, `padreid`, `profundidad_nivelid`, `cuatrigrama`) | `usu_organicas` |
| **estructura** | un árbol orgánico completo (una versión del organigrama) | `usu_estructuras` |
| **grupo** | lista de personas para permisos o destinatarios | `usu_grupos`, `usu_user_grupos` |
| **agrupamiento** | catálogo para clasificar sistemas o grupos | `usu_agrupamiento` |
| **destino** | ubicación organizacional de la persona (`$_SESSION['destino']`) | sesión |
| **centro de costo** (`cc`) | unidad presupuestaria a la que se imputa una persona, un equipo o un gasto | `vw_integ_cc_*`, `autog_*` |
| **localidad / provincia / país** | geografía compartida | `ento_localidades`, `ento_provincias`, `ento_paises` |
| **notificación** | aviso interno del entorno a una persona | `ento_notificaciones` |
| **auditoría** | `auditoria_usu` (ndoc) + `auditoria_fecha` en cada INSERT/UPDATE | todas |
| **secuencia** | generador de IDs propio: `<prefijo>_id(tabla, id_tabla)` + `get_id()` | `usu_id`, `conc_id`, `gast_id`… |
| **evento** | hecho ocurrido en un contexto que otro puede necesitar: `modulo_origen, tipo, cc, legajo, referencia_id, descripcion, datos_json` | `integ_eventos` |

### 3.2 Concursos (núcleo; `ctm_conc`) — fuente: `ESPECIFICACION_CONCURSOS.md`, `docs/MANUAL_USUARIO.md`

| término | significado |
|---|---|
| **concurso** | proceso de selección para un cargo; `numero` único; tipo `abierto` / `combinado` / `interno`; estados `borrador → abierto → en_evaluacion → cerrado`, con salidas `desierto` y `cancelado` |
| **bases** | PDF con las condiciones (`pdf_bases`) |
| **postulante** | la persona que se presenta; única por (`documento`, `tipo_documento`); puede ser **interno** (`es_interno`, `legajo_interno`) |
| **postulación** | inscripción de un postulante a un concurso; única por (`id_concurso`, `id_postulante`); `nro_postulacion` = `{numero}-{seq3}`; estados `inscripto → verificado → en_evaluacion → finalista → recomendado → designado`, con salidas `no_cumple_requisitos`, `eliminado`, `desistio` |
| **requisito excluyente / preferente** | condición obligatoria / que suma puntos **informativos** |
| **conocimiento** / **competencia** | saber técnico / competencia organizacional evaluada |
| **etapa** | paso de evaluación: `orden`, `tipo` (postulacion, escrita, practica, preferentes, desarrollo_interno, entrevista, psicolaboral, medica, otra), `eliminatoria`, `puntaje_maximo/minimo`, `max_postulantes_pasan`; estados `pendiente / en_curso / finalizada` |
| **tribunal** | quienes evalúan; roles `gerencia_general, gerente_area, jefe_directo, rrhh, veedor_sindical` |
| **evaluación** | puntaje de una postulación en una etapa; única por (`id_postulacion`, `id_etapa`) |
| **desarrollo interno** | bonus para internos, sumado después de la etapa de conocimientos |
| **orden de prelación** | ranking final; **empate** = diferencia < `umbral_empate`; se resuelve interno → entrevista → tribunal |
| **validación IA** | sugerencia automática (`*_ia`) que el administrador confirma (`*_final`, `verificado_por`); cada confirmación va a `concurso_log` |
| **duplicado / descartado** | marcas sobre una postulación repetida o retirada |

Donde el código y la especificación se contradicen (§9.4), **manda la especificación**.

### 3.3 Resto de contextos (vocabulario breve)

| contexto | términos |
|---|---|
| **Gastos comunes** (núcleo; `gast_`) | gasto, tipo de gasto, participante, inversión, cuota parte, pago, pago eliminado, cobranza; estados 1 Activo, 2 En espera, 3 Parcial, 4 Pagado, 5 Cerrado |
| **Alertas hidrológicas / PADE** (núcleo; `pade_`) | alerta, tipo de alerta, caudal, velocidad de emergencia, tiempo de llegada, localidad, grupo de destinatarios, seguimiento, **aviso** (mensaje SMS/WhatsApp/email) |
| **Horas extras** (núcleo; `hsex_`) | período (mes de liquidación), solicitud, supervisor, categoría, bolsa |
| **Autogestión de trámites RRHH** (núcleo; `autog_`) | trámite, tipo de trámite (1 Datos personales, 2 Acceso al chatbot, 3 Período, 4/5 flujos con firma), gestión, obligatorio, datos personales/familiares/estudios/domicilio/seguro |
| **Orgánica** (soporte; `org_`) | organigrama, sector, estructura, personal, historial de internos |
| **Monedas** (soporte; `mone_`) | cotización, moneda, origen (BCRA/BNA/BROU), moneda objetivo, feriado, estado de API |
| **Consola** (soporte; `cons_`) | crontab, frecuencia, log de cron, servicio web, servicio email, conexión (`dbconf`), clave criptográfica, **sincronización** (`cons_sync_tabla` → `cp_*`) |
| **Reportes** (soporte; `repo_`) | reporte, parámetro (`@@param@@`), grupo de reportes, conexión destino |
| **Inventario / préstamos / reparaciones / presupuesto** (`inve_`, `more_`, `vw_integ_*`) | equipo (pc, monitor, impresora, otro), zona de inventario, préstamo (`devuelto`), reparación (`reparado`), proveedor, presupuesto del cc (`BINV_DISPONIBLE`) — **el código no está en este repo (§10)** |

---

## 4. Mapa de contextos

### 4.1 Inventario de carpetas y su estado

Estado leído del código. **vivo** = se usa y tiene dueño; **legacy** = funciona con `conf/`,
pendiente de migrar; **reemplazado / duplicado**; **ajeno** = no es de la CTM.

| carpeta | contexto | subdominio | código | prefijo | estilo | estado |
|---|---|---|---|---|---|---|
| `ctm_entorno` | **Entorno** (login, sesión, panel, notificaciones, integración) | genérico | `AHID_051` | `ento_`, `integ_` | `config/`+`includes/` | vivo — puerta de entrada |
| `ctm_usu` | **Identidad y acceso** | genérico | `CTM_051` | `usu_` | `config/`+`includes/` | vivo (v3) |
| `ctm_conc` | **Concursos** | núcleo | `RRHH_060` | sin prefijo + `conc_` | `config/`+`includes/` | vivo, **híbrido**: ~35 PHP propios sobre ~241 de Morebius (§9.4) |
| `ctm_gastcomunes` | **Gastos comunes** | núcleo | `CTM_060` | `gast_` | `config/` + shim `conf/` | vivo |
| `ctm_organica` | **Orgánica** v2 | soporte | `CTM_052` | `org_` | `config/`+`includes/` | vivo |
| `ctm_pade` | **Alertas hidrológicas** | núcleo | `AHID_053` (`modules.php` registra `CTM_053`) | `pade_` | `conf/` | legacy; dueño del motor de avisos |
| `ctm_monedas` | **Monedas** | soporte | `CTM_054` | `mone_` | `conf/` | legacy |
| `ctm_hsextras` | **Horas extras** | núcleo | `CTM_056` (cron `CTM_054`) | `hsex_` | `conf/` | legacy; arrastra copia de Monedas |
| `ctm_autogestion` | **Autogestión** | núcleo | `CTM_056` (cron `CTM_054`) | `autog_`, `cbot_` | `conf/` | legacy; el más evolucionado del fork hsextras→segtramites→autogestion |
| `ctm_segtramites` | (paso intermedio del fork) | — | `CTM_056` | `autog_`, `hsex_` | `conf/` | **probablemente muerto** |
| `ctm_consola` | **Consola** | soporte | `CTM_055` | `cons_` | `conf/` | legacy; arrastra `sime_`, `dtll_` ajenas |
| `ctm_reportes` | **Reportes** | soporte | `CTM_057` (cron `CTM_055`) | `repo_` | `conf/` | legacy |
| `ctm_usu_` | Identidad v1 | — | `CTM_051` | `usu_` | `conf/` | **reemplazado** por `ctm_usu` |
| `ctm_organica_` | Orgánica v1 | — | `CTM_051` | `usu_`, `org_menus` | `conf/` | **reemplazado** por `ctm_organica` |
| `ctm_chatia` | — | — | `CTM_051` | — | `conf/` | **copia byte a byte de `ctm_organica_`**; sin IA |
| `aced_chatai` | Chat Azure OpenAI | — | `ACED_051` | `chai_` | `conf/` | **ajeno** (ACED) → caminos separados |
| `polo_ofertaadmin` | Eventos y cursos | — | `POLO_001` | `evto_`, `mame_` | `conf/` | **ajeno** (Polo) → caminos separados |
| raíz: `index.php`, `intermedio_aico.php`, `chequeo*.php` | login y chequeos | — | — | — | `index.php` → `ctm_entorno/default.php`; `chequeo_alertas.php` roto |

### 4.2 El núcleo compartido Entorno–Identidad y lo que publica

`ctm_entorno` y `ctm_usu` forman el único **núcleo compartido** del sistema: comparten el
contrato de la sesión y lo cambian juntos. Hacia los demás contextos actúan como **servicio
de host abierto**: lo que publican y nada más.

| publica | forma | contenido |
|---|---|---|
| **Sesión** | `$_SESSION` tras el login | `us` (ndoc), `destino`, `usuarioid`, `nombres`, `apellido`, `ndoc`, `cargo`, `gerencia`, `email`, y **una clave por sistema**: `$_SESSION['<CODIGO>'] = nro_priv`. Un contexto lee **su** nivel con `$_SESSION[APP_SISTEMA]`, nunca el de otro |
| **Personas** | `identidad_api_persona(string $ndoc): ?array`, `identidad_api_buscar_personas(array $f): array` | `ndoc, apellido, nombres, email, celular, cargo, organicaid, estadoid` |
| **Permisos** | `identidad_api_nivel(string $ndoc, string $codigo): int`, `identidad_api_tiene_sistema(...)` | el `nro_priv` vigente |
| **Orgánica** | `identidad_api_organica(int $organicaid): ?array`, `identidad_api_arbol(int $estructuraid): array` | nodo / árbol |
| **Geografía** | `entorno_api_localidad(int $id)`, `entorno_api_localidades(int $provinciaid)` | `id, nombre, provincia, pais` |
| **Notificaciones** | `entorno_api_notificar(string $ndoc, string $tipo, string $texto, ?string $link)` | escribe en `ento_notificaciones` — es la **única** forma de notificar |
| **Eventos** | `evento_emitir(...)`, `evento_pendientes(...)`, `evento_marcar(...)` | sobre `integ_eventos` |
| **Eventos que emite** | `persona_creada`, `persona_modificada`, `persona_dada_de_baja`, `privilegio_asignado`, `privilegio_revocado`, `organica_modificada` | para que cada contexto refresque su *read model* |

Hoy nada de esto existe como función: los módulos leen `vw_personas`, `usu_*`, `ento_*`
directo (patrón conformista sobre tablas). Es la primera pieza de §6.

### 4.3 Relaciones: patrón de hoy y patrón objetivo

```
                        ┌─────────────────────────────────────┐
                        │   NÚCLEO COMPARTIDO                 │
                        │   Entorno (AHID_051) · Identidad    │
                        │   (CTM_051) — sesión · personas ·   │
                        │   permisos · orgánica · geografía · │
                        │   notificaciones · bus de eventos   │
                        └──┬──────┬──────┬──────┬──────┬──────┘
          OHS + eventos    │      │      │      │      │     OHS + eventos
   ┌───────────┐  ┌────────┴──┐ ┌─┴─────┐ ┌────┴────┐ ┌┴────────┐ ┌───────────────┐
   │ Concursos │  │  Gastos   │ │Orgán. │ │ Monedas │ │ Horas   │ │ Consola ──►   │
   │ RRHH_060  │  │  comunes  │ │CTM_052│ │ CTM_054 │ │ extras  │ │ Reportes      │
   │  núcleo   │  │  CTM_060  │ │soporte│ │ soporte │ │ Autogest│ │ (cons_dbconf) │
   └───────────┘  └─────┬─────┘ └──┬────┘ └────┬────┘ │ CTM_056 │ │ soporte       │
                        │          │           │      └────┬────┘ └───────────────┘
      cliente–proveedor │          │ OHS       │ ACL       │ cliente–proveedor
                        ▼          ▼           ▼           ▼
                  ┌───────────┐ ┌────────────┐ ┌──────────────────┐
                  │   PADE    │ │ Inventario │ │ EXTERNOS         │
                  │ AHID_053  │ │ Morebius   │ │ K2B · PayWeb ·   │
                  │ núcleo:   │ │ Presupuesto│ │ RHPro · Payroll ·│
                  │ motor de  │ │ (fuera del │ │ EAM · intranet   │
                  │ avisos    │ │  repo)     │ │ int_* · Twilio · │
                  └───────────┘ └────────────┘ │ Azure · Anthropic│
                                               └──────────────────┘
          caminos separados: aced_chatai (ACED) · polo_ofertaadmin (POLO)
```

| relación | hoy (medido) | patrón hoy | patrón objetivo y qué cambia |
|---|---|---|---|
| Núcleo → todos | leen `usu_*`, `ento_*`, `vw_personas` directo; siete módulos **escriben** `usu_usuarios` | conformista sobre tablas + bola de barro | **OHS**: funciones `identidad_api_*`/`entorno_api_*` + eventos; cada contexto con su *read model* de personas si lo necesita. Nadie escribe `usu_*` fuera de Identidad |
| PADE → Horas extras, Autogestión, Gastos, Reportes | insertan en `pade_alertas`, `pade_usuarios`, `pade_localidades_*`, `pade_talertas_*` | bola de barro | **cliente–proveedor**: PADE publica `pade_api_solicitar_aviso(array $aviso)` y consume el evento `aviso_solicitado`; los clientes nunca ven `pade_*` |
| Externos → Monedas, Horas extras, Autogestión, Orgánica | páginas escriben `cp_k2bprod_cotizacionmoneda`; leen `cp_*` por `SELECT` | ACL rota | **ACL**: un solo sincronizador (Consola, `cons_sync_tabla`) escribe `cp_*` en **su** esquema; cada contexto lee por `acl_<sistema>_*()` o recibe eventos `cotizacion_externa_recibida` |
| Inventario/Morebius/Presupuesto → Entorno, Orgánica | `ctm_entorno` lee `vw_integ_*`; `ctm_organica` lee `morebius.*`, `ctm_inventario_unificado.*` por nombre de esquema fijo | conformista cruzando esquemas | **OHS** del contexto Inventario (cuando entre al repo, §10): `inventario_api_*`; mientras, las `vw_integ_*` son su lenguaje publicado provisorio y se leen **sólo** desde `integracion.php` |
| Consola → Reportes | Reportes lee `cons_dbconf` | conformista | **OHS**: `consola_api_conexion(string $nombre)`; Reportes no toca `cons_*` |
| Intranet `int_*` (ODBC) ← todos los legacy | copias de `ws/ws_usu_intranet.php` en 8 módulos escriben `int_usuarios`, `int_log` | ACL copiada 8 veces | **una** ACL en Identidad (`acl_intranet.php`); los contextos no conocen `int_*` |
| Morebius ↔ Concursos | `ctm_conc` arrastra el código de Morebius y usa `integ_registrar_evento('ctm_morebius', …)` desde ahí | bola de barro | **caminos separados**: el código de Morebius sale de `ctm_conc` (§7.5.4) |
| ACED, POLO | comparten plantilla `conf/`, nada más | — | **caminos separados** declarados: no se integran, no se documentan acá más allá de esta fila |

### 4.4 Una base por contexto: asignación

Objetivo: un esquema y un usuario por contexto. El nombre del esquema sigue al del contexto,
no al de la carpeta histórica.

| contexto | esquema objetivo | usuario | tablas que se mudan (prefijos de hoy) | catálogos propios |
|---|---|---|---|---|
| Identidad y acceso | `ctm_identidad` | `u_identidad` | `usu_*`, `vw_personas`, `int_*` (ACL) | `usu_estados` → `ide_estados`; `usu_id` → `ide_id` |
| Entorno | `ctm_entorno` | `u_entorno` | `ento_*`, `integ_eventos` | `ento_estados` |
| Concursos | `ctm_concursos` | `u_concursos` | `concursos`, `concurso_*`, `postulantes`, `postulaciones`, `postulacion_*`, `evaluaciones`, `ia_extracciones`, `concurso_log`, `conc_menus`, `conc_estados`, `conc_id` | ya los tiene |
| Gastos comunes | `ctm_gastos` | `u_gastos` | `gast_*` | `gast_estados`, `gast_id` (existen) |
| Alertas hidrológicas | `ctm_pade` | `u_pade` | `pade_*` | `pade_estados`, `pade_id` (existen) |
| Monedas | `ctm_monedas` | `u_monedas` | `mone_*` | `mone_estados`, `mone_id` (existen) |
| Horas extras | `ctm_hsextras` | `u_hsextras` | `hsex_periodos`, `hsex_estados`, `hsex_id`, `hsex_log`, `hsex_menus` — **no** `hsex_monedas*` (copia de Monedas: se borra) | existen |
| Autogestión | `ctm_autogestion` | `u_autogestion` | `autog_*`, `cbot_*` | `autog_id`, `autog_menus` (existen) |
| Orgánica | `ctm_organica` | `u_organica` | `org_*` (el árbol `usu_organicas`/`usu_estructuras` **queda en Identidad** y Orgánica lo consume por OHS, o se decide moverlo — §11) | `org_menus` |
| Consola | `ctm_consola` | `u_consola` | `cons_*`, **`cp_*`** (la ACL materializada), `sime_*` sólo si se decide conservarlas | `cons_estados`, `cons_id` |
| Reportes | `ctm_reportes` | `u_reportes` | `repo_*` | `repo_id`, `repo_menus` |
| Inventario / Morebius / Presupuesto | `ctm_inventario` | `u_inventario` | `inve_*`, `more_*`, las `vw_integ_*` (pasan a ser read models de Entorno) | — (fuera del repo) |

Reglas de la asignación:

- **`GRANT ALL ON ctm_<ctx>.* TO u_<ctx>`** y ninguna otra concesión. Las credenciales de
  `u_<ctx>` viven **sólo** en `ctm_<ctx>/config/config.local.php`.
- Las funciones `<ctx>_api_*` corren con la conexión de **su** contexto aunque las incluya
  otro: por eso son la única puerta.
- `usu_estados` como catálogo transversal **desaparece**: cada contexto define los suyos (en
  tabla si los administra el usuario, en constantes PHP si son fijos).
- Las secuencias `*_id` se reemplazan por `AUTO_INCREMENT` al mudar; donde se conserve
  `get_id()`, va con `SELECT … FOR UPDATE` dentro de la transacción del agregado.

### 4.5 Agregados por contexto

Un **agregado** es el grupo de tablas que cambia junto y tiene **una** raíz; su invariante se
protege en **una** función de dominio, y todo lo que toca más de una tabla va en una
transacción. Lo que esté en otro agregado se referencia **por id**.

| contexto | agregado (raíz → hijos) | invariantes (lo que la función de dominio garantiza) | eventos que emite |
|---|---|---|---|
| Identidad | **Sistema** `usu_sistemas` → `usu_sist_privilegio` | código único; un privilegio no se duplica por (`codigo_sistema`,`nro_priv`) | `sistema_registrado`, `privilegio_definido` |
| Identidad | **Persona** `vw_personas`/`usu_usuarios` → `usu_user_sist`, `usu_user_grupos` | ndoc único; a lo sumo un privilegio activo por sistema; contraseña con hash | `persona_creada`, `persona_modificada`, `privilegio_asignado`, `privilegio_revocado` |
| Identidad | **Estructura orgánica** `usu_estructuras` → `usu_organicas` | exactamente un nodo raíz (`padreid=0`) por estructura; un nodo pertenece a una sola estructura; sin ciclos | `organica_modificada` |
| Concursos | **Concurso** `concursos` → `concurso_etapas`, `concurso_requisitos`, `concurso_conocimientos`, `concurso_competencias`, `concurso_tribunal`, `concurso_archivos` | transiciones de `CONCURSO_TRANSICIONES`; en `abierto` no se tocan etapas ni requisitos; en `cerrado` nada; no se abre sin etapas | `concurso_abierto`, `concurso_cerrado`, `concurso_cancelado` |
| Concursos | **Postulación** `postulaciones` → `postulacion_*`, `evaluaciones` | única por (concurso, postulante); `nro_postulacion` secuencial por concurso; sólo se inscribe a concurso `abierto`; la etapa N exige la N-1 finalizada; sólo pasan aprobados y hasta `max_postulantes_pasan`; los preferentes **no** suman al total | `postulacion_inscripta`, `postulacion_verificada`, `postulacion_eliminada` |
| Concursos | **Postulante** `postulantes` | único por (`documento`, `tipo_documento`) | — |
| Gastos | **Gasto** `gast_gastos` → `gast_user_gastos`, `gast_inversiones`, `gast_pagos` | la suma de cuotas partes cubre el gasto; un pago cancelado va a `gast_pagos_eliminados`; estados 1→2→3→4→5 | `gasto_creado`, `pago_registrado`, `gasto_cerrado` |
| PADE | **Alerta** `pade_alertas` → `pade_msg_ctrl` | tipo, localidad y grupo destinatario obligatorios; cada envío queda en `msg_ctrl` | `alerta_emitida`, `aviso_enviado`, `aviso_fallido` |
| PADE | **Aviso** (lo que otros piden) | destinatario resuelto; canal válido; un intento por `referencia_id` (idempotente) | consume `aviso_solicitado` |
| Monedas | **Cotización** `mone_monedas` | una por (moneda, origen, fecha) | `cotizacion_registrada` |
| Horas extras | **Período** `hsex_periodos` → solicitudes | cierra el día `$fecha_limite` del mes siguiente; no se agregan solicitudes a período cerrado | `periodo_cerrado`, `solicitud_aprobada` |
| Autogestión | **Trámite** `autog_gestion_tramites` → `autog_ctm_actrrhhdatos*`, `autog_gestion_emails` | los obligatorios del tipo están completos antes de confirmar; transiciones por tipo | `tramite_confirmado` |
| Orgánica | **Sector** `org_*` | — (a definir con el dueño) | — |
| Consola | **Tarea programada** `cons_crontab` → `cons_cronlog` | frecuencia válida; una ejecución por disparo | — |
| Reportes | **Reporte** `repo_reportes` → `repo_reportes_parametros` | la conexión destino existe en Consola; la consulta es `SELECT` (sin DML) | — |

Si un invariante no está en la tabla, **va a una función de `includes/dominio/`**, no a la
página ni a un trigger.

---

## 5. Arquitectura de un módulo

### 5.1 El molde (`ctm_usu` es la base del esqueleto; `ctm_conc` **no**, arrastra Morebius)

```
ctm_<contexto>/
├── index.php                    front controller: ?estado=<id de <prefijo>_menus>
├── CLAUDE.md                    (opcional) sólo lo específico del contexto; lo común está acá
├── DOCUMENTACION_ARCHIVOS.md    qué es cada archivo (formato en §7.5.6)
├── api/
│   ├── <ctx>_api.php            SERVICIO DE HOST ABIERTO: lo único que otro contexto puede incluir
│   └── <ctx>_suscripciones.php  <ctx>_on_<evento>() — qué eventos ajenos consume y qué hace
├── config/
│   ├── config.php               elige config_desa.php o config_prod.php por HTTP_HOST
│   ├── config_desa.php          constantes del módulo (sin secretos)
│   ├── config_prod.php
│   └── config.local.php         SECRETOS: usuario u_<ctx>, su clave, claves de APIs — fuera de git, 600
├── includes/
│   ├── functions.php            bootstrap: config → ../ctm_entorno/includes/{database,sesion,csrf,eventos}.php
│   │                            → helpers → infraestructura → dominio → aplicacion → api
│   ├── helpers.php              UI: h(), badges, combos, alertas JS
│   ├── header.php / navbar.php / footer.php
│   ├── dominio/
│   │   ├── vo.php               objetos de valor del contexto: vo_<nombre>()
│   │   ├── <agregado>.php       entidad raíz: <agregado>_crear(), _validar(), _cambiar_estado(), transiciones
│   │   └── servicio_<nombre>.php  servicios de dominio (reglas entre agregados, sin SQL)
│   ├── aplicacion/
│   │   └── caso_<verbo>_<agregado>.php   un caso de uso por archivo: autoriza · transacción · dominio · repo · eventos
│   └── infraestructura/
│       ├── <agregado>_repo.php  repositorio: repo_<agregado>_obtener/guardar/buscar, SQL preparado
│       ├── <agregado>_rm.php    read models: consultas de listado/dashboard (sólo lectura)
│       └── acl_<externo>.php    capa anticorrupción hacia sistemas externos
├── web/
│   ├── mod/<area>/<agregado>/{listar,nuevo,ver,editar}.php   páginas (incluidas por index.php)
│   ├── int/ajax/aj_<agregado>_<accion>.php                   endpoints JSON autónomos
│   └── cron/                    tareas programadas (sin sesión web; validan por token)
├── sql/
│   ├── 001_esquema.sql          CREATE DATABASE ctm_<ctx>; usuario; tablas; idempotente
│   ├── 002_menus.sql
│   └── NNN_<que>.sql
├── assets/{css,js}/             CSS con variables --<prefijo>-*; JS en UN objeto <PREFIJO>
└── uploads/                     .htaccess que niega PHP; nombres aleatorios; servido por script con permisos
```

**Constantes** (`config/config_desa.php`): `<PREFIJO>_APP` (guard), `APP_SISTEMA` (el código,
igual a `usu_sistemas.codigo`), `APP_TITULO`, `APP_NAME`, `APP_VERSION`, `APP_ROOT/CONFIG/
INCLUDES/WEB/UPLOADS/ASSETS`, `APP_URL`, `TAMANO_PAGINA`, `APP_DB` (nombre del esquema propio)
y `getDbConfig()` que lee `config.local.php`. **Un contexto, un código**: `cron.php`,
`session.php`, `config_*.php` y `ajax/` declaran el mismo (hoy no —§9.1—).

### 5.2 El ciclo de una página

```
GET index.php?estado=14
  index.php
    define <PREFIJO>_APP, APP_ROOT…            require includes/functions.php
    [$usu,$nivel,$destino] = verificarSesion($estado)       → sin sesión: ../ctm_entorno/default.php
    verificarPermisoMenu($estado,$nivel,$usu) o CORTA (HTTP 403 + mensaje); no sólo SweetAlert
    include header.php · navbar.php (lee <prefijo>_menus: menu=1, estadoid activo, nivacc ≤ $nivel;
                                     resuelve $direcion con realpath() dentro de APP_WEB y filtrando estadoid)
    include_once $direcion                                   la página hereda $usu, $nivel, $estado, $hoy, $destino
    include footer.php
```

La página — **capa de interfaz**: request → caso de uso → vista. Sin SQL, sin reglas:

```php
// web/mod/concursos/abrir.php
require_once APP_INCLUDES . '/aplicacion/caso_abrir_concurso.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $r = caso_abrir_concurso((int)$_POST['id'], $usu, $nivel);
    if ($r['ok']) { redirigir(url_estado(13, ['id' => $r['id']])); }
    $error = $r['error'];
}
$c = repo_concurso_obtener((int)$_GET['id']);        // lectura directa al repo está permitida en la vista
// … HTML; cada salida con h()
```

El caso de uso — **capa de aplicación**: autoriza, transacción, dominio, persistencia, eventos:

```php
// includes/aplicacion/caso_abrir_concurso.php
function caso_abrir_concurso(int $id, string $usu, int $nivel): array {
    if ($nivel < NIVACC_ADMINISTRAR) return ['ok' => false, 'error' => 'Sin permiso.'];
    $db = Database::getInstance();                     // conexión de ESTE contexto (u_concursos)
    $db->begin();
    try {
        $c = repo_concurso_obtener($id, true);          // FOR UPDATE: carga el agregado entero
        if (!$c) throw new DomainException('El concurso no existe.');
        $c = concurso_cambiar_estado($c, 'abierto', $usu);     // dominio: transiciones + invariantes
        repo_concurso_guardar($c, $usu);                        // repositorio
        registrar_log('concursos', $id, 'estado', 'borrador', 'abierto', $usu);
        evento_emitir(APP_SISTEMA, 'concurso_abierto', ['referencia_id' => $id, 'numero' => $c['numero']], $usu); // outbox, misma transacción
        $db->commit();
        return ['ok' => true, 'id' => $id];
    } catch (DomainException $e) { $db->rollback(); return ['ok' => false, 'error' => $e->getMessage()]; }
      catch (Throwable $e)       { $db->rollback(); error_log($e); return ['ok' => false, 'error' => 'No se pudo abrir el concurso.']; }
}
```

El dominio — sin SQL (ejemplo en §1.3). El repositorio — sólo SQL preparado:

```php
// includes/infraestructura/concurso_repo.php
function repo_concurso_obtener(int $id, bool $bloquear = false): ?array {
    $db = Database::getInstance();
    $sql = 'SELECT * FROM concursos WHERE id = ?' . ($bloquear ? ' FOR UPDATE' : '');
    $c = Database::fetchUpper($db->execute($sql, [$id], 'i'));
    if (!$c) return null;
    $c['etapas'] = Database::fetchAll($db->execute('SELECT * FROM concurso_etapas WHERE id_concurso = ? ORDER BY orden', [$id], 'i'));
    return $c;
}
```

### 5.3 Base de datos: convenciones

- **Un esquema por contexto** (§4.4); dentro, prefijo de 4–5 letras + `_` para las tablas
  (las sin prefijo de Concursos son herencia; las nuevas de ese contexto llevan `conc_`).
- **Toda tabla de negocio**: `estadoid`, `auditoria_usu`, `auditoria_fecha`.
- **Catálogo de estados** por contexto: `(estadoid, descripcion, tabla)` o constantes PHP.
- **Menú**: `<prefijo>_menus` con el modelo, los tipos, la escala de `nivacc` y las formas
  visuales del **estándar de menús (§5.8)**; ids `1 Inicio`, `10 Salir` reservados en todos.
- **Vistas**: sólo **dentro** del propio esquema (read models). Ninguna vista apunta a otro
  esquema.
- **IDs**: `AUTO_INCREMENT`. Si se conserva `get_id()`, con `FOR UPDATE` en la transacción.
- **Claves de fila en MAYÚSCULAS** con `fetchUpper()` (`$row['NOMBRE']`): convención
  histórica; un contexto nuevo puede normalizar en minúsculas en el repositorio, pero no
  mezcla dentro del mismo contexto.
- **Nombres de esquema y host no van en el SQL**: la conexión ya está en el esquema propio.

### 5.4 La capa de datos: `Database`

Una sola clase canónica en `ctm_entorno/includes/database.php`, incluida por todos (hoy hay 5
copias distintas —§9.3—). API mínima que todo módulo asume:

| método | qué hace |
|---|---|
| `Database::getInstance($alias='default')` | una instancia por alias; el alias `default` es el esquema del contexto que incluye |
| `execute($sql, $params=[], $types='')` | con `$params` prepara y liga; sin `$params` sólo para SQL **sin datos variables** |
| `fetchUpper($r)` / `fetchAll($r)` / `numRows($r)` / `lastInsertId()` | lectura |
| `begin()` / `commit()` / `rollback()` | **a agregar**: `mysqli::begin_transaction/commit/rollback` |
| `escape($s)` | sólo identificadores validados contra lista blanca; nunca valores |

Lo que **no** se escribe más: `sql_do_query("… WHERE id='$id'")`, `addslashes()` como
escape, `mysqli_connect` dentro de una función, `echo $sql`, `die()` en la capa de datos,
`esquema.tabla` en el SQL.

### 5.5 Servicio de host abierto: `api/<ctx>_api.php`

- Funciones **puras hacia afuera**: reciben escalares o arrays simples, devuelven arrays del
  lenguaje publicado (§3), nunca `mysqli_result` ni filas crudas con claves internas.
- Usan la conexión **del contexto dueño** (sus credenciales), aunque las llame otro.
- **Sólo lectura o comandos explícitos**: `identidad_api_persona()` lee; `pade_api_solicitar_aviso()`
  es un comando que PADE acepta y procesa con **sus** reglas. No existe `pade_api_insertar_alerta()`.
- Se documentan en §3 (qué devuelven) y en §4.2/4.3 (quién las usa).
- Cambios incompatibles → función nueva con sufijo de versión; la vieja se mantiene hasta que
  nadie la llame (se verifica con `grep`).

### 5.6 Eventos: emisión, despacho, consumo

```
[contexto A, caso de uso]           [cron despachador, Entorno]              [contexto B]
  begin                              cada minuto:                              B_on_<evento>($e):
  cambio en agregado                   lee integ_eventos pendientes             begin · idempotencia por evento_id
  evento_emitir(...)  ──outbox──►      para cada suscriptor registrado  ──►      su propio cambio · commit
  commit                               llama, marca entregado/fallido,          (si falla: reintento con backoff;
                                       reintenta N veces                         tras N, alerta a Entorno)
```

- `integ_eventos` suma las columnas `evento_id` (PK), `estado` (`pendiente/entregado/fallido`),
  `intentos`, `entregado_a` (JSON de suscriptores ya servidos). Hoy no tiene DDL en el repo (§10).
- **Nombre del evento**: pasado, lenguaje ubicuo, listado en §4.5. **Contenido** (`datos_json`):
  sólo ids y los datos mínimos que el consumidor necesita, en el lenguaje publicado; nunca la
  fila entera.
- **Idempotencia**: el consumidor guarda `evento_id` procesados (`<prefijo>_eventos_vistos`) y
  descarta repetidos.
- Hoy existe `integ_registrar_evento()` que escribe y `integ_obtener_eventos()` que lee para el
  dashboard; **no hay despachador ni suscriptores**. Es la pieza 2 de §6.

### 5.7 Capa anticorrupción: `infraestructura/acl_<externo>.php`

Una función por necesidad del dominio, que habla con el externo (ODBC, HTTP, tabla `cp_*`) y
devuelve nuestro lenguaje: `acl_rhpro_plantel(string $legajo): ?array` →
`['legajo','ndoc','apellido','nombres','cc','categoria']`. El modelo del externo (nombres de
columna, códigos) **no sale** de la ACL. Si el externo cambia, cambia la ACL y nada más.

---

### 5.8 Estándar de menús: datos, tipos y formas

El menú es la **interfaz publicada de un contexto hacia las personas**: define qué se puede
hacer, quién puede hacerlo (`nivacc`) y cómo se llega (`?estado=`). Hoy cada módulo lo arma
distinto (columnas `estado` vs `estadoid`, `tipo` con significados diversos, `menu.php`
legacy con CSS inline, Bootstrap 5 en los nuevos, menús leídos de la tabla de otro módulo).
Este es el estándar único; se aplica completo en lo nuevo y al tocar el menú de un contexto.

#### 5.8.1 Modelo de datos: `<prefijo>_menus`

Una tabla por contexto, **en su propio esquema**, con estas columnas y ninguna otra
obligatoria (modelo: `ctm_conc/sql/tablas_estados_menus.sql`, corregido):

| columna | tipo | significado | regla |
|---|---|---|---|
| `id` | `SMALLINT UNSIGNED` PK | el **estado** de la URL (`index.php?estado=<id>`) | estable para siempre: un `id` no se reutiliza ni se renumera (los enlaces y los permisos lo referencian) |
| `opcion` | `VARCHAR(60)` | texto visible | sustantivo o verbo+sustantivo, en español, con mayúscula inicial, sin punto: *Concursos*, *Nuevo concurso*, *Ranking* |
| `descripcion` | `VARCHAR(200)` | ayuda larga (tooltip, breadcrumb, buscador) | una frase; vacía si `opcion` alcanza |
| `tipo` | `ENUM('seccion','pagina','accion','externo','separador','sistema')` | **qué clase de ítem es** (§5.8.2) | el tipo decide cómo se dibuja y si tiene `direccion` |
| `menu` | `TINYINT(1)` | `1` = primer nivel (cabecera de la barra), `0` = hijo | sólo `seccion` y `externo` pueden ser `menu=1` |
| `submenu` | `SMALLINT UNSIGNED` NULL | `id` del padre; `NULL` en el primer nivel | máximo **dos niveles** (barra → desplegable). Un tercer nivel es una página con pestañas, no un menú |
| `orden` | `SMALLINT` | posición entre hermanos | de 10 en 10, para insertar sin renumerar |
| `direccion` | `VARCHAR(200)` NULL | ruta relativa a la raíz del contexto (`web/mod/<area>/<agregado>/listar.php`) o URL absoluta si `tipo=externo` | obligatoria en `pagina`, `accion`, `externo`; `NULL` en `seccion`, `separador` |
| `icono` | `VARCHAR(40)` NULL | clase de Bootstrap Icons (`bi-people`) | obligatoria en `menu=1`; opcional en hijos |
| `nivacc` | `TINYINT` | nivel mínimo de privilegio para **ver y ejecutar** | §5.8.3 |
| `estadoid` | `TINYINT` | `9` activo, `10` baja (catálogo del contexto, `tabla='<PREFIJO>_MENUS'`) | la baja es lógica; una fila nunca se borra |
| `auditoria_usu`, `auditoria_fecha` | | quién y cuándo | como toda tabla |

Índices: `(menu, estadoid, orden)` para la barra; `(submenu, estadoid, orden)` para los
desplegables. Vista de conveniencia `<prefijo>_menus_activos` (sólo `estadoid=9`).

**Lo que desaparece**: la columna `estado` varchar `'1'/'0'` de los legacy; `tipo` como clave
foránea al catálogo de estados; `pade_menus` leída por otros módulos; `direccion` con
`?mod=&ac=`; ids numerados a mano sin rango.

**Ids reservados en todo contexto**:

| id | tipo | opcion | direccion | para qué |
|---|---|---|---|---|
| `1` | `pagina` | Inicio | `web/mod/inicio/dashboard.php` | la landing del contexto (`pagina_inicio` en `usu_sistemas` apunta a `?estado=1`) |
| `2` | `pagina` | Acerca de | `web/mod/acerca_de/version.php` | versión, dueño, interfaz publicada, enlace a la documentación |
| `9` | `pagina` | Mis datos | `web/mod/datos_personales/detalle.php` | sólo si el contexto los muestra; si no, no se usa el id |
| `10` | `accion` | Salir | `web/int/salir.php` | cierra sesión. **`estado=10` es salir en todos los contextos** |
| `11–99` | | | | reservados para el núcleo (futuras acciones transversales) |
| `100+` | | | | las secciones del contexto |

Rango por sección: la sección `N00` (`100`, `200`, `300`…) y sus hijos `N01–N99`. Ejemplo
Concursos: `100 Concursos` → `101 Listar`, `102 Nuevo`, `110 Ver` (sin mostrar), `120 Abrir`
(acción); `200 Postulantes` → `201 Listar`, `202 Nuevo`…

#### 5.8.2 Tipos de ítem y cómo se dibuja cada uno

| `tipo` | qué es | cómo se dibuja | `direccion` |
|---|---|---|---|
| `seccion` | cabecera de primer nivel que agrupa hijos | ítem de la barra con desplegable; no es clickeable si tiene hijos; si no tiene, se oculta | `NULL` |
| `pagina` | una vista: listar, ver, nuevo, editar | ítem del desplegable (si `menu=0` y se quiere en el menú) o **no se muestra** (páginas de detalle que se abren desde un listado: igual tienen fila, por el permiso) | ruta relativa |
| `accion` | algo que ejecuta y redirige (abrir concurso, cerrar período, salir) | botón dentro de la página, **no** en la barra; la fila existe para que `verificarPermisoMenu` la autorice | ruta relativa |
| `externo` | enlace a otro contexto o a una URL | ítem con ícono de salida, abre en la misma pestaña si es otro contexto del entorno, nueva si es externo | URL absoluta o `../ctm_<ctx>/index.php?estado=1` |
| `separador` | línea entre grupos de un desplegable | `<li><hr class="dropdown-divider"></li>` | `NULL` |
| `sistema` | **sólo en Entorno**: la entrada a un contexto en el panel | card del panel (§5.8.5) con color e ícono de `modules.php` | `usu_sistemas.pagina_inicio` |

Regla: **toda página o acción alcanzable tiene su fila**, aunque no se muestre. Sin fila no
hay permiso, y sin permiso `index.php` no la incluye. Es lo que convierte al menú en la
lista de control de acceso del contexto.

#### 5.8.3 Niveles de acceso (`nivacc`): escala estándar

La escala es la misma en todos los contextos, para que un privilegio se lea igual en
`usu_sist_privilegio.nro_priv` y en `*_menus.nivacc`:

| nivacc | nombre del privilegio (en `usu_sist_privilegio.descripcion`) | puede |
|---|---|---|
| `0` | — | nada; es el valor de "no tiene el sistema" |
| `1` | `CONSULTA` | ver listados y detalles |
| `2` | `OPERADOR` | crear y editar lo propio del flujo diario |
| `3` | `SUPERVISOR` | aprobar, cambiar estados, ver lo de otros |
| `4` | `ADMINISTRADOR` | configurar catálogos, parámetros, menús del contexto |
| `5` | `AUDITOR`/`SISTEMAS` | todo, incluido el log y la interfaz publicada |

Un contexto puede no usar todos los niveles, pero **no inventa otros ni cambia el
significado** de uno. Convención por tipo de página: `listar`/`ver` → 1; `nuevo`/`editar` →
2; acciones de estado → 3; `admin/*` → 4. Las constantes `NIVACC_CONSULTA…NIVACC_AUDITOR`
viven en `ctm_entorno/includes/sesion.php` y son las que usan los casos de uso.

#### 5.8.4 Comportamiento: cómo se resuelve un `estado`

1. `index.php` recibe `estado`; lo castea a entero (`(int)`), sin `LIKE`, sin `addslashes`.
2. `verificarSesion()`; si no hay, redirige a `../ctm_entorno/default.php`. **No existe el
   `estado=7` público**: lo público vive fuera de `index.php`.
3. `verificarPermisoMenu($estado, $nivel)`: busca la fila con `id=?` **y `estadoid=9`**; si no
   existe → 404; si `nivacc > nivel` → **403 en el servidor** (cabecera HTTP + página de
   "sin permiso"); nunca "avisar por JS y renderizar igual".
4. `navbar.php` dibuja: ítems `menu=1 AND estadoid=9 AND nivacc <= ? ORDER BY orden`; por cada
   uno, hijos `submenu=? AND estadoid=9 AND nivacc <= ? ORDER BY orden`; una `seccion` sin
   hijos visibles no se dibuja. Todo con consultas preparadas.
5. **Breadcrumb**: CTE recursivo sobre `submenu` desde el `estado` actual (como hoy en
   `ctm_usu/includes/navbar.php:69-75`), mostrado bajo la barra: *Inicio › Concursos › Nuevo*.
6. `$direcion = realpath(APP_WEB . '/' . direccion)`; se incluye **sólo si** está dentro de
   `APP_WEB` y existe; si no, 500 con log, nunca `include` de lo que venga.
7. El ítem activo (`estado` actual o su padre) lleva `.active` y `aria-current="page"`.

#### 5.8.5 Formas: las tres piezas visuales estándar

Todas con **Bootstrap 5.3 + Bootstrap Icons + SweetAlert2 por CDN con SRI**, como ya hacen
`ctm_usu` y `ctm_conc`; nada de CSS inline ni de `menu.php`/`menuv.php` legacy.

**(a) Panel del Entorno** (`ctm_entorno`, `estado=6`): la puerta a los contextos. Una **card
por sistema** que la persona tiene (`usu_user_sist` activo): color y símbolo de
`$MODULE_STYLES` según `$MODULE_STYLE_MAP[codigo]`, ícono `img/picto/<CODIGO>.png`, nombre,
privilegio de la persona en ese sistema, y el enlace a `pagina_inicio`. Arriba, la **barra
superior** (nombre, foto, alertas rojas, *Salir*) y a la izquierda el **sidebar offcanvas**
con los sistemas en lista. Los sistemas se ordenan por `usu_agrupamiento` y después por
nombre.

**(b) Barra del contexto** (`includes/navbar.php` de cada `ctm_<ctx>`): `navbar navbar-expand-lg`
fija arriba, con: marca a la izquierda (ícono + `APP_TITULO`, enlace a `?estado=1`); las
`seccion` con sus desplegables (`dropdown-menu`), ícono + `opcion`; a la derecha, el nombre
de la persona, su privilegio (`badge`), *Volver al entorno* (`externo`) y *Salir*
(`?estado=10`). Debajo, el **breadcrumb**. En pantallas chicas colapsa a hamburguesa. Los
colores del contexto salen de `assets/css/styles.css` con variables `--<prefijo>-primario`,
`--<prefijo>-secundario`, tomadas de `modules.php` para que el contexto se vea igual en su
barra y en su card del panel.

**(c) Acciones dentro de la página**: lo que cambia estado (abrir, cerrar, aprobar, eliminar)
es un **botón** en la página de detalle (`btn btn-<variante>` con ícono), nunca un ítem del
menú; pide confirmación con `<PREFIJO>.confirm()` (SweetAlert2) y envía un POST con token
CSRF a la fila `accion` correspondiente. Variantes: `primary` crear/guardar, `success`
aprobar/abrir, `warning` suspender/cerrar, `danger` eliminar/cancelar, `secondary` volver.
Botones deshabilitados cuando la transición no está permitida en el estado actual
(`<agregado>_puede_<accion>($x)`), con tooltip que dice por qué.

**Listados estándar**: campo de búsqueda + filtros arriba (`generarCampoBusqueda()`), tabla
`table table-hover` con cabecera generada (`generarTablaHeader()`), badges de estado
(`badgeEstado<Agregado>()`), columna de acciones a la derecha con íconos (ver / editar),
paginación `TAMANO_PAGINA` abajo (`generarPaginacion()`). Los helpers ya existen en
`ctm_gastcomunes/includes/helpers.php` y `ctm_conc/includes/helpers.php`: se unifican en el
núcleo y se incluyen.

#### 5.8.6 Cómo se declara el menú de un contexto: `sql/002_menus.sql`

Idempotente, con `INSERT … ON DUPLICATE KEY UPDATE` (una sentencia por fila, con su `;`):

```sql
INSERT INTO conc_menus (id, opcion, descripcion, tipo, menu, submenu, orden, direccion, icono, nivacc, estadoid, auditoria_usu)
VALUES
 (1,   'Inicio',          'Tablero del contexto',        'pagina',   1, NULL, 10,  'web/mod/inicio/dashboard.php',        'bi-house',        1, 9, 'sistema'),
 (10,  'Salir',           'Cerrar la sesión',            'accion',   0, NULL, 990, 'web/int/salir.php',                   'bi-box-arrow-right', 1, 9, 'sistema'),
 (100, 'Concursos',       'Gestión de concursos',        'seccion',  1, NULL, 20,  NULL,                                  'bi-briefcase',    1, 9, 'sistema'),
 (101, 'Listar',          'Concursos vigentes e históricos','pagina',0, 100,  10,  'web/mod/concursos/concurso/listar.php', NULL,            1, 9, 'sistema'),
 (102, 'Nuevo concurso',  'Alta de un concurso',         'pagina',   0, 100,  20,  'web/mod/concursos/concurso/nuevo.php', 'bi-plus-circle', 2, 9, 'sistema'),
 (110, 'Ver concurso',    'Detalle (desde el listado)',  'pagina',   0, 100,  900, 'web/mod/concursos/concurso/ver.php',   NULL,            1, 9, 'sistema'),
 (120, 'Abrir concurso',  'borrador → abierto',          'accion',   0, 100,  910, 'web/mod/concursos/concurso/abrir.php', NULL,            3, 9, 'sistema')
ON DUPLICATE KEY UPDATE opcion=VALUES(opcion), descripcion=VALUES(descripcion), tipo=VALUES(tipo), menu=VALUES(menu),
 submenu=VALUES(submenu), orden=VALUES(orden), direccion=VALUES(direccion), icono=VALUES(icono), nivacc=VALUES(nivacc);
```

`estadoid` **no** se pisa en el `UPDATE`: la baja de un ítem es una decisión operativa
(`UPDATE … SET estadoid=10`) que el script de menús no revierte. Las filas con `orden ≥ 900`
son las que existen por permiso y no se muestran.

#### 5.8.7 Qué cambia en lo existente (y cómo se migra, por contexto, con una spec 7.5.3)

| hoy | estándar |
|---|---|
| `estado` varchar `'1'/'0'` (legacy) · `estadoid` sin `tipo` (usu) · `tipo` como FK a estados (conc) | `estadoid` 9/10 + `tipo` ENUM |
| `menu`/`submenu` sin `orden`; orden por `id` | `orden` de 10 en 10 |
| `direccion` con `?mod=&ac=` y redirección por `LIKE` | ruta relativa; el castea a `(int)` |
| menús de otro módulo (`pade_menus` desde cinco módulos) | cada contexto el suyo |
| `menu.php`/`menuv.php`/`ariadna.php` legacy con CSS inline | `navbar.php` Bootstrap 5 + breadcrumb CTE |
| "acceso denegado" por SweetAlert y la página se renderiza | 403 en el servidor |
| `estado=7` público | eliminado |
| ids sin rango (`11-14`, `21-23`…) | rangos por sección `N00–N99`; reservados `1–99` |

---

## 6. Camino de migración (patrón *estrangulador*)

De una base compartida y 14 módulos acoplados a contextos aislados, **sin parar producción y
sin big bang**. Cada fase deja el sistema funcionando y se puede frenar ahí.

**Esta tabla es el esqueleto del `MASTERPLAN.md` raíz** (§7.1): cada fase es una entrada
del Masterplan con su estado, y cada ítem de la columna "qué se hace" es **una spec**
(`specs/NNN-<slug>/`) que se especifica, planifica y corta en tareas antes de tocarse. La
columna "criterio de salida" es el criterio de aceptación de la fase, verificable por
comando.

| fase | qué se hace | criterio de salida |
|---|---|---|
| **0. Red de seguridad** | `git init` + `.gitignore`; levantar `localhost` (Docker: MariaDB + Apache/PHP); respaldo de la base; sacar secretos a `config.local.php` y **rotarlos**; `display_errors=0` en prod | el repo versionado, un entorno local que levanta, cero secretos en el código |
| **1. Infraestructura común única** | una `Database` (con `begin/commit/rollback`), `csrf.php`, `eventos.php` en `ctm_entorno/includes/`; los módulos `config/` la incluyen y borran su copia; `verificarPermisoMenu` corta en servidor; `estado=7` eliminado; `session_regenerate_id` en login | 1 `database.php`; `grep -rl csrf_verificar web/ | wc -l` = cantidad de formularios |
| **2. Fronteras en el código** (sin mover datos) | publicar `identidad_api_*`, `entorno_api_*`, `pade_api_solicitar_aviso`, `consola_api_conexion`; reemplazar en los clientes los `SELECT`/`INSERT` cruzados por esas llamadas; despachador de eventos + primeros suscriptores (PADE consume `aviso_solicitado`) | `grep` de `pade_`, `usu_`, `ento_`, `cons_`, `cp_` fuera de su contexto = 0 |
| **3. Retirar lo muerto** | borrar `ctm_chatia`, `ctm_usu_`, `ctm_organica_`, `ctm_segtramites` (tras decisión §11), `web/mod/viejas/`, los `_old`/`_1`; sacar Morebius de `ctm_conc`; separar `aced_chatai` y `polo_ofertaadmin` a sus propios repos | §4.1 sin filas "reemplazado/duplicado" |
| **4. Un esquema por contexto** | por contexto, del más aislado al más usado: crear `ctm_<ctx>` + `u_<ctx>`; `RENAME TABLE contexto_unificado.<prefijo>_* TO ctm_<ctx>.*` (misma instancia: instantáneo); cambiar `getDbConfig()`; los *read models* de personas (`<prefijo>_personas_rm`) se llenan por `identidad_api_*` y se mantienen por eventos. Orden sugerido: Monedas → Reportes → Consola (+`cp_*`) → Gastos → Horas extras → Autogestión → Concursos → PADE → Orgánica → Identidad/Entorno al final | `SHOW GRANTS FOR u_<ctx>` muestra un solo esquema; `contexto_unificado` queda vacía |
| **5. Capas dentro de cada contexto** | al tocar cada página: SQL → `infraestructura/`, reglas → `dominio/`, orquestación → `aplicacion/`; máquinas de estado; objetos de valor; transacciones | `grep -l "SELECT\|INSERT\|UPDATE" web/mod/ | wc -l` = 0 en el contexto |

La fase 4 es la que da el **aislamiento real**: hasta ahí, la frontera es disciplina; desde
ahí, es el motor. Por eso las fases 1–3 son cortas y la 4 es la meta.

---

## 7. Proceso de trabajo: Masterplan y spec-kit

DDD dice **qué** forma tiene un buen diseño; no dice **cómo** se llega a él sin romper lo que
anda. Para eso este proyecto usa dos instrumentos, y los usa en **todo** lo que se planifica,
ejecuta y documenta: el **Masterplan** (dónde estamos y hacia dónde vamos) y **spec-kit**
(cómo se hace cada paso). No son burocracia: son la parte de "calidad" del trabajo —un
cambio especificado antes de escribirse no duplica, no rompe una frontera sin saberlo y deja
rastro para el que viene después.

### 7.1 Los instrumentos

#### `MASTERPLAN.md` — el plan maestro

Un archivo en la raíz y uno por contexto. Es **el estado de ahora de la hoja de ruta**: se
lee para saber qué fase corre, qué specs están abiertas y qué se decidió. Se actualiza al
cerrar cada spec, no al final.

```
MASTERPLAN.md (raíz)
├── Objetivo            una frase: contextos aislados, una base por contexto, dominio en el centro
├── Estado al <fecha>   fase en curso; % de contextos migrados; deuda crítica abierta
├── Fases               las de §6, cada una con: estado (pendiente / en curso / terminada),
│                       criterio de salida (verificable por comando), specs que la componen
│                       (enlace a specs/NNN), bloqueos
├── Decisiones          registro de decisiones (ADR cortos): fecha · decisión · por qué ·
│                       alternativas descartadas · quién. Acá van las respuestas de §11
├── Riesgos             lo que puede salir mal y qué se hace si pasa
└── Próximos pasos      las 3 specs siguientes, en orden

ctm_<contexto>/MASTERPLAN.md
├── Dueño               quién decide en este contexto
├── Estado al <fecha>   capa alcanzada (conf/ · config/ · capas · esquema propio · OHS · eventos)
├── Deuda del contexto  lo de §9 que le toca + lo nuevo que aparezca (lo "no cubierto")
├── Agregados           los de §4.5 con estado: modelado / con repositorio / con máquina de estados
├── Interfaz publicada  api/ y eventos: existentes, pendientes, deprecadas
├── Specs               abiertas y cerradas de este contexto
└── Decisiones          locales al contexto
```

El Masterplan raíz **manda sobre** los de contexto: una fase no se da por terminada hasta
que todos los contextos que toca la cerraron.

#### spec-kit — el método por cambio

La **constitución** del proyecto son §1 y §2 de este archivo, copiadas a
`.specify/memory/constitution.md` (se mantiene con `/speckit-constitution`; si cambia una
regla acá, cambia allá en la misma spec). Cada cambio recorre, en orden y sin saltear:

| paso | comando | produce | qué responde |
|---|---|---|---|
| 1. Especificar | `/speckit-specify` | `specs/NNN-<slug>/spec.md` | **qué** y **por qué**, sin cómo. Requisitos verificables, lo que NO cubre, preguntas abiertas marcadas |
| 1b. Aclarar | `/speckit-clarify` | preguntas resueltas en `spec.md` | las ambigüedades que el paso 1 dejó marcadas, antes de planificar |
| 2. Planificar | `/speckit-plan` | `plan.md` (+ `research.md`, `data-model.md`, `contracts/` si hacen falta) | **cómo**, con verificación contra la constitución, opciones con veredicto (incluido *no hacerlo*), plan de prueba y reversa |
| 3. Tareas | `/speckit-tasks` | `tasks.md` | el corte en tareas atómicas, ordenadas, con dependencias y responsable |
| 3b. Analizar | `/speckit-analyze` | informe de consistencia | que spec, plan y tareas digan lo mismo y cubran la constitución |
| 4. Implementar | `/speckit-implement` | commits, uno por tarea | la ejecución, con las verificaciones del plan |
| 5. Cerrar | (manual) | Masterplan, `HISTORIAL.md`, `DOCUMENTACION_ARCHIVOS.md`, glosario | el rastro |

`/speckit-checklist` genera listas de verificación para el plan o la implementación cuando
el cambio lo amerita (seguridad, migración de datos).

### 7.2 Qué lleva cada artefacto en este sistema

Lo que spec-kit pide **más lo que DDD exige**. Una spec que no responde estas preguntas no
está lista para planificar.

**`spec.md`**
- **Contexto acotado afectado**: uno. Si son dos, o son dos specs o es una integración
  (y entonces la spec es de la relación: patrón, interfaz, eventos).
- **Lenguaje ubicuo**: términos nuevos o cambiados, con su definición (van a §3 al cerrar).
- **Agregado(s)**: raíz, hijos, **invariantes** que la spec agrega o cambia, estados y
  transiciones, objetos de valor.
- **Eventos**: emitidos (nombre en pasado, contenido) y consumidos (de qué contexto).
- **Mapa de contextos**: si crea o cambia una relación, el patrón (§1.2) y qué publica/consume.
- **Interfaz publicada**: funciones `api/` nuevas, cambiadas o deprecadas.
- **Criterios de aceptación**: verificables por comando o por prueba manual descrita.
- **Fuera de alcance** y **preguntas abiertas** (marcadas `[NEEDS CLARIFICATION]`).

**`plan.md`**
- **Verificación contra la constitución**: las 16 reglas de §2, una por una: *cumple / no
  aplica / excepción* — y toda excepción con su justificación y su fecha de revisión.
- **Capas tocadas**: archivos por capa (`web/`, `aplicacion/`, `dominio/`,
  `infraestructura/`, `api/`), y la confirmación de que el dominio no tiene SQL.
- **Datos**: `sql/NNN_*.sql` (idempotentes), read models, impacto en el esquema del contexto
  y **en ningún otro**.
- **Opciones consideradas** con veredicto, costo y riesgo; la opción "no hacerlo" siempre está.
- **Plan de prueba** en `localhost` (qué se ejercita, con qué datos, qué se espera) y
  **reversa** probada antes de aplicar.
- **Secretos y permisos**: qué `GRANT` o `config.local.php` cambia.

**`tasks.md`**
- Tareas atómicas (una tarea = un commit), en orden, con dependencias explícitas.
- Separadas por tipo: datos (`sql/`), dominio, infraestructura, aplicación, interfaz,
  documentación.
- Responsable: desarrollador / dueño del contexto / administrador de la base; lo que
  requiere producción se marca.
- Estado: `[ ]` / `[x]`, con fecha al cerrar.

**Al cerrar (paso 5)**, en este orden:
1. `ctm_<ctx>/DOCUMENTACION_ARCHIVOS.md`: una línea por archivo nuevo o movido.
2. `ctm_<ctx>/HISTORIAL.md`: entrada nueva **arriba**, fecha y hora, qué se cambió y por qué,
   qué se probó, **qué falló y qué se revirtió**. Incremental: nunca se reescribe una entrada
   anterior; una corrección es una entrada nueva.
3. `ctm_<ctx>/MASTERPLAN.md` y `MASTERPLAN.md` raíz: spec cerrada, fase avanzada, decisiones
   tomadas, deuda nueva.
4. §3 (glosario) y §4 (mapa) de este archivo si la spec los cambió.
5. La spec queda en `specs/` como registro; no se borra ni se edita después de cerrada.

### 7.3 Cuándo aplica cada nivel

| cambio | proceso |
|---|---|
| **Trivial**: un `h()` que faltaba, un typo, una condición de una línea sin regla de negocio | commit directo + entrada en `HISTORIAL.md`. Sin spec |
| **Dentro de un contexto**: página nueva, agregado nuevo, migración de una página a capas, `sql/NNN` | spec completa (7.1–7.2); cierra con 7.2 paso 5 |
| **Entre contextos**: relación nueva, evento nuevo, función `api/` nueva o cambiada, mover una tabla de esquema | spec completa **+** actualización de §4.3 **+** decisión en `MASTERPLAN.md` raíz (con el patrón de relación elegido) |
| **Fase de §6** | entrada del `MASTERPLAN.md` raíz que agrupa N specs; se cierra cuando el criterio de salida pasa por comando |
| **Cambio de regla** (§1, §2) | spec que modifica este archivo y la constitución, con decisión registrada; nunca por edición directa |
| **Incidente en producción** | primero dejar el sistema sano (reversa); después spec de corrección con el diagnóstico **confirmado por efecto**, no por hipótesis; `HISTORIAL.md` con lo que se probó y lo que no era |

### 7.4 Dónde va cada cosa escrita

Siete lugares; cada uno responde una pregunta distinta. Escribir en el lugar equivocado es
peor que no escribir: nadie lo vuelve a encontrar.

| dónde | qué pregunta responde | qué NO va |
|---|---|---|
| `CLAUDE.md` (este archivo) | ¿cómo se diseña y se trabaja acá? (principios, reglas, glosario, mapa, molde) | estado de ninguna tarea; eso es el Masterplan |
| `MASTERPLAN.md` raíz | ¿en qué fase estamos, qué se decidió, qué sigue? | detalle técnico de un cambio |
| `ctm_<ctx>/MASTERPLAN.md` | ¿en qué estado está este contexto y qué le falta? | reglas generales |
| `specs/NNN-<slug>/` | ¿qué se pidió, cómo se planificó, en qué tareas se cortó? (el registro del cambio) | el estado actual (se cierra y no se toca) |
| `ctm_<ctx>/DOCUMENTACION_ARCHIVOS.md` | ¿qué es cada archivo de este contexto? | el relato de cómo se llegó |
| `ctm_<ctx>/HISTORIAL.md` | ¿qué le fue pasando a este contexto y por qué? (cronológico, incremental) | el estado actual |
| `.specify/memory/constitution.md` | ¿contra qué se verifica un plan? (= §1 y §2) | nada que no esté acá |

Tres reglas para todo lo que se escribe: **distinguir lo verificado de lo supuesto** (si no
se comprobó, se dice; si salió de una fuente que puede estar mal, se dice cuál); **escribir
lo que NO está cubierto** (un invariante sin implementar, un evento sin consumidor, una
restauración nunca probada van en lugar visible); y **dejar cómo verificarlo de nuevo** (el
comando, la consulta, la fecha).

### 7.5 Las recetas, como specs

Cada receta es la **plantilla de `spec.md` + `tasks.md`** de ese tipo de cambio. Se copia a
`specs/NNN-<slug>/` y se completa; no se ejecuta "de memoria".

#### 7.5.1 Contexto nuevo `ctm_<nombre>`

*spec.md debe fijar*: subdominio (núcleo/soporte/genérico), glosario del contexto, código de
sistema único (ver §9.1), prefijo, esquema `ctm_<nombre>` y usuario `u_<nombre>`, agregados
con invariantes y eventos, relaciones con cada contexto que toque (patrón de §1.2), interfaz
que publica.

*tasks.md (orden)*:
1. Esqueleto desde `ctm_usu` (`index.php`, `config/`, `includes/{functions,helpers,header,
   navbar,footer}.php`, `assets/`); cambiar `USU_APP`→`<PREFIJO>_APP`, `APP_SISTEMA`,
   `APP_TITULO`, `APP_NAME`, `APP_DB`, `usu_menus`→`<prefijo>_menus`, objeto JS. **No copiar
   `database.php`**: incluir el de `ctm_entorno`. Crear `api/`, `includes/{dominio,aplicacion,
   infraestructura}/` con `README` de una línea.
2. `sql/001_esquema.sql`: `CREATE DATABASE`, `CREATE USER`, `GRANT` sólo a ese esquema,
   `<prefijo>_menus` (modelo `conc_menus`), `<prefijo>_estados`, `<prefijo>_eventos_vistos`,
   menús iniciales según §5.8 (`1 Inicio`, `10 Salir`, secciones `N00` con sus hijos).
3. `sql/002_sistema.sql` para Identidad (lo aplica su dueño): `usu_sistemas` (`codigo`,
   `pagina_inicio`, `estadoid=5`), `usu_sist_privilegio` (MAYÚSCULAS, `nro_priv`, `estado=9`),
   asignación de prueba en `usu_user_sist` (`estadoid=19`).
4. Código en `$MODULE_STYLE_MAP` (`ctm_entorno/config/modules.php`) e ícono.
5. `includes/functions.php` incluye `../ctm_entorno/includes/{database,csrf,eventos}.php` y
   sólo la `api/` de los contextos que consume.
6. Verificación: login → aparece en el panel → menú por `nivacc` → página con `nivacc`
   mayor devuelve 403 → `estado=10` sale → `SHOW GRANTS FOR u_<nombre>` muestra un esquema.
7. `DOCUMENTACION_ARCHIVOS.md`, `MASTERPLAN.md` del contexto, `HISTORIAL.md`; fila en §4.1,
   relaciones en §4.3, agregados en §4.5.

#### 7.5.2 Agregado nuevo en un contexto existente

*spec.md debe fijar*: raíz, hijos, invariantes, estados y tabla de transiciones, objetos de
valor, eventos, palabras nuevas del glosario, casos de uso (uno por verbo).

*tasks.md*: `sql/NNN_<agregado>.sql` (FKs **dentro** del esquema, estados, menús con
`nivacc`) → `dominio/vo.php` + `dominio/<agregado>.php` → `infraestructura/<agregado>_repo.php`
(+ `_rm.php` si hay listados) → `aplicacion/caso_<verbo>_<agregado>.php` → páginas y
`aj_<agregado>_<accion>.php` (sesión + `nivacc` + pertenencia) → `evento_emitir()` en la
transacción → cierre (7.2 paso 5), con el evento en §4.5.

#### 7.5.3 Cambio de esquema o de menú

*spec.md*: qué cambia y por qué; qué repositorio cambia con él (esquema y repositorio
**cambian juntos**); si toca la interfaz publicada, la versión nueva de la función.

*tasks.md*: `sql/NNN_*.sql` nuevo e idempotente (nunca editar uno aplicado) → repositorio
→ prueba con un usuario de cada `nivacc` → reversa probada → cierre.

#### 7.5.4 Retirar un duplicado, un archivo o un módulo

*spec.md*: qué se retira, el `grep -r` que prueba que nadie lo incluye (`cron.php`, `ws/`,
`web/int/ajax/`, `api/` incluidos), y —para un módulo— la decisión de §11 registrada en el
Masterplan.

*tasks.md*: exportar el esquema si lo tiene → mover fuera de la raíz web (no renombrar
`_old`) → commit propio con el `grep` en el mensaje → cierre.

#### 7.5.5 Integración entre dos contextos

*spec.md*: los dos contextos, el **patrón** de relación (§1.2), qué publica el proveedor
(funciones `api/` con su lenguaje publicado) o qué evento se emite y quién lo consume, la
consistencia aceptada (inmediata vía OHS / eventual vía evento), y qué `SELECT`/`INSERT`
cruzados reemplaza.

*tasks.md*: `api/<proveedor>_api.php` o `evento_emitir` + `<cliente>_on_<evento>()` →
reemplazo en el cliente → `grep` del prefijo ajeno en el cliente = 0 → §4.3 actualizado →
decisión en el Masterplan raíz → cierre.

#### 7.5.6 `DOCUMENTACION_ARCHIVOS.md` de un contexto

Formato ya existente (`ctm_conc/DOCUMENTACION_ARCHIVOS.md`, aunque su contenido sea de
Morebius): título `# <Contexto> (<APP_SISTEMA>) — Documentación de archivos` con fecha y
versión; `## Estadísticas`; `## Estructura de directorios`; `## Archivos por directorio` (una
tabla *Archivo | Propósito* por carpeta, una línea cada uno); `## Interfaz publicada` (las
`api/*` y los eventos que emite/consume); `## Archivos legacy / obsoletos`; `## Patrones`. Se
actualiza en el mismo cambio que toca el archivo.

---

## 8. Seguridad mínima (lo que todo contexto cumple)

| control | cómo |
|---|---|
| **Aislamiento de datos** | usuario de MariaDB por contexto con `GRANT` sólo a su esquema; credenciales sólo en su `config.local.php` |
| **CSRF** | `csrf_token()` en cada formulario; `csrf_verificar()` al inicio de cada POST/AJAX de escritura; implementado una vez en `ctm_entorno/includes/csrf.php`. Hoy no existe en ningún módulo |
| **Inyección SQL** | sólo `execute()` con parámetros; identificadores contra lista blanca; `vo_*` antes del dominio |
| **XSS** | `h()` en cada salida; `json_encode` para pasar datos a JS |
| **Autorización** | `verificarPermisoMenu` **corta en el servidor**; cada `aj_*.php` verifica `$_SESSION['us']` **y** `$_SESSION[APP_SISTEMA] >= nivacc` de la acción; cada acción sobre un registro verifica pertenencia (la postulación al concurso de la etapa) |
| **Sesión** | cookies httponly/strict/SameSite (ya está); `session_regenerate_id(true)` tras el login (falta); sin `estado=7` |
| **Contraseñas** | `password_hash()`/`password_verify()` con migración gradual (ya lo hace `verificarPassword()` de Gastos); el login del entorno compara texto plano (§9.1) |
| **Archivos subidos** | extensión **y** MIME (`finfo`), tamaño, nombre aleatorio, fuera de la raíz web o servidos por script con permisos; nunca `mkdir 0777` |
| **Inclusión dinámica** | `include` sólo de rutas que salen de `*_menus.direccion` validadas con `realpath()` dentro de `APP_WEB`; nunca de la URL |
| **Secretos** | `config.local.php` fuera de git, 600; `display_errors=0` en prod; nada de `echo $sql` |
| **Comandos externos** | `escapeshellarg()`; binarios por constante (`PYTHON_BIN`) |
| **Eventos** | el consumidor valida el `datos_json` como entrada externa (es de otro contexto) |

---

## 9. Deuda conocida — leer antes de tocar

Medida el 2026-10-10. Para no "descubrirla" de nuevo y no repetirla. Rutas relativas a la
raíz del repo.

### 9.1 Identidad y permisos

- Login compara contraseñas en texto plano: `intermedio_aico.php:45,55,68`,
  `ctm_entorno/includes/functions.php:301`, `ctm_usu/includes/functions.php:199,228`. Sin
  `session_regenerate_id`. Captcha desactivado (`intermedio_aico.php:98-100,133-140`).
- `estado=7` devuelve sesión ficticia sin login: `ctm_entorno/config/session.php:25-27`,
  `ctm_usu/config/session.php:21-23`, `ctm_conc/config/session.php:36-38`.
- El entorno no verifica que el usuario tenga el sistema que pide: `ctm_entorno/index.php:159-179`.
  `ctm_usu/index.php:54-59` y `ctm_conc/index.php:77-82` avisan "acceso denegado" por JS **y
  renderizan la página igual**.
- La mayoría de los `aj_*.php` de Concursos sólo chequean `isset($_SESSION['us'])`.
- **Usuario fijo** `$usu='7683'` en `ctm_hsextras/conf/seccion.php:14`, sesión comentada.
- **Códigos de sistema pisados**: `CTM_056` en autogestion, hsextras y segtramites; `CTM_051`
  en `ctm_usu`, `ctm_usu_`, `ctm_organica_`, `ctm_chatia`; `cron.php` de hsextras/segtramites/
  autogestion dice `CTM_054`, el de reportes `CTM_055`, el de conc `CTM_055`; `ctm_conc` se
  declara `RRHH_060`; `ctm_entorno` `AHID_051` y `ctm_usu` `CTM_051`; `ctm_pade` usa `AHID_053`
  pero `modules.php` registra `CTM_053`. Se resuelve con §4.1 y la pregunta 1 de §11.

### 9.2 Fronteras rotas entre contextos (la "bola de barro")

- Escriben `pade_*` desde afuera: `ctm_hsextras/web/mod/autogestion/nuevo_p1.php:38,61`,
  `ctm_segtramites`, `ctm_autogestion` (`pade_alertas`, `pade_usuarios`); `ctm_gastcomunes`
  (`pade_localidades_grupos/velocidades`); `ctm_reportes` (`pade_talertas_localidades`).
- Leen su menú de `pade_menus`: `ctm_segtramites/conf/seccion.php:27,57`,
  `ctm_organica_/conf/seccion.php:23`, hsextras, chatia, usu_.
- Escriben `cp_k2bprod_cotizacionmoneda` desde páginas: `ctm_monedas/conf/funcion.php:2558,2656,2726`,
  hsextras, autogestion.
- `ctm_pade` escribe `gast_inversiones` por un ajax copiado (`ctm_pade/web/int/ajax/gast_inversiones.php:17`).
- Siete módulos escriben `usu_usuarios`; cuatro escriben `usu_organicas`/`usu_estructuras`.
- Esquemas externos con nombre fijo: `ctm_organica` (`morebius.*`, `farinong.*`,
  `ctm_inventario_unificado.*`), `ctm_autogestion/conf/funcion.php:146,166` (`farinong.inve_id`).
- `ctm_usu_` y `aced_chatai` hacen INSERT/UPDATE en `vw_personas` (`ctm_usu_/web/mod/usuarios/nuevo.php:110,134`).
- Secuencia Oracle `auto_nivacc_basic.nextval` en `ctm_usu_/…/nivaccbasic/consultar.php:33` y
  `ctm_pade/…/nivaccbasic/consultar.php:33`.
- 8 copias de `ws/ws_usu_intranet.php` escribiendo `int_*` por ODBC.

### 9.3 Duplicación

- **5 copias de `config/database.php`** (entorno, usu, conc, gastcomunes, organica), todas
  distintas (66–232 líneas). **13 copias de `conf/funcion.php`** legacy.
- `ctm_chatia` = `ctm_organica_` byte a byte. `ctm_usu_`, `ctm_organica_`, `ctm_segtramites`
  reemplazados o intermedios (§4.1).
- Idénticos por md5 en ≥9 módulos: `web/int/encabezado.php`, `web/int/pie.php`,
  `conf/javascript.js`, `web/int/presentacion.php`, `ws/intermedio.php`, `ws/ws_usu_intranet.php`,
  `cron.php`, `popups_manager.php`.
- `ctm_usu.sql` idéntico en gastcomunes, usu_, chatia, organica_ (no es el esquema de ninguno).
- Recálculo de puntaje duplicado: `ctm_conc/web/mod/ajax/aj_guardar_etapa.php:110-156` y
  `aj_recalcular.php`. Validación IA triplicada: `aj_validar_ia.php`, `aj_validar_ia_todos.php`,
  `includes/claude_api.php:~614-650`.
- `getSistemasUsuario` repetido inline en `ctm_entorno/web/int/menuv.php:15-24` y
  `presentacion_1.php:578-587,625-634`.
- `ctm_autogestion/web/mod/tramites/4/vendor` y `5/vendor`: dos `vendor/` (446 PHP) dentro de `web/mod`.
- ~70 archivos `*_old`, `*_1`, `_` (autogestion 15, reportes 10, consola 7, hsextras 7,
  segtramites 7, chatia 6…). Carpetas `reportes/`, `reportes_1/`, `reportes_2/`; `autogestion_1/`;
  `web/mod/viejas/` en conc.

### 9.4 Concursos: código vs. especificación

- Docs copiados de Morebius sin adaptar: `ctm_conc/DOCUMENTACION.md`, `DOCUMENTACION_ARCHIVOS.md`,
  `README_MIGRACION.md`, `sql_migracion_v2.sql`. Los propios: `ESPECIFICACION_CONCURSOS.md`,
  `README_SISTEMA_ESTADOS.md`, `README_ESTADOS_MENUS.md`, `docs/MANUAL_USUARIO.md`.
- **Edición de concurso rota**: `web/mod/concursos/nuevo.php:17` depende de `$ac`, definido
  sólo en la redirección de `index.php:27`; `?estado=14&id=X` hace INSERT y choca con el
  UNIQUE. Si funcionara, `nuevo.php:93-97` borra etapas/requisitos y el `ON DELETE CASCADE`
  arrastra evaluaciones.
- Transición de estado libre (`concursos/ver.php:54-63`); sólo `aj_clasificar_postulantes.php:56-84`
  cambia estados de postulación; nada pasa etapas a `en_curso/finalizada`; no se aplican
  secuencialidad, `max_postulantes_pasan` ni `umbral_empate`; **suma** preferentes al total
  (`aj_guardar_etapa.php:124-150`, `aj_recalcular.php:66-82`), al revés que la especificación.
- Esquema ≠ código: `excl_aprobado`, `pref_cumple/total`, `con_cumple/total` usadas
  (`includes/helpers.php:271`, `aj_exportar_evaluacion.php:53`) y ausentes en `sql/schema.sql`;
  `desestimado` fuera del ENUM; base `ctm_conc` vs `ctm_concursos`.
- `includes/extract_pdf.py` **falta**, lo usan 4 endpoints. `app.js` llama a `aj_cambiar_estado.php`
  y `aj_guardar_validacion.php`, inexistentes.
- `sql/poblar_menus_estados.sql:40-42,81-83` con errores de sintaxis; los scripts de menús
  copian `more_menus` y fallan contra `ctm_conc`.
- `includes/navbar.php:7` relee `$_GET['estado']` sin truncar y lo interpola (`:72,:102`).
- CVs con datos personales servidos por URL directa (`postulantes/ver.php:293`), nombres
  predecibles, `mkdir 0777` (`aj_subir_cv.php:65`), sin MIME.
- `config_prod.php:8` define `ENTO_APP` en vez de `CONC_APP`; `APP_URL` apunta a `ctm_hidrologia`.
- `APP_VERSION='1.0.0'` mientras el manual describe la 1.2.0.

### 9.5 Capa de datos y configuración

- **Ninguna transacción en todo el repo.** `get_id()`/`get_id_conc()`/`generarNroPostulacion()`
  con SELECT+UPDATE sueltos.
- `Database::execute` sigue con la conexión rota tras un fallo (`ctm_entorno/config/database.php:59-62`).
  El `sql_do_query_mysql` legacy abre una conexión por consulta y hace `die()`.
- Páginas con SQL concatenado: 97–100 % en consola, reportes, pade, gastcomunes, usu_; 77–81 %
  en monedas, hsextras, segtramites, autogestion, organica_. Cero prepared statements desde
  `web/mod` salvo 3 páginas de `ctm_organica` y el código propio de Concursos.
- SQL del usuario ejecutado tal cual: `ctm_reportes/web/mod/reportes/nuevo.php:23`,
  `reportes_1/{nuevo,probar,exe}.php` (función del módulo, sin restricción de conexión ni
  de sentencia).
- `include` con dato de la URL: `ctm_autogestion/web/mod/autogestion/conf_tramite.php:69`.
- `$_POST` sin filtrar en SQL: `ctm_consola/web/mod/estadistica/mensajes/porzona.php:65-67`,
  `polo_ofertaadmin/web/mod/eventos/{editar,nuevo}.php`, `ctm_reportes/web/mod/empleados/listar_.php:17,126`.
- `echo $sql`: `ctm_entorno/web/int/presentacion_1.php:588,635`, `ctm_conc/web/mod/inicio/prestamos/nuevo.php:231`,
  `ctm_consola/conf/funcion.php:1852,1881`, `ctm_hsextras/conf/funcion.php:2572,2616`,
  monedas (9), `ctm_autogestion/conf/funcion_2.php` (12), `ctm_reportes/…/exe.php:153`,
  `ctm_usu_/web/mod/usuarios/nuevo.php:171`, `aced_chatai` (2).
- `display_errors=1` en prod: `ctm_entorno/config/config_prod.php:14`, `ctm_conc/config/config_prod.php:14`,
  `ctm_gastcomunes/conf/seccion.php:12`, `intermedio_aico.php:14`, `chequeo_alertas.php:3`, polo.
- **Secretos en el repo** (dónde; no se copian acá): contraseñas de BD en `ctm_entorno/config/
  config_{desa,prod}.php`, `ctm_usu/config/config_{desa,prod}.php`, `ctm_conc/config/config_{desa,
  prod,_}.php`, `ctm_gastcomunes/config/config.php` y `conf/conf_desa.php`, y en el `conf/conf.php`
  de cada legacy (incluida una base en Azure en `ctm_pade`); tokens de Twilio en gastcomunes,
  hsextras, pade; **API key de Anthropic** en `ctm_conc/config/config_.php:6`, `config_desa.php:24`,
  `config_prod.php:24`; credenciales reales comentadas en `intermedio_aico.php:85-88`. **Al
  sacarlos, se rotan.**
- Rutas rotas: `chequeo_alertas.php:4-5`, `ctm_usu/index.php:21,26,33`, `ctm_usu/web/int/menu.php:125`,
  `ctm_entorno/web/int/pie.php:4` (`herramienta_pie` no definida), `ctm_conc/cron.php:3`.
- README que mienten: `ctm_usu/README_MIGRACION.md` (`AHID_051`, `error_reporting` sin warnings;
  el código dice `CTM_051`, `E_ALL`); `ctm_entorno/README_MIGRACION.md` da `conf/` por eliminado
  y lo lista como pendiente.

---

## 10. Lo que NO está en este repositorio

- **Inventarios, Morebius (préstamos/reparaciones) y Presupuestos** como contextos: no están
  en `C:\www\contexto_unificado`. Quedan rastros: `integ_*` los consulta por vistas
  (`vw_integ_cc_equipos_presupuesto`, `vw_integ_personal_equipos`, `vw_integ_prestamos_unificados`,
  `vw_integ_reparaciones_unificadas`, tablas `inve_pc/monitores/impresoras/otros`), y `ctm_conc`
  arrastra páginas de Morebius (`web/mod/inicio/prestamos`, `reparaciones`, `inventario`,
  `equipos`, tablas `more_*`). Existen copias viejas de `ctm_morebius` (respaldo de
  2026-07-05) y de `ctm_inventarios`/`ctm_presupuestos` (respaldo de 2024-12) **fuera de
  este repositorio**; la versión actual de los tres **está en otro lado** (§11).
- **El esquema completo de la base**: sin DDL de `ento_menus`, `usu_menus`, `usu_sistemas`,
  `integ_eventos`, `inve_*`, `vw_integ_*`. El último respaldo disponible de la base
  (2026-07-05) tiene 136 tablas en `contexto_unificado` (prefijos `autog, cbot, chai, cons,
  ento, gast, hsex, mone, org, pade, repo, usu, vw`) y **ninguna** `integ_`, `inve_`, `conc_`,
  `concursos`: la base que corre con este código es más nueva que ese respaldo.
- **Un servidor local**: el entorno de desarrollo (MariaDB + Apache/PHP) hay que levantarlo
  de nuevo; se recomienda en contenedores para fijar versiones. Es la primera spec de la
  fase 0 (§6).
- **Git**: el directorio no tiene `.git`.
- **El brief original** (secciones 1–6.3) no se encontró; sólo se tiene desde la 6.3.

---

## 11. Preguntas abiertas (las decide el equipo, no el código)

Cada respuesta se registra como **Decisión** en `MASTERPLAN.md` (fecha, decisión, por qué,
alternativas descartadas) y, si cambia una regla, se corrige este archivo en la misma spec.

1. **Un código por contexto**: ¿`ctm_conc` es `RRHH_060` o `CTM_055`? ¿Presupuestos es `CTM_061`
   (`ctm_entorno/includes/functions.php:189`) e Inventarios `CTM_062` / `AICO_777` (`:140`)?
   ¿Qué código queda para autogestion / hsextras / segtramites (los tres `CTM_056`)? ¿PADE es
   `AHID_053` o `CTM_053`?
2. **Una base por contexto** (§4.4): ¿se aprueba la asignación y el orden de la fase 4 (§6)?
   ¿El árbol orgánico (`usu_organicas`/`usu_estructuras`) queda en Identidad o pasa a Orgánica?
3. **Módulo de referencia**: `ctm_usu` (limpio) y no `ctm_conc` (híbrido). ¿Se acepta?
4. **Capas**: `includes/{dominio,aplicacion,infraestructura}/` + `api/` para lo nuevo, y se
   migra lo viejo sólo al tocarlo. ¿Se acepta?
5. **Credenciales fuera del repo**: ruta de `config.local.php` en el servidor; quién rota las
   que hoy están en el código.
6. **Eventos**: ¿se implementa el despachador por cron (§5.6) con PADE como primer consumidor
   (`aviso_solicitado`)? ¿Cada cuánto corre?
7. **Dónde está la versión actual de Inventarios, Morebius y Presupuestos**, y si entra a este
   repo como contexto `ctm_inventario` o queda externo consumido por OHS.
8. **Qué se retira**: `ctm_chatia`, `ctm_organica_`, `ctm_usu_`, `ctm_segtramites`,
   `web/mod/viejas/` de conc, `aced_chatai` y `polo_ofertaadmin`. Mientras no se decida, no se tocan.
9. **CLAUDE.md por contexto**: propuesta: sólo la raíz, hasta que un contexto tenga reglas que
   no quepan acá; entonces uno corto con su glosario, agregados e interfaz publicada.
10. **Inicializar git** sobre `C:\www\contexto_unificado` con `.gitignore` para
    `config.local.php`, `uploads/`, `*/vendor/`, `*.zip`.
11. **Consola y las `cp_*`**: ¿Consola es el dueño del sincronizador de réplicas externas
    (ACL materializada) o eso es un contexto aparte (`ctm_integracion_externa`)?
