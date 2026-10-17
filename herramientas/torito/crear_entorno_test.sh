#!/usr/bin/env bash
# Crea el entorno de TEST en torito, al lado de produccion:
#   codigo:  /var/www/ctm/contexto_unificado_test  (clon del repo)
#   base:    contexto_unificado_test               (copia de contexto_unificado)
#   url:     https://172.16.1.223/ctm/contexto_unificado_test/
# Uso (desde la notebook):
#   scp herramientas\torito\crear_entorno_test.sh ctm223:/tmp/
#   ssh -t ctm223 "bash /tmp/crear_entorno_test.sh"
# Pide usuario y contrasena de MariaDB una sola vez. Es seguro repetirlo.
set -e

RUTA_TEST="${RUTA_TEST:-/var/www/ctm/contexto_unificado_test}"
BD_PROD="${BD_PROD:-contexto_unificado}"
BD_TEST="${BD_TEST:-contexto_unificado_test}"
URL_TEST="${URL_TEST:-https://172.16.1.223/ctm/contexto_unificado_test}"
URL_REPO="${URL_REPO:-http://127.0.0.1:3000/farinong/contexto_unificado.git}"
RAMA="${RAMA:-main}"

verificar_permisos() {
    if [ ! -w "$(dirname "$RUTA_TEST")" ]; then
        echo "No tengo permiso de escritura en $(dirname "$RUTA_TEST"). Corre el script con sudo."
        exit 1
    fi
}

pedir_credenciales() {
    read -r -p "Usuario de MariaDB (con permiso para crear $BD_TEST): " BD_USUARIO < /dev/tty
    read -r -s -p "Contrasena de MariaDB para $BD_USUARIO: " BD_CLAVE < /dev/tty
    echo
    export BD_USUARIO BD_CLAVE
}

clonar_codigo() {
    if [ -d "$RUTA_TEST/.git" ]; then
        echo "El codigo de test ya existe en $RUTA_TEST, actualizando..."
        git -C "$RUTA_TEST" fetch origin "$RAMA"
        git -C "$RUTA_TEST" checkout "$RAMA"
        git -C "$RUTA_TEST" pull --ff-only origin "$RAMA"
        return
    fi
    echo "Clonando el repo en $RUTA_TEST..."
    git clone --branch "$RAMA" "$URL_REPO" "$RUTA_TEST"
}

copiar_base() {
    echo "Creando la base $BD_TEST como copia de $BD_PROD..."
    export MYSQL_PWD="$BD_CLAVE"
    mysql -u "$BD_USUARIO" -e "CREATE DATABASE IF NOT EXISTS \`$BD_TEST\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    # El sed corrige vistas y procedimientos que mysqldump deja apuntando a la base original
    mysqldump -u "$BD_USUARIO" --single-transaction --quick --routines --triggers --events "$BD_PROD" \
        | sed "s/\`$BD_PROD\`\./\`$BD_TEST\`./g" \
        | mysql -u "$BD_USUARIO" "$BD_TEST"
    unset MYSQL_PWD
    echo "Base $BD_TEST lista."
}

generar_configs() {
    bash "$RUTA_TEST/herramientas/torito/generar_config_local.sh" "$RUTA_TEST" "$BD_TEST" "$URL_TEST" on
}

ajustar_permisos() {
    # el servidor web necesita escribir donde la app sube archivos
    for d in "$RUTA_TEST/ctm_presupuestos/web/arch"; do
        [ -d "$d" ] && chmod -R g+w "$d" && echo "Escritura habilitada en $d"
    done
    return 0
}

verificar_permisos
pedir_credenciales
clonar_codigo
copiar_base
generar_configs
ajustar_permisos
echo
echo "Entorno de test listo: $URL_TEST/ctm_entorno/default.php"
echo "Para actualizarlo desde la notebook: herramientas\\desplegar.ps1 -Destino test -Rama <rama>"
