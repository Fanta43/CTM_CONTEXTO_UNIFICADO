#!/usr/bin/env bash
# Configura este equipo (Linux/Mac) para conectarse por SSH al servidor 172.16.1.223
# Uso:  bash configurar_ssh.sh [usuario] [ip] [puerto] [alias]
set -e

USUARIO="${1:-$USER}"
IP="${2:-172.16.1.223}"
PUERTO="${3:-22}"
ALIAS="${4:-ctm223}"

CARPETA_SSH="$HOME/.ssh"
ARCHIVO_CLAVE="$CARPETA_SSH/id_ed25519_ctm"
ARCHIVO_CONFIG="$CARPETA_SSH/config"

verificar_ssh() {
    if ! command -v ssh >/dev/null; then
        echo "No se encontro el cliente ssh. Instalalo con:  sudo apt install openssh-client"
        exit 1
    fi
}

crear_carpeta_ssh() {
    mkdir -p "$CARPETA_SSH"
    chmod 700 "$CARPETA_SSH"
}

generar_clave() {
    if [ -f "$ARCHIVO_CLAVE" ]; then
        echo "La clave ya existe: $ARCHIVO_CLAVE"
        return
    fi
    ssh-keygen -t ed25519 -f "$ARCHIVO_CLAVE" -N "" -C "$USER@$(hostname)"
    echo "Clave generada: $ARCHIVO_CLAVE"
}

agregar_config() {
    if [ -f "$ARCHIVO_CONFIG" ] && grep -q "^Host $ALIAS\$" "$ARCHIVO_CONFIG"; then
        echo "El alias '$ALIAS' ya esta en $ARCHIVO_CONFIG"
        return
    fi
    cat >> "$ARCHIVO_CONFIG" <<FIN

Host $ALIAS
    HostName $IP
    User $USUARIO
    Port $PUERTO
    IdentityFile $ARCHIVO_CLAVE
    ServerAliveInterval 60
FIN
    chmod 600 "$ARCHIVO_CONFIG"
    echo "Alias '$ALIAS' agregado a $ARCHIVO_CONFIG"
}

copiar_clave_publica() {
    echo "Copiando la clave publica al servidor (te va a pedir la contrasena de $USUARIO@$IP)..."
    ssh-copy-id -i "$ARCHIVO_CLAVE.pub" -p "$PUERTO" "$USUARIO@$IP"
}

probar_conexion() {
    echo "Probando conexion sin contrasena..."
    if ssh -o BatchMode=yes "$ALIAS" 'echo CONEXION_OK desde $(hostname)'; then
        echo "Listo. Conectate con:  ssh $ALIAS"
    else
        echo "No se pudo conectar sin contrasena. Revisa usuario, red y permisos en el servidor."
    fi
}

verificar_ssh
crear_carpeta_ssh
generar_clave
agregar_config
copiar_clave_publica
probar_conexion
