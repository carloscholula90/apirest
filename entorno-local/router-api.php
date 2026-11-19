<?php
/*
 * apirest local: el código del repositorio contra la copia SQLite (datos/siaweb_local.sqlite).
 * Todas las conexiones de la configuración se redirigen a esa copia, así que nada llega a MySQL.
 *
 * Uso: C:/xampp/php/php.exe -S 127.0.0.1:8000 entorno-local/router-api.php
 */

$base = dirname(__DIR__);
$datos = __DIR__.'/datos/siaweb_local.sqlite';

if (!is_file($datos)) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Falta la copia local; corre entorno-local/copiar-datos.php', 'status' => 503]);
    return true;
}

chdir($base);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$local = ['driver' => 'sqlite', 'database' => $datos, 'prefix' => '', 'foreign_key_constraints' => false];
$conexiones = ['siaweb_local' => $local];
foreach (array_keys(config('database.connections')) as $nombre) {
    $conexiones[$nombre] = $local;
}
config(['database.default' => 'siaweb_local', 'database.connections' => $conexiones]);
Illuminate\Support\Facades\DB::purge();

// Funciones de MySQL que usan los controladores y SQLite no trae
$pdo = Illuminate\Support\Facades\DB::connection()->getPdo();
$pdo->sqliteCreateFunction('CONCAT', function (...$p) {
    return in_array(null, $p, true) ? null : implode('', $p); // como MySQL: un nulo vuelve nulo todo
});
$pdo->sqliteCreateFunction('CONCAT_WS', function ($sep, ...$p) {
    return implode($sep, array_filter($p, function ($v) { return $v !== null; }));
});
$pdo->sqliteCreateFunction('IF', function ($c, $si, $no) { return $c ? $si : $no; }, 3);

// CORS para abrir las pantallas desde otro puerto
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
    return true;
}

// Como el servidor real: el encabezado sale antes de Laravel, porque pdfController hace flush() y envía
// los encabezados antes de que la respuesta regrese aquí
header('Access-Control-Allow-Origin: *');

$req = Illuminate\Http\Request::capture();
$res = $kernel->handle($req);
$res->headers->set('Access-Control-Allow-Origin', '*');

// Bitácora en datos/peticiones.log para revisar qué llegó y qué respondió la API
$linea = (new DateTime('now', new DateTimeZone('America/Mexico_City')))->format('Y-m-d H:i:s').' '.$req->method().' '.$req->getRequestUri().' -> '.$res->getStatusCode();
if ($req->method() !== 'GET' && $req->getContent() !== '') {
    $linea .= ' datos='.substr(preg_replace('/\s+/', ' ', $req->getContent()), 0, 300);
}
if ($res->getStatusCode() >= 400 || $req->method() !== 'GET') {
    $cuerpo = json_decode($res->getContent(), true);
    if (isset($cuerpo['message'])) $linea .= ' mensaje='.substr($cuerpo['message'], 0, 300);
}
@file_put_contents(__DIR__.'/datos/peticiones.log', $linea.PHP_EOL, FILE_APPEND);

// Si nadie mandó los encabezados aún, se quita el de arriba: Laravel manda el suyo y el navegador rechaza el duplicado
if (!headers_sent()) {
    header_remove('Access-Control-Allow-Origin');
}
$res->send();
$kernel->terminate($req, $res);
