@echo off
setlocal

:: Solicita la entrada de las variables desde la consola
set /p nombre="Introduce el nombre del model (UpperCase y singular):"
set /p Nombre="Introduce el valor del controlador (singular y formato UpperCase) - NO incluir la palabra Controller:"
set /p ruta="Introduce el valor para 'ruta' (perfil donde se encuentra):"
set /p tabla="Introduce el valor para 'tabla' (nombre de la entidad):"
set /p nameApi="Nombre de la api (minusculas y plural):"
set /p rutaDestino="Introduce la ruta donde se generarán los archivos:"

:: Define el nombre del archivo final del controlador
set "archivoFinal= C:\xampp\htdocs\apirest\app\Http\Controllers\Api\%ruta%\%Nombre%Controller.php"
:: Copia la plantilla y renombra para el controlador
copy NombreController.php %archivoFinal%
:: Reemplaza los marcadores con PowerShell en el archivo del controlador
PowerShell -Command "(Get-Content %archivoFinal%) -replace '{Nombre}', '%Nombre%' -replace '{nombre}', '%nombre%' -replace '{ruta}', '%ruta%' -replace '{nameApi}', '%nameApi%' | Set-Content %archivoFinal%"
if %errorlevel% neq 0 (
    echo Error al procesar el archivo %archivoFinal%.
    exit /b %errorlevel%
)
:: Define el nombre del archivo final para la API
set "archivoFinalApi=C:\xampp\htdocs\apirest\routes\%ruta%\%nameApi%.php"
:: Copia la plantilla y renombra para la API
copy api.php %archivoFinalApi%
:: Reemplaza los marcadores con PowerShell en el archivo de la API
PowerShell -Command "(Get-Content %archivoFinalApi%) -replace '{Nombre}', '%Nombre%' -replace '{ruta}', '%ruta%' | Set-Content %archivoFinalApi%"
if %errorlevel% neq 0 (
    echo Error al procesar el archivo %archivoFinalApi%.
    exit /b %errorlevel%
)
:: Define el nombre del archivo final para el modelo
set "archivoFinalModel= C:\xampp\htdocs\apirest\app\Models\%ruta%\%nombre%.php"
:: Copia la plantilla y renombra para el modelo
copy modelo.php %archivoFinalModel%
:: Reemplaza los marcadores con PowerShell en el archivo del modelo
PowerShell -Command "(Get-Content %archivoFinalModel%) -replace '{Nombre}', '%Nombre%' -replace '{ruta}', '%ruta%' -replace '{tabla}', '%tabla%' | Set-Content %archivoFinalModel%"
if %errorlevel% neq 0 (
    echo Error al procesar el archivo %archivoFinalModel%.
    exit /b %errorlevel%
)
echo %archivoFinalModel% Modelo generado con éxito.
endlocal
