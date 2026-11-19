<?php
/*
 * Genera las pantallas de /secciones del esquema de pago a partir del prototipo de esta carpeta.
 * Cada pantalla queda en frontend/<nombre>/<nombre>.php, autosuficiente: estilos, utilerias comunes y JS en linea.
 *
 * Uso: C:/xampp/php/php.exe frontend/esquema-pago/generar-secciones.php
 *
 * Se excluyen index.html (el menu lo da el layout) y esquema-pago-demo.js.
 */

$base = __DIR__;
$pantallas = ['turnos-bloques', 'horario-profesor', 'configuracion-fiscal', 'procesamiento-pagos'];

// Prefijo para no chocar con ids genericos del layout (#alerta-general, #sel-turno...)
const PREFIJO = 'ep-';

$css = file_get_contents("$base/css/esquema-pago.css");
$comun = file_get_contents("$base/js/esquema-pago-comun.js");

function entre($texto, $desde, $hasta, $archivo)
{
    $i = strpos($texto, $desde);
    $j = $i === false ? false : strpos($texto, $hasta, $i + strlen($desde));

    if ($i === false || $j === false) {
        fwrite(STDERR, "No se encontro '$desde' ... '$hasta' en $archivo\n");
        exit(1);
    }

    return substr($texto, $i + strlen($desde), $j - $i - strlen($desde));
}

// Dentro de <script> un "</script" literal cierra la etiqueta aunque este en un comentario
function seguroEnScript($js)
{
    return str_ireplace('</script', '<\/script', $js);
}

function renombrarIds($html, $js, $archivo)
{
    preg_match_all('/\bid="([^"]+)"/', $html, $m);
    $mapa = [];
    foreach (array_unique($m[1]) as $id) {
        if (strpos($id, PREFIJO) !== 0) {
            $mapa[$id] = PREFIJO . $id;
        }
    }

    // Los mas largos primero para que "tipo" no toque "tipo-color"
    uksort($mapa, function ($a, $b) {
        return strlen($b) - strlen($a);
    });

    $atributos = function ($texto) use ($mapa) {
        $texto = preg_replace_callback('/\b(id|for|aria-labelledby|aria-describedby|aria-controls|list|form)="([^"]+)"/', function ($c) use ($mapa) {
            return $c[1] . '="' . ($mapa[$c[2]] ?? $c[2]) . '"';
        }, $texto);

        return preg_replace_callback('/\b(data-bs-target|href)="#([^"]+)"/', function ($c) use ($mapa) {
            return $c[1] . '="#' . ($mapa[$c[2]] ?? $c[2]) . '"';
        }, $texto);
    };

    $html = $atributos($html);
    $js = $atributos($js);

    foreach ($mapa as $viejo => $nuevo) {
        $js = preg_replace('/#' . preg_quote($viejo, '/') . '(?![\w-])/', '#' . $nuevo, $js);
        $js = preg_replace('/getElementById\(([\'"])' . preg_quote($viejo, '/') . '\1\)/', 'getElementById($1' . $nuevo . '$1)', $js);
    }

    // Lo que quede sin prefijo en un selector del JS es un id que el HTML no declara: avisar
    preg_match_all('/[\'"]#([a-zA-Z][\w-]*)/', $js, $sueltos);
    foreach (array_unique($sueltos[1]) as $id) {
        if (strpos($id, PREFIJO) !== 0) {
            fwrite(STDERR, "Aviso $archivo: el JS usa #$id y el HTML no lo declara\n");
        }
    }

    return [$html, $js, count($mapa)];
}

foreach ($pantallas as $nombre) {
    $html = file_get_contents("$base/$nombre.html");
    $js = file_get_contents("$base/js/$nombre.js");

    $principal = entre($html, '<main class="main-content">', '</main>', "$nombre.html");
    $despues = entre($html, '</main>', '<script', "$nombre.html");

    // El layout ya da contenedor y margenes; solo se conserva el alcance de estilos
    $principal = str_replace('class="container-fluid content-inner py-4 siaweb-ep"', 'class="siaweb-ep"', $principal);

    [$marcado, $js, $renombrados] = renombrarIds(rtrim($principal) . "\n\n  " . trim($despues), $js, $nombre);

    $salida = <<<PHP
<?php
// Llamar al CRUD/UTILS
include 'crud/utils.php';
// Llamar al CRUD/GET
include 'crud/get.php';

// Dirección de apirest; en otro servidor se cambia con la variable de entorno SIAWEB_API_URL
\$apiUrl = rtrim(getenv('SIAWEB_API_URL') ?: 'https://api.pruebas.siaweb.com.mx/api', '/');

// Generado con frontend/esquema-pago/generar-secciones.php; los cambios se hacen en el prototipo y se vuelve a generar
?>
<body>
  <style>
{$css}
  </style>

{$marcado}

  <script>
    window.SIAWEB_API_URL = <?= json_encode(\$apiUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  </script>
  <script>
{$comun}
  </script>
  <script>
    // Modales y hoja de impresion van directo en body para que el layout no los recorte
    $('.siaweb-ep-modal, .ep-impresion').appendTo('body');
  </script>
  <script>
{$js}
  </script>
</body>

PHP;

    // Lo que viene del prototipo no debe abrir codigo PHP ni cerrar etiquetas script
    $salida = str_replace(["{$comun}", "{$js}"], [seguroEnScript($comun), seguroEnScript($js)], $salida);
    if (substr_count($salida, '<?') !== 2) {
        fwrite(STDERR, "$nombre: el prototipo contiene '<?' y PHP lo interpretaria\n");
        exit(1);
    }

    $carpeta = dirname($base) . "/$nombre";
    if (!is_dir($carpeta)) {
        mkdir($carpeta);
    }
    file_put_contents("$carpeta/$nombre.php", $salida);

    echo "$nombre/$nombre.php: $renombrados ids con prefijo, " . number_format(strlen($salida) / 1024, 1) . " KB\n";
}
