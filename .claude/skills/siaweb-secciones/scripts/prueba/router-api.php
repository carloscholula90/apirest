<?php
// Sirve apirest contra una base SQLite desechable (prueba.sqlite, junto a este archivo). Nunca toca MySQL.
// Uso: C:/xampp/php/php.exe -S 127.0.0.1:8000 router-api.php
$base = 'C:/xampp/htdocs/apirest';

chdir($base);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

config([
    'database.default' => 'siaweb_prueba',
    'database.connections.siaweb_prueba' => ['driver' => 'sqlite', 'database' => __DIR__.'/prueba.sqlite', 'prefix' => ''],
]);
Illuminate\Support\Facades\DB::purge('siaweb_prueba');
// SQLite no trae CONCAT, y la búsqueda de empleados lo usa
Illuminate\Support\Facades\DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', function (...$p) { return implode('', $p); });

$req = Illuminate\Http\Request::capture();
$res = $kernel->handle($req);
$res->send();
$kernel->terminate($req, $res);
