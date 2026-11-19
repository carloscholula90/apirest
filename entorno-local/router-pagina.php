<?php
/*
 * Layout simulado de SIAWEB para abrir las pantallas de frontend/<nombre>/<nombre>.php como /secciones/<nombre>,
 * apuntando a la API local. Las imágenes de /secciones/img se toman del servidor de pruebas.
 *
 * Uso: C:/xampp/php/php.exe -S 127.0.0.1:8001 entorno-local/router-pagina.php
 */

$frontend = dirname(__DIR__).'/frontend';
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Logotipos y demás imágenes que las pantallas piden con ruta relativa (img/...)
if (preg_match('#^/secciones/(img/[A-Za-z0-9_./-]+)$#', $ruta, $m) && strpos($m[1], '..') === false) {
    header('Location: https://pruebas.siaweb.com.mx/secciones/'.$m[1], true, 302);
    return true;
}

// Índice con las pantallas disponibles
if ($ruta === '/' || $ruta === '/secciones' || $ruta === '/secciones/') {
    $pantallas = [];
    foreach (glob($frontend.'/*', GLOB_ONLYDIR) as $dir) {
        $nombre = basename($dir);
        if (is_file("$dir/$nombre.php")) $pantallas[] = $nombre;
    }
    ?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIAWEB local</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap">
</head>
<body style="font-family:'Titillium Web',sans-serif;padding:32px;max-width:720px">
<h1 style="font-size:22px;font-weight:700">SIAWEB local</h1>
<p class="text-muted">API local en <a href="http://127.0.0.1:8000/api/niveles">127.0.0.1:8000</a> con la copia de siaweb_pruebas. Lo que guardes aquí no llega al servidor.</p>
<div class="list-group">
<?php foreach ($pantallas as $p): ?>
  <a class="list-group-item list-group-item-action" href="/secciones/<?= htmlspecialchars($p) ?>">/secciones/<?= htmlspecialchars($p) ?></a>
<?php endforeach; ?>
</div>
</body></html>
<?php
    return true;
}

if (!preg_match('#^/secciones/([a-z0-9-]+)$#', $ruta, $m)) {
    http_response_code(404);
    echo 'No encontrado';
    return true;
}

$archivo = $frontend.'/'.$m[1].'/'.$m[1].'.php';
if (!is_file($archivo)) {
    http_response_code(404);
    echo 'No existe frontend/'.htmlspecialchars($m[1]).'/'.htmlspecialchars($m[1]).'.php';
    return true;
}

putenv('SIAWEB_API_URL=http://127.0.0.1:8000/api');
// crud/utils.php y crud/get.php del layout real se sustituyen por archivos vacíos
chdir(__DIR__.'/stubs');
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($m[1]) ?> · SIAWEB local</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap">
  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body style="font-family:'Titillium Web',sans-serif;padding:24px">
<div style="margin-bottom:16px;font-size:13px;color:#5c665d"><a href="/">← Pantallas</a> · Ambiente local (copia de siaweb_pruebas)</div>
<?php include $archivo; ?>
</body>
</html>
