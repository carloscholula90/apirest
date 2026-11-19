<?php

namespace App\Http\Controllers\Api\escolar;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

// Excel del reporte de alumnos inscritos por rango de fechas (alumno.fechaAlta)
class ReporteAlumnosInscritosExport implements FromCollection, ShouldAutoSize
{
    protected $filas;
    protected $rango;

    public function __construct(array $filas, string $rango)
    {
        $this->filas = $filas;
        $this->rango = $rango;
    }

    public function collection()
    {
        $rows = [];
        $rows[] = ['REPORTE DE ALUMNOS INSCRITOS'];
        $rows[] = [$this->rango];
        $rows[] = [''];
        $rows[] = ['UID', 'NOMBRE', 'CARRERA', 'FECHA DE INSCRIPCIÓN'];

        foreach ($this->filas as $fila) {
            $rows[] = [$fila['uid'], $fila['nombre'], $fila['carrera'], $fila['fechaInscripcion']];
        }

        $rows[] = [''];
        $rows[] = ['TOTAL DE ALUMNOS', count($this->filas)];

        return collect($rows);
    }
}
