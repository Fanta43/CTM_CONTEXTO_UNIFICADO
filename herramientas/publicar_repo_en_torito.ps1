# Publica el repo local en el Gitea de torito y lo deja como "origin".
# Si el repositorio no existe en Gitea, lo crea (privado, vacio) con la API.
# Uso:  powershell -ExecutionPolicy Bypass -File herramientas\publicar_repo_en_torito.ps1
#       (opcional) -ClaveGit "xxxx"   para no tener que escribir la contrasena
# Si habia un origin anterior (por ejemplo GitHub) queda renombrado como "anterior".

param(
    [string]$Servidor = "http://172.16.1.223:3000",
    [string]$UsuarioGit = "farinong",
    [string]$ClaveGit = "",
    [string]$NombreRepo = "contexto_unificado"
)

$RaizRepo = Resolve-Path (Join-Path $PSScriptRoot "..")
$UrlRepo = "$Servidor/$UsuarioGit/$NombreRepo.git"

function Pedir-Clave {
    if ($ClaveGit) { return $ClaveGit }
    $segura = Read-Host "Contrasena de $UsuarioGit en el Gitea de torito" -AsSecureString
    $ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($segura)
    $clave = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr)
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr)
    return $clave
}

function Cabecera-Auth {
    param([string]$Clave)
    $par = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("${UsuarioGit}:$Clave"))
    return @{ Authorization = "Basic $par" }
}

function Existe-Repo {
    param([hashtable]$Auth)
    try {
        Invoke-RestMethod -Uri "$Servidor/api/v1/repos/$UsuarioGit/$NombreRepo" -Headers $Auth -Method Get | Out-Null
        return $true
    } catch {
        return $false
    }
}

function Crear-Repo {
    param([hashtable]$Auth)
    Write-Host "Creando el repositorio $UsuarioGit/$NombreRepo en Gitea (privado, vacio)..."
    $cuerpo = '{"name":"' + $NombreRepo + '","private":true,"auto_init":false}'
    try {
        $r = Invoke-RestMethod -Uri "$Servidor/api/v1/user/repos" -Headers $Auth -Method Post -ContentType "application/json" -Body $cuerpo
        Write-Host "  Creado: $($r.full_name)"
    } catch {
        Write-Host "  ERROR creando el repositorio: $($_.Exception.Message)"
        Write-Host "  Revisa usuario/contrasena o crealo a mano en $Servidor"
        exit 1
    }
}

function Configurar-Origin {
    Push-Location $RaizRepo
    $actual = git remote get-url origin 2>$null
    if ($actual -eq $UrlRepo) {
        Write-Host "origin ya apunta a torito."
    } elseif ($actual) {
        git remote rename origin anterior
        git remote add origin $UrlRepo
        Write-Host "origin anterior ($actual) renombrado a 'anterior'. origin ahora es torito."
    } else {
        git remote add origin $UrlRepo
        Write-Host "origin agregado: $UrlRepo"
    }
    Pop-Location
}

function Publicar-Todo {
    Push-Location $RaizRepo
    Write-Host "Publicando ramas y tags en torito (la primera vez tarda, son unos 400 MB)..."
    Write-Host "Si git pide usuario y contrasena, son los del Gitea ($UsuarioGit); Windows los guarda."
    git push origin --all
    git push origin --tags
    $rama = git branch --show-current
    git branch --set-upstream-to="origin/$rama" $rama
    Pop-Location
}

$clave = Pedir-Clave
$auth = Cabecera-Auth -Clave $clave
if (Existe-Repo -Auth $auth) {
    Write-Host "El repositorio ya existe en Gitea: $UrlRepo"
} else {
    Crear-Repo -Auth $auth
}
$clave = $null
Configurar-Origin
Publicar-Todo
Write-Host "Listo. Desde ahora git pull / git push van contra torito ($UrlRepo)."
Write-Host "Verificalo en $Servidor/$UsuarioGit/$NombreRepo"
