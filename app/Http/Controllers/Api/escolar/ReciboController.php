<?php

namespace App\Http\Controllers\Api\escolar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;  
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDSFormat;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;


class ReciboController extends Controller
{
    public function generarYGuardarPDF($uid, $folio)
{
    $orientation = 'P';
    $size = 'letter';
    $nameReport = 'recibos_' . mt_rand(100, 999) . '.pdf';

    $datos = DB::table('edocta as edo')
        ->select([
            'carrera.descripcion as nombreCarrera',
            'edo.fechaPago',
            'edo.uid',
            'edo.folio',
            'edo.comprobante',
            DB::raw('GROUP_CONCAT(DISTINCT s.descripcion ORDER BY s.descripcion SEPARATOR ", ") as servicios'),
            DB::raw('SUM(importe) as total'),
            DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre')
        ])
        ->join('alumno as al', 'al.uid', '=', 'edo.uid')
        ->join('carrera', 'carrera.idCarrera', '=', 'al.idCarrera')
        ->join('persona', 'persona.uid', '=', 'al.uid')
        ->join('servicio as s', 's.idServicio', '=', 'edo.idServicio')
        ->where('edo.uid', $uid)
        ->where('edo.folio', $folio)
        ->where('edo.tipomovto','A')
        ->groupBy(
            'carrera.descripcion',
            'edo.fechaPago',
            'edo.uid',
            'edo.folio',
            'persona.primerApellido',
            'persona.segundoApellido',
            'persona.nombre',
            'edo.comprobante'
        )
        ->get();

    if ($datos->isEmpty()) {
        return response()->json(['status' => 404, 'message' => 'Datos no encontrados']);
    }

    $datosRecibos = $datos[0];
    $fecha = Carbon::now()->locale('es')->translatedFormat('d \d\e F \d\e Y');
    $folioFormateado = str_pad($folio, 5, '0', STR_PAD_LEFT);
    $totalFormateado = number_format($datosRecibos->total, 2, '.', ',');

    // ✅ Generar QR en PNG base64 (sin Imagick ni SVG)
            
        try {
            $qrCode = new QrCode($datosRecibos->comprobante ?? 'Contenido vacío');
        
            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            $qrPngData = $result->getString();
            $qrBase64 = base64_encode($qrPngData);
        } catch (\Throwable $e) {
            \Log::error('Error generando QR: ' . $e->getMessage());
            $qrBase64 = null;
        }   

    $pdf = new CustomTCPDSFormat($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SIAWEB');
    $pdf->SetMargins(30, 10, 20);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->AddPage();
     $imageUrl = 'https://siaweb.com.mx/images/Recibo.jpg';
        $pdf->Image($imageUrl, 0, 0, $pdf->getPageWidth(), $pdf->getPageHeight());


    $html = '
    <br><br><br><br><br><br>
    <div style="text-align: right; color: red; font-weight: bold;">' . $folioFormateado . '</div>';
   
   
    $html .= '<br><br><br>
    <table border="0" cellpadding="1" style="font-family: Arial; font-size: 10pt;line-height: 1.5;">
        <tr>
            <td style="width: 20cm; font-size: 10pt;"><b>Recibo de:</b> ' . $datosRecibos->nombre . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>La cantidad de: </b>$ ' . $totalFormateado . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>Por concepto de: </b>' . $datosRecibos->servicios . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>Carrera: </b>' . $datosRecibos->nombreCarrera . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;">Puebla, Pue. a ' . $fecha . '</td>
        </tr>
    </table>';
    if ($qrBase64) {
        $html .= '<br><br><br><div style="text-align:left;"><img src="data:image/png;base64,' . $qrBase64 . '" style="width: 75px;" /></div>';
    } 
   
    $html .= '
    <br><br><br><br><br><br><br><br><br>
    <div style="text-align: right; color: red; font-weight: bold;">' . $folioFormateado . '</div>';
   
   
    $html .= '<br><br><br>
    <table border="0" cellpadding="1" style="font-family: Arial; font-size: 10pt;line-height: 1.5;">
        <tr>
            <td style="width: 20cm; font-size: 10pt;"><b>Recibo de:</b> ' . $datosRecibos->nombre . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>La cantidad de: </b>$ ' . $totalFormateado . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>Por concepto de: </b>' . $datosRecibos->servicios . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>Carrera: </b>' . $datosRecibos->nombreCarrera . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;">Puebla, Pue. a ' . $fecha . '</td>
        </tr>
    </table>';
    if ($qrBase64) {
        $html .= '<br><br><br><div style="text-align:left;"><img src="data:image/png;base64,' . $qrBase64 . '" style="width: 75px;" /></div>';
    } 
   
   
   
    $pdf->writeHTML($html, true, false, true, false, '');

    $filePath = storage_path('app/public/' . $nameReport);
    $pdf->Output($filePath, 'F');

    if (file_exists($filePath)) {
        return response()->json([
            'status' => 200,
            'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $nameReport
        ]);
    } else {
        return response()->json([
            'status' => 500,
            'message' => 'Error al generar el reporte'
        ]);
    }
}
}
