# Despliega el codigo en torito por SSH usando git.
# Uso:  powershell -ExecutionPolicy Bypass -File herramientas\desplegar.ps1 -Destino test  [-Rama mi-rama]
#       powershell -ExecutionPolicy Bypass -File herramientas\desplegar.ps1 -Destino prod
# "test" acepta cualquier rama; "prod" solo despliega main y pide confirmacion.
# config.local.php de cada modulo no esta en git, asi que el despliegue no lo toca.

param(
    [Parameter(Mandatory = $true)][ValidateSet("test", "prod")][string]$Destino,
    [string]$Rama = "main",
    [string]$Alias = "ctm223",
    [string]$RutaTest = "/var/www/ctm/contexto_unificado_test",
    [string]$RutaProd = "/var/www/ctm/contexto_unificado"
)

function Ruta-Destino {
    if ($Destino -eq "prod") { return $RutaProd }
    return $RutaTest
}

function Confirmar-Produccion {
    if ($Destino -ne "prod") { return }
    if ($Rama -ne "main") {
        Write-Host "A produccion solo se despliega la rama main."
        exit 1
    }
    $resp = Read-Host "Vas a actualizar PRODUCCION en torito ($RutaProd). Escribi SI para continuar"
    if ($resp -ne "SI") {
        Write-Host "Cancelado."
        exit 0
    }
}

function Verificar-Repo {
    param([string]$Ruta)
    ssh $Alias "test -d $Ruta/.git"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "En torito no hay un repo git en $Ruta."
        Write-Host "Clonalo primero:  ssh $Alias 'git clone http://127.0.0.1:3000/farinong/contexto_unificado.git $Ruta'"
        exit 1
    }
}

function Actualizar-Codigo {
    param([string]$Ruta)
    Write-Host "Actualizando $Ruta a la rama $Rama..."
    $comando = "cd $Ruta && git fetch origin $Rama && git checkout $Rama && git pull --ff-only origin $Rama && git log -1 --oneline"
    ssh $Alias $comando
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: el despliegue fallo. Revisa la salida de arriba."
        exit 1
    }
}

function Verificar-ConfigLocal {
    param([string]$Ruta)
    $faltan = ssh $Alias "for m in ctm_entorno ctm_inventarios ctm_morebius ctm_presupuestos; do test -f $Ruta/`$m/config/config.local.php || echo `$m; done"
    if ($faltan) {
        Write-Host "AVISO: faltan config.local.php en torito para: $($faltan -join ', ')"
        Write-Host "       Copia el .ejemplo de cada modulo y ajusta credenciales y URL."
    }
}

$ruta = Ruta-Destino
Confirmar-Produccion
Verificar-Repo -Ruta $ruta
Actualizar-Codigo -Ruta $ruta
Verificar-ConfigLocal -Ruta $ruta
Write-Host "Despliegue en $Destino terminado."
