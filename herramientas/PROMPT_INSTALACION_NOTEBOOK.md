# Prompt para instalar el entorno de desarrollo CTM en otra notebook

Copiar todo el bloque de abajo y pegarlo como primer mensaje en una sesion nueva de Claude.
Reemplazar lo que esta entre < >.

---

Quiero configurar esta notebook Windows como entorno de desarrollo del sistema CTM
(Contexto Unificado). Vos solo tenes que guiarme paso a paso y revisar las salidas que te pego.
Prefiero codigo sencillo en funciones, sin objetos. Hablame en espanol rioplatense.
Dame UN comando por vez y espera mi salida antes del siguiente. Si te pego una salida que
muestra que me salte un paso, decimelo antes de seguir.

REGLA PRINCIPAL: nada en la nube. El repositorio git central esta en el propio servidor de
produccion y los entornos de desarrollo son locales. No uses GitHub ni ningun servicio externo.

Contexto:
- Produccion es el servidor "torito", IP 172.16.1.223, Ubuntu 26.04, nginx 1.28, PHP 8.5,
  MariaDB 11.8. La app vive en /var/www/ctm/contexto_unificado y se sirve en
  https://172.16.1.223/ctm/contexto_unificado/ctm_entorno/default.php
  Es el servidor real en uso: nunca probar cambios directamente ahi.
- Repositorio git central: Gitea en torito, http://172.16.1.223:3000, usuario farinong,
  repo privado farinong/contexto_unificado. Su rama main ES el codigo de produccion mas las
  herramientas de desarrollo. URL para clonar: http://172.16.1.223:3000/farinong/contexto_unificado.git
- Usuario SSH en torito: serviciosau (tiene cuota de disco, no escribir archivos grandes ahi).
  Usuario de MariaDB en torito: root. Usuario de Gitea: farinong.
  Las contrasenas las escribo yo cuando los scripts las pidan; no las guardes en archivos
  que vayan a git.
- El repo trae todos los scripts en herramientas/ y la guia completa en ENTORNO_DESARROLLO.md.
- El entorno local es Docker: nginx + PHP 8.5 (con las mismas extensiones que torito, incluidas
  soap, odbc, ldap, intl, gd) + MariaDB 11.8 + Adminer. Se entra por HTTPS en el puerto 8080
  con certificado autofirmado, porque la aplicacion fuerza HTTPS (ctm_entorno/default.php).

Pasos a seguir, en este orden:

1. Conexion SSH a torito. En PowerShell normal (pide la contrasena de serviciosau dos veces):
     cd $HOME\Downloads
     scp serviciosau@172.16.1.223:/var/www/ctm/contexto_unificado/herramientas/ssh/configurar_ssh.ps1 .
     powershell -ExecutionPolicy Bypass -File .\configurar_ssh.ps1 -Usuario serviciosau
   Tiene que terminar con "CONEXION_OK desde torito". Si el archivo no esta en torito todavia
   (produccion aun no es checkout de git), copiarlo desde otra notebook que tenga el repo.

2. Preparar la notebook (instala Git y Docker Desktop, clona el repo desde el Gitea de torito en
   C:\dev\ctm\contexto_unificado y crea los config.local.php). En PowerShell COMO ADMINISTRADOR:
     cd $HOME\Downloads
     scp ctm223:/var/www/ctm/contexto_unificado/herramientas/preparar_notebook.ps1 .
     powershell -ExecutionPolicy Bypass -File .\preparar_notebook.ps1
   Al clonar pide usuario y contrasena del Gitea (farinong); Git los guarda para la proxima.
   Si Git no manda la contrasena por HTTP (error "Repository not found" sin pedir clave):
     git config --global credential.allowUnsafeRemotes true
   Es seguro repetir el script.

3. Habilitar WSL2 (Docker lo necesita). En la misma PowerShell de administrador:
     wsl --install --no-distribution
   Despues REINICIAR WINDOWS. Esto tambien arregla que git y docker no se reconozcan.

4. Despues del reinicio: abrir Docker Desktop desde el menu Inicio, aceptar los terminos y
   esperar a que abajo a la izquierda diga "Engine running".

5. En una PowerShell normal NUEVA verificar:
     git --version
     docker --version

6. Configuracion local del repo (fines de linea Linux, como torito y Docker):
     cd C:\dev\ctm\contexto_unificado
     git config core.autocrlf false

7. Credenciales de MariaDB para el sincronizador (archivo local ignorado por git):
     Set-Content herramientas\db\credenciales.local.ps1 '$UsuarioBd = "root"'
     Add-Content herramientas\db\credenciales.local.ps1 '$ClaveBd = "<CONTRASENA_ROOT_MARIADB>"'
     git check-ignore -v herramientas\db\credenciales.local.ps1
   El ultimo comando TIENE que mostrar el archivo como ignorado. Si no, parar y revisar .gitignore.

8. Levantar Docker (la primera vez tarda varios minutos, compila las extensiones de PHP):
     docker compose up -d --build
     docker compose ps
   Tienen que quedar web, php, db y adminer en "Up".

9. Traer las bases de produccion (no pide nada si existe credenciales.local.ps1):
     powershell -ExecutionPolicy Bypass -File herramientas\db\sincronizar_bd.ps1

10. Probar:
     curl.exe -k -sI https://localhost:8080/ctm/contexto_unificado/ctm_entorno/default.php | Select-Object -First 1
   Tiene que decir HTTP/1.1 200 OK. Despues en Chrome abrir
     https://localhost:8080/ctm/contexto_unificado/ctm_entorno/default.php
   Chrome avisa que el certificado no es de confianza: "Configuracion avanzada" y "Continuar a
   localhost". Si no ofrece el boton, escribir thisisunsafe con la pagina de error abierta.
   Adminer: http://localhost:8081 (servidor db, usuario root, clave ctm_dev).
   Tiene que verse igual que produccion.

Errores ya conocidos y su causa, para que no me hagas dar vueltas:
- "El termino 'git' / 'docker' no se reconoce": la ventana de PowerShell es anterior a la
  instalacion. Abrir una nueva o reiniciar.
- "-File ... no existe": estoy parado en otra carpeta. Hacer cd primero.
- Si pego comandos mientras un script espera una contrasena, entran como contrasena.
- "ERR_SSL_PROTOCOL_ERROR" o "no puede proporcionar una conexion segura" en localhost:8080:
  el contenedor web no genero el certificado. Ver "docker compose logs web"; si openssl pidio
  datos interactivos, el comando del docker-compose quedo partido en varias lineas. Recrear con
  "docker compose rm -sf web; docker volume rm contexto_unificado_ctm_certs; docker compose up -d web".
- "Class SoapClient not found" u otra extension PHP faltante: comparar "docker compose exec php php -m"
  con "ssh ctm223 php -m" y agregar la que falte en docker/php/Dockerfile.
- "Access denied for user 'xxx'@'localhost'" al bajar las bases: usuario o contrasena de MariaDB
  mal, o el repo local esta desactualizado (hacer git pull).
- "Disk quota exceeded" en torito: serviciosau tiene cuota. Los scripts ya no escriben en el servidor.
- "Rename-Item: el archivo esta siendo utilizado": alguna ventana o editor tiene la carpeta
  abierta. Cerrarlos o reiniciar.
- Split-Path en PowerShell rompe las rutas Linux (cambia / por \). No usarlo para rutas de torito.
- Avisos "LF will be replaced by CRLF": falta "git config core.autocrlf false".
- Antes de cualquier "git add -A" verificar que .gitignore tenga config.local.php,
  herramientas/db/credenciales.local.ps1 y herramientas/db/dumps/*. Una vez se subieron los
  volcados de produccion al repo por no revisarlo y hubo que rehacer el commit y publicar con --force.
- El .gitignore de torito es mas completo que el viejo (no excluye modulos); al traer codigo de
  produccion lo reemplaza. Hay que volver a agregarle las tres reglas de arriba.
- El login pasa por intermedio_aico.php, que valida contra un web service SOAP. Si falla solo
  en la notebook, puede ser que ese servicio no acepte conexiones desde fuera de torito.

Flujo de trabajo una vez instalado (esta en ENTORNO_DESARROLLO.md):
rama nueva -> editar -> probar en https://localhost:8080 -> commit + push al Gitea de torito -> PR en Gitea
-> herramientas\desplegar.ps1 -Destino test -Rama <rama>  (prueba en torito)
-> merge a main -> herramientas\desplegar.ps1 -Destino prod

Si sospecho que alguien edito a mano en torito: herramientas\traer_codigo_produccion.ps1,
revisar "git status", y commit "Sincronizar codigo con produccion torito".

---
