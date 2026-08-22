<?php

namespace App\Http\Controllers\Api\escolar;

use Maatwebsite\Excel\Concerns\FromCollection; // o FromArray
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReporteConcentradoExport implements FromCollection,  ShouldAutoSize, WithColumnFormatting
{
    protected $data;
    protected $headers;
    protected $idPeriodo;
    protected $totalesSemestre;

    public function __construct($data, $headers, $idPeriodo, $totalesSemestre = [])
    {
        $this->data = $data;       // array de filas
        $this->headers = $headers; // nombres de columnas
        $this->idPeriodo = $idPeriodo;
        $this->totalesSemestre = $totalesSemestre;
    }
    /**
     * Retorna los datos como colección
     */
    public function collection()
    {
        // Agregamos una fila inicial con el PERIODO
        $rows = [];
        $rows[] = ['CONCENTRADO DE INSCRITOS POR ESCUELA '];
        $rows[] = ['PERIODO '.$this->idPeriodo];
        $rows[] = array_map('mb_strtoupper', $this->headers);  

        foreach ($this->data as $row) {
            $fila = [];
            foreach ($this->headers as $h) {
                $fila[] = $row[$h] ?? '';
            }
            $rows[] = $fila;
        }

        // Total general al final
        $total = array_sum(array_column($this->data, 'total'));
        $rows[] = ['TOTAL ALUMNOS', $total];

        $rows[] = [];
        $rows[] = ['CONCENTRADO POR SEMESTRE'];
        $rows[] = ['SEMESTRE', 'TOTAL'];

        $totalSemestres = 0;
        foreach ($this->totalesSemestre as $row) {
            $totalSemestres += $row['total'];
            $rows[] = [
                $row['semestre'],
                $row['total'],
            ];
        }

        $rows[] = ['TOTAL GENERAL', $totalSemestres];

        return collect($rows);
    }
    /**
     * Encabezados de columnas (solo se usan si no quieres fila PERIODO arriba)
     */
    public function headings(): array
    {
        return $this->headers;
    }
    /**
     * Opcional: formato de columnas (moneda, número)
     */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER, // columna total
        ];
    }
}
