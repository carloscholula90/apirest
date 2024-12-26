<?php
namespace App\Http\Controllers\Api\serviciosGenerales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function generateReport(array $data, array $columnWidths = null, array $keys = null,string $title = 'Reporte PDF', array $headers = null,string $orientation = 'P', string $size = 'letter')
    {
        $imagePathEnc = public_path('images/encPag.png');   
        $imagePathPie = public_path('images/piePag.png');

        $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
        $pdf->setHeaders($headers, $columnWidths, $title);
        $pdf->setImagePaths($imagePathEnc, $imagePathPie, $orientation);
        $pdf->SetFont('TitilliumWeb-Bold', '', 14);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');
        $pdf->SetMargins(15, 55, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();

        $pdf->SetFont('TitilliumWeb-Regular', '', 12);
        $html2 = '<table border="0" cellpadding="5">';

        foreach ($data as $row) {
            $html2 .= '<tr>';
            foreach ($keys as $index => $key) {
                $value = isset($row[$key]) ? $row[$key] : '';
                if (strtotime($value) === true) {
                    $value = (new \DateTime($value))->format('d/m/Y');
                }
                $html2 .= '<td width="' . $columnWidths[$index] . '">' . htmlspecialchars((string)$value) . '</td>';
            }
            $html2 .= '</tr>';
        }
        $html2 .= '</table>';
        $pdf->writeHTML($html2, true, false, true, false, '');

        // Devolver el PDF como respuesta
        return response($pdf->Output('reporte.pdf', 'S')) // 'S' devuelve el PDF como string
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="reporte.pdf"');
    }
}  
