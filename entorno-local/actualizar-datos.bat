@echo off
rem Vuelve a copiar siaweb_pruebas a la base local; descarta lo que se haya guardado en local
cd /d "%~dp0"
set PHP=C:\xampp\php\php.exe
echo Se reemplazara la base local con una copia nueva de siaweb_pruebas.
echo Cierra antes las ventanas "SIAWEB local" si estan abiertas.
pause
"%PHP%" copiar-datos.php
pause
