# Trae las bases de datos de torito (produccion) y las carga en el MariaDB local de Docker.
# Uso:  powershell -ExecutionPolicy Bypass -File herramientas\db\sincronizar_bd.ps1
#       (opcional) -Bases contexto_unificado,morebius -SoloBajar
# Requisitos: ssh ctm223 funcionando, Docker Desktop corriendo con "docker compose up -d".
# La contrasena de MariaDB en torito se pide al ejecutar, salvo que exista
# herramientas\db\credenciales.local.ps1 (ignorado por git, ver el .ejemplo) con $UsuarioBd y $ClaveBd.

param(
    [string]$Alias = "ctm223",
    [string[]]$Bases = @("contexto_unificado", "morebius", "ctm_ocsweb"),
    [string]$UsuarioBd = "root",
    [string]$ClaveLocal = "ctm_dev",
    [switch]$SoloBajar,
    [switch]$SoloCargar
)

$CarpetaDumps = Join-Path $PSScriptRoot "dumps"
$RaizRepo = Resolve-Path (Join-Path $PSScriptRoot "..\..")
$ArchivoCredenciales = Join-Path $PSScriptRoot "credenciales.local.ps1"
$ClaveBd = $null

function Cargar-Credenciales {
    if (Test-Path $ArchivoCredenciales) {
        . $ArchivoCredenciales
        Write-Host "Credenciales tomadas de $ArchivoCredenciales (usuario $UsuarioBd)"
    }
}

function Pedir-ClaveBd {
    if ($ClaveBd) { return $ClaveBd }
    $segura = Read-Host "Contrasena de MariaDB para $UsuarioBd en torito" -AsSecureString
    $ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($segura)
    $clave = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr)
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr)
    return $clave
}

function Bajar-Dump {
    param([string]$Base, [string]$Clave)
    $archivo = Join-Path $CarpetaDumps "$Base.sql"
    Write-Host "Bajando $Base desde torito..."
    $comando = "MYSQL_PWD='$Clave' mysqldump --user=$UsuarioBd --single-transaction --quick --routines --triggers --events --databases $Base"
    ssh $Alias $comando | Out-File -FilePath $archivo -Encoding utf8
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  ERROR bajando $Base (revisa usuario/contrasena o permisos en torito)"
        return $false
    }
    $mb = [math]::Round((Get-Item $archivo).Length / 1MB, 1)
    Write-Host "  OK -> $archivo ($mb MB)"
    return $true
}

function Cargar-Dump {
    param([string]$Base)
    $archivo = Join-Path $CarpetaDumps "$Base.sql"
    if (-not (Test-Path $archivo)) {
        Write-Host "No existe $archivo, saltando $Base"
        return
    }
    Write-Host "Cargando $Base en MariaDB local..."
    Push-Location $RaizRepo
    cmd /c "docker compose exec -T db mariadb -uroot -p$ClaveLocal < `"$archivo`""
    $ok = ($LASTEXITCODE -eq 0)
    Pop-Location
    if ($ok) { Write-Host "  OK $Base cargada" } else { Write-Host "  ERROR cargando $Base" }
}

function Verificar-Docker {
    Push-Location $RaizRepo
    docker compose ps db 2>$null | Out-Null
    $ok = ($LASTEXITCODE -eq 0)
    Pop-Location
    if (-not $ok) {
        Write-Host "Docker no responde. Abri Docker Desktop y corre: docker compose up -d"
        exit 1
    }
}

New-Item -ItemType Directory -Path $CarpetaDumps -Force | Out-Null
Cargar-Credenciales

if (-not $SoloCargar) {
    $clave = Pedir-ClaveBd
    foreach ($base in $Bases) { Bajar-Dump -Base $base -Clave $clave | Out-Null }
    $clave = $null
}

if (-not $SoloBajar) {
    Verificar-Docker
    foreach ($base in $Bases) { Cargar-Dump -Base $base }
}

Write-Host "Listo. Adminer: http://localhost:8081  (servidor db, usuario root, clave $ClaveLocal)"
