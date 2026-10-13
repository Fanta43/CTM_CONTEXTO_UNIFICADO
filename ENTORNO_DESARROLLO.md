# Entorno de desarrollo CTM

Como trabajar en la notebook con un entorno igual al de produccion (torito) y pasar
los cambios a produccion con seguridad.

## Los tres entornos

| Entorno | Donde | Codigo | Base de datos | URL |
|---|---|---|---|---|
| Desarrollo | Notebook, Docker Desktop | este repo (rama de trabajo) | copia de produccion, local | http://localhost:8080/ctm/contexto_unificado/ |
| Test | torito `/var/www/ctm/contexto_unificado_test` | rama en prueba | `contexto_unificado_test` (copia) | la que configure nginx en torito |
| Produccion | torito `/var/www/ctm/contexto_unificado` | rama `main` | `contexto_unificado` | la actual |

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

## 1. Levantar el entorno de desarrollo (una sola vez)

En una notebook limpia, `herramientas/preparar_notebook.ps1` instala Git y Docker Desktop,
clona el repo en `C:\dev\ctm\contexto_unificado` y crea los `config.local.php`.
Se descarga y ejecuta asi, en PowerShell como Administrador:

```powershell
cd $HOME\Downloads
curl.exe -L -o preparar_notebook.ps1 https://raw.githubusercontent.com/Fanta43/CTM_CONTEXTO_UNIFICADO/main/herramientas/preparar_notebook.ps1
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
# probar en el test de torito; si esta bien, merge del PR a main en GitHub y luego:
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

## 4. Armar el entorno de test en torito (una sola vez)

Pendiente de confirmar la estructura real de `/var/www/ctm` y la configuracion de nginx
en torito. La idea:

1. Clonar el repo en `/var/www/ctm/contexto_unificado_test`.
2. Crear la base `contexto_unificado_test` como copia de `contexto_unificado`.
3. Crear los `config.local.php` de cada modulo apuntando a esa base y a la URL de test.
4. Agregar en nginx un `location` o un `server` para la carpeta de test.

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
