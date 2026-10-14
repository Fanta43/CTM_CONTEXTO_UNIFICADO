# Conexion SSH al servidor 172.16.1.223 (torito)

Scripts para dejar configurado el equipo local y conectarse con `ssh ctm223`.

## Datos de la conexion

| Dato | Valor |
|---|---|
| IP | 172.16.1.223 |
| Hostname del servidor | torito |
| Usuario | serviciosau |
| Puerto | 22 |
| Alias en `~/.ssh/config` | ctm223 |
| Clave privada local | `~/.ssh/id_ed25519_ctm` |
| Fingerprint del servidor (ED25519) | `SHA256:9uaFjWGVwqGEgYrFdAOoMAN9mushWq2cdgZVLp3UY2Q` |

Una vez configurado, se entra con:

```
ssh ctm223
```

## Windows (PowerShell)

Opcion A, desde el repo clonado:

```powershell
cd D:\xampp\htdocs\ctm\contexto_unificado
powershell -ExecutionPolicy Bypass -File .\herramientas\ssh\configurar_ssh.ps1 -Usuario serviciosau
```

Opcion B, sin clonar el repo (descarga solo el script):

```powershell
cd $HOME\Downloads
scp serviciosau@172.16.1.223:/var/www/ctm/contexto_unificado/herramientas/ssh/configurar_ssh.ps1 .
powershell -ExecutionPolicy Bypass -File .\configurar_ssh.ps1 -Usuario serviciosau
```

Parametros opcionales: `-Ip 172.16.1.223 -Puerto 22 -Alias ctm223`

## Linux / Mac

```bash
bash herramientas/ssh/configurar_ssh.sh serviciosau
```

Argumentos opcionales en orden: usuario, ip, puerto, alias.

## Que hace el script

1. Verifica que exista el cliente `ssh` (Windows 10/11 ya lo trae).
2. Crea `~/.ssh` si no existe.
3. Genera la clave `~/.ssh/id_ed25519_ctm` (solo si no existe).
4. Agrega el alias `ctm223` a `~/.ssh/config` (sin duplicar si ya esta).
5. Copia la clave publica al servidor. Pide la contrasena una sola vez.
6. Prueba la conexion sin contrasena y muestra `CONEXION_OK desde torito`.

## Que esperar al ejecutarlo

- La primera vez pregunta si confiar en el servidor:
  `Are you sure you want to continue connecting (yes/no/[fingerprint])?`
  Responder `yes`. Conviene verificar que el fingerprint coincida con el de la tabla de arriba.
- Despues pide la contrasena de `serviciosau@172.16.1.223` una sola vez.
- Si termina con `CONEXION_OK desde torito` y `Listo. Conectate con: ssh ctm223`, quedo todo configurado.

## Registro de equipos configurados

| Fecha | Equipo | Usuario local | Resultado |
|---|---|---|---|
| 2026-10-13 | P2572NOT (Windows) | farinong | OK, conexion sin contrasena verificada |

## Problemas comunes

- `El argumento '...configurar_ssh.ps1' para el parametro -File no existe`: PowerShell esta parado en otra carpeta. Hacer `cd` a la carpeta del repo o de la descarga antes de ejecutar.
- `No se encontro el cliente ssh`: instalarlo con
  `Add-WindowsCapability -Online -Name OpenSSH.Client~~~~0.0.1.0`
- `Permission denied (publickey)` en la prueba final: la clave no se copio bien. Volver a correr el script, que repite el paso de copia.
