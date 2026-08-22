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
    protected $titleRows = [];

    public function __construct($data, $headers, $keys, $titleRows = [])
    {
        $this->data = collect($data);
        $this->headers = $headers;
        $this->keys = $keys;
        $this->titleRows = $titleRows;
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
    return collect($this->keys)->map(function ($k) use ($row) {
        if (is_array($row)) {
            return $row[$k] ?? '';
        }

        return data_get($row, $k, '');
    })->toArray();
}

   public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();
            $columnCount = count($this->headers);

            // Última columna (importe normalmente)
            $lastColumn = chr(64 + $columnCount);

            if (!empty($this->titleRows)) {
                $sheet->insertNewRowBefore(1, count($this->titleRows));

                foreach ($this->titleRows as $index => $title) {
                    $rowNumber = $index + 1;
                    $sheet->setCellValue("A{$rowNumber}", $title);
                    $sheet->mergeCells("A{$rowNumber}:{$lastColumn}{$rowNumber}");
                    $sheet->getStyle("A{$rowNumber}:{$lastColumn}{$rowNumber}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => $index === 0 ? 13 : 11,
                        ],
                    ]);
                    $sheet->getStyle("A{$rowNumber}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }

            // Columna antes de la última (para combinar sin tocar importe)
            $beforeLastColumn = chr(63 + $columnCount);

            foreach ($this->cutRows as $row) {

                // 🔥 Combinar TODAS menos la última columna (importe)
                $sheet->mergeCells("A{$row}:{$beforeLastColumn}{$row}");

                // 🎨 Estilo general de toda la fila
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EDEDED'],
                    ],
                ]);

                // 📌 Alineación texto (TOTAL ...)
                $sheet->getStyle("A{$row}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // 📌 Alineación importe (derecha)
                $sheet->getStyle("{$lastColumn}{$row}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
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
