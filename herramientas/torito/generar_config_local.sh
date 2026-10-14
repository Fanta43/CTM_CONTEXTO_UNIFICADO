#!/usr/bin/env bash
# Genera config/config.local.php en los 4 modulos de una instalacion CTM en torito.
# Uso:  bash generar_config_local.sh RUTA_APP BD_RW URL_BASE [on|off]
# Ej:   bash generar_config_local.sh /var/www/ctm/contexto_unificado_test contexto_unificado_test https://172.16.1.223/ctm/contexto_unificado_test on
# Pide usuario y contrasena de MariaDB (o toma BD_USUARIO / BD_CLAVE del entorno). No pisa archivos existentes.
set -e

RUTA_APP="$1"
BD_RW="$2"
URL_BASE="${3%/}"
MOSTRAR_ERRORES="${4:-off}"

verificar_parametros() {
    if [ -z "$RUTA_APP" ] || [ -z "$BD_RW" ] || [ -z "$URL_BASE" ]; then
        echo "Uso: bash $0 RUTA_APP BD_RW URL_BASE [on|off]"
        exit 1
    fi
    if [ ! -d "$RUTA_APP/ctm_entorno" ]; then
        echo "No parece una instalacion CTM: falta $RUTA_APP/ctm_entorno"
        exit 1
    fi
}

pedir_credenciales() {
    if [ -z "$BD_USUARIO" ]; then
        read -r -p "Usuario de MariaDB: " BD_USUARIO < /dev/tty
    fi
    if [ -z "$BD_CLAVE" ]; then
        read -r -s -p "Contrasena de MariaDB para $BD_USUARIO: " BD_CLAVE < /dev/tty
        echo
    fi
}

bloque_bd() { # indice base
    cat <<FIN
        '$1' => ['host' => 'localhost', 'user' => '$BD_USUARIO', 'password' => '$BD_CLAVE', 'database' => '$2', 'port' => 3306, 'charset' => 'utf8mb4', 'cert' => ''],
FIN
}

escribir_archivo() { # modulo contenido
    local destino="$RUTA_APP/$1/config/config.local.php"
    if [ -f "$destino" ]; then
        echo "Ya existe, no se toca: $destino"
        return
    fi
    printf '%s\n' "$2" > "$destino"
    chmod 640 "$destino"
    echo "Creado: $destino"
}

generar_entorno() {
    escribir_archivo ctm_entorno "<?php
// Generado por herramientas/torito/generar_config_local.sh
define('APP_URL', '$URL_BASE/ctm_entorno/');
define('MOSTRAR_ERRORES', '$([ "$MOSTRAR_ERRORES" = on ] && echo 1 || echo 0)');
define('DB_CONFIGS', serialize([
    'default' => ['host' => 'localhost', 'user' => '$BD_USUARIO', 'password' => '$BD_CLAVE', 'database' => '$BD_RW', 'port' => 3306, 'charset' => 'utf8mb4', 'cert' => null],
    'db1'     => ['host' => 'localhost', 'user' => '$BD_USUARIO', 'password' => '$BD_CLAVE', 'database' => '$BD_RW', 'port' => 3306, 'charset' => 'utf8mb4', 'cert' => null],
    'db2'     => ['host' => 'localhost', 'user' => '$BD_USUARIO', 'password' => '$BD_CLAVE', 'database' => '$BD_RW', 'port' => 3306, 'charset' => 'utf8mb4', 'cert' => null],
]));"
}

generar_modulo() { # modulo "indice:base indice:base ..."
    local modulo="$1"; shift
    local bloques=""
    for par in "$@"; do
        bloques+="$(bloque_bd "${par%%:*}" "${par##*:}")"$'\n'
    done
    escribir_archivo "$modulo" "<?php
// Generado por herramientas/torito/generar_config_local.sh
\$servidor = '$URL_BASE/$modulo/';
define('MOSTRAR_ERRORES', '$MOSTRAR_ERRORES');
function getDbConfig(\$db = '0') {
    \$configs = [
$bloques    ];
    return isset(\$configs[\$db]) ? \$configs[\$db] : \$configs['0'];
}"
}

verificar_parametros
pedir_credenciales
generar_entorno
generar_modulo ctm_inventarios  "0:$BD_RW" "1:$BD_RW" "2:ctm_ocsweb" "3:morebius" "4:inventario"
generar_modulo ctm_morebius     "0:$BD_RW" "1:$BD_RW" "2:morebius"
generar_modulo ctm_presupuestos "0:$BD_RW" "1:$BD_RW" "2:$BD_RW"
echo "Listo. Las bases morebius y ctm_ocsweb quedan apuntando a las reales (solo lectura)."
