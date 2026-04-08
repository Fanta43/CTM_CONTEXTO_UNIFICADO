print("Hola Mundo")

"""
pip install beautifulsoup4
pip install mysql-connector-python
pip install selenium

CREATE TABLE screenermatic_bonos_cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,         -- ID auto-incremental para cada bono
    simbolo_texto VARCHAR(50),                 -- Texto del símbolo, por ejemplo "AE38"
    simbolo_url VARCHAR(255),                  -- URL del símbolo, por ejemplo "simulador_bonos.php?id=120"
    simbolo_id INT,                            -- ID extraído de la URL del símbolo, por ejemplo "120"
    descripcion TEXT,                          -- Descripción del bono
    emisor VARCHAR(100),                       -- Nombre del emisor, por ejemplo "Argentina"
    tipo VARCHAR(50),                          -- Tipo de bono, por ejemplo "Soberano"
    pais VARCHAR(50),                          -- País asociado al bono, por ejemplo "Argentina"
    mercado VARCHAR(50),                       -- Mercado en el que se negocia, por ejemplo "BYMA"
    moneda VARCHAR(10),                        -- Moneda en la que está denominado, por ejemplo "ARS"
    simulacion_texto VARCHAR(50),              -- Texto del enlace de simulación, por ejemplo "Simulador"
    simulacion_url VARCHAR(255),               -- URL de la simulación, por ejemplo "simulador_bonos.php?id=120"
    simulacion_id INT,                         -- ID extraído de la URL de simulación
    fecha_hora DATE,                           -- Fecha/Hora del bono, formato DATE
    volumen BIGINT,                            -- Volumen del bono, número grande
    precio DECIMAL(15,2),                      -- Precio del bono, con hasta 2 decimales
    variacion DECIMAL(5,2)                     -- Variación porcentual, con hasta 2 decimales
);

"""

from funcion import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from urllib.parse import urlparse, parse_qs
import time
import datetime
from datetime import datetime
from bs4 import BeautifulSoup
import json


def iniciar_sesion(driver, email, password):
    driver.get('http://erp-prod:8080/gxpbackend/hgxpp001?1,7,54,P,S,0,,')
    email_field = driver.find_element(By.NAME, 'W0026W000600010002W0004W0004_USER')
    password_field = driver.find_element(By.NAME, 'W0026W000600010002W0004W0004_PASSWORD')
    email_field.send_keys(email)
    password_field.send_keys(password)
    password_field.send_keys(Keys.RETURN)
    time.sleep(3)


def iniciar_driver():
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service)
    return driver


def extraer_id_de_url(url):
    parsed_url = urlparse(url)
    query_params = parse_qs(parsed_url.query)
    return query_params.get('id', [None])[0]


def obtener_contenido_pagina(driver, url):
    try:
        driver.get(url)
        time.sleep(5)
        return driver.page_source
    except Exception as e:
        return f"Ocurrió un error al obtener la página: {e}"


def obtener_contenido_tabla(driver, url):
    try:
        driver.get(url)
        time.sleep(5)
        page_source = driver.page_source
        soup = BeautifulSoup(page_source, 'html.parser')
        div_registros = soup.find('div', {'id': 'registros'})
        if not div_registros:
            return "No se encontró el div con id 'registros'."
        tabla = div_registros.find('table')
        if not tabla:
            return "No se encontró ninguna tabla dentro del div con id 'registros'."
        return tabla.prettify()
    except Exception as e:
        return f"Ocurrió un error al obtener la tabla: {e}"


def tabla_html_a_json(tabla_html):
    try:
        soup = BeautifulSoup(tabla_html, 'html.parser')
        encabezados = [th.get_text(strip=True) for th in soup.find('thead').find_all('td')]
        filas = []
        for fila in soup.find('tbody').find_all('tr'):
            celdas = fila.find_all('td')
            fila_data = {}
            for i, celda in enumerate(celdas):
                texto = celda.get_text(strip=True)
                enlace = celda.find('a')
                if enlace and enlace.has_attr('href'):
                    fila_data[encabezados[i]] = {"texto": texto, "enlace": enlace['href']}
                else:
                    fila_data[encabezados[i]] = {"texto": texto}
            filas.append(fila_data)
        tabla_json = {"encabezados": encabezados, "filas": filas}
        return json.dumps(tabla_json, indent=4, ensure_ascii=False)
    except Exception as e:
        return {"error": f"Ocurrió un error al procesar la tabla: {e}"}


def tabla_html_a_lista(tabla_html):
    try:
        soup = BeautifulSoup(tabla_html, 'html.parser')
        encabezados = [th.get_text(strip=True) for th in soup.find('thead').find_all('td')]
        filas = []
        for fila in soup.find('tbody').find_all('tr'):
            celdas = fila.find_all('td')
            fila_data = {}
            for i, celda in enumerate(celdas):
                texto = celda.get_text(strip=True)
                enlace = celda.find('a')
                if enlace and enlace.has_attr('href'):
                    fila_data[encabezados[i]] = {"texto": texto, "enlace": enlace['href']}
                else:
                    fila_data[encabezados[i]] = {"texto": texto}
            for j in range(len(celdas), len(encabezados)):
                fila_data[encabezados[j]] = {"texto": ""}
            filas.append(fila_data)
        return filas
    except Exception as e:
        print(f"Error al procesar la tabla: {e}")
        return []


def insertar_datos_pres_k2b_sr_logs(solicitudes, db='0', usu='9999'):
    try:
        # Verificar que solicitudes es una lista de diccionarios
        if not isinstance(solicitudes, list) or not all(isinstance(s, dict) for s in solicitudes):
            raise ValueError("El parámetro 'solicitudes' debe ser una lista de diccionarios.")

        for solicitud in solicitudes:
            print(solicitud)

            # Extraer y procesar los valores del JSON
            def obtener_valor(campo, tipo="texto"):
                valor = solicitud.get(campo, {}).get(tipo, "NULL")
                return valor.replace("'", "''") if valor != "NULL" else "NULL"

            def procesar_numerico(valor):
                """Convierte un string numérico en float manejando formato latino.

                Ejemplos esperados de entrada:
                - "1.234,56" -> 1234.56
                - "123,45"   -> 123.45
                - "1.234"    -> 1234.0
                Si no se puede convertir, devuelve "NULL".
                """
                if valor == "NULL":
                    return "NULL"

                # Limpiar espacios y símbolos típicos
                valor_str = str(valor).strip()

                # El origen suele usar punto como separador de miles y coma como decimal
                # 1) quitar puntos de miles
                valor_str = valor_str.replace(".", "")
                # 2) cambiar coma decimal por punto
                valor_str = valor_str.replace(",", ".")


                try:
                    return float(valor_str)
                except ValueError:
                    # Si viene algo no numérico, devolvemos NULL para no romper el insert
                    return "NULL"


            def convertir_fecha(fecha_str):
                """
                Convierte una fecha en formato 'DD/MM/YYYY' a 'YYYY-MM-DD'.
                Si la fecha no es válida o está vacía, devuelve 'NULL'.
                """
                try:
                    return datetime.strptime(fecha_str, '%d/%m/%Y').strftime('%Y-%m-%d')
                except ValueError:
                    return 'NULL'


            def obtener_valor_raw(campo, default="NULL"):
                """Obtiene un valor directamente del dict y escapa comillas simples."""
                valor = solicitud.get(campo, {}).get("texto", default)
                return str(valor).replace("'", "''") if valor != "NULL" else "NULL"


            codigo_texto = obtener_valor("Código")
            codigo_link = obtener_valor("Código", "enlace")
            imp_texto = obtener_valor("IMP")
            imp_link = obtener_valor("IMP", "enlace")
            lin = obtener_valor_raw("Lín.", "NULL")
            lin = int(lin) if isinstance(lin, str) and lin.isdigit() else "NULL"


            usuario_texto = obtener_valor("Usuario")
            usuario_link = obtener_valor("Usuario", "enlace")
            estado = obtener_valor("Estado")
            matricula_texto = obtener_valor("Matrícula")
            matricula_link = obtener_valor("Matrícula", "enlace")
            descripcion = obtener_valor("Descrip")
            observacion = obtener_valor("Obsv")
            observacion_linea = obtener_valor("Obsv Lin")
            clasificacion_producto = obtener_valor("Clasificación de Producto Químico")
            peligroso = 1 if solicitud.get("PEL!", {}).get("texto", "") else 0
            at = obtener_valor("AT")
            et_texto = obtener_valor("ET")
            et_link = obtener_valor("ET", "enlace")
            unidad = obtener_valor("Unidad")
            # En el JSON la columna viene como 'P.\xa0Unit.' (espacio no rompiente)
            precio_unitario = procesar_numerico(obtener_valor("P.\xa0Unit."))
            cantidad_solicitada = procesar_numerico(obtener_valor("C.Sol."))
            cantidad_autorizada = procesar_numerico(obtener_valor("C.Aut."))
            importe_total = procesar_numerico(obtener_valor("Solic Importe Tot"))
            esquema = obtener_valor("Esquema")
            proveedor_sugerido = obtener_valor("Proveedor Sugerido")
            gestor = obtener_valor("Gestor")
            delegada = obtener_valor("Delegada")
            pedido_texto = obtener_valor("Pedido")
            pedido_link = obtener_valor("Pedido", "enlace")
            linea_pedido = obtener_valor_raw("Lin.Ped")
            estado_pedido = obtener_valor("Est Ped")
            estado_linea_pedido = obtener_valor("Est Lin Ped")
            comprador_pedido = obtener_valor("Comprador Ped")
            peticion_cotizacion = obtener_valor("Ped?")
            peticion_cotizacion_link = obtener_valor("Ped?", "enlace")
            cotizacion = obtener_valor("Cotiz?")
            cotizacion_link = obtener_valor("Cotiz?", "enlace")
            orden_compra = obtener_valor("OC")
            orden_compra_link = obtener_valor("OC", "enlace")
            proveedor = obtener_valor("Prov")
            categoria = obtener_valor("Categoria")
            linea_orden_compra = obtener_valor_raw("Lin.Oc")
            estado_orden_compra = obtener_valor("Est OC")
            estado_linea_orden_compra = obtener_valor("Est Lin OC")
            dias_prorroga = obtener_valor_raw("Días Prorroga")
            comprador_orden_compra = obtener_valor("Comprador OC")
            termino_pago = obtener_valor("Termino Pago")
            cantidad_comprada = procesar_numerico(obtener_valor("C.Comp"))
            cantidad_recepcionada = procesar_numerico(obtener_valor("C.Recep"))
            cantidad_finalizada = procesar_numerico(obtener_valor("C.Fin"))
            monto_linea_oc_usd = procesar_numerico(obtener_valor("Mnt Lin OC (Usd)"))
            orden_stock = obtener_valor("O.Stock")
            orden_stock_link = obtener_valor("O.Stock", "enlace")
            devolucion = obtener_valor("Dev?")
            fecha = convertir_fecha(solicitud.get("Fecha", {}).get("texto", "NULL"))
            # El campo en el JSON se llama "Fecha de Entrega Linea SR"
            fecha_entrega_sol = convertir_fecha(solicitud.get("Fecha de Entrega Linea SR", {}).get("texto", "NULL"))
            fecha_entrega_fin = convertir_fecha(solicitud.get("F. Entrega", {}).get("texto", "NULL"))
            fecha_prorroga = convertir_fecha(solicitud.get("F. Prorroga", {}).get("texto", "NULL"))


            # Usar INSERT ... ON DUPLICATE KEY UPDATE para hacer upsert atómico
            # Esto evita problemas con caracteres especiales en la PK y race conditions
            sql_upsert = f"""
                INSERT INTO pres_k2b_sr_logs (
                    codigo, codigo_link, imp, imp_link, lin, fecha, fecha_entrega_sol,
                    usuario, usuario_link, estado, matricula, matricula_link, descripcion,
                    observacion, observacion_linea, clasificacion_producto, peligroso, at, et, et_link,
                    unidad, precio_unitario, cantidad_solicitada, cantidad_autorizada, importe_total,
                    esquema, proveedor_sugerido, gestor, delegada, pedido, pedido_link,
                    linea_pedido, estado_pedido, estado_linea_pedido, comprador_pedido,
                    peticion_cotizacion, peticion_cotizacion_link, cotizacion, cotizacion_link,
                    orden_compra, orden_compra_link, proveedor, categoria, linea_orden_compra,
                    estado_orden_compra, estado_linea_orden_compra, fecha_entrega_fin,
                    dias_prorroga, fecha_prorroga, comprador_orden_compra, termino_pago,
                    cantidad_comprada, cantidad_recepcionada, cantidad_finalizada, monto_linea_oc_usd,
                    orden_stock, orden_stock_link, devolucion
                ) VALUES (
                    '{codigo_texto}', '{codigo_link}', '{imp_texto}', '{imp_link}', '{lin}',
                    '{fecha}', '{fecha_entrega_sol}', '{usuario_texto}', '{usuario_link}', '{estado}',
                    '{matricula_texto}', '{matricula_link}', '{descripcion}', '{observacion}',
                    '{observacion_linea}', '{clasificacion_producto}', '{peligroso}', '{at}', 
                    '{et_texto}', '{et_link}', '{unidad}', '{precio_unitario}', '{cantidad_solicitada}', 
                    '{cantidad_autorizada}', '{importe_total}', '{esquema}', '{proveedor_sugerido}', 
                    '{gestor}', '{delegada}', '{pedido_texto}', '{pedido_link}', '{linea_pedido}', 
                    '{estado_pedido}', '{estado_linea_pedido}', '{comprador_pedido}', '{peticion_cotizacion}',
                    '{peticion_cotizacion_link}', '{cotizacion}', '{cotizacion_link}', '{orden_compra}', 
                    '{orden_compra_link}', '{proveedor}', '{categoria}', '{linea_orden_compra}',
                    '{estado_orden_compra}', '{estado_linea_orden_compra}', '{fecha_entrega_fin}', 
                    '{dias_prorroga}', '{fecha_prorroga}', '{comprador_orden_compra}', '{termino_pago}', 
                    '{cantidad_comprada}', '{cantidad_recepcionada}', '{cantidad_finalizada}', '{monto_linea_oc_usd}',
                    '{orden_stock}', '{orden_stock_link}', '{devolucion}'
                )
                ON DUPLICATE KEY UPDATE
                    codigo_link = '{codigo_link}',
                    imp = '{imp_texto}',
                    imp_link = '{imp_link}',
                    lin = '{lin}',
                    fecha = '{fecha}',
                    fecha_entrega_sol = '{fecha_entrega_sol}',
                    usuario = '{usuario_texto}',
                    usuario_link = '{usuario_link}',
                    estado = '{estado}',
                    matricula_link = '{matricula_link}',
                    observacion = '{observacion}',
                    observacion_linea = '{observacion_linea}',
                    clasificacion_producto = '{clasificacion_producto}',
                    peligroso = '{peligroso}',
                    at = '{at}',
                    et = '{et_texto}',
                    et_link = '{et_link}',
                    unidad = '{unidad}',
                    precio_unitario = '{precio_unitario}',
                    cantidad_solicitada = '{cantidad_solicitada}',
                    cantidad_autorizada = '{cantidad_autorizada}',
                    importe_total = '{importe_total}',
                    esquema = '{esquema}',
                    proveedor_sugerido = '{proveedor_sugerido}',
                    gestor = '{gestor}',
                    delegada = '{delegada}',
                    pedido = '{pedido_texto}',
                    pedido_link = '{pedido_link}',
                    linea_pedido = '{linea_pedido}',
                    estado_pedido = '{estado_pedido}',
                    estado_linea_pedido = '{estado_linea_pedido}',
                    comprador_pedido = '{comprador_pedido}',
                    peticion_cotizacion = '{peticion_cotizacion}',
                    peticion_cotizacion_link = '{peticion_cotizacion_link}',
                    cotizacion = '{cotizacion}',
                    cotizacion_link = '{cotizacion_link}',
                    orden_compra = '{orden_compra}',
                    orden_compra_link = '{orden_compra_link}',
                    proveedor = '{proveedor}',
                    categoria = '{categoria}',
                    linea_orden_compra = '{linea_orden_compra}',
                    estado_orden_compra = '{estado_orden_compra}',
                    estado_linea_orden_compra = '{estado_linea_orden_compra}',
                    fecha_entrega_fin = '{fecha_entrega_fin}',
                    dias_prorroga = '{dias_prorroga}',
                    fecha_prorroga = '{fecha_prorroga}',
                    comprador_orden_compra = '{comprador_orden_compra}',
                    termino_pago = '{termino_pago}',
                    cantidad_comprada = '{cantidad_comprada}',
                    cantidad_recepcionada = '{cantidad_recepcionada}',
                    cantidad_finalizada = '{cantidad_finalizada}',
                    monto_linea_oc_usd = '{monto_linea_oc_usd}',
                    orden_stock = '{orden_stock}',
                    orden_stock_link = '{orden_stock_link}',
                    devolucion = '{devolucion}';
            """
            print(sql_upsert)
            result = sql_do_query(sql_upsert, db, usu)


            if result is None:
                print(f"Error al procesar el código {codigo_texto}")
            else:
                print(f"Datos procesados correctamente para el código {codigo_texto} (filas: {result})")
    except Exception as error:
        print("Error al insertar datos:", error)


driver = iniciar_driver()
#iniciar_sesion(driver, 'gonzalo.farinon@gmail.com', 'ZAZ3rbL.vVCuUL')
iniciar_sesion(driver, 'farinong', 'M@cross2021')

try:
    inicial = 0
    while True:

        # Obtener el año en curso
        current_year = datetime.now().year
        #current_year = 2025

        # Construir las fechas en formato requerido (dd/mm/yyyy)
        fecha_desde = f"01%2F01%2F{current_year}"
        fecha_hasta = f"31%2F12%2F{current_year}"

        # Construir la URL con las fechas dinámicas
        url_base = "https://intranet.saltogrande.org/k2b/solicitudes_reorden.php"
        parametros = (
            f"SolicitudCmpCodigo=&desde={fecha_desde}&hasta={fecha_hasta}"
            "&UsuarioNombre=&ProductoCodigo=&ProductoDescripcion="
            "&EsquemaDistribucionCostoId=&ProductoGestorId="
            "&PedidoCmpCodigo=&desdePed=&hastaPed=&CompradorCodigo="
            "&NivelCodigo=&OrdenCmpCodigo=&desdeOc=&hastaOc="
            "&desdeOcValor=&hastaOcValor=&ProveedorCodigo="
            "&ProveedorNombreLegal=&comprador=&SolCmpTipoTrnCmpCodigo="
            "&SectorCodigo=880&buscar=Buscar"
        )

        url = f"{url_base}?{parametros}"

        #url = f'https://intranet.saltogrande.org/k2b/solicitudes_reorden.php?SolicitudCmpCodigo=&desde=01%2F01%2F2024&hasta=31%2F12%2F2024&UsuarioNombre=&ProductoCodigo=&ProductoDescripcion=&EsquemaDistribucionCostoId=&ProductoGestorId=&PedidoCmpCodigo=&desdePed=&hastaPed=&CompradorCodigo=&NivelCodigo=&OrdenCmpCodigo=&desdeOc=&hastaOc=&desdeOcValor=&hastaOcValor=&ProveedorCodigo=&ProveedorNombreLegal=&comprador=&SolCmpTipoTrnCmpCodigo=&SectorCodigo=880&buscar=Buscar'

        #url = f'https://intranet.saltogrande.org/k2b/solicitudes_reorden.php?SolicitudCmpCodigo=173849&desde=01%2F01%2F2024&hasta=31%2F12%2F2024&UsuarioNombre=&ProductoCodigo=&ProductoDescripcion=&EsquemaDistribucionCostoId=&ProductoGestorId=&PedidoCmpCodigo=&desdePed=&hastaPed=&CompradorCodigo=&NivelCodigo=&OrdenCmpCodigo=&desdeOc=&hastaOc=&desdeOcValor=&hastaOcValor=&ProveedorCodigo=&ProveedorNombreLegal=&comprador=&SolCmpTipoTrnCmpCodigo=&SectorCodigo=880&buscar=Buscar'
        
        contenido_html = obtener_contenido_tabla(driver, url)

        print(contenido_html)

        contenido_json = tabla_html_a_lista(contenido_html)

        #print(contenido_json)

        insertar_datos_pres_k2b_sr_logs(contenido_json, '1', '9999')

        break


        #if not isjson(contenido_json):
        #    print(f"Contenido no válido en la URL: {url}")
        #    break 

        #contenido_dict = manejar_json(contenido_json)
        
        #if contenido_dict is None:
        #    print(f"Error al manejar el JSON en la URL: {url}")
        #    break  

        #insertar_datos_bonos_screenermatic(contenido_dict, '1', '9999')

        #inicial += 20

        #if contenido_dict == {}:
        #    print("Fin de los datos.")
        #    break
finally:
    driver.quit()