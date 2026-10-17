#!/usr/bin/env python3
"""
Analiza un equipo (ej. P2423NOT) en todas las bases del inventario unificado y arma una
linea de tiempo con todas las fechas que lo mencionan, para detectar discrepancias.

Uso (desde la raiz del repo, con Docker levantado):
    python herramientas\\db\\analizar_equipo.py P2423NOT
Consulta la replica local (contenedor db). Solo lectura. Guarda el informe en
herramientas/db/analisis/<CODIGO>.txt (carpeta ignorada por git).

Funciones simples, sin clases. Solo biblioteca estandar: ejecuta las consultas con el cliente
mariadb del contenedor, asi no hace falta instalar nada en Python.
"""
import os
import re
import subprocess
import sys

USUARIO_LOCAL = "root"
CLAVE_LOCAL = "ctm_dev"
RAIZ_REPO = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Que buscar en cada base: tabla -> columnas donde puede estar el codigo del equipo.
# Si ninguna existe, se busca en todas las columnas de texto.
BUSQUEDAS = {
    "contexto_unificado": {
        "inve_pc": ["codigopc"],
        "inve_prestamos": ["codigopc", "equipo"],
        "inve_reparaciones": ["codigopc", "equipo"],
        "inve_pistasauditorias": ["detalle", "descripcion", "pista"],
    },
    "morebius": {
        "ctm_inventario": ["codigo", "nombre"],
        "articulos_con": ["referencia", "nserie", "detalle", "observaciones"],
        "articulos_his": ["referencia", "nserie", "detalle"],
    },
    "ctm_ocsweb": {
        "hardware": ["name"],
    },
}

PATRON_FECHA = re.compile(r"^\d{4}-\d{2}-\d{2}")


def ejecutar_sql(base, sql):
    """Ejecuta SQL en el contenedor db y devuelve (filas, error). Cada fila es un dict."""
    comando = [
        "docker", "compose", "exec", "-T", "db",
        "mariadb", "-u" + USUARIO_LOCAL, "-p" + CLAVE_LOCAL,
        "--batch", "--column-names", "--raw", base, "-e", sql,
    ]
    proceso = subprocess.run(comando, cwd=RAIZ_REPO, capture_output=True, text=True, encoding="utf-8", errors="replace")
    if proceso.returncode != 0:
        return [], proceso.stderr.strip() or "error desconocido"
    lineas = [l for l in proceso.stdout.splitlines() if l != ""]
    if len(lineas) < 2:
        return [], None
    cabecera = lineas[0].split("\t")
    filas = []
    for linea in lineas[1:]:
        valores = linea.split("\t")
        filas.append(dict(zip(cabecera, valores)))
    return filas, None


def existe_base(base):
    filas, error = ejecutar_sql("information_schema", f"SELECT schema_name FROM schemata WHERE schema_name = '{base}'")
    return not error and len(filas) > 0


def columnas_de(base, tabla):
    """Devuelve lista de (nombre, tipo) o [] si la tabla no existe."""
    filas, error = ejecutar_sql(base, f"SHOW COLUMNS FROM `{tabla}`")
    if error:
        return []
    return [(f["Field"], f["Type"].lower()) for f in filas]


def armar_where(columnas, preferidas, codigo):
    """WHERE por las columnas preferidas que existan; si no hay, por todas las de texto."""
    nombres = [c for c, _ in columnas]
    usar = [c for c in preferidas if c in nombres]
    if not usar:
        usar = [c for c, t in columnas if any(x in t for x in ("char", "text"))]
    if not usar:
        return None
    partes = [f"UPPER(`{c}`) LIKE '%{codigo}%'" for c in usar]
    return " OR ".join(partes)


def buscar(base, tabla, preferidas, codigo):
    columnas = columnas_de(base, tabla)
    if not columnas:
        return None, f"la tabla {base}.{tabla} no existe"
    where = armar_where(columnas, preferidas, codigo)
    if not where:
        return [], "sin columnas de texto donde buscar"
    filas, error = ejecutar_sql(base, f"SELECT * FROM `{tabla}` WHERE {where} LIMIT 50")
    return filas, error


def buscar_historial_morebius(ids_art):
    if not ids_art:
        return [], None
    lista = ",".join(f"'{i}'" for i in ids_art)
    return ejecutar_sql("morebius", f"SELECT * FROM articulos_his WHERE id_art IN ({lista}) ORDER BY fecha_des")


def es_columna_fecha(nombre, valor):
    n = nombre.lower()
    por_nombre = any(x in n for x in ("fecha", "fec_", "fch_", "ingreso", "alta", "garantia", "lastdate", "lastcome", "auditoria"))
    return por_nombre or PATRON_FECHA.match(valor or "") is not None


def recolectar_fechas(origen, filas):
    """Devuelve lista de (fecha_o_vacio, origen, columna, fila_resumen)."""
    fechas = []
    for i, fila in enumerate(filas, 1):
        for col, val in fila.items():
            if es_columna_fecha(col, val):
                fechas.append((val if val and val != "NULL" else "", f"{origen} #{i}", col))
    return fechas


def imprimir_filas(salida, titulo, filas, error):
    salida.append("")
    salida.append(f"--- {titulo} ---")
    if error:
        salida.append(f"  (no se pudo consultar: {error})")
        return
    if not filas:
        salida.append("  sin registros")
        return
    for i, fila in enumerate(filas, 1):
        salida.append(f"  registro {i}:")
        for col, val in fila.items():
            if val not in ("", "NULL", None):
                salida.append(f"    {col}: {val}")


def analizar_fechas(salida, fechas, filas_pc, filas_art):
    salida.append("")
    salida.append("=== LINEA DE TIEMPO DE FECHAS ===")
    con_valor = sorted([f for f in fechas if f[0]], key=lambda f: f[0])
    for fecha, origen, col in con_valor:
        salida.append(f"  {fecha[:19]:<19}  {origen:<28} {col}")
    vacias = [f for f in fechas if not f[0]]
    if vacias:
        salida.append("  Fechas vacias:")
        for _, origen, col in vacias:
            salida.append(f"    {origen:<28} {col}")

    salida.append("")
    salida.append("=== POSIBLES CAUSAS DE DISCREPANCIA ===")
    hallazgos = 0
    compra = [f.get("fechacompra", "") for f in filas_pc if f.get("fechacompra") not in ("", "NULL", None)]
    for art in filas_art:
        fecha_art = art.get("fecha", "")
        if fecha_art in ("", "NULL", None):
            salida.append("  * El articulo de morebius no tiene FECHA: la pantalla ver_articulo.php muestra la fecha de HOY en su lugar.")
            hallazgos += 1
        elif compra and fecha_art[:10] in [c[:10] for c in compra]:
            salida.append("  * La FECHA del articulo en morebius es igual a FECHACOMPRA del inventario: se precargo al dar de alta")
            salida.append("    desde inventarios (resumen/morebius.php linea 77) y nadie la corrigio. No es la fecha de entrega.")
            hallazgos += 1
        if art.get("ingreso") not in ("", "NULL", None) and fecha_art not in ("", "NULL", None) and art.get("ingreso")[:10] != fecha_art[:10]:
            salida.append("  * FECHA e INGRESO difieren en morebius. En inventarios la etiqueta 'INGRESO' muestra FECHA y la")
            salida.append("    etiqueta 'ACTUALIZACION' muestra INGRESO (resumen/morebius.php lineas 189-190): etiquetas cruzadas.")
            hallazgos += 1
    if len(filas_pc) > 1:
        salida.append(f"  * Hay {len(filas_pc)} registros en inve_pc que empiezan con el codigo: consultaInventario usa LIKE 'codigo%'")
        salida.append("    y toma el primero que devuelve la base. Puede estar mostrando el equipo equivocado.")
        hallazgos += 1
    if len(filas_art) > 1:
        salida.append(f"  * Hay {len(filas_art)} articulos en morebius para el mismo equipo: cada pantalla puede estar leyendo uno distinto.")
        hallazgos += 1
    if hallazgos == 0:
        salida.append("  No se detecto ninguna de las causas conocidas en los datos. Revisar la linea de tiempo a mano.")
    salida.append("")
    salida.append("  Recordar: la fecha de ENTREGA real a una persona es articulos_his.fecha_des (DESDE),")
    salida.append("  no inve_pc.fechacompra ni articulos_con.fecha.")


def verificar_docker():
    proceso = subprocess.run(["docker", "compose", "ps", "-q", "db"], cwd=RAIZ_REPO, capture_output=True, text=True)
    if proceso.returncode != 0 or not proceso.stdout.strip():
        print("El contenedor db no esta corriendo. Desde la raiz del repo: docker compose up -d")
        sys.exit(1)


def analizar(codigo):
    salida = [f"INFORME DEL EQUIPO {codigo}", "=" * 60]
    todas_fechas = []
    filas_pc, filas_art = [], []
    for base, tablas in BUSQUEDAS.items():
        salida.append("")
        salida.append(f"##### BASE {base} #####")
        if not existe_base(base):
            salida.append(f"  la base {base} no esta en la replica local (correr sincronizar_bd.ps1)")
            continue
        for tabla, preferidas in tablas.items():
            filas, error = buscar(base, tabla, preferidas, codigo)
            imprimir_filas(salida, f"{base}.{tabla}", filas or [], error)
            if filas:
                todas_fechas += recolectar_fechas(f"{tabla}", filas)
                if base == "contexto_unificado" and tabla == "inve_pc":
                    filas_pc = filas
                if base == "morebius" and tabla == "articulos_con":
                    filas_art = filas
        if base == "morebius" and filas_art:
            ids = [a.get("id_art") for a in filas_art if a.get("id_art")]
            hist, error = buscar_historial_morebius(ids)
            imprimir_filas(salida, "morebius.articulos_his (historial de asignaciones de esos articulos)", hist, error)
            if hist:
                todas_fechas += recolectar_fechas("articulos_his", hist)
    analizar_fechas(salida, todas_fechas, filas_pc, filas_art)
    return "\n".join(salida)


def guardar_informe(codigo, texto):
    carpeta = os.path.join(RAIZ_REPO, "herramientas", "db", "analisis")
    os.makedirs(carpeta, exist_ok=True)
    ruta = os.path.join(carpeta, f"{codigo}.txt")
    with open(ruta, "w", encoding="utf-8") as f:
        f.write(texto)
    return ruta


def main():
    if len(sys.argv) < 2:
        print("Uso: python herramientas/db/analizar_equipo.py CODIGO_EQUIPO   (ej. P2423NOT)")
        sys.exit(1)
    codigo = sys.argv[1].strip().upper()
    if not re.fullmatch(r"[A-Z0-9_-]+", codigo):
        print("Codigo invalido: solo letras, numeros, guion y guion bajo.")
        sys.exit(1)
    verificar_docker()
    texto = analizar(codigo)
    print(texto)
    ruta = guardar_informe(codigo, texto)
    print(f"\nInforme guardado en {ruta}")


if __name__ == "__main__":
    main()
