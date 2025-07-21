<?php

namespace App\Http\Controllers\Api\escolar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PDF;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDSFormat;

class ReciboController extends Controller
{
    public function generarYGuardarPDF()
    {

        $orientation='P';
        $size='letter';
        $nameReport='recibos'.'_'.mt_rand(100, 999).'.pdf';
     
        $pdf = new CustomTCPDSFormat($orientation, PDF_UNIT, $size, true, 'UTF-8', false);       
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');          
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(30, 10, 15); // Margenes 
        $pdf->SetAutoPageBreak(FALSE, 0);
        $pdf->AddPage();
        $imageUrl = 'https://siaweb.com.mx/images/Recibo.jpg';
        $pdf->Image($imageUrl, 0, 0, $pdf->getPageWidth(), $pdf->getPageHeight());
   
    $filePath = storage_path('app/public/'.$nameReport);  // Ruta donde se guardará el archivo
       
    $pdf->Output($filePath, 'F');  // 'F' para guardar el archivo en el servidor
  
        // Ahora puedes verificar si el archivo se ha guardado correctamente en la ruta especificada.
        if (file_exists($filePath)) {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/'.$nameReport // Puedes devolver la ruta para fines de depuración
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte'
            ]);
        }
              
    }
}