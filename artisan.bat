@echo off
REM Usa siempre el PHP de 64 bits de XAMPP, sin depender del PATH del sistema.
"C:\xampp\php\php.exe" "%~dp0artisan" %*
