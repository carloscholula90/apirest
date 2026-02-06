<?php
namespace App\Http\Controllers\Api\serviciosGenerales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PdfController extends Controller
{
    public function generateReport(array $data, array $columnWidths = null, array $keys = null, string $title = 'Reporte PDF', array $headers = null, string $orientation = 'L', string $size = 'letter', string $nameReport = null)
{
  
    // Configurar límites de memoria y tiempo de ejecución para asegurar que no haya interrupciones
    //set_time_limit(300);  // 5 minutos
   // ini_set('memory_limit', '512M');  // Aumentar el límite de memoria
    
    // Rutas de las imágenes para el encabezado y pie
    $imagePathEnc = public_path('images/encPag.png');
    $imagePathPie = public_path('images/piePag.png');

    // Crear una nueva instancia de CustomTCPDF
    $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);

    // Configurar los encabezados, las rutas de las imágenes y otros parámetros
    $pdf->setHeaders($headers, $columnWidths, strtoupper($title));
    $pdf->setImagePaths($imagePathEnc, $imagePathPie, $orientation);

    // Configurar las fuentes
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SIAWEB');

    // Establecer márgenes y auto-rotura de página
    $pdf->SetMargins(15, 55, 15);
    $pdf->SetAutoPageBreak(TRUE, 25);
    $pdf->AddPage();

    // Establecer fuente para el cuerpo del documento
    $pdf->SetFont('helvetica', '', 10);

    // Iniciar el contenido HTML de la tabla
    $html2 = '<table border="0" cellpadding="5">';
    
    foreach ($data as $row) {
        $html2 .= '<tr>';
        foreach ($keys as $index => $key) {
            $value = isset($row[$key]) ? $row[$key] : '';
           $html2 .= '<td style="height: 0.3cm; width:' . $columnWidths[$index] . '">' . (isset($value) ? htmlspecialchars((string)$value) : '') . '</td>';

        }
        $html2 .= '</tr>';
    }
    $html2 .= '</table>';

    // Controlar el buffer de salida
    ob_start();
    ob_implicit_flush(true);  // Esto fuerza a PHP a no usar un búfer intermedio
    set_time_limit(600); // Establecer el tiempo de ejecución a 5 minutos (300 segundos)
    ini_set('memory_limit', '1G'); // Establecer el límite de memoria a 512 MB
    // Escribir la tabla en el PDF
    $pdf->writeHTML($html2, true, false, true, false, '');

    // Liberar el contenido del buffer y enviarlo al navegador o archivo
    ob_flush();
    flush();

    // Verificar el nombre del reporte y establecer la ruta de almacenamiento
    if ($nameReport == null) {
        $filePath = storage_path('app/public/reporte.pdf');  // Ruta por defecto
    } else {
        $filePath = storage_path('app/public/' . $nameReport);  // Ruta con nombre de archivo personalizado
    }

    // Generar el archivo PDF
    $pdf->Output($filePath, 'F');  // 'F' para guardar el archivo en el servidor

    // Verificar si el archivo se ha guardado correctamente
    if (file_exists($filePath)) {
        return response()->json([
            'status' => 200,
            'message' => 'https://reportes.pruebas.com.mx/storage/app/public/' . basename($filePath)
        ]);
    } else {
        return response()->json([
            'status' => 500,
            'message' => 'Error al generar el reporte'
        ]);
    }
}

}
