<?php

namespace App\Http\Controllers\Api\serviciosGenerales;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ResultadoCargaExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected array $resultados;

    public function __construct(array $resultados)
    {
        $this->resultados = $resultados;
    }

    public function array(): array
    {
        return array_map(function ($item) {
            return [
                'renglon' => $item['renglon'] ?? '',
                'estatus' => $item['estatus'] ?? '',
                'mensaje' => $item['mensaje'] ?? '',
                'uid'      => $item['uid'] ?? '',
            ];
        }, $this->resultados);
    }

    public function headings(): array
    {
        return [
            'Renglón',
            'Estatus',
            'Mensaje',
            'UID'
        ];
    }

    /**
     * Estilo del encabezado
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12
                ]
            ]
        ];
    }

    /**
     * Colorear filas según el estatus
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $ultimaFila = count($this->resultados) + 1;

                // Encabezado
                $sheet->getStyle('A1:D1')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9EAD3']
                    ],
                    'font' => [
                        'bold' => true
                    ]
                ]);

                // Recorrer filas
                for ($fila = 2; $fila <= $ultimaFila; $fila++) {

                    $estatus = strtoupper(
                        trim((string)$sheet->getCell("B{$fila}")->getValue())
                    );

                    // Verde para OK
                    if ($estatus === 'OK') {

                        $sheet->getStyle("A{$fila}:D{$fila}")
                            ->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'D9EAD3']
                                ]
                            ]);
                    }

                    // Rojo para ERROR
                    if ($estatus === 'ERROR') {

                        $sheet->getStyle("A{$fila}:D{$fila}")
                            ->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F4CCCC']
                                ]
                            ]);
                    }
                }
            }
        ];
    }
}