#!/usr/bin/env bash
# Pasa la carpeta de PRODUCCION de torito a un checkout de git, sin perder nada.
# Despues de esto, producion se actualiza con "git pull" (herramientas\desplegar.ps1 -Destino prod).
#
# Que hace:
#   1. Clona el repo en una carpeta nueva al lado de produccion.
#   2. Muestra las diferencias entre produccion y el repo y pide confirmacion.
#   3. Copia a la carpeta nueva todo lo que produccion tenga de mas (modulos no versionados,
#      archivos subidos, etc.) sin pisar nada del repo.
#   4. Genera los config.local.php con las credenciales reales (las pide).
#   5. Intercambia las carpetas. La vieja queda como respaldo: contexto_unificado_old_FECHA
#
# Uso (desde la notebook):
#   scp herramientas\torito\adoptar_git_produccion.sh ctm223:/tmp/
#   ssh -t ctm223 "bash /tmp/adoptar_git_produccion.sh"
# Si falla por permisos, correrlo con sudo.
# Volver atras:  mv contexto_unificado contexto_unificado_git && mv contexto_unificado_old_FECHA contexto_unificado
set -e

RUTA_PROD="${RUTA_PROD:-/var/www/ctm/contexto_unificado}"
URL_PROD="${URL_PROD:-https://172.16.1.223/ctm/contexto_unificado}"
BD_PROD="${BD_PROD:-contexto_unificado}"
URL_REPO="${URL_REPO:-http://127.0.0.1:3000/farinong/contexto_unificado.git}"
RAMA="${RAMA:-main}"

FECHA=$(date +%Y%m%d_%H%M)
RUTA_NUEVA="${RUTA_PROD}_git_nuevo"
RUTA_VIEJA="${RUTA_PROD}_old_${FECHA}"
ARCHIVO_DIFF="/tmp/diferencias_prod_vs_repo_${FECHA}.txt"

verificar_estado() {
    if [ -d "$RUTA_PROD/.git" ]; then
        echo "Produccion ya es un checkout de git ($RUTA_PROD). No hay nada que hacer."
        exit 0
    fi
    if [ ! -d "$RUTA_PROD" ]; then
        echo "No existe $RUTA_PROD"
        exit 1
    fi
    if [ ! -w "$(dirname "$RUTA_PROD")" ]; then
        echo "Sin permiso de escritura en $(dirname "$RUTA_PROD"). Corre el script con sudo."
        exit 1
    fi
    rm -rf "$RUTA_NUEVA"
}

clonar_repo() {
    echo "Clonando el repo en $RUTA_NUEVA..."
    git clone --branch "$RAMA" "$URL_REPO" "$RUTA_NUEVA"
}

mostrar_diferencias() {
    echo "Comparando produccion con el repo..."
    diff -rq "$RUTA_PROD" "$RUTA_NUEVA" --exclude=.git --exclude=config.local.php > "$ARCHIVO_DIFF" || true
    local distintos solo_prod
    distintos=$(grep -c "^Files .* differ$" "$ARCHIVO_DIFF" || true)
    solo_prod=$(grep -c "^Only in $RUTA_PROD" "$ARCHIVO_DIFF" || true)
    echo "  Archivos distintos entre produccion y repo: $distintos  (produccion usara la version del repo)"
    echo "  Archivos/carpetas que solo estan en produccion: $solo_prod  (se copian a la carpeta nueva)"
    echo "  Detalle completo en $ARCHIVO_DIFF"
    echo
    echo "  Primeros archivos distintos:"
    grep "^Files .* differ$" "$ARCHIVO_DIFF" | head -15 | sed 's/^/    /'
    echo
    read -r -p "Continuar? (escribi SI) " resp < /dev/tty
    if [ "$resp" != "SI" ]; then
        echo "Cancelado. No se toco produccion."
        rm -rf "$RUTA_NUEVA"
        exit 0
    fi
}

copiar_extras_de_produccion() {
    echo "Copiando a la carpeta nueva lo que solo existe en produccion..."
    if command -v rsync > /dev/null; then
        rsync -a --ignore-existing --exclude=.git "$RUTA_PROD/" "$RUTA_NUEVA/"
    else
        cp -rn "$RUTA_PROD/." "$RUTA_NUEVA/"
    fi
}

generar_configs() {
    echo "Generando config.local.php de produccion (credenciales reales de MariaDB)..."
    bash "$RUTA_NUEVA/herramientas/torito/generar_config_local.sh" "$RUTA_NUEVA" "$BD_PROD" "$URL_PROD" off
}

igualar_dueno() {
    chown -R --reference="$RUTA_PROD" "$RUTA_NUEVA" 2>/dev/null \
        || echo "AVISO: no pude igualar el dueno de los archivos (correr con sudo si el sitio falla por permisos)."
}

intercambiar_carpetas() {
    echo "Intercambiando carpetas (corte de segundos)..."
    mv "$RUTA_PROD" "$RUTA_VIEJA"
    mv "$RUTA_NUEVA" "$RUTA_PROD"
}

verificar_estado
clonar_repo
mostrar_diferencias
copiar_extras_de_produccion
generar_configs
igualar_dueno
intercambiar_carpetas
echo
echo "Listo. Produccion ahora es un checkout de git en $RUTA_PROD (rama $RAMA)."
echo "Respaldo de la carpeta anterior: $RUTA_VIEJA"
echo "Proba el sitio: $URL_PROD/ctm_entorno/default.php"
echo "Si algo falla, volver atras:  mv $RUTA_PROD ${RUTA_PROD}_git && mv $RUTA_VIEJA $RUTA_PROD"
