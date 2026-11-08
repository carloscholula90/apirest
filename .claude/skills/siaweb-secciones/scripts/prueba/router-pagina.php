<?php
// Simula el layout de SIAWEB e incluye la pantalla /secciones/<nombre> desde frontend/<nombre>/<nombre>.php
// Uso: C:/xampp/php/php.exe -S 127.0.0.1:8001 router-pagina.php
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (!preg_match('#^/secciones/([a-z0-9-]+)$#', $ruta, $m)) {
    http_response_code(404);
    return true;
}

$archivo = 'C:/xampp/htdocs/apirest/frontend/'.$m[1].'/'.$m[1].'.php';
if (!is_file($archivo)) {
    http_response_code(404);
    echo 'No existe '.htmlspecialchars($archivo);
    return true;
}

putenv('SIAWEB_API_URL=http://127.0.0.1:8000/api');
// crud/utils.php y crud/get.php se toman de aquí como archivos vacíos
chdir(__DIR__.'/stubs');
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Layout de prueba</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap">
  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body style="font-family:'Titillium Web',sans-serif;padding:24px">
<?php include $archivo; ?>
</body>
</html>
