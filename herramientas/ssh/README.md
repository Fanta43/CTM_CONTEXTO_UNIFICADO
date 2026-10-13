# Conexion SSH al servidor 172.16.1.223

Scripts para dejar configurado el equipo local y conectarse con `ssh ctm223`.

## Windows (PowerShell)

```powershell
powershell -ExecutionPolicy Bypass -File herramientas\ssh\configurar_ssh.ps1 -Usuario root
```

Parametros opcionales: `-Ip 172.16.1.223 -Puerto 22 -Alias ctm223`

## Linux / Mac

```bash
bash herramientas/ssh/configurar_ssh.sh root
```

Argumentos opcionales en orden: usuario, ip, puerto, alias.

## Que hace

1. Verifica que exista el cliente `ssh`.
2. Crea `~/.ssh` si no existe.
3. Genera la clave `~/.ssh/id_ed25519_ctm` (solo si no existe).
4. Agrega el alias `ctm223` a `~/.ssh/config`.
5. Copia la clave publica al servidor (pide la contrasena una sola vez).
6. Prueba la conexion sin contrasena.

Despues de correrlo: `ssh ctm223`
