<?php
/*
 * Copia a una base SQLite local (datos/siaweb_local.sqlite) las tablas que usan las pantallas de /secciones.
 * Solo LEE de siaweb_pruebas; nunca escribe ahí. Volver a correrlo deja la copia igual que el servidor.
 *
 * Uso: C:/xampp/php/php.exe entorno-local/copiar-datos.php
 */

$base = dirname(__DIR__);
chdir($base);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$origen = DB::connection();
$bd = $origen->selectOne('SELECT DATABASE() d')->d;

// Candado: solo se copia desde la base de pruebas
if ($bd !== 'siaweb_pruebas') {
    fwrite(STDERR, "La conexion por defecto apunta a '$bd', no a siaweb_pruebas. No se copia nada.\n");
    exit(1);
}

// Tablas de Oferta de asignaturas y del esquema de pago. Filtro opcional por tabla.
$tablas = [
    'nivel' => null, 'periodo' => null, 'carrera' => null, 'plan' => null, 'detasignatura' => null, 'alumno' => null,
    'asignatura' => null, 'grupos' => null, 'horarios' => null, 'turno' => null, 'dtlTurno' => null,
    'defaultTurno' => null, 'tipoBloque' => null, 'formato' => null, 'duracionSesion' => null,
    'diasFestivos' => null, 'persona' => null, 'empleado' => null, 'pais' => null, 'edoCivil' => null,
    'estado' => null, 'ciudad' => null, 'horarioProfesor' => null, 'calendarioPago' => null,
    'regimenFiscalNomina' => null, 'costoHoraRegimen' => null, 'regimenFiscalImpuesto' => null,
    'impuestos' => null, 'pago' => null, 'detallePago' => null,
    // Firmantes predeterminados del acta por nivel (uids de persona)
    'configuracionActas' => null,
];

$carpeta = __DIR__.'/datos';
if (!is_dir($carpeta)) {
    mkdir($carpeta);
}
$archivo = $carpeta.'/siaweb_local.sqlite';
$temporal = $archivo.'.nuevo';
@unlink($temporal);

$destino = new PDO('sqlite:'.$temporal);
$destino->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$destino->exec('PRAGMA journal_mode = OFF; PRAGMA synchronous = OFF;');

function tipoSqlite($tipo)
{
    $tipo = strtolower($tipo);
    if (preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)/', $tipo)) return 'INTEGER';
    if (preg_match('/^(decimal|numeric|float|double|real)/', $tipo)) return 'NUMERIC';
    return 'TEXT';
}

$inicio = microtime(true);

foreach ($tablas as $tabla => $filtro) {
    $existe = $origen->selectOne('SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$tabla])->n;
    if (!$existe) {
        echo str_pad($tabla, 24)."no existe en $bd, se omite\n";
        continue;
    }

    $columnas = $origen->select("SHOW COLUMNS FROM `$tabla`");
    $indices = $origen->select("SHOW INDEX FROM `$tabla`");
    $llave = array_map(function ($i) { return $i->Column_name; }, array_filter($indices, function ($i) { return $i->Key_name === 'PRIMARY'; }));
    $auto = null;
    foreach ($columnas as $c) {
        if (stripos($c->Extra, 'auto_increment') !== false) $auto = $c->Field;
    }

    // Misma estructura: tipos equivalentes, NOT NULL, valores por omisión, llave e índices únicos
    $definiciones = [];
    foreach ($columnas as $c) {
        $d = '"'.$c->Field.'" ';
        if ($c->Field === $auto) {
            $definiciones[] = $d.'INTEGER PRIMARY KEY AUTOINCREMENT';
            continue;
        }
        $d .= tipoSqlite($c->Type);
        if ($c->Null === 'NO') $d .= ' NOT NULL';
        if ($c->Default !== null) {
            $d .= stripos($c->Default, 'current_timestamp') !== false ? ' DEFAULT CURRENT_TIMESTAMP' : ' DEFAULT '.$destino->quote($c->Default);
        }
        $definiciones[] = $d;
    }
    if (!$auto && $llave) {
        $definiciones[] = 'PRIMARY KEY ("'.implode('", "', $llave).'")';
    }
    $destino->exec('CREATE TABLE "'.$tabla.'" ('.implode(', ', $definiciones).')');

    $unicos = [];
    foreach ($indices as $i) {
        if ($i->Key_name !== 'PRIMARY' && !$i->Non_unique) $unicos[$i->Key_name][] = $i->Column_name;
    }
    foreach ($unicos as $nombre => $cols) {
        $destino->exec('CREATE UNIQUE INDEX "'.$tabla.'_'.$nombre.'" ON "'.$tabla.'" ("'.implode('", "', $cols).'")');
    }

    $nombres = array_map(function ($c) { return $c->Field; }, $columnas);
    $insertar = $destino->prepare('INSERT INTO "'.$tabla.'" ("'.implode('", "', $nombres).'") VALUES ('.implode(', ', array_fill(0, count($nombres), '?')).')');

    $consulta = $origen->table($tabla);
    if ($filtro) $filtro($consulta);

    $total = 0;
    $destino->beginTransaction();
    foreach ($consulta->cursor() as $fila) {
        $insertar->execute(array_values((array) $fila));
        $total++;
    }
    $destino->commit();

    echo str_pad($tabla, 24).$total." filas\n";
}

// En Windows el archivo queda bloqueado mientras exista la conexión o una sentencia preparada
$insertar = null;
$destino = null;
gc_collect_cycles();
if (is_file($archivo) && !@unlink($archivo)) {
    fwrite(STDERR, "No se pudo reemplazar $archivo; cierra los servidores locales y vuelve a intentar.\n");
    @unlink($temporal);
    exit(1);
}
rename($temporal, $archivo);

printf("Copia lista en %s (%.1f MB, %.0f s)\n", $archivo, filesize($archivo) / 1048576, microtime(true) - $inicio);
