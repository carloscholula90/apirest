<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/*
 * Reporte de alumnos inscritos entre dos fechas: todos los registros de alumno cuya fechaAlta cae en el rango
 * (sin importar si siguen activos), con uid, nombre, carrera y fecha de inscripción. Sale en PDF o en Excel;
 * cada generación usa un nombre de archivo único para que el visor no muestre una copia en caché.
 */
class ReporteAlumnosInscritosController extends Controller
{
    protected $pdfController;

    public function __construct(pdfController $pdfController)
    {
        $this->pdfController = $pdfController;
    }

    // GET /alumnos/inscritosFechas/{fechaInicio}/{fechaFin}
    public function pdf($fechaInicio, $fechaFin)
    {
        $error = $this->validaFechas($fechaInicio, $fechaFin);
        if ($error) {
            return $error;
        }

        $filas = $this->alumnos($fechaInicio, $fechaFin);
        if (!$filas) {
            return $this->returnEstatus('No hay alumnos inscritos del '.$this->fecha($fechaInicio).' al '.$this->fecha($fechaFin), 404, null);
        }

        return $this->pdfController->generateReport(
            $filas,
            [70, 230, 190, 80],
            ['uid', 'nombre', 'carrera', 'fechaInscripcion'],
            'ALUMNOS INSCRITOS DEL '.$this->fecha($fechaInicio).' AL '.$this->fecha($fechaFin).' ('.count($filas).')',
            ['UID', 'NOMBRE', 'CARRERA', 'FECHA DE INSCRIPCIÓN'],
            'P',
            'letter',
            $this->nombreReporte('rptAlumnosInscritos', 'pdf')
        );
    }

    // GET /alumnos/inscritosFechasExc/{fechaInicio}/{fechaFin}
    public function excel($fechaInicio, $fechaFin)
    {
        $error = $this->validaFechas($fechaInicio, $fechaFin);
        if ($error) {
            return $error;
        }

        $filas = $this->alumnos($fechaInicio, $fechaFin);
        if (!$filas) {
            return $this->returnEstatus('No hay alumnos inscritos del '.$this->fecha($fechaInicio).' al '.$this->fecha($fechaFin), 404, null);
        }

        $nombre = $this->nombreReporte('alumnosInscritosRpt', 'xlsx');
        $rango = 'DEL '.$this->fecha($fechaInicio).' AL '.$this->fecha($fechaFin);
        Excel::store(new ReporteAlumnosInscritosExport($filas, $rango), $nombre, 'public');

        if (!file_exists(storage_path('app/public/'.$nombre))) {
            return response()->json(['status' => 500, 'message' => 'Error al generar el reporte'], 500);
        }

        return response()->json([
            'status' => 200,
            'message' => 'https://reportes.siaweb.com.mx/storage/app/public/'.$nombre,
        ]);
    }

    // Fechas AAAA-MM-DD válidas y la de inicio no posterior a la de fin
    private function validaFechas($fechaInicio, $fechaFin)
    {
        $errores = [];
        foreach (['fechaInicio' => [$fechaInicio, 'inicio'], 'fechaFin' => [$fechaFin, 'fin']] as $campo => [$valor, $nombre]) {
            $fecha = \DateTime::createFromFormat('!Y-m-d', (string) $valor);
            if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
                $errores[$campo] = ['La fecha de '.$nombre.' debe tener el formato AAAA-MM-DD.'];
            }
        }

        if (!$errores && $fechaInicio > $fechaFin) {
            $errores['fechaFin'] = ['La fecha de fin no puede ser anterior a la fecha de inicio.'];
        }

        return $errores ? $this->returnEstatus('Error en la validación de los datos', 400, $errores) : null;
    }

    // Alumnos con fechaAlta dentro del rango (días completos); los que no tienen carrera en el catálogo salen sin ella
    private function alumnos($fechaInicio, $fechaFin)
    {
        return DB::table('alumno as a')
            ->join('persona as p', 'p.uid', '=', 'a.uid')
            ->leftJoin('carrera as c', function ($join) {
                $join->on('c.idNivel', '=', 'a.idNivel')
                    ->on('c.idCarrera', '=', 'a.idCarrera');
            })
            ->whereBetween('a.fechaAlta', [$fechaInicio.' 00:00:00', $fechaFin.' 23:59:59'])
            ->orderBy('a.fechaAlta')
            ->orderBy('a.uid')
            ->get(['a.uid', 'p.primerApellido', 'p.segundoApellido', 'p.nombre', 'c.descripcion as carrera', 'a.fechaAlta'])
            ->map(function ($fila) {
                $partes = [$fila->primerApellido, $fila->segundoApellido, $fila->nombre];

                return [
                    'uid' => (int) $fila->uid,
                    'nombre' => trim(implode(' ', array_filter(array_map('trim', $partes)))),
                    'carrera' => trim((string) $fila->carrera),
                    'fechaInscripcion' => date('d/m/Y', strtotime($fila->fechaAlta)),
                ];
            })
            ->all();
    }

    private function fecha($iso)
    {
        return date('d/m/Y', strtotime($iso));
    }

    // Nombre único (fecha, hora de México y secuencial aleatorio); borra copias de más de un día
    private function nombreReporte($base, $extension)
    {
        foreach (glob(storage_path('app/public/'.$base.'_*.'.$extension)) ?: [] as $anterior) {
            if (filemtime($anterior) < time() - 86400) {
                @unlink($anterior);
            }
        }

        $fecha = (new \DateTime('now', new \DateTimeZone('America/Mexico_City')))->format('Ymd_His');

        return $base.'_'.$fecha.'_'.bin2hex(random_bytes(3)).'.'.$extension;
    }
}
