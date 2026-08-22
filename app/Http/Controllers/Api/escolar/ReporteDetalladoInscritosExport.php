<?php

namespace App\Http\Controllers\Api\escolar;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReporteDetalladoInscritosExport implements FromCollection, ShouldAutoSize
{
    protected $data;
    protected $periodo;
    protected $carreras;
    protected $totalesSemestre;

    public function __construct($data, $periodo, $carreras, $totalesSemestre)
    {
        $this->data = $data;
        $this->periodo = $periodo;
        $this->carreras = $carreras;
        $this->totalesSemestre = $totalesSemestre;
    }

    public function collection()
    {
        $rows = [];
        $rows[] = ['DETALLADO DE INSCRITOS POR ESCUELA'];
        $rows[] = ['PERIODO ' . $this->periodo];
        $rows[] = ['CARRERA(S): ' . implode(', ', $this->carreras)];
        $rows[] = [];
        $rows[] = ['UID', 'NOMBRE', 'GRUPO'];

        foreach ($this->data as $row) {
            $rows[] = [
                $row['uid'] ?? '',
                $row['nombre'] ?? '',
                $row['grupo'] ?? '',
            ];
        }

        $rows[] = [];
        $rows[] = ['CONCENTRADO POR SEMESTRE'];
        $rows[] = ['SEMESTRE', 'TOTAL'];

        $totalGeneral = 0;
        foreach ($this->totalesSemestre as $row) {
            $totalGeneral += $row['total'];
            $rows[] = [
                $row['semestre'],
                $row['total'],
            ];
        }

        $rows[] = ['TOTAL GENERAL', $totalGeneral];

        return collect($rows);
    }
}
