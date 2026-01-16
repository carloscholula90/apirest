<?php  
namespace App\Http\Controllers\Api\serviciosGenerales;  

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class GenericExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $data;
    protected $headers;
    protected $keys;
    protected $cutRows = [];

    public function __construct($data, $headers, $keys)
    {
        $this->data = collect($data);
        $this->headers = $headers;
        $this->keys = $keys;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function map($row): array
    {
        return collect($this->keys)->map(fn($k) => $row[$k] ?? '')->toArray();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $columnCount = count($this->headers);
                $lastColumn = chr(64 + $columnCount); // A,B,C...

                foreach ($this->cutRows as $row) {
                    // Combinar celdas
                    $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");

                    // Estilo del corte
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 11,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'EDEDED'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                        ],
                    ]);
                }
            }
        ];
    }

    /**
     * Marca una fila como corte
     */
    public function addCutRow($rowNumber)
    {
        $this->cutRows[] = $rowNumber;
    }
}
