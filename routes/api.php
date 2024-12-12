<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/* Validar conexión a la BD http://127.0.0.1:8000/api/test-db*/
Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();
        
        $databaseName = DB::select('SELECT DATABASE() AS db_name');
        
        // Ejecutar una consulta SQL para obtener la versión de MySQL
        $version = DB::select('SELECT VERSION() AS version');
        
        return response()->json([
            'message' => 'Conexión a la base de datos exitosa!',
            'database' => $databaseName[0]->db_name,
            'version' => $version[0]->version,
        ]);
    } catch (\Exception $e) {
        return 'No se puede conectar a la base de datos. Error: ' . $e->getMessage();
    }
});

Route::prefix('aceptaAviso')->group(function () {
    require base_path('routes/general/aceptaAviso.php');
});


Route::prefix('aplicaciones')->group(function () {
    require base_path('routes/seguridad/aplicaciones.php');
});

Route::prefix('asentamientos')->group(function () {
    require base_path('routes/general/asentamientos.php');
});

Route::prefix('aspirantes')->group(function () {
    require base_path('routes/admisiones/aspirantes.php');
});

Route::prefix('avisosPrivacidad')->group(function () {
    require base_path('routes/general/avisosPrivacidad.php');
});

Route::prefix('carreras')->group(function () {
    require base_path('routes/escolar/carreras.php');
});

Route::prefix('ciudades')->group(function () {
    require base_path('routes/general/ciudades.php');
});

Route::prefix('contactos')->group(function () {
    require base_path('routes/general/contactos.php');
});  
  
Route::prefix('documentos')->group(function () {
    require base_path('routes/escolar/documentos.php');
});

Route::prefix('direcciones')->group(function () {
    require base_path('routes/general/direcciones.php');
});

Route::prefix('documentos')->group(function () {
    require base_path('routes/escolar/documentos.php');
});

Route::prefix('edocivil')->group(function () {
    require base_path('routes/general/edocivil.php');
});

Route::prefix('escolaridad')->group(function () {
    require base_path('routes/escolar/escolaridad.php');
});

Route::prefix('estados')->group(function () {
    require base_path('routes/general/estados.php');
});

Route::prefix('estados')->group(function () {
    require base_path('routes/general/estados.php');
});

Route::prefix('idiomas')->group(function () {
    require base_path('routes/escolar/idiomas.php');
});

Route::prefix('impuestos')->group(function () {
    require base_path('routes/tesoreria/impuestos.php');
});

Route::prefix('periodos')->group(function () {
    require base_path('routes/escolar/periodos.php');
});  

Route::prefix('medios')->group(function () {
    require base_path('routes/general/medios.php');
});

Route::prefix('modalidades')->group(function () {
    require base_path('routes/escolar/modalidades.php');
});

Route::prefix('modulos')->group(function () {
    require base_path('routes/seguridad/modulos.php');
});

Route::prefix('niveles')->group(function () {
    require base_path('routes/escolar/niveles.php');
});

Route::prefix('pais')->group(function () {
    require base_path('routes/general/pais.php');
});

Route::prefix('parentesco')->group(function () {
    require base_path('routes/general/parentesco.php');
});

Route::prefix('perfiles')->group(function () {
    require base_path('routes/seguridad/perfiles.php');
});

Route::prefix('permisosrol')->group(function () {
    require base_path('routes/seguridad/permisosrol.php');
});

Route::prefix('permisospersona')->group(function () {
    require base_path('routes/seguridad/permisospersona.php');
});
  

Route::prefix('personas')->group(function () {
    require base_path('routes/general/personas.php');
});

Route::prefix('puestos')->group(function () {
    require base_path('routes/general/puestos.php');
});

Route::prefix('rol')->group(function () {
    require base_path('routes/seguridad/rol.php');
});

Route::prefix('rolespersona')->group(function () {
    require base_path('routes/seguridad/rolespersona.php');
});

Route::prefix('rolesseguridad')->group(function () {
    require base_path('routes/seguridad/rolesseguridad.php');
});

Route::prefix('tipoasignatura')->group(function () {
    require base_path('routes/escolar/tipoasignatura.php');
});

Route::prefix('salud')->group(function () {
    require base_path('routes/general/salud.php');    
});

Route::prefix('tipocontacto')->group(function () {
    require base_path('routes/general/tipocontacto.php');
});

Route::prefix('turnos')->group(function () {
    require base_path('routes/escolar/turnos.php');
});

Route::prefix('usuarios')->group(function () {
    require base_path('routes/general/usuarios.php');
});
   
#Este es un comentario para probar el fech y el pull..
