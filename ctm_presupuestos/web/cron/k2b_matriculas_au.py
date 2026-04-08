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
from urllib.parse import urlparse, parse_qs
import time
import datetime
from datetime import datetime
from bs4 import BeautifulSoup
import json


def iniciar_sesion(driver, email, password):
    driver.get('http://erp-prod:8080/gxpbackend/hgxpp001?1,7,54,P,S,0,,')
    
    # Localizar los campos de email y password
    email_field = driver.find_element(By.NAME, 'W0026W000600010002W0004W0004_USER')
    password_field = driver.find_element(By.NAME, 'W0026W000600010002W0004W0004_PASSWORD')
    
    # Rellenar las credenciales y enviar el formulario
    email_field.send_keys(email)
    password_field.send_keys(password)
    password_field.send_keys(Keys.RETURN)
    
    # Esperar unos segundos para que se procese el login
    time.sleep(3)

# Función para iniciar el WebDriver de Google Chrome
def iniciar_driver():
    service = Service(r"D:\\xampp\\htdocs\\ctm\\contexto_unificado\\ctm_presupuestos\\web\\cron\\chromedriver\\chromedriver.exe")  # Ajusta tu ruta
    driver = webdriver.Chrome(service=service)
    return driver

# Función para extraer el ID de la URL (si existe)
def extraer_id_de_url(url):
    parsed_url = urlparse(url)
    query_params = parse_qs(parsed_url.query)
    return query_params.get('id', [None])[0]

# Función para obtener el contenido de la tabla de bonos y retornarlo en formato JSON
import time

def obtener_contenido_pagina(driver, url):
    """
    Función que obtiene el código fuente completo de una página web y lo devuelve como un string.
    """
    try:
        # Cargar la página
        driver.get(url)
        
        # Esperar unos segundos para asegurar la carga completa de la página
        time.sleep(5)  # Puedes ajustar este tiempo según la velocidad de carga
        
        # Obtener el código fuente completo de la página
        page_source = driver.page_source
        
        # Retornar el código fuente como string
        return page_source
    
    except Exception as e:
        return f"Ocurrió un error al obtener la página: {e}"
    
from bs4 import BeautifulSoup
import time

def obtener_contenido_tabla(driver, url):
    """
    Función que obtiene el contenido de la tabla dentro del div con id 'registros' de una página web.
    """
    try:
        # Cargar la página
        driver.get(url)
        
        # Esperar unos segundos para asegurar la carga completa de la página
        time.sleep(5)  # Ajusta este tiempo según sea necesario
        
        # Obtener el código fuente completo de la página
        page_source = driver.page_source
        
        # Usar BeautifulSoup para analizar el HTML
        soup = BeautifulSoup(page_source, 'html.parser')
        
        # Buscar el div con id "registros"
        div_registros = soup.find('div', {'id': 'registros'})
        
        if not div_registros:
            return "No se encontró el div con id 'registros'."
        
        # Buscar la tabla dentro del div
        tabla = div_registros.find('table')
        
        if not tabla:
            return "No se encontró ninguna tabla dentro del div con id 'registros'."
        
        # Extraer el contenido de la tabla
        contenido_tabla = tabla.prettify()
        
        # Retornar el contenido de la tabla como string
        return contenido_tabla
    
    except Exception as e:
        return f"Ocurrió un error al obtener la tabla: {e}"

def tabla_html_a_json(tabla_html):
    """
    Convierte una tabla HTML en un JSON.
    
    :param tabla_html: Código fuente de la tabla en formato HTML.
    :return: Un objeto JSON con los datos de la tabla.
    """
    try:
        # Analizar la tabla HTML con BeautifulSoup
        soup = BeautifulSoup(tabla_html, 'html.parser')
        
        # Extraer los encabezados de la tabla
        encabezados = [th.get_text(strip=True) for th in soup.find('thead').find_all('td')]

        # Extraer los datos de las filas del cuerpo de la tabla
        filas = []
        for fila in soup.find('tbody').find_all('tr'):
            celdas = fila.find_all('td')
            fila_data = {}
            for i, celda in enumerate(celdas):
                # Capturar el texto de la celda
                texto = celda.get_text(strip=True)
                
                # Capturar el enlace si existe
                enlace = celda.find('a')
                if enlace and enlace.has_attr('href'):
                    fila_data[encabezados[i]] = {"texto": texto, "enlace": enlace['href']}
                else:
                    fila_data[encabezados[i]] = {"texto": texto}
            
            filas.append(fila_data)
        
        # Crear el objeto JSON
        tabla_json = {"encabezados": encabezados, "filas": filas}
        
        return json.dumps(tabla_json, indent=4, ensure_ascii=False)
    
    except Exception as e:
        return {"error": f"Ocurrió un error al procesar la tabla: {e}"}
    
def tabla_html_a_lista(tabla_html):
    try:
        # Analizar la tabla HTML con BeautifulSoup
        soup = BeautifulSoup(tabla_html, 'html.parser')
        
        # Extraer encabezados
        encabezados = [th.get_text(strip=True) for th in soup.find('thead').find_all('td')]

        # Extraer datos de las filas
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
            
            # Verifica si faltan celdas y rellena con valores vacíos
            for j in range(len(celdas), len(encabezados)):
                fila_data[encabezados[j]] = {"texto": ""}
            
            filas.append(fila_data)
        
        return filas
    except Exception as e:
        print(f"Error al procesar la tabla: {e}")
        return []



from datetime import datetime

from datetime import datetime

def insertar_datos_pres_k2b_matriculas_logs(solicitudes, db='0', usu='9999'):
    try:
        # Verificar que 'solicitudes' es una lista de diccionarios
        if not isinstance(solicitudes, list) or not all(isinstance(s, dict) for s in solicitudes):
            raise ValueError("El parámetro 'solicitudes' debe ser una lista de diccionarios.")

        def obtener_valor(obj, campo, tipo="texto"):
            """
            Devuelve el valor de obj[campo][tipo] o 'NULL' si no existe.
            """
            return obj.get(campo, {}).get(tipo, "NULL")

        def safe_sql(val):
            """
            Si val es 'NULL', devuelve NULL (sin comillas).
            En caso contrario, escapa comillas y lo encierra entre comillas simples.
            """
            if val == "NULL":
                return "NULL"
            # Escapamos comillas simples internas
            escaped = val.replace("'", "''")
            return f"'{escaped}'"

        for solicitud in solicitudes:
            print("Procesando solicitud:", solicitud)

            # --------------------------------------------------------------------------------
            # OBTENER valores del JSON (todos como texto) y ASIGNAR a sus columnas correspondientes
            # --------------------------------------------------------------------------------

            # 1) Código
            codigo_texto  = obtener_valor(solicitud, "Código", "texto")
            codigo_enlace = obtener_valor(solicitud, "Código", "enlace")

            # 2) IMP
            imp_texto  = obtener_valor(solicitud, "IMP", "texto")
            imp_enlace = obtener_valor(solicitud, "IMP", "enlace")

            # 3) Lín. -> lin_texto
            lin_texto = solicitud.get("Lín.", {}).get("texto", "NULL")

            # 4) Fechas en columnas de texto
            fecha_texto      = solicitud.get("Fecha", {}).get("texto", "NULL")
            fecha_entrega    = solicitud.get("Fecha de Entrega", {}).get("texto", "NULL")

            # 5) Usuario
            usuario_texto  = obtener_valor(solicitud, "Usuario", "texto")
            usuario_enlace = obtener_valor(solicitud, "Usuario", "enlace")

            # 6) Estado
            estado_texto = obtener_valor(solicitud, "Estado")

            # 7) Matrícula
            matricula_texto  = obtener_valor(solicitud, "Matrícula", "texto")
            matricula_enlace = obtener_valor(solicitud, "Matrícula", "enlace")

            # 8) Descripción, observaciones
            descrip_texto  = obtener_valor(solicitud, "Descrip")
            obsv_texto     = obtener_valor(solicitud, "Obsv")
            obsv_lin_texto = obtener_valor(solicitud, "Obsv Lin")

            # 9) Clasif. de producto químico
            clasif_prod_quimico_texto = obtener_valor(solicitud, "Clasificación de Producto Químico")

            # 10) PEL! -> pel_texto
            pel_texto = solicitud.get("PEL!", {}).get("texto", "NULL")

            # 11) AT
            at_texto = obtener_valor(solicitud, "AT")

            # 12) ET
            et_texto  = obtener_valor(solicitud, "ET", "texto")
            et_enlace = obtener_valor(solicitud, "ET", "enlace")

            # 13) Unidad
            unidad_texto = obtener_valor(solicitud, "Unidad")

            # 14) Precios/cantidades (también texto)
            p_unit_texto           = obtener_valor(solicitud, "P. Unit.")
            c_sol_texto            = obtener_valor(solicitud, "C.Sol.")
            c_aut_texto            = obtener_valor(solicitud, "C.Aut.")
            solic_importe_tot_texto= obtener_valor(solicitud, "Solic Importe Tot")

            # 15) Esquema, proveedor, gestor, delegada
            esquema_texto         = obtener_valor(solicitud, "Esquema")
            proveedor_sug_texto   = obtener_valor(solicitud, "Proveedor Sugerido")
            gestor_texto          = obtener_valor(solicitud, "Gestor")
            delegada_texto        = obtener_valor(solicitud, "Delegada")

            # 16) Pedido
            pedido_texto  = obtener_valor(solicitud, "Pedido", "texto")
            pedido_enlace = obtener_valor(solicitud, "Pedido", "enlace")

            lin_ped_texto         = solicitud.get("Lin.Ped", {}).get("texto", "NULL")
            est_ped_texto         = obtener_valor(solicitud, "Est Ped")
            est_lin_ped_texto     = obtener_valor(solicitud, "Est Lin Ped")
            comprador_ped_texto   = obtener_valor(solicitud, "Comprador Ped")

            # 17) Petición de cotización ("Ped?")
            ped_q_texto  = obtener_valor(solicitud, "Ped?", "texto")
            ped_q_enlace = obtener_valor(solicitud, "Ped?", "enlace")

            # 18) Cotización ("Cotiz?")
            cotiz_q_texto  = obtener_valor(solicitud, "Cotiz?", "texto")
            cotiz_q_enlace = obtener_valor(solicitud, "Cotiz?", "enlace")

            # 19) Orden de compra ("OC")
            oc_texto  = obtener_valor(solicitud, "OC", "texto")
            oc_enlace = obtener_valor(solicitud, "OC", "enlace")

            # 20) Proveedor y categoría
            prov_texto     = obtener_valor(solicitud, "Prov")
            categoria_texto= obtener_valor(solicitud, "Categoria")

            # 21) Línea OC
            lin_oc_texto = solicitud.get("Lin.Oc", {}).get("texto", "NULL")

            # 22) Estados de la OC
            est_oc_texto     = obtener_valor(solicitud, "Est OC")
            est_lin_oc_texto = obtener_valor(solicitud, "Est Lin OC")

            # 23) Fechas y prórrogas
            f_entrega_texto    = solicitud.get("F. Entrega", {}).get("texto", "NULL")
            dias_prorroga_texto= solicitud.get("Días Prorroga", {}).get("texto", "NULL")
            f_prorroga_texto   = solicitud.get("F. Prorroga", {}).get("texto", "NULL")

            comprador_oc_texto = obtener_valor(solicitud, "Comprador OC")
            termino_pago_texto = obtener_valor(solicitud, "Termino Pago")

            # 24) Cantidades en la OC
            c_comp_texto     = obtener_valor(solicitud, "C.Comp")
            c_recep_texto    = obtener_valor(solicitud, "C.Recep")
            c_fin_texto      = obtener_valor(solicitud, "C.Fin")
            mnt_lin_oc_usd_texto = obtener_valor(solicitud, "Mnt Lin OC (Usd)")

            # 25) Orden stock
            o_stock_texto  = obtener_valor(solicitud, "O.Stock", "texto")
            o_stock_enlace = obtener_valor(solicitud, "O.Stock", "enlace")

            # 26) Devolución
            dev_texto = obtener_valor(solicitud, "Dev?")

            # --------------------------------------------------------------------------------
            # VERIFICAR SI YA EXISTE ESE REGISTRO (criterio de unicidad: puedes personalizarlo)
            # --------------------------------------------------------------------------------
            sqlxyx = f"""
                SELECT id
                FROM pres_k2b_matriculas_logs m
                WHERE m.codigo_texto = {safe_sql(codigo_texto)}
                  AND m.matricula_texto = {safe_sql(matricula_texto)}
                  AND m.mnt_lin_oc_usd_texto = {safe_sql(mnt_lin_oc_usd_texto)}
                  AND m.f_entrega_texto = {safe_sql(f_entrega_texto)}
            """
            print("Consulta de verificación:\n", sqlxyx)
            resultxyx = sql_do_query(sqlxyx, '1', '9999')

            # Si no hay resultados, o la primera fila tiene id=None, entonces insertamos
            if not resultxyx:
                # --------------------------------------------------------------------------------
                # ARMAR SENTENCIA DE INSERT
                # El orden de columnas debe coincidir con el orden de VALUES
                # --------------------------------------------------------------------------------
                sql_insert = f"""
                INSERT INTO pres_k2b_matriculas_logs (
                    codigo_texto, codigo_enlace,
                    imp_texto, imp_enlace,
                    lin_texto,
                    fecha_texto,
                    fecha_entrega,
                    usuario_texto, usuario_enlace,
                    estado_texto,
                    matricula_texto, matricula_enlace,
                    descrip_texto,
                    obsv_texto, obsv_lin_texto,
                    clasif_prod_quimico_texto,
                    pel_texto,
                    at_texto,
                    et_texto, et_enlace,
                    unidad_texto,
                    p_unit_texto,
                    c_sol_texto,
                    c_aut_texto,
                    solic_importe_tot_texto,
                    esquema_texto,
                    proveedor_sug_texto,
                    gestor_texto,
                    delegada_texto,
                    pedido_texto, pedido_enlace,
                    lin_ped_texto,
                    est_ped_texto, est_lin_ped_texto,
                    comprador_ped_texto,
                    ped_q_texto, ped_q_enlace,
                    cotiz_q_texto, cotiz_q_enlace,
                    oc_texto, oc_enlace,
                    prov_texto, categoria_texto,
                    lin_oc_texto,
                    est_oc_texto, est_lin_oc_texto,
                    f_entrega_texto,
                    dias_prorroga_texto,
                    f_prorroga_texto,
                    comprador_oc_texto,
                    termino_pago_texto,
                    c_comp_texto, c_recep_texto, c_fin_texto,
                    mnt_lin_oc_usd_texto,
                    o_stock_texto, o_stock_enlace,
                    dev_texto
                ) VALUES (
                    {safe_sql(codigo_texto)}, {safe_sql(codigo_enlace)},
                    {safe_sql(imp_texto)}, {safe_sql(imp_enlace)},
                    {safe_sql(lin_texto)},
                    {safe_sql(fecha_texto)},
                    {safe_sql(fecha_entrega)},
                    {safe_sql(usuario_texto)}, {safe_sql(usuario_enlace)},
                    {safe_sql(estado_texto)},
                    {safe_sql(matricula_texto)}, {safe_sql(matricula_enlace)},
                    {safe_sql(descrip_texto)},
                    {safe_sql(obsv_texto)}, {safe_sql(obsv_lin_texto)},
                    {safe_sql(clasif_prod_quimico_texto)},
                    {safe_sql(pel_texto)},
                    {safe_sql(at_texto)},
                    {safe_sql(et_texto)}, {safe_sql(et_enlace)},
                    {safe_sql(unidad_texto)},
                    {safe_sql(p_unit_texto)},
                    {safe_sql(c_sol_texto)},
                    {safe_sql(c_aut_texto)},
                    {safe_sql(solic_importe_tot_texto)},
                    {safe_sql(esquema_texto)},
                    {safe_sql(proveedor_sug_texto)},
                    {safe_sql(gestor_texto)},
                    {safe_sql(delegada_texto)},
                    {safe_sql(pedido_texto)}, {safe_sql(pedido_enlace)},
                    {safe_sql(lin_ped_texto)},
                    {safe_sql(est_ped_texto)}, {safe_sql(est_lin_ped_texto)},
                    {safe_sql(comprador_ped_texto)},
                    {safe_sql(ped_q_texto)}, {safe_sql(ped_q_enlace)},
                    {safe_sql(cotiz_q_texto)}, {safe_sql(cotiz_q_enlace)},
                    {safe_sql(oc_texto)}, {safe_sql(oc_enlace)},
                    {safe_sql(prov_texto)}, {safe_sql(categoria_texto)},
                    {safe_sql(lin_oc_texto)},
                    {safe_sql(est_oc_texto)}, {safe_sql(est_lin_oc_texto)},
                    {safe_sql(f_entrega_texto)},
                    {safe_sql(dias_prorroga_texto)},
                    {safe_sql(f_prorroga_texto)},
                    {safe_sql(comprador_oc_texto)},
                    {safe_sql(termino_pago_texto)},
                    {safe_sql(c_comp_texto)}, {safe_sql(c_recep_texto)}, {safe_sql(c_fin_texto)},
                    {safe_sql(mnt_lin_oc_usd_texto)},
                    {safe_sql(o_stock_texto)}, {safe_sql(o_stock_enlace)},
                    {safe_sql(dev_texto)}
                );
                """
                print("Ejecutando INSERT:\n", sql_insert)
                result_in = sql_do_query(sql_insert, db, usu)

                if result_in is not None:
                    print(f"Error al insertar el código {codigo_texto}: {result_in}")
                else:
                    print(f"Datos insertados correctamente para el código {codigo_texto}")
            else:
                # Si la consulta devolvió resultados, significa que (probablemente) ya existe
                for rowxyx in resultxyx:
                    if rowxyx[0] is None:
                        # Significa que devolvió fila con id=None (caso muy raro), insertamos igual
                        print("El registro tiene id=None. Se procede a insertar de todas formas.")
                        # (Podrías copiar la lógica anterior de INSERT si deseas.)
                    else:
                        print(f"Registro ya existente para código {codigo_texto}")

    except Exception as error:
        print("Error al insertar datos:", error)




driver = iniciar_driver()
#iniciar_sesion(driver, 'gonzalo.farinon@gmail.com', 'ZAZ3rbL.vVCuUL')
iniciar_sesion(driver, 'farinong', 'M@cross2021')

try:
    inicial = 0
    while True:

        sqlxxx = "SELECT DISTINCT(matricula) AS matr FROM pres_elementos;"
        print(sqlxxx)
        resultxxx = sql_do_query(sqlxxx, '1', '9999')

        if resultxxx:
            for rowxxx in resultxxx:
                # Obtener el año en curso
                current_year = datetime.now().year
                anio_anterior = (current_year - 1)
                matricula = rowxxx[0]

                # Construir las fechas en formato requerido (dd/mm/yyyy)
                fecha_desde = f"01%2F01%2F{anio_anterior}"
                fecha_hasta = f"31%2F12%2F{current_year}"

                # Construir la URL con las fechas dinámicas
                url_base = "https://intranet.saltogrande.org/k2b/solicitudes_reorden.php"
                parametros = (
                    f"SolicitudCmpCodigo=&desde={fecha_desde}&hasta={fecha_hasta}"
                    f"&UsuarioNombre=&ProductoCodigo={matricula}&ProductoDescripcion="
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

                #print(contenido_html)

                contenido_json = tabla_html_a_lista(contenido_html)

                print(contenido_json)

                #pause = input("Preseiona una tecla para continuar ...")

                insertar_datos_pres_k2b_matriculas_logs(contenido_json, '1', '9999')

                #break


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