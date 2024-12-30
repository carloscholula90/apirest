<?php
namespace App\Http\Controllers\Api\serviciosGenerales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function generateReport(array $data, array $columnWidths = null, array $keys = null, string $title = 'Reporte PDF', array $headers = null, string $orientation = 'P', string $size = 'letter',string $nameReport=null)
    {
        // Rutas de las imágenes para el encabezado y pie
        $imagePathEnc = public_path('images/encPag.png');
        $imagePathPie = public_path('images/piePag.png');

        // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)
        $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
        
        // Configurar los encabezados, las rutas de las imágenes y otros parámetros
        $pdf->setHeaders($headers, $columnWidths, $title);
        $pdf->setImagePaths($imagePathEnc, $imagePathPie, $orientation);
        
        // Configurar las fuentes
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');
        
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(15, 55, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();

        // Establecer fuente para el cuerpo del documento
        $pdf->SetFont('helvetica', '', 12);
        
        // Generar la tabla HTML para los datos
        $html2 = '<table border="0" cellpadding="5">';
        foreach ($data as $row) {
            $html2 .= '<tr>';
            foreach ($keys as $index => $key) {
                $value = isset($row[$key]) ? $row[$key] : '';
                // Formatear las fechas si se encuentra en el dato
                if (strtotime($value) !== false) {
                    $value = (new \DateTime($value))->format('d/m/Y');
                }
                $html2 .= '<td width="' . $columnWidths[$index] . '">' . htmlspecialchars((string)$value) . '</td>';
            }
            $html2 .= '</tr>';
        }
        $html2 .= '</table>';

        // Escribir la tabla en el PDF
        $pdf->writeHTML($html2, true, false, true, false, '');

        if($nameReport==null)
            $filePath = storage_path('app/public/reporte.pdf');  // Ruta donde se guardará el archivo
        else $filePath = storage_path('app/public/'.$nameReport);  // Ruta donde se guardará el archivo
       
        $pdf->Output($filePath, 'F');  // 'F' para guardar el archivo en el servidor
    
        // Ahora puedes verificar si el archivo se ha guardado correctamente en la ruta especificada.
        if (file_exists($filePath)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Reporte generado correctamente',
                'filePath' => $filePath // Puedes devolver la ruta para fines de depuración
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al generar el reporte'
            ]);
        }    
    }
}
