@echo off
rem Inicia el ambiente local de SIAWEB: API en 127.0.0.1:8000 y pantallas en 127.0.0.1:8001
cd /d "%~dp0"
set PHP=C:\xampp\php\php.exe

if not exist "%PHP%" (
  echo No se encontro PHP en %PHP%. Ajusta la variable PHP en este archivo.
  pause
  exit /b 1
)

if not exist "datos\siaweb_local.sqlite" (
  echo Primera vez: copiando datos de siaweb_pruebas a la base local...
  "%PHP%" copiar-datos.php
  if errorlevel 1 (
    echo No se pudo copiar la base. Revisa el mensaje de arriba.
    pause
    exit /b 1
  )
)

start "SIAWEB local - API (no cerrar)" "%PHP%" -S 127.0.0.1:8000 router-api.php
start "SIAWEB local - Pantallas (no cerrar)" "%PHP%" -S 127.0.0.1:8001 router-pagina.php
timeout /t 2 /nobreak >nul
start "" http://127.0.0.1:8001/
echo Ambiente local iniciado. Para detenerlo cierra las dos ventanas "SIAWEB local".
