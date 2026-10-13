# Configura este equipo (Windows) para conectarse por SSH al servidor 172.16.1.223
# Uso:  powershell -ExecutionPolicy Bypass -File configurar_ssh.ps1 -Usuario root
# Requiere el cliente OpenSSH de Windows (viene incluido en Windows 10/11).

param(
    [string]$Usuario = $env:USERNAME,
    [string]$Ip = "172.16.1.223",
    [int]$Puerto = 22,
    [string]$Alias = "ctm223"
)

$CarpetaSsh = Join-Path $HOME ".ssh"
$ArchivoClave = Join-Path $CarpetaSsh "id_ed25519_ctm"
$ArchivoConfig = Join-Path $CarpetaSsh "config"

function Verificar-Ssh {
    if (-not (Get-Command ssh -ErrorAction SilentlyContinue)) {
        Write-Host "No se encontro el cliente ssh. Instalalo con:"
        Write-Host "  Add-WindowsCapability -Online -Name OpenSSH.Client~~~~0.0.1.0"
        exit 1
    }
}

function Crear-CarpetaSsh {
    if (-not (Test-Path $CarpetaSsh)) {
        New-Item -ItemType Directory -Path $CarpetaSsh | Out-Null
        Write-Host "Carpeta creada: $CarpetaSsh"
    }
}

function Generar-Clave {
    if (Test-Path $ArchivoClave) {
        Write-Host "La clave ya existe: $ArchivoClave"
        return
    }
    ssh-keygen -t ed25519 -f $ArchivoClave -N '""' -C "$env:USERNAME@$env:COMPUTERNAME"
    Write-Host "Clave generada: $ArchivoClave"
}

function Agregar-Config {
    if ((Test-Path $ArchivoConfig) -and (Select-String -Path $ArchivoConfig -Pattern ("^Host " + $Alias + "$") -Quiet)) {
        Write-Host "El alias '$Alias' ya esta en $ArchivoConfig"
        return
    }
    $bloque = @"

Host $Alias
    HostName $Ip
    User $Usuario
    Port $Puerto
    IdentityFile $ArchivoClave
    ServerAliveInterval 60
"@
    Add-Content -Path $ArchivoConfig -Value $bloque
    Write-Host "Alias '$Alias' agregado a $ArchivoConfig"
}

function Copiar-ClavePublica {
    Write-Host "Copiando la clave publica al servidor (te va a pedir la contrasena de $Usuario@$Ip)..."
    $clavePublica = Get-Content "$ArchivoClave.pub"
    $comando = "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo '$clavePublica' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"
    ssh -p $Puerto "$Usuario@$Ip" $comando
}

function Probar-Conexion {
    Write-Host "Probando conexion sin contrasena..."
    ssh -o BatchMode=yes $Alias 'echo CONEXION_OK desde $(hostname)'
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Listo. Conectate con:  ssh $Alias"
    } else {
        Write-Host "No se pudo conectar sin contrasena. Revisa usuario, red y permisos en el servidor."
    }
}

Verificar-Ssh
Crear-CarpetaSsh
Generar-Clave
Agregar-Config
Copiar-ClavePublica
Probar-Conexion
