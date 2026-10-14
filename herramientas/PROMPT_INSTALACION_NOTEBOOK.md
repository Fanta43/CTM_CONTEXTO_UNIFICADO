# Prompt para instalar el entorno de desarrollo CTM en otra notebook

Copiar todo el bloque de abajo y pegarlo como primer mensaje en una sesion nueva de Claude.
Reemplazar lo que esta entre < >.

---

Quiero configurar esta notebook Windows como entorno de desarrollo del sistema CTM
(Contexto Unificado). Vos solo tenes que guiarme paso a paso y revisar las salidas que te pego.
Prefiero codigo sencillo en funciones, sin objetos. Hablame en espanol rioplatense.

REGLA PRINCIPAL: nada en la nube. El repositorio git central esta en el propio servidor de
produccion y los entornos de desarrollo son locales. No uses GitHub ni ningun servicio externo.

Contexto:
- Produccion es el servidor "torito", IP 172.16.1.223, Ubuntu 26.04, nginx 1.28, PHP 8.5,
  MariaDB 11.8. La app vive en /var/www/ctm/contexto_unificado y se sirve en
  https://172.16.1.223/ctm/contexto_unificado/ctm_entorno/default.php
  Es el servidor real en uso: nunca probar cambios directamente ahi.
- Repositorio git central: Gitea en torito, http://172.16.1.223:3000, usuario farinong,
  repo privado farinong/contexto_unificado.
  URL para clonar desde la notebook: http://172.16.1.223:3000/farinong/contexto_unificado.git
- Usuario SSH en torito: serviciosau. Usuario de MariaDB en torito: root.
  Las contrasenas las escribo yo cuando los scripts las pidan; no las guardes en archivos
  que vayan a git.
- El repo ya trae todos los scripts en la carpeta herramientas/ y la guia completa en
  ENTORNO_DESARROLLO.md. Leela si necesitas detalle.
- La fuente de verdad del codigo es produccion: el repo debe ser copia exacta de torito.

Pasos a seguir, en este orden. Dame un comando por vez y espera mi salida antes del siguiente:

1. Conexion SSH a torito. En PowerShell normal (pide la contrasena de serviciosau dos veces):
     cd $HOME\Downloads
     scp serviciosau@172.16.1.223:/var/www/ctm/contexto_unificado/herramientas/ssh/configurar_ssh.ps1 .
     powershell -ExecutionPolicy Bypass -File .\configurar_ssh.ps1 -Usuario serviciosau
   Tiene que terminar con "CONEXION_OK desde torito". Si el archivo no esta en torito todavia,
   copiarlo desde otra notebook que ya tenga el repo.

2. Preparar la notebook (instala Git y Docker Desktop, clona el repo desde el Gitea de torito en
   C:\dev\ctm\contexto_unificado y crea los config.local.php). En PowerShell COMO ADMINISTRADOR:
     cd $HOME\Downloads
     scp ctm223:/var/www/ctm/contexto_unificado/herramientas/preparar_notebook.ps1 .
     powershell -ExecutionPolicy Bypass -File .\preparar_notebook.ps1
   Al clonar pide usuario y contrasena del Gitea (farinong); Git los guarda para la proxima.
   Es seguro repetirlo.

3. Habilitar WSL2 (Docker lo necesita). En la misma PowerShell de administrador:
     wsl --install --no-distribution
   Despues REINICIAR WINDOWS. Esto tambien arregla que git y docker no se reconozcan,
   porque recarga el PATH.

4. Despues del reinicio: abrir Docker Desktop desde el menu Inicio, aceptar los terminos y
   esperar a que abajo a la izquierda diga "Engine running".

5. En una PowerShell normal NUEVA verificar:
     git --version
     docker --version
   Si alguno no se reconoce: cerrar todas las PowerShell y abrir una nueva; si sigue igual,
   reiniciar.

6. Credenciales de MariaDB para el sincronizador (archivo local ignorado por git):
     cd C:\dev\ctm\contexto_unificado
     Set-Content herramientas\db\credenciales.local.ps1 '$UsuarioBd = "root"'
     Add-Content herramientas\db\credenciales.local.ps1 '$ClaveBd = "<CONTRASENA_ROOT_MARIADB>"'

7. Levantar Docker (la primera vez tarda varios minutos, compila extensiones de PHP):
     docker compose up -d --build
   Tiene que terminar con los servicios web, php, db y adminer levantados.

8. Traer las bases de produccion:
     powershell -ExecutionPolicy Bypass -File herramientas\db\sincronizar_bd.ps1
   Tiene que arrancar con "Credenciales tomadas de ...credenciales.local.ps1 (usuario root)".

9. Probar en el navegador:
     http://localhost:8080/ctm/contexto_unificado/ctm_entorno/default.php
   Adminer en http://localhost:8081 (servidor db, usuario root, clave ctm_dev).
   Tiene que verse igual que produccion. Si no, traer el codigo de torito al repo:
     powershell -ExecutionPolicy Bypass -File herramientas\traer_codigo_produccion.ps1
   y hacer commit + push de lo que cambie.

Errores ya conocidos y su causa, para que no me hagas dar vueltas:
- "El termino 'git' / 'docker' no se reconoce": la ventana de PowerShell es anterior a la
  instalacion. Abrir una nueva o reiniciar.
- "-File ... no existe": estoy parado en otra carpeta. Hacer cd primero.
- Si pego comandos mientras un script espera una contrasena, entran como contrasena.
  Avisame si ves que eso paso.
- "Access denied for user 'xxx'@'localhost'" al bajar las bases: usuario o contrasena de
  MariaDB mal, o el repo local esta desactualizado (hacer git pull).
- "Disk quota exceeded" en torito: el usuario serviciosau tiene cuota de disco. No escribir
  archivos grandes en el servidor; los scripts ya estan hechos para eso.
- Split-Path en PowerShell rompe las rutas Linux (cambia / por \). No usarlo para rutas de torito.

Flujo de trabajo una vez instalado (esta en ENTORNO_DESARROLLO.md):
rama nueva -> editar -> probar en localhost:8080 -> commit + push al Gitea de torito -> PR en Gitea
-> herramientas\desplegar.ps1 -Destino test -Rama <rama>  (prueba en torito)
-> merge a main -> herramientas\desplegar.ps1 -Destino prod

---
