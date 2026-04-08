print("Hola Mundo Presupuestos")

import time
from datetime import datetime
from urllib.parse import urlparse, parse_qs
from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from funcion import *

def iniciar_driver():
    """Inicia el WebDriver de Chrome."""
    service = Service(r"D:\\xampp\\htdocs\\ctm\\contexto_unificado\\ctm_presupuestos\\web\\cron\\chromedriver\\chromedriver.exe")  # Ajusta tu ruta
    return webdriver.Chrome(service=service)

def iniciar_sesion(driver, plan, cgp):
    """Inicia sesión en el sistema (o ejecuta la búsqueda/consulta) con los parámetros dados."""
    print("[DEBUG] Navegando a página de consulta...")
    driver.get('http://erp-prod:8080/k2b/servlet/hejecutarconsultastandalone?QUERY253,')
    # Esperar y localizar campos
    print("[DEBUG] Esperando campo PLAN...")
    plan_field = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.NAME, 'W0018_PARAMETROVALORCARACTER_0001'))
    )
    print("[DEBUG] Esperando campo CGP...")
    cgp_field = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.NAME, 'W0018_PARAMETROVALORCARACTER_0002'))
    )
    print("[DEBUG] Esperando botón EJECUTAR...")
    boton = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.NAME, 'W0018EJECUTARCONSULTA1'))
    )
    # Rellenar y enviar
    print(f"[DEBUG] Enviando parámetros: plan={plan}, cgp={cgp}")
    plan_field.send_keys(plan)
    cgp_field.send_keys(cgp)
    boton.click()
    # Esperar a que se cargue la tabla
    print("[DEBUG] Esperando a que se cargue la tabla W0018TABLE3...")
    WebDriverWait(driver, 60).until(
        EC.presence_of_element_located((By.ID, "W0018TABLE3"))
    )
    html = driver.page_source
    print(f"[DEBUG] HTML de la página cargada. Longitud: {len(html)} caracteres")
    return html

def get_html_iframe(driver, url):
    """Carga la URL del iframe y devuelve el HTML completo de esa página."""
    print("[DEBUG] Navegando a URL de iframe...")
    driver.get(url)

    # Esperar un poco a que cargue el contenido del iframe
    # (si conoces un ID/clase concreta de la tabla dentro del iframe, se puede afinar aquí)
    time.sleep(5)

    html = driver.page_source
    print(f"[DEBUG] HTML del iframe cargado. Longitud: {len(html)} caracteres")
    return html

def extraer_tablas(html):
    """
    Recibe un string con código HTML, filtra para quedarse con la primera tabla
    y devuelve la(s) tabla(s) como lista de strings HTML.
    """
    print("[DEBUG] Iniciando extraer_tablas...")
    # Filtra y obtiene la primera tabla limpia
    tabla_limpia = filtrar_tabla_html(html)
    if not tabla_limpia:
        return []
    soup = BeautifulSoup(str(tabla_limpia), "html.parser")
    tablas = soup.find_all("table")
    return [str(tabla) for tabla in tablas]

def obtener_iframe_url(html):
    """Devuelve la URL (src) del primer iframe encontrado en el HTML, o None si no hay iframes."""
    print("[DEBUG] Buscando iframe en el HTML...")
    soup = BeautifulSoup(html, 'html.parser')
    iframe = soup.find('iframe')
    if iframe and iframe.has_attr('src'):
        src = iframe['src']
        print(f"[DEBUG] iframe encontrado. SRC = {src}")
        return src
    print("[DEBUG] No se encontró ningún iframe con atributo src en el HTML.")
    return None

def filtrar_tabla_html(html):
    """
    Elimina etiquetas innecesarias y atributos (excepto href en <a>),
    y retorna la primera tabla encontrada.
    """
    print("[DEBUG] Iniciando filtrar_tabla_html...")
    soup = BeautifulSoup(html, 'html.parser')
    for tag in soup(["script", "style", "img", "link", "meta"]):
        tag.decompose()
    for t in soup.find_all():
        if t.name == 'a' and 'href' in t.attrs:
            t.attrs = {'href': t.attrs['href']}
        else:
            t.attrs = {}
    return soup.find('table')

def obtener_contenido_tabla(html):
    try:
        print("[DEBUG] Buscando tabla objetivo en el HTML...")
        soup = BeautifulSoup(html, 'html.parser')

        # Mostrar algunas tablas encontradas para debug
        tablas = soup.find_all('table')
        print(f"[DEBUG] Cantidad de <table> encontradas en la página: {len(tablas)}")
        primeros_ids = [t.get('id') for t in tablas[:10]]
        print(f"[DEBUG] IDs de las primeras tablas: {primeros_ids}")

        # Ajusta este ID si la tabla real tiene otro identificador
        # En tu caso, la tabla que se espera en el iframe es ctl00_ContentPlaceHolder1_ASPxPivotGrid1_ME
        div_registros = soup.find('table', {'id': 'ctl00_ContentPlaceHolder1_ASPxPivotGrid1_ME'})
        if not div_registros:
            print("[DEBUG] No se encontró la tabla con id 'ctl00_ContentPlaceHolder1_ASPxPivotGrid1_ME'.")
            return "No se encontró la tabla objetivo en el HTML."

        tabla = div_registros.find('tbody') or div_registros
        if not tabla:
            print("[DEBUG] No se encontró <tbody> dentro de la tabla objetivo.")
            return "No se encontró el cuerpo (tbody) de la tabla objetivo."

        html_tabla = str(tabla.prettify())
        print(f"[DEBUG] HTML de la tabla objetivo obtenido. Longitud: {len(html_tabla)} caracteres")
        return html_tabla

    except Exception as e:
        return f"Ocurrió un error al obtener la tabla: {e}"

import json
from bs4 import BeautifulSoup

def extraer_filas_tabla(html):
    """
    Recibe el HTML de la tabla o de un <tbody> y devuelve
    una lista de diccionarios con los valores de cada fila.
    """
    print("[DEBUG] Iniciando extraer_filas_tabla...")
    soup = BeautifulSoup(html, "html.parser")

    # Si viene una tabla completa, usamos esa; si no, buscamos directamente los <tr>
    table = soup.find("table")
    if table:
        rows = table.find_all("tr")
        print(f"[DEBUG] Se encontró <table>. Cantidad de filas (tr) dentro de la tabla: {len(rows)}")
    else:
        rows = soup.find_all("tr")
        print(f"[DEBUG] No se encontró <table>. Cantidad de filas (tr) en el HTML: {len(rows)}")

    filas_resultado = []
    filas_descartadas = 0
    
    for row in rows:
        tds = row.find_all("td", recursive=False)
        if len(tds) == 9:

            col0 = tds[0].get_text(strip=True)
            col1 = tds[1].get_text(strip=True)
            col2 = tds[2].get_text(strip=True)
            col3 = tds[3].get_text(strip=True)
            col4 = tds[4].get_text(strip=True)
            col5 = tds[5].get_text(strip=True)
            col6 = tds[6].get_text(strip=True)
            col7 = tds[7].get_text(strip=True)
            col8 = tds[8].get_text(strip=True)
            
            fila_dict = {
                "item_nombre": col0,
                "tipo_activo": col1,
                "tipo_recurso": col2,
                "importe_planificado": col3, 
                "importe_precomprometido": col4,
                "importe_definitivo": col5,
                "importe_causado": col6,
                "pagado": col7,
                "importe_disponible": col8
            }
            
            filas_resultado.append(fila_dict)
        else:
            filas_descartadas += 1

    print(f"[DEBUG] Filas válidas encontradas: {len(filas_resultado)}")
    print(f"[DEBUG] Filas descartadas por no tener 9 columnas: {filas_descartadas}")

    return filas_resultado

import json

def insertar_datos_pres_k2b_presupuestos(list_presupuesto, db='0', usu='9999', anio=None, cc=None):
    """
    Inserta en la tabla 'pres_k2b_presupuestos' los registros
    que recibe en 'list_presupuesto', el cual debe ser una lista de diccionarios
    con las claves:
      campo0, campo1, campo2, importe_planificado, importe_precomprometido,
      importe_definitivo, importe_causado, pagado, importe_disponible

    Cada valor se insertará en la columna correspondiente.
    Parámetros adicionales: anio (año fiscal) y cc (centro de costos).
    """

    try:
        # Verificar que list_presupuesto sea una lista de diccionarios
        if not isinstance(list_presupuesto, list):
            raise ValueError("El parámetro 'list_presupuesto' debe ser una lista.")
        if not all(isinstance(item, dict) for item in list_presupuesto):
            raise ValueError("Cada elemento de 'list_presupuesto' debe ser un diccionario.")

        # Función auxiliar para convertir el string numérico (que puede traer comas) a float
        def procesar_monto(valor):
            """
            Convierte un string como '187,500.00' a float 187500.00
            Si hay error, retorna 0.0
            """
            try:
                valor_sin_comas = str(valor).replace(",", "")
                return float(valor_sin_comas)
            except:
                return 0.0

        print(f"[DEBUG] Cantidad de registros a insertar: {len(list_presupuesto)}")

        # Insertar cada registro en pres_k2b_presupuestos
        for idx, reg in enumerate(list_presupuesto, start=1):

            # Campos de texto (max 255 caracteres)
            # Se mapean desde las claves generadas en extraer_filas_tabla
            campo0 = str(reg.get("item_nombre", ""))[:255].replace("'", "''")
            campo1 = str(reg.get("tipo_activo", ""))[:255].replace("'", "''")
            campo2 = str(reg.get("tipo_recurso", ""))[:255].replace("'", "''")

            # Campos numéricos
            importe_planificado = procesar_monto(reg.get("importe_planificado", "0.00"))
            importe_precomprometido = procesar_monto(reg.get("importe_precomprometido", "0.00"))
            importe_definitivo = procesar_monto(reg.get("importe_definitivo", "0.00"))
            importe_causado = procesar_monto(reg.get("importe_causado", "0.00"))
            pagado = procesar_monto(reg.get("pagado", "0.00"))
            importe_disponible = procesar_monto(reg.get("importe_disponible", "0.00"))

            # Construir valores de anio y cc para SQL
            anio_sql = f"{anio}" if anio else "NULL"
            cc_sql = f"{cc}" if cc else "NULL"

            # Construir la sentencia de INSERT
            sql_insert = f"""
                INSERT INTO pres_k2b_presupuestos (
                    campo0,
                    campo1,
                    campo2,
                    importe_planificado,
                    importe_precomprometido,
                    importe_definitivo,
                    importe_causado,
                    pagado,
                    importe_disponible,
                    anio,
                    cc
                ) VALUES (
                    '{campo0}',
                    '{campo1}',
                    '{campo2}',
                    {importe_planificado},
                    {importe_precomprometido},
                    {importe_definitivo},
                    {importe_causado},
                    {pagado},
                    {importe_disponible},
                    {anio_sql},
                    {cc_sql}
                );
            """

            #print(sql_insert)  # Para debug, puedes quitarlo en producción

            # Ejecutar la consulta (ajusta a tu forma de ejecución SQL)
            print(f"[DEBUG] Insertando registro {idx}: campo0={campo0}, importe_planificado={importe_planificado}")
            result_in = sql_do_query(sql_insert, db, usu)

            if result_in is not None:
                print(f"Error al insertar el registro con campo0={campo0}: {result_in}")

        print(f"Se insertaron {len(list_presupuesto)} registros correctamente en pres_k2b_presupuestos.")

    except Exception as e:
        print("Error al insertar datos:", e)
        # Si tu motor de SQL lo permite y usas transacciones, podrías hacer un rollback

driver = iniciar_driver()

try:
    # 1) Obtener años desde la base de datos
    sql_anios = """
        SELECT anio
        FROM pres_presupuesto
        WHERE auditoria_usu = '10433'
          AND estadoid = '3';
    """
    print("[DEBUG] Consultando años para procesar desde la base de datos...")
    res_anios = sql_do_query(sql_anios, '1', '10433') or []
    print(f"[DEBUG] Años encontrados: {res_anios}")

    # 2) Procesar cada año
    for fila_anio in res_anios:
        # Dependiendo del formato que devuelva sql_do_query:
        anio = None
        if isinstance(fila_anio, dict):
            anio = fila_anio.get('ANIO') or fila_anio.get('anio')
        else:
            try:
                # si es una tupla/lista, tomamos la primera posición
                anio = fila_anio[0]
            except Exception:
                anio = None

        if not anio:
            print(f"[DEBUG] Fila de años sin campo ANIO válido: {fila_anio}")
            continue

        print(f"[DEBUG] === INICIO PROCESO K2B PRESUPUESTOS PARA AÑO {anio} ===")
        html = iniciar_sesion(driver, f"'%{anio}GASTOSCHSG%'", "'%880%'")

        filas_tabla = []  # aseguramos definición por año

        # 3) Obtener y procesar el iframe
        iframe_url = obtener_iframe_url(html)
        if iframe_url:
            print(f"URL del iframe encontrada: {iframe_url}")
            html_iframe = get_html_iframe(driver, iframe_url)
            html_tabla = obtener_contenido_tabla(html_iframe)
            if isinstance(html_tabla, str) and html_tabla.startswith("No se encontró"):
                print("[DEBUG] No se pudo obtener la tabla objetivo desde el iframe. Mensaje:")
                print(html_tabla)
            else:
                filas_tabla = extraer_filas_tabla(html_tabla)

        print("Filas extraidas: \n")
        print(filas_tabla)

        # 4) Insertar en la tabla de destino (solo si hay filas)
        if filas_tabla:
            insertar_datos_pres_k2b_presupuestos(filas_tabla, '1', '9999', anio=anio, cc=880)

            # 5) Sincronizar con ciclo_presupuesto (crear presupuesto aprobado)
            print(f"[DEBUG] Sincronizando K2B → ciclo_presupuesto para año {anio}, cc=880...")
            sql_sync = f"CALL sp_sync_k2b_presupuesto({anio}, 880, 9999);"
            res_sync = sql_do_query(sql_sync, '1', '9999')
            print(f"[DEBUG] Resultado sync: {res_sync}")
        else:
            print(f"[DEBUG] No hay filas para insertar en la base de datos para el año {anio}.")
finally:
    print("FINALIZA!!!")
    if driver is not None:
        driver.quit()