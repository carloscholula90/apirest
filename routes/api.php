<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\serviciosGenerales\reporteController;

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


Route::prefix('aplicaciones')->group(function () {
    require base_path('routes/seguridad/aplicaciones.php');
});

Route::prefix('asentamientos')->group(function () {
    require base_path('routes/general/asentamientos.php');
});

Route::prefix('carreras')->group(function () {
    require base_path('routes/escolar/carreras.php');
});

Route::prefix('edocivil')->group(function () {
    require base_path('routes/general/edocivil.php');
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

Route::prefix('personas')->group(function () {
    require base_path('routes/general/personas.php');
});

Route::prefix('rolesseguridad')->group(function () {
    require base_path('routes/seguridad/rolesseguridad.php');
});

Route::prefix('usuarios')->group(function () {
    require base_path('routes/general/usuarios.php');
});

Route::post('/generate-report', [reporteController::class, 'generateReport']);

#Este es un comentario para probar el fech y el pull..
