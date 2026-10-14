# Publica el repo local en el git de torito y lo deja como "origin".
# Uso:  powershell -ExecutionPolicy Bypass -File herramientas\publicar_repo_en_torito.ps1
#       (opcional) -UrlRepo ctm223:/opt/git/contexto_unificado.git   si se usa el repo bare por SSH
# Antes hay que crear el repositorio vacio en el Gitea de torito (http://172.16.1.223:3000),
# privado, con nombre contexto_unificado, SIN inicializar (sin README).
# Si habia un origin anterior (por ejemplo GitHub) queda renombrado como "anterior".

param(
    [string]$UrlRepo = "http://172.16.1.223:3000/farinong/contexto_unificado.git"
)

$RaizRepo = Resolve-Path (Join-Path $PSScriptRoot "..")

function Verificar-RepoServidor {
    Write-Host "Verificando $UrlRepo (puede pedir usuario y contrasena del git de torito)..."
    git ls-remote $UrlRepo 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "No puedo acceder a $UrlRepo. Crea el repositorio en torito o revisa usuario/contrasena."
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
    git push origin --all
    git push origin --tags
    $rama = git branch --show-current
    git branch --set-upstream-to="origin/$rama" $rama
    Pop-Location
}

Verificar-RepoServidor
Configurar-Origin
Publicar-Todo
Write-Host "Listo. Desde ahora git pull / git push van contra torito ($UrlRepo)."
