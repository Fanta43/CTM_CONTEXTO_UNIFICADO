# Trae el codigo de PRODUCCION (torito) al repo local, para que el repo sea copia exacta del servidor.
# Uso:  powershell -ExecutionPolicy Bypass -File herramientas\traer_codigo_produccion.ps1
#       (opcional) -BorrarFaltantes   borra del repo los archivos que ya no existen en produccion
# Que hace:
#   1. Empaqueta en torito la carpeta de produccion (sin .git ni config/config.php).
#   2. La descarga y la descomprime encima del repo local, pisando los archivos del repo.
#   3. Muestra un resumen de lo que cambio para revisarlo con "git status" y hacer commit.
# No toca produccion. No trae config/config.php (tiene credenciales): eso va en config.local.php.

param(
    [string]$Alias = "ctm223",
    [string]$RutaProd = "/var/www/ctm/contexto_unificado",
    [switch]$BorrarFaltantes
)

$RaizRepo = Resolve-Path (Join-Path $PSScriptRoot "..")
$Paquete = Join-Path $env:TEMP "prod_ctm.tgz"
$ListaProd = Join-Path $env:TEMP "lista_prod.txt"
# rutas Linux: no usar Split-Path (en Windows cambia las barras)
$RutaProd = $RutaProd.TrimEnd("/")
$CarpetaProd = $RutaProd.Substring(0, $RutaProd.LastIndexOf("/"))
$NombreProd = $RutaProd.Substring($RutaProd.LastIndexOf("/") + 1)
# Archivos del repo que no estan en produccion y NO deben borrarse (herramientas de desarrollo)
$Protegidos = "^(herramientas/|docker/|docker-compose.yml|ENTORNO_DESARROLLO.md|PROMPT_MEJORA_CTM.md|\.gitignore|.*/config/config\.php$|.*/config/config\.local\.php\.ejemplo$)"

function Verificar-Repo {
    if (-not (Test-Path (Join-Path $RaizRepo ".git"))) {
        Write-Host "No encuentro el repo git en $RaizRepo"
        exit 1
    }
    Push-Location $RaizRepo
    $pendientes = git status --porcelain
    Pop-Location
    if ($pendientes) {
        Write-Host "El repo tiene cambios sin commitear. Hace commit o descartalos antes de traer produccion."
        exit 1
    }
}

function Empaquetar-Produccion {
    Write-Host "Empaquetando $RutaProd en torito..."
    ssh $Alias "cd $CarpetaProd && tar czf /tmp/prod_ctm.tgz --exclude='.git' --exclude='*/config/config.php' --exclude='*/config/config.local.php' $NombreProd && find $NombreProd -type f -not -path '*/.git/*' | sed 's#^$NombreProd/##' | sort > /tmp/lista_prod.txt && ls -lh /tmp/prod_ctm.tgz"
    if ($LASTEXITCODE -ne 0) { Write-Host "ERROR empaquetando en torito"; exit 1 }
}

function Descargar-Paquete {
    Write-Host "Descargando..."
    scp -q "${Alias}:/tmp/prod_ctm.tgz" $Paquete
    scp -q "${Alias}:/tmp/lista_prod.txt" $ListaProd
    if ($LASTEXITCODE -ne 0) { Write-Host "ERROR descargando"; exit 1 }
    ssh $Alias "rm -f /tmp/prod_ctm.tgz /tmp/lista_prod.txt"
}

function Extraer-Encima-Del-Repo {
    Write-Host "Descomprimiendo encima de $RaizRepo..."
    Push-Location (Split-Path $RaizRepo -Parent)
    # el paquete trae la carpeta $NombreProd; el repo local se llama contexto_unificado
    tar xzf $Paquete
    if ($NombreProd -ne (Split-Path $RaizRepo -Leaf)) {
        Copy-Item -Path "$NombreProd\*" -Destination $RaizRepo -Recurse -Force
        Remove-Item $NombreProd -Recurse -Force
    }
    Pop-Location
}

function Listar-Faltantes {
    Push-Location $RaizRepo
    $enRepo = git ls-files
    Pop-Location
    $enProd = @{}
    foreach ($l in Get-Content $ListaProd) { $enProd[$l] = $true }
    $faltantes = @()
    foreach ($f in $enRepo) {
        if (-not $enProd.ContainsKey($f) -and $f -notmatch $Protegidos) { $faltantes += $f }
    }
    return $faltantes
}

function Mostrar-Resumen {
    Push-Location $RaizRepo
    $modificados = (git status --porcelain | Where-Object { $_ -match '^ M' }).Count
    $nuevos = (git status --porcelain | Where-Object { $_ -match '^\?\?' }).Count
    $ignorados = git status --porcelain --ignored | Where-Object { $_ -match '^!!' } | ForEach-Object { $_.Substring(3) }
    Pop-Location
    $faltantes = Listar-Faltantes
    Write-Host ""
    Write-Host "Resumen:"
    Write-Host "  Archivos del repo modificados por produccion: $modificados"
    Write-Host "  Archivos nuevos que solo estaban en produccion: $nuevos"
    Write-Host "  Archivos del repo que NO existen en produccion: $($faltantes.Count)"
    if ($faltantes.Count -gt 0) {
        $faltantes | Select-Object -First 20 | ForEach-Object { Write-Host "    $_" }
        if ($BorrarFaltantes) {
            Push-Location $RaizRepo
            foreach ($f in $faltantes) { git rm -q $f }
            Pop-Location
            Write-Host "  -> borrados del repo (-BorrarFaltantes)"
        } else {
            Write-Host "  -> no se borraron. Para borrarlos: volver a correr con -BorrarFaltantes"
        }
    }
    $modIgnorados = $ignorados | Where-Object { $_ -match '^(ctm_|aced_|openai_|polo_|prueba_|uai_|cron_)' }
    if ($modIgnorados) {
        Write-Host "  Carpetas traidas de produccion pero IGNORADAS por .gitignore (no van al repo):"
        $modIgnorados | ForEach-Object { Write-Host "    $_" }
        Write-Host "  -> si produccion las usa, quitarlas de .gitignore para que sean parte del repo."
    }
    Write-Host ""
    Write-Host "Revisa con:  git status  /  git diff --stat"
    Write-Host "Y confirma con:  git add -A ; git commit -m 'Sincronizar codigo con produccion torito' ; git push"
}

Verificar-Repo
Empaquetar-Produccion
Descargar-Paquete
Extraer-Encima-Del-Repo
Mostrar-Resumen
