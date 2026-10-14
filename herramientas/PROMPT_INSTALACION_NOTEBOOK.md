# Prompt para instalar el entorno de desarrollo CTM en otra notebook

Copiar todo el bloque de abajo y pegarlo como primer mensaje en una sesion nueva de Claude.
Reemplazar lo que esta entre < >.

---

Quiero configurar esta notebook Windows como entorno de desarrollo del sistema CTM
(Contexto Unificado). El repo es https://github.com/Fanta43/CTM_CONTEXTO_UNIFICADO y ya trae
todos los scripts; vos solo tenes que guiarme paso a paso y revisar las salidas que te pego.
Prefiero codigo sencillo en funciones, sin objetos. Hablame en espanol rioplatense.

Contexto:
- Produccion es el servidor "torito", IP 172.16.1.223, Ubuntu 26.04, nginx 1.28, PHP 8.5,
  MariaDB 11.8. La app vive en /var/www/ctm/contexto_unificado y se sirve en
  https://172.16.1.223/ctm/contexto_unificado/ctm_entorno/default.php
- Usuario SSH en torito: serviciosau. Usuario de MariaDB en torito: root.
  Las contrasenas las escribo yo cuando los scripts las pidan; no las guardes en archivos
  que vayan a git.
- La guia completa esta en ENTORNO_DESARROLLO.md del repo. Leela si necesitas detalle.

Pasos a seguir, en este orden. Dame un comando por vez y espera mi salida antes del siguiente:

1. Conexion SSH a torito. En PowerShell normal:
     cd $HOME\Downloads
     curl.exe -L -o configurar_ssh.ps1 https://raw.githubusercontent.com/Fanta43/CTM_CONTEXTO_UNIFICADO/main/herramientas/ssh/configurar_ssh.ps1
     powershell -ExecutionPolicy Bypass -File .\configurar_ssh.ps1 -Usuario serviciosau
   Tiene que terminar con "CONEXION_OK desde torito".

2. Preparar la notebook (instala Git y Docker Desktop, clona el repo en
   C:\dev\ctm\contexto_unificado y crea los config.local.php). En PowerShell COMO ADMINISTRADOR:
     cd $HOME\Downloads
     curl.exe -L -o preparar_notebook.ps1 https://raw.githubusercontent.com/Fanta43/CTM_CONTEXTO_UNIFICADO/main/herramientas/preparar_notebook.ps1
     powershell -ExecutionPolicy Bypass -File .\preparar_notebook.ps1
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

Errores ya conocidos y su causa, para que no me hagas dar vueltas:
- "El termino 'git' / 'docker' no se reconoce": la ventana de PowerShell es anterior a la
  instalacion. Abrir una nueva o reiniciar.
- "-File ... no existe": estoy parado en otra carpeta. Hacer cd primero.
- Si pego comandos mientras un script espera una contrasena, entran como contrasena.
  Avisame si ves que eso paso.
- "Access denied for user 'xxx'@'localhost'" al bajar las bases: usuario o contrasena de
  MariaDB mal, o el repo local esta desactualizado (hacer git pull).

Flujo de trabajo una vez instalado (esta en ENTORNO_DESARROLLO.md):
rama nueva -> editar -> probar en localhost:8080 -> commit + push -> PR
-> herramientas\desplegar.ps1 -Destino test -Rama <rama>  (prueba en torito)
-> merge a main -> herramientas\desplegar.ps1 -Destino prod

---
