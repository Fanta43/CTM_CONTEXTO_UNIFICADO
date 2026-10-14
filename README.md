# CTM - Contexto Unificado

Sistema web PHP procedural (funciones, sin objetos) con tres módulos que gestionan el
equipamiento IT de la organización: `ctm_inventarios`, `ctm_morebius` y `ctm_presupuestos`,
más `ctm_entorno` como base común.

## Servidor de producción

| Dato | Valor |
|---|---|
| Nombre | torito |
| IP | **172.16.1.223** |
| Sistema | Ubuntu 26.04, nginx 1.28, PHP 8.5, MariaDB 11.8 |
| Código | `/var/www/ctm/contexto_unificado` |
| URL | https://172.16.1.223/ctm/contexto_unificado/ctm_entorno/default.php |
| SSH | alias `ctm223`, usuario `serviciosau` (ver `herramientas/ssh/README.md`) |
| Base de datos | MariaDB, usuario `root`, bases `contexto_unificado` (RW), `morebius` y `ctm_ocsweb` (RO) |

Es el servidor real en uso. Los cambios se prueban primero en la notebook (Docker) y en la
carpeta de test de torito; a producción solo llega la rama `main` con `herramientas\desplegar.ps1`.

## Documentación

- `ENTORNO_DESARROLLO.md`: cómo instalar el entorno de desarrollo, sincronizar código y bases
  con producción, armar el entorno de test y desplegar.
- `herramientas/PROMPT_INSTALACION_NOTEBOOK.md`: prompt para repetir la instalación en otra notebook.
- `herramientas/ssh/README.md`: conexión SSH al servidor.
- `PROMPT_MEJORA_CTM.md`: contexto del sistema y plan de mejoras.
