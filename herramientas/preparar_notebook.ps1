# Prepara una notebook Windows limpia para desarrollar CTM:
# instala Git y Docker Desktop (con winget), clona el repo y crea los config.local.php.
# El repo se clona desde el git de torito; va a pedir usuario y contrasena de ese git una vez.
# Ejecutar en PowerShell COMO ADMINISTRADOR:
#   powershell -ExecutionPolicy Bypass -File preparar_notebook.ps1
# Si Docker Desktop pide reiniciar (WSL2), reiniciar y volver a correr el script: es seguro repetirlo.

param(
    [string]$CarpetaRepo = "C:\dev\ctm\contexto_unificado",
    [string]$UrlRepo = "http://172.16.1.223:3000/farinong/contexto_unificado.git",
    [string]$Rama = "main"
)

function Verificar-Admin {
    $esAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
    if (-not $esAdmin) {
        Write-Host "Abri PowerShell como Administrador (clic derecho > Ejecutar como administrador) y volve a correr el script."
        exit 1
    }
}

function Instalar-Si-Falta {
    param([string]$Comando, [string]$IdWinget, [string]$Nombre)
    if (Get-Command $Comando -ErrorAction SilentlyContinue) {
        Write-Host "$Nombre ya esta instalado."
        return
    }
    Write-Host "Instalando $Nombre..."
    winget install --id $IdWinget -e --accept-source-agreements --accept-package-agreements
    $env:Path = [Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [Environment]::GetEnvironmentVariable("Path", "User")
}

function Clonar-Repo {
    if (Test-Path (Join-Path $CarpetaRepo ".git")) {
        Write-Host "El repo ya esta en $CarpetaRepo, actualizando..."
        git -C $CarpetaRepo fetch origin
        git -C $CarpetaRepo checkout $Rama
        git -C $CarpetaRepo pull --ff-only origin $Rama
        return
    }
    New-Item -ItemType Directory -Path (Split-Path $CarpetaRepo) -Force | Out-Null
    Write-Host "Clonando el repo en $CarpetaRepo..."
    git clone --branch $Rama $UrlRepo $CarpetaRepo
}

function Crear-ConfigsLocales {
    foreach ($m in "ctm_entorno", "ctm_inventarios", "ctm_morebius", "ctm_presupuestos") {
        $ejemplo = Join-Path $CarpetaRepo "$m\config\config.local.php.ejemplo"
        $destino = Join-Path $CarpetaRepo "$m\config\config.local.php"
        if (Test-Path $destino) { continue }
        if (Test-Path $ejemplo) {
            Copy-Item $ejemplo $destino
            Write-Host "Creado $m\config\config.local.php"
        }
    }
}

function Mostrar-SiguientesPasos {
    Write-Host ""
    Write-Host "Listo. Siguientes pasos:"
    Write-Host "  1. Si recien se instalo Docker Desktop, abrilo una vez y espera a que diga 'Engine running'."
    Write-Host "     Si pidio reiniciar Windows, reinicia y volve a correr este script."
    Write-Host "  2. cd $CarpetaRepo"
    Write-Host "  3. docker compose up -d --build"
    Write-Host "  4. powershell -ExecutionPolicy Bypass -File herramientas\db\sincronizar_bd.ps1"
    Write-Host "  5. Abrir http://localhost:8080/ctm/contexto_unificado/ctm_inventarios/"
}

Verificar-Admin
Instalar-Si-Falta -Comando git -IdWinget "Git.Git" -Nombre "Git"
Instalar-Si-Falta -Comando docker -IdWinget "Docker.DockerDesktop" -Nombre "Docker Desktop"
Clonar-Repo
Crear-ConfigsLocales
Mostrar-SiguientesPasos
