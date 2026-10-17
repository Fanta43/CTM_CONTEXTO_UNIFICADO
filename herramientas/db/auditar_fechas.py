#!/usr/bin/env python3
"""
Audita las fechas del inventario unificado en la replica local para cuantificar las causas
de discrepancia detectadas en el codigo de migracion y de carga. Solo lectura.

Uso (raiz del repo, Docker levantado):
    python herramientas\\db\\auditar_fechas.py            # toda la base
    python herramientas\\db\\auditar_fechas.py P2423NOT   # ademas, detalle de ese equipo
Informe en herramientas/db/analisis/auditoria_fechas.txt (carpeta ignorada por git).
"""
import os
import subprocess
import sys

USUARIO_LOCAL = "root"
CLAVE_LOCAL = "ctm_dev"
RAIZ_REPO = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Cada control: (titulo, base, sql, explicacion de que significa un resultado alto)
CONTROLES = [
    ("Fechas centinela 1900-01-01 en inve_pc (migracion desde SQL Server: CONVERT de '' da 1900)", "contexto_unificado",
     "SELECT SUM(fechacompra LIKE '1900%') compra_1900, SUM(fechavtogarantia LIKE '1900%') garantia_1900, "
     "SUM(fechabaja LIKE '1900%') baja_1900, SUM(fechabaja LIKE '0000%') baja_0000, COUNT(*) total FROM inve_pc",
     "Las pantallas tratan '0000-00-00' como vacio pero no '1900-01-01': se muestra como fecha real."),
    ("Fechas centinela en inve_prestamos", "contexto_unificado",
     "SELECT SUM(fechaprestamo LIKE '1900%') prestamo_1900, SUM(fechadevolucion LIKE '1900%') devolucion_1900, "
     "SUM(fechadevolucion LIKE '0000%') devolucion_0000, COUNT(*) total FROM inve_prestamos",
     "Prestamo no devuelto migrado con fecha de devolucion '' -> 1900-01-01."),
    ("Prestamos con devolucion_estimada inventada = fechaprestamo + 90 dias (script 151.php)", "contexto_unificado",
     "SELECT SUM(DATEDIFF(devolucion_estimada, fechaprestamo) = 90) inventadas, COUNT(*) total FROM inve_prestamos",
     "El script de migracion nunca leia la estimada del origen: TODAS las migradas son fechaprestamo+90."),
    ("inve_pc: dia en que se cargo todo (auditoria_fecha). Un solo dia con casi todo = migracion masiva", "contexto_unificado",
     "SELECT DATE(auditoria_fecha) dia, COUNT(*) cantidad FROM inve_pc GROUP BY DATE(auditoria_fecha) ORDER BY cantidad DESC LIMIT 5",
     "Si la migracion se re-ejecuto (TRUNCATE + INSERT), las correcciones manuales previas se perdieron."),
    ("Articulos morebius con fecha vacia o centinela (ver_articulo.php muestra HOY cuando esta vacia)", "morebius",
     "SELECT SUM(fecha IS NULL OR fecha = '' OR fecha LIKE '0000%') vacias, SUM(fecha LIKE '1900%') f_1900, "
     "SUM(fch_baja LIKE '1900%') baja_1900, COUNT(*) total FROM articulos_con",
     "Cada articulo con fecha vacia muestra una fecha distinta cada dia."),
    ("Articulos morebius cuya fecha es igual a la fecha de compra del inventario (precarga de morebius.php linea 77)", "morebius",
     "SELECT COUNT(*) iguales FROM articulos_con a JOIN contexto_unificado.inve_pc p "
     "ON a.referencia LIKE CONCAT('%', p.codigopc, '%') WHERE DATE(a.fecha) = DATE(p.fechacompra)",
     "La 'fecha' del articulo quedo como fecha de compra, no de entrega."),
    ("Articulos con fecha posterior a su primera asignacion (fecha sobreescrita en cada reasignacion, cargador_2.php)", "morebius",
     "SELECT COUNT(*) sobreescritas FROM articulos_con a JOIN (SELECT nro_art, MIN(fecha_des) primera FROM articulos_his GROUP BY nro_art) h "
     "ON h.nro_art = a.nro_art WHERE DATE(a.fecha) > DATE(h.primera)",
     "La fecha del articulo ya no es la entrega original: se piso al reasignar."),
    ("Articulos asignados (legajo) SIN historial en articulos_his (historial borrado al revertir una baja, editar.php)", "morebius",
     "SELECT COUNT(*) sin_historial FROM articulos_con a LEFT JOIN articulos_his h ON h.nro_art = a.nro_art "
     "WHERE a.legajo_art IS NOT NULL AND a.legajo_art <> '' AND h.nro_art IS NULL",
     "Sin articulos_his no existe fecha de entrega real (fecha_des); las pantallas caen a 'fecha' o a hoy."),
    ("Articulos dados de baja sin fecha de baja (UPDATE con CONVERT T-SQL falla en silencio en MariaDB)", "morebius",
     "SELECT COUNT(*) baja_sin_fecha FROM articulos_con WHERE de_baja = 1 AND (fch_baja IS NULL OR fch_baja = '' OR fch_baja LIKE '0000%' OR fch_baja LIKE '1900%')",
     "rrhh/personal/editar.php usa CONVERT(DATETIME,...,120): sintaxis de SQL Server, en MariaDB no hace nada."),
    ("Historial con 'desde' posterior a 'hasta' (fechas cruzadas o dia/mes invertidos)", "morebius",
     "SELECT COUNT(*) cruzadas FROM articulos_his WHERE fecha_has IS NOT NULL AND fecha_has NOT LIKE '0000%' AND fecha_has NOT LIKE '1900%' AND fecha_des > fecha_has",
     "Un 'desde' mayor que 'hasta' es imposible: dato mal migrado."),
]


def ejecutar_sql(base, sql):
    comando = ["docker", "compose", "exec", "-T", "db", "mariadb", "-u" + USUARIO_LOCAL, "-p" + CLAVE_LOCAL,
               "--batch", "--column-names", "--raw", base, "-e", sql]
    p = subprocess.run(comando, cwd=RAIZ_REPO, capture_output=True, text=True, encoding="utf-8", errors="replace")
    if p.returncode != 0:
        return None, (p.stderr.strip().splitlines() or ["error"])[-1]
    lineas = [l for l in p.stdout.splitlines() if l]
    if len(lineas) < 2:
        return [], None
    cab = lineas[0].split("\t")
    return [dict(zip(cab, l.split("\t"))) for l in lineas[1:]], None


def formatear_filas(filas):
    if not filas:
        return ["  (sin filas)"]
    salida = []
    for f in filas:
        salida.append("  " + "  |  ".join(f"{k}: {v}" for k, v in f.items()))
    return salida


def correr_controles():
    salida = ["AUDITORIA DE FECHAS DEL INVENTARIO UNIFICADO (replica local)", "=" * 70]
    for i, (titulo, base, sql, explicacion) in enumerate(CONTROLES, 1):
        salida.append("")
        salida.append(f"[{i}] {titulo}")
        filas, error = ejecutar_sql(base, sql)
        if error:
            salida.append(f"  no se pudo ejecutar: {error}")
            salida.append("  (la columna o tabla puede llamarse distinto en esta base; ajustar el SQL del control)")
            continue
        salida += formatear_filas(filas)
        salida.append(f"  -> {explicacion}")
    return salida


def detalle_equipo(codigo):
    salida = ["", "=" * 70, f"DETALLE DE {codigo}"]
    consultas = [
        ("contexto_unificado", f"SELECT pcid, codigopc, legajo, fechacompra, fechavtogarantia, fechabaja, baja, auditoria_fecha FROM inve_pc WHERE UPPER(codigopc) LIKE '{codigo}%'"),
        ("contexto_unificado", f"SELECT prestamoid, legajo, fechaprestamo, fechadevolucion, devolucion_estimada, devuelto FROM inve_prestamos WHERE id_pc IN (SELECT pcid FROM inve_pc WHERE UPPER(codigopc) LIKE '{codigo}%')"),
        ("morebius", f"SELECT nro_art, legajo_art, fecha, ingreso, fch_baja, de_baja, devuelto, referencia FROM articulos_con WHERE UPPER(referencia) LIKE '%{codigo}%' OR UPPER(nserie) LIKE '%{codigo}%'"),
        ("morebius", f"SELECT h.nro_art, h.legajo_art, h.fecha_des, h.fecha_has, h.ingreso FROM articulos_his h WHERE h.nro_art IN (SELECT nro_art FROM articulos_con WHERE UPPER(referencia) LIKE '%{codigo}%' OR UPPER(nserie) LIKE '%{codigo}%') ORDER BY h.fecha_des"),
    ]
    for base, sql in consultas:
        salida.append("")
        salida.append(f"  {base}: {sql.split(' FROM ')[1].split(' WHERE ')[0]}")
        filas, error = ejecutar_sql(base, sql)
        salida += [f"  no se pudo ejecutar: {error}"] if error else formatear_filas(filas)
    return salida


def verificar_docker():
    p = subprocess.run(["docker", "compose", "ps", "-q", "db"], cwd=RAIZ_REPO, capture_output=True, text=True)
    if p.returncode != 0 or not p.stdout.strip():
        print("El contenedor db no esta corriendo. Desde la raiz del repo: docker compose up -d")
        sys.exit(1)


def main():
    verificar_docker()
    salida = correr_controles()
    if len(sys.argv) > 1:
        salida += detalle_equipo(sys.argv[1].strip().upper())
    texto = "\n".join(salida)
    print(texto)
    carpeta = os.path.join(RAIZ_REPO, "herramientas", "db", "analisis")
    os.makedirs(carpeta, exist_ok=True)
    ruta = os.path.join(carpeta, "auditoria_fechas.txt")
    with open(ruta, "w", encoding="utf-8") as f:
        f.write(texto)
    print(f"\nInforme guardado en {ruta}")


if __name__ == "__main__":
    main()
