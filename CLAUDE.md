# CLAUDE.md - Sistema CTM (Contexto Unificado)

Guia para cualquier sesion de Claude que trabaje en este repositorio. Leer entera antes de tocar nada.

## Que es este sistema

Sistema web PHP procedural con tres modulos que gestionan el equipamiento IT de la organizacion:
`ctm_inventarios` (inventario IT), `ctm_morebius` (asignacion de equipos al personal) y
`ctm_presupuestos` (gestion presupuestaria). `ctm_entorno` es la base comun (login, menu, sesion)
y `shared/` tiene las conexiones MySQL y ODBC compartidas. Detalle funcional y plan de mejoras en
`PROMPT_MEJORA_CTM.md`.

## Estilo de codigo (obligatorio)

- PHP procedural: funciones simples, **sin clases ni objetos** nuevos. Mantener el estilo existente.
- Nombres de variables, funciones y comentarios en espanol.
- Consultas con prepared statements de mysqli (placeholders `?`), nunca concatenar entradas.
- Mantener la estructura de carpetas existente (`config/`, `includes/`, `web/mod/...`).
- Bootstrap 5 + Bootstrap Icons + SweetAlert2 desde CDN, como el resto.
- Scripts de herramientas: PowerShell o bash, tambien en funciones simples.

## Regla principal: nada en la nube

El repositorio git central, los entornos y los datos viven dentro de la red de la organizacion.
No usar GitHub ni servicios externos para codigo, datos o credenciales.

## Entornos

| Entorno | Donde | Codigo | Bases | URL |
|---|---|---|---|---|
| **Produccion** | servidor **torito**, IP **172.16.1.223** (Ubuntu 26.04, nginx 1.28, PHP 8.5, MariaDB 11.8) | `/var/www/ctm/contexto_unificado`, rama `main` | las reales | https://172.16.1.223/ctm/contexto_unificado/ |
| Test | torito, `/var/www/ctm/contexto_unificado_test` | rama en prueba | `contexto_unificado_test` (copia) | https://172.16.1.223/ctm/contexto_unificado_test/ |
| Desarrollo | notebook, Docker (mismas versiones que torito) | `C:\dev\ctm\contexto_unificado` | copia completa de torito | https://localhost:8080/ctm/contexto_unificado/ |

- **Produccion es el servidor real en uso. Nunca probar cambios ahi.** Los cambios van:
  desarrollo -> test en torito -> merge a `main` -> produccion con `herramientas\desplegar.ps1`.
- La fuente de verdad del codigo es produccion. Si se sospecha que alguien edito a mano en torito,
  `herramientas\traer_codigo_produccion.ps1` lo trae al repo para commitearlo.
- El entorno local sirve HTTPS en el 8080 con certificado autofirmado porque la aplicacion fuerza
  HTTPS en `ctm_entorno/default.php`. No quitar esa redireccion.

## Donde estan los archivos de desarrollo

La carpeta `C:\dev\ctm\contexto_unificado` de la notebook es a la vez el repositorio git de
trabajo (origin = Gitea de torito), la carpeta que sirve Docker (nginx y PHP la montan directo:
cualquier edicion se ve al instante en https://localhost:8080 sin reiniciar) y una copia del
codigo de produccion.

| Que | Donde |
|---|---|
| Modulos de la aplicacion | `ctm_entorno/`, `ctm_inventarios/`, `ctm_morebius/`, `ctm_presupuestos/`, `shared/` |
| Configuracion local de cada modulo (no va a git) | `ctm_*/config/config.local.php` |
| Entorno Docker | `docker-compose.yml`, `docker/nginx/`, `docker/php/` |
| Bases bajadas de torito (no van a git) | `herramientas/db/dumps/*.sql` |
| Credenciales de MariaDB (no va a git) | `herramientas/db/credenciales.local.ps1` |
| Scripts de sincronizacion y despliegue | `herramientas/` |
| Guias | `CLAUDE.md`, `ENTORNO_DESARROLLO.md`, `README.md`, `PROMPT_MEJORA_CTM.md` |

Los datos de las bases locales no estan en archivos de la carpeta: viven en el volumen Docker
`ctm_db`, que persiste aunque se apaguen los contenedores. Se administran con Adminer en
http://localhost:8081 (servidor `db`, usuario `root`, clave `ctm_dev`).

## Ciclo de trabajo

```powershell
cd C:\dev\ctm\contexto_unificado
git checkout -b mi-cambio
# editar, probar en https://localhost:8080/ctm/contexto_unificado/
git add . ; git commit -m "Descripcion del cambio" ; git push -u origin mi-cambio
# pull request en http://172.16.1.223:3000 y merge a main
powershell -ExecutionPolicy Bypass -File herramientas\desplegar.ps1 -Destino test -Rama mi-cambio   # probar en torito
powershell -ExecutionPolicy Bypass -File herramientas\desplegar.ps1 -Destino prod                   # solo main, pide SI
```

Estado actual (2026-10-14): produccion y desarrollo funcionan y `main` en Gitea es copia de
produccion. **Pendiente:** que la carpeta de produccion en torito sea un checkout de git
(`herramientas/torito/adoptar_git_produccion.sh`, fuera de horario, deja respaldo) y armar el
entorno de test (`herramientas/torito/crear_entorno_test.sh`). Hasta entonces `desplegar.ps1`
no puede actualizar torito.

## Repositorio git

- Central: **Gitea en torito**, http://172.16.1.223:3000, repo privado `farinong/contexto_unificado`.
  Desde la notebook: `http://172.16.1.223:3000/farinong/contexto_unificado.git`.
  Desde el propio torito: `http://127.0.0.1:3000/farinong/contexto_unificado.git`.
- `main` = codigo de produccion + herramientas de desarrollo. Se trabaja en ramas y se integra
  por pull request en Gitea.
- Fines de linea Linux (`git config core.autocrlf false`). No convertir a CRLF.

## Accesos

- SSH a torito: alias `ctm223`, usuario `serviciosau` (tiene **cuota de disco**: no escribir
  archivos grandes en el servidor; los scripts comprimen y transfieren por streaming).
- MariaDB en torito: usuario `root`. Gitea: usuario `farinong`.
- Las contrasenas las escribe la persona cuando los scripts las piden, o viven en archivos locales
  ignorados por git. **Nunca escribirlas en archivos versionados ni en el codigo.**

## Configuracion por entorno

- Cada modulo tiene `config/config.php` (en git, valores por defecto de desarrollo) y
  `config/config.local.php` (**ignorado por git**) que manda sobre el anterior: `$servidor` (URL),
  `MOSTRAR_ERRORES`, `getDbConfig()` con host/usuario/clave/base por indice de conexion, y
  `OLLAMA_HOST` en presupuestos. Hay un `config.local.php.ejemplo` por modulo.
- Los `config.php` de produccion no estan en git porque tienen credenciales. El mapa de que base
  usa cada indice de conexion debe coincidir con produccion en los `.ejemplo`.
- La aplicacion usa varias bases: `contexto_unificado` (RW), `morebius` y `ctm_ocsweb` (RO),
  `ctm_itsm` y otras. El entorno local replica **todas** las bases de torito.

## Herramientas (carpeta `herramientas/`)

| Script | Para que |
|---|---|
| `ssh/configurar_ssh.ps1` | clave SSH y alias `ctm223` en la notebook |
| `preparar_notebook.ps1` | instala Git y Docker, clona desde Gitea, crea los `config.local.php` |
| `db/sincronizar_bd.ps1` | trae todas las bases de torito al MariaDB local (credenciales en `db/credenciales.local.ps1`, ignorado) |
| `db/analizar_equipo.py CODIGO` | informe de un equipo en todas las bases locales con linea de tiempo de fechas y causas de discrepancia |
| `traer_codigo_produccion.ps1` | copia el codigo de torito encima del repo local para commitearlo |
| `publicar_repo_en_torito.ps1` | crea el repo en Gitea si falta y publica el repo local |
| `desplegar.ps1 -Destino test|prod` | `git pull` por SSH en torito; prod solo acepta `main` y pide confirmacion |
| `torito/adoptar_git_produccion.sh` | convierte la carpeta de produccion en checkout de git, con respaldo |
| `torito/crear_entorno_test.sh` | arma la carpeta y la base de test en torito |
| `torito/generar_config_local.sh` | genera los `config.local.php` en una instalacion de torito |
| `PROMPT_INSTALACION_NOTEBOOK.md` | prompt para repetir la instalacion en otra notebook |

Entorno local: `docker-compose.yml` + `docker/` (nginx con HTTPS, PHP 8.5 con las mismas
extensiones que torito incluidas soap, odbc, ldap, intl, gd; MariaDB 11.8; Adminer en 8081).
Guia completa en `ENTORNO_DESARROLLO.md`.

## Antes de commitear

1. `git status` y revisar que **no** aparezcan `config.local.php`, `herramientas/db/credenciales.local.ps1`
   ni nada en `herramientas/db/dumps/`. Si aparecen, falta la regla en `.gitignore`: agregarla antes.
   (Ya paso una vez que se subieron volcados de produccion y hubo que rehacer el commit.)
2. Si se trajo codigo de torito, su `.gitignore` reemplaza al del repo: volver a agregar esas tres reglas.
3. Lint rapido: `php -l` sobre los PHP tocados; `docker compose config -q` si se toco el compose.
4. Mensajes de commit en espanol, cortos, en imperativo.

## Errores conocidos

- Comandos `git`/`docker` no reconocidos en Windows: la PowerShell es anterior a la instalacion.
- `Split-Path` de PowerShell rompe rutas Linux; para rutas de torito usar texto plano.
- Comandos largos de `docker-compose.yml` en una sola linea (YAML plegado partio un `openssl`).
- `php:8.5-fpm` ya trae opcache: no instalarlo con `docker-php-ext-install`, solo habilitarlo.
- "Class SoapClient not found": falta extension PHP; comparar `php -m` local contra torito.
- "Table 'xxx.yyy' doesn't exist": falta esa base en local; correr el sincronizador.
- Git por HTTP sin cifrar: `git config --global credential.allowUnsafeRemotes true`.

## Que no hacer

- No tocar produccion a mano ni desplegar nada que no haya pasado por test.
- No subir contrasenas, volcados de base ni `config.local.php` al repositorio.
- No convertir el codigo a clases, frameworks ni TypeScript. Funciones simples.
- No usar GitHub ni la nube para nada de este proyecto.
