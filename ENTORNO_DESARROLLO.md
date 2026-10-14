# Entorno de desarrollo CTM

Como trabajar en la notebook con un entorno igual al de produccion (torito) y pasar
los cambios a produccion con seguridad. **Nada en la nube**: el repositorio git central
vive en el propio torito y las notebooks trabajan contra el.

## Repositorio git central (en torito)

| Dato | Valor |
|---|---|
| Servicio | Gitea en torito, http://172.16.1.223:3000 (usuario `farinong`) |
| Repositorio | `farinong/contexto_unificado`, privado |
| URL desde la notebook | `http://172.16.1.223:3000/farinong/contexto_unificado.git` |
| URL desde el propio torito | `http://127.0.0.1:3000/farinong/contexto_unificado.git` |

Primera vez: crear el repositorio vacio en Gitea (privado, sin README) y publicar el repo local:

```powershell
cd C:\dev\ctm\contexto_unificado
powershell -ExecutionPolicy Bypass -File herramientas\publicar_repo_en_torito.ps1
```

Git en Windows guarda el usuario y la contrasena del Gitea la primera vez que los pide.
En torito, para que produccion y test puedan hacer `git pull` sin pedir clave cada vez:
`git config --global credential.helper store` y hacer el primer pull a mano.

Si torito no tuviera Gitea, la alternativa es un repositorio bare por SSH:
`herramientas/torito/crear_repo_servidor.sh` y despues
`publicar_repo_en_torito.ps1 -UrlRepo ctm223:/opt/git/contexto_unificado.git`.

## Los tres entornos

| Entorno | Donde | Codigo | Base de datos | URL |
|---|---|---|---|---|
| Desarrollo | Notebook, Docker Desktop | este repo (rama de trabajo) | copia de produccion, local | http://localhost:8080/ctm/contexto_unificado/ |
| Test | torito `/var/www/ctm/contexto_unificado_test` | rama en prueba | `contexto_unificado_test` (copia) | https://172.16.1.223/ctm/contexto_unificado_test/ |
| Produccion | torito `/var/www/ctm/contexto_unificado` | rama `main` | `contexto_unificado` | https://172.16.1.223/ctm/contexto_unificado/ |

Produccion (torito, 172.16.1.223): Ubuntu 26.04, nginx 1.28, PHP 8.5, MariaDB 11.8.
El `docker-compose.yml` levanta exactamente esas versiones.

## Configuracion por entorno: config.local.php

Cada modulo tiene `config/config.php` (en git, valores por defecto) y puede tener
`config/config.local.php` (NO en git, valores del entorno). Si existe, `config.php`
lo carga primero y lo que defina ahi manda:

- `$servidor`: URL base del modulo.
- `MOSTRAR_ERRORES`: `'on'` en desarrollo, `'off'` en produccion.
- `getDbConfig()`: hosts, usuarios y claves de las bases.
- `OLLAMA_HOST` (solo presupuestos).

Para crear el tuyo, copia el ejemplo de cada modulo y ajustalo:

```powershell
cd C:\dev\ctm\contexto_unificado
foreach ($m in "ctm_entorno","ctm_inventarios","ctm_morebius","ctm_presupuestos") {
  Copy-Item "$m\config\config.local.php.ejemplo" "$m\config\config.local.php"
}
```

Los ejemplos ya traen los valores del entorno Docker (host `db`, usuario `root`,
clave `ctm_dev`). En torito hay que crear los mismos archivos con las credenciales
reales y `MOSTRAR_ERRORES` en `'off'`. Como no estan en git, un `git pull` nunca los pisa.

## 0. Hacer que el repo sea copia exacta de produccion

La fuente de verdad es torito. Antes de desarrollar, traer su codigo al repo:

```powershell
cd C:\dev\ctm\contexto_unificado
powershell -ExecutionPolicy Bypass -File herramientas\traer_codigo_produccion.ps1
git status
git add -A ; git commit -m "Sincronizar codigo con produccion torito" ; git push
```

El script empaqueta `/var/www/ctm/contexto_unificado` en torito (sin `.git` ni los
`config/config.php`, que tienen credenciales), lo descomprime encima del repo local y muestra
un resumen: archivos modificados, archivos nuevos, archivos del repo que ya no existen en
produccion (se borran solo con `-BorrarFaltantes`) y carpetas que `.gitignore` deja afuera.
No toca produccion. Repetirlo cada vez que se sospeche que alguien edito a mano en torito.

## 1. Levantar el entorno de desarrollo (una sola vez)

En una notebook limpia, `herramientas/preparar_notebook.ps1` instala Git y Docker Desktop,
clona el repo en `C:\dev\ctm\contexto_unificado` y crea los `config.local.php`.
Se descarga y ejecuta asi, en PowerShell como Administrador:

```powershell
cd $HOME\Downloads
scp ctm223:/var/www/ctm/contexto_unificado/herramientas/preparar_notebook.ps1 .
powershell -ExecutionPolicy Bypass -File .\preparar_notebook.ps1
```

Si Docker Desktop pide reiniciar Windows (WSL2), reiniciar y volver a correr el script.
Luego abrir Docker Desktop una vez y esperar a que diga "Engine running".

Si ya tenes Git y Docker, alcanza con clonar el repo y crear los `config.local.php`
(bloque de arriba). Despues, levantar los servicios:

```powershell
cd C:\dev\ctm\contexto_unificado
docker compose up -d --build
```

5. Traer las bases de produccion y cargarlas (pide la clave de MariaDB de torito):

```powershell
powershell -ExecutionPolicy Bypass -File herramientas\db\sincronizar_bd.ps1
```

6. Abrir http://localhost:8080/ctm/contexto_unificado/ctm_inventarios/

Servicios que quedan corriendo:

| Servicio | Puerto en la notebook | Para que |
|---|---|---|
| nginx + PHP 8.5 | 8080 | la aplicacion |
| MariaDB 11.8 | 3307 | conectarse desde HeidiSQL o similar (root / ctm_dev) |
| Adminer | 8081 | administrar la base desde el navegador |

XAMPP puede seguir instalado: no comparte puertos con esto.

## 2. Ciclo de trabajo diario

```
rama nueva  ->  editar  ->  probar en localhost:8080  ->  commit + push  ->  PR
            ->  desplegar a test en torito  ->  probar  ->  merge a main  ->  desplegar a prod
```

```powershell
git checkout -b mi-cambio
# ... editar, probar en http://localhost:8080 ...
git add . ; git commit -m "Descripcion del cambio" ; git push -u origin mi-cambio
powershell -ExecutionPolicy Bypass -File herramientas\desplegar.ps1 -Destino test -Rama mi-cambio
# probar en el test de torito; si esta bien, merge del PR a main en el Gitea de torito y luego:
powershell -ExecutionPolicy Bypass -File herramientas\desplegar.ps1 -Destino prod
```

`desplegar.ps1` hace `git pull` por SSH en la carpeta correspondiente de torito.
A produccion solo acepta `main` y pide confirmacion escribiendo `SI`.

## 3. Refrescar los datos locales

Cuando quieras datos frescos de produccion:

```powershell
powershell -ExecutionPolicy Bypass -File herramientas\db\sincronizar_bd.ps1
```

Opciones: `-Bases contexto_unificado` (solo una), `-SoloBajar` (deja los .sql en
`herramientas/db/dumps/` sin cargarlos), `-SoloCargar` (carga los .sql que ya estan).
Los dumps no se suben a git.

## 4. Poner produccion bajo git en torito (una sola vez)

Hoy produccion en torito (`/var/www/ctm/contexto_unificado`, servido en
`https://172.16.1.223/ctm/contexto_unificado/`) son archivos copiados. Para que se
actualice con `git pull`, `herramientas/torito/adoptar_git_produccion.sh` la convierte en un
checkout del repo sin perder nada: clona al lado, muestra las diferencias, copia lo que
produccion tenga de mas, genera los `config.local.php` con las credenciales reales y recien
al final intercambia las carpetas. La carpeta anterior queda como respaldo
`contexto_unificado_old_FECHA`.

```powershell
scp herramientas\torito\adoptar_git_produccion.sh ctm223:/tmp/
ssh -t ctm223 "bash /tmp/adoptar_git_produccion.sh"
```

Pide confirmacion (`SI`) despues de mostrar las diferencias, y usuario y contrasena de
MariaDB para los `config.local.php`. Si falla por permisos, correrlo con `sudo`.
Hacerlo fuera de horario de uso: el intercambio de carpetas corta el sitio unos segundos.

IMPORTANTE: el `config.php` del repo tiene valores de desarrollo (localhost, root sin
clave). Produccion funciona porque su `config.local.php` manda. Nunca desplegar a
produccion sin esos archivos; `desplegar.ps1` avisa si faltan.

## 5. Armar el entorno de test en torito (una sola vez)

Como nginx sirve todo `/var/www`, una carpeta al lado de produccion queda publicada sola.
`herramientas/torito/crear_entorno_test.sh` clona el repo en
`/var/www/ctm/contexto_unificado_test`, crea la base `contexto_unificado_test` como copia de
la de produccion y genera los `config.local.php` apuntando a esa base y a
`https://172.16.1.223/ctm/contexto_unificado_test/`. Las bases `morebius` y `ctm_ocsweb`
quedan apuntando a las reales, que son de solo lectura.

```powershell
scp herramientas\torito\crear_entorno_test.sh ctm223:/tmp/
ssh -t ctm223 "bash /tmp/crear_entorno_test.sh"
```

Para refrescar la base de test con datos de produccion, volver a correrlo. Para actualizar
el codigo de test con una rama: `herramientas\desplegar.ps1 -Destino test -Rama mi-rama`.

## Comandos utiles

```powershell
docker compose ps                 # estado de los servicios
docker compose logs -f php        # errores de PHP en vivo
docker compose exec php php -v    # version de PHP del contenedor
docker compose down               # apagar (la base queda guardada)
docker compose down -v            # apagar y borrar la base local
```

## Cosas a tener en cuenta

- Linux distingue mayusculas en nombres de archivo; Windows no. Un `include 'Archivo.php'`
  que funciona en XAMPP puede fallar en Docker y en torito. Eso es justamente lo que
  queremos detectar antes de llegar a produccion.
- `ini_set('extension', 'php_openssl.dll')` en los config.php es de XAMPP Windows; en
  Linux no hace nada y no molesta.
- Las conexiones ODBC de ctm_morebius ('3' y '4') no existen en Docker.
- Nunca pegues contrasenas en archivos que esten en git. Van en `config.local.php` o se
  piden al ejecutar.
