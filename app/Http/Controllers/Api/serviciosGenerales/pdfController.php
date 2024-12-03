<?php    
namespace App\Http\Controllers\Api\serviciosGenerales;
use App\Http\Controllers\Controller;  
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF;   

class pdfController extends Controller
{
    public function generateReport(array $data, string $title = 'Reporte PDF', string $orientation = 'P', string $size = 'letter')
    {
        // Establecer las rutas de las imágenes
        $imagePathEnc = public_path('images/encPag.png');
        $imagePathPie = public_path('images/piePag.png');   
        
        // Crear una nueva instancia de CustomTCPDF con orientación y tamaño
        $pdf = new \App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
     
        // Pasar las rutas de las imágenes a la clase CustomTCPDF
        $pdf->setImagePaths($imagePathEnc, $imagePathPie);  // Llamada al nuevo método
        $pdf->SetFont('TitilliumWeb-Bold', '', 16);    
        // Establecer metadatos del documento
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Tu Nombre o Empresa');
        $pdf->SetTitle($title);
        
        // Establecer márgenes y saltos de página automáticos
        $pdf->SetMargins(15, 27, 15);  // Márgenes izquierdo, superior, derecho
        $pdf->SetAutoPageBreak(TRUE, 25);  // Saltos automáticos de página con margen inferior

        // Agregar una página
        $pdf->AddPage();   

        // Título del reporte
        $pdf->Cell(0, 10, $title, 0, 1, 'C');

        // Salto de línea
        $pdf->Ln(10);

        // Crear una tabla HTML con los datos
        $html = '<table border="0" cellpadding="5">';
        $html .= '<tr><th>Columna 1</th><th>Columna 2</th></tr>';
        $pdf->SetFont('TitilliumWeb-Regular', '', 14);          
       
        // Agregar datos a la tabla
        foreach ($data as $row) {
            $html .= '<tr>';
            $html .= '<td>' . $row['columna1'] . '</td>';
            $html .= '<td>' . $row['columna2'] . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        // Escribir el HTML en el PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Generar el archivo PDF y enviarlo al navegador
        $pdf->Output('reporte.pdf', 'I');  // 'I' lo muestra en el navegador
    }
}