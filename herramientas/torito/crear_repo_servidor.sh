#!/usr/bin/env bash
# ALTERNATIVA si torito NO tiene Gitea: repositorio git bare accesible por SSH.
# Las notebooks hacen push/pull por SSH:  ctm223:/opt/git/contexto_unificado.git
# Produccion y test clonan desde la ruta local:  /opt/git/contexto_unificado.git
# Uso (desde la notebook):
#   scp herramientas\torito\crear_repo_servidor.sh ctm223:/tmp/
#   ssh -t ctm223 "bash /tmp/crear_repo_servidor.sh"
# Pide sudo solo para crear /opt/git la primera vez. Es seguro repetirlo.
set -e

RUTA_REPO="${RUTA_REPO:-/opt/git/contexto_unificado.git}"
CARPETA=$(dirname "$RUTA_REPO")

crear_carpeta() {
    if [ -d "$CARPETA" ] && [ -w "$CARPETA" ]; then
        return
    fi
    echo "Creando $CARPETA (pide sudo)..."
    sudo mkdir -p "$CARPETA"
    sudo chown "$USER":"$USER" "$CARPETA"
}

crear_repo() {
    if [ -d "$RUTA_REPO" ]; then
        echo "El repositorio ya existe: $RUTA_REPO"
        return
    fi
    git init --bare --shared=group "$RUTA_REPO"
    git -C "$RUTA_REPO" symbolic-ref HEAD refs/heads/main
    echo "Repositorio creado: $RUTA_REPO"
}

verificar_cuota() {
    if command -v quota > /dev/null; then
        echo "Cuota de disco de $USER (el repo pesa unos 400 MB):"
        quota -s 2>/dev/null || true
    fi
}

crear_carpeta
crear_repo
verificar_cuota
echo
echo "Desde la notebook, publicar el repo con:  herramientas\\publicar_repo_en_torito.ps1"
echo "URL para clonar desde otra notebook:      ctm223:$RUTA_REPO"
