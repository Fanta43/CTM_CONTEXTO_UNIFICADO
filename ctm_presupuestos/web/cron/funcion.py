from datetime import datetime
import mysql.connector
from datetime import datetime
from pprint import pprint
import importlib
import sys
import os
import logging
import traceback
#import pyodbc

def sql_cnx_data(db='0'):
    data = {}
    if db == '3':
        data['tipo'] = "mssql"
        data['host'] = "localhost"
        data['user'] = "root"
        data['pws'] = ""
        data['db'] = "ahor_datos_origenes"
    elif db == '2':
        data['tipo'] = "odbc"
        data['dns'] = "localhost"
        data['user'] = "root"
        data['pws'] = "2"
        data['db'] = "ahor_datos_origenes"
    elif db == '1':
        data['tipo'] = "mysql"
        data['host'] = "localhost"
        data['user'] = "root"
        data['pws'] = ""
        data['db'] = "contexto_unificado"
    else: 
        data['tipo'] = "mysql"
        data['host'] = "localhost"
        data['user'] = "root"
        data['pws'] = ""
        data['db'] = "contexto_unificado"
    return data

def conf_broker(tipo_com='0'):
    data = {}
    if tipo_com == 'IOL__':
        data['url_token']='https://api.invertironline.com/token'
        data['username']='ctmhidrologia@gmail.com'
        data['password']='QJTh5y4zEtUx*+d'
    elif tipo_com == 'IOL':
        data['url_token']='https://api.invertironline.com/token'
        data['username']='ctmhidrologia@gmail.com'
        data['password']='QJTh5y4zEtUx*+d'
    else: 
        data['url_token']='https://api.invertironline.com/token'
        data['username']='ctmhidrologia@gmail.com'
        data['password']='QJTh5y4zEtUx*+d'
    return data


def sql_do_query_mysql(query, db='0', usu='9999'):
    conf = sql_cnx_data(db)
    try:
        # Establecemos la conexión con la base de datos
        conexion = mysql.connector.connect(
            host=conf['host'],
            user=conf['user'],
            password=conf['pws'],
            database=conf['db']
        )

        if conexion.is_connected():
            cursor = conexion.cursor()
            cursor.execute(query)
            
            # Comprobar si la consulta es un SELECT
            if query.strip().lower().startswith("select"):
                resultado = cursor.fetchall()
            else:
                # Para INSERT, UPDATE y DELETE, se confirma la transacción
                conexion.commit()
                resultado = cursor.rowcount  # Número de filas afectadas por la operación
            
            cursor.close()
            conexion.close()
            return resultado
        else:
            print("Error: No se pudo establecer la conexión")
            return None

    except mysql.connector.Error as err:
        print("Error de MySQL: {}".format(err))
        return None

    except Exception as e:
        print("Error inesperado: {}".format(e))
        return None

def sql_do_query_odbc(query, db='0', usu='9999'):
    conf = sql_cnx_data(db)
    try:
         conexion = pyodbc.connect(
             f"DSN={conf['dns']};UID={conf['user']};PWD={conf['pws']}"
         )
         cursor = conexion.cursor()
         cursor.execute(query)
         resultado = cursor.fetchall()
         cursor.close()
         conexion.close()
         return resultado
    except pyodbc.Error as err:
        print("Error: {}".format(err))
        return None

def sql_do_query(query, db='0', usu='9999'):
    conf = sql_cnx_data(db)
    if conf['tipo'] == 'odbc':
        resultado = sql_do_query_odbc(query, db, usu)
    elif conf['tipo'] == 'mysql':
        resultado = sql_do_query_mysql(query, db, usu)
    else:
        resultado = sql_do_query_mysql(query, db, usu)
    return resultado

def entreTiempos(fecha1, fecha2):
    formato = '%Y-%m-%d %H:%M:%S'
    try:
        if isinstance(fecha1, str):
            date1 = datetime.strptime(fecha1, formato)
        else:
            date1 = fecha1
        if isinstance(fecha2, str):
            date2 = datetime.strptime(fecha2, formato)
        else:
            date2 = fecha2
    except ValueError as e:
        print("Error en el formato de fecha:", e)
        return None
    diff = abs(date2 - date1)
    segundos = diff.total_seconds()
    return segundos

def incluir_ejecutar(ruta_modulo):
    directorio, archivo = os.path.split(ruta_modulo)
    nombre_modulo, ext = os.path.splitext(archivo)
    
    if not ext == '.py':
        logging.error(f"El archivo {archivo} no es un módulo Python válido.")
        return
    
    # Verificar que el directorio existe
    if directorio and not os.path.isdir(directorio):
        logging.error(f"El directorio {directorio} no existe.")
        return
    
    # Añadir el directorio al sys.path si no está presente
    if directorio and directorio not in sys.path:
        sys.path.append(directorio)
    
    try:
        modulo = importlib.import_module(nombre_modulo)
        if hasattr(modulo, 'ejecutar'):
            modulo.ejecutar()
        else:
            logging.error(f"El módulo {nombre_modulo} no tiene una función 'ejecutar'.")
    except ImportError as e:
        error_detallado = traceback.format_exc()
        logging.error(f"No se pudo importar el módulo: {e}\n{error_detallado}")
    except Exception as e:
        error_detallado = traceback.format_exc()
        logging.error(f"Error al ejecutar el módulo: {e}\n{error_detallado}")

def limpiar_caracteres_raros(texto: str) -> str:
    return ''.join(c for c in texto if ord(c) < 128)