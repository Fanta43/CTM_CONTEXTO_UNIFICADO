@echo off
rem Ejecuta los procesos de cron de presupuestos

rem Ruta base del proyecto
set "BASE_DIR=D:\xampp\htdocs\ctm\contexto_unificado\ctm_presupuestos"

rem Ruta al ejecutable de Python
set "PYTHON=C:\Users\farinong\AppData\Local\Programs\Python\Python310-32\python.exe"

rem Scripts a ejecutar
set "SCRIPT_PRESUPUESTOS=%BASE_DIR%\web\cron\k2b_presupuestos.py"
set "SCRIPT_SR_AICO=%BASE_DIR%\web\cron\k2b_sr_aico.py"

rem Cambiar al directorio base (por si los scripts usan rutas relativas)
cd /d "%BASE_DIR%"

echo [RUN_CRON] Ejecutando k2b_presupuestos.py...
"%PYTHON%" "%SCRIPT_PRESUPUESTOS%"
echo [RUN_CRON] Finalizó k2b_presupuestos.py.
echo.

echo [RUN_CRON] Ejecutando k2b_sr_aico.py...
"%PYTHON%" "%SCRIPT_SR_AICO%"
echo [RUN_CRON] Finalizó k2b_sr_aico.py.
echo.

rem Descomenta si quieres ver la salida cuando lo lanzas a mano
rem pause