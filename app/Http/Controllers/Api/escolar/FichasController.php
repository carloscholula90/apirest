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


class FichasController extends Controller
{
    public function generarYGuardarPDF($idPeriodo, $idCarrera, $parcialidad)
{
    $orientation = 'P';
    $size = 'letter';
    $nameReport = 'fichaPago_' . mt_rand(100, 999) . '.pdf';
    DB::statement("SET lc_time_names = 'es_ES'");
    
    $fechaPago = DB::table('edocta as edo')
                ->select([
                    DB::raw("DATE_FORMAT(edo.fechaPago, '%d/%m/%Y') AS fechaPago"),
                    DB::raw("DATE_FORMAT(edo.fechaPago, '%Y%m%d') AS fechaPagoLinea"),
                    DB::raw("UPPER(DATE_FORMAT(edo.fechaPago, '%M')) AS mes")
                    ])
                ->join('alumno as al', 'al.uid', '=', 'edo.uid')
                ->join('servicio as s', 's.idServicio', '=', 'edo.idServicio')
                ->join('carrera', 'carrera.idCarrera', '=', 'al.idCarrera')
                ->join('persona', 'persona.uid', '=', 'al.uid')

                ->leftJoin('configuracionTesoreria as colegiatura', function($join) {
                    $join->on('colegiatura.idNivel', '=', 'al.idNivel')
                        ->on('colegiatura.idServicioColegiatura', '=', 's.idServicio');
                })
                ->where('parcialidad',$parcialidad)
                ->where('idPeriodo', $idPeriodo)
                ->where('al.idCarrera',$idCarrera)
                ->groupBy('edo.fechaPago')
                ->get();

    
    $datos = $datos = DB::table('edocta as edo')
    ->select([
        'CONS.*',
        DB::raw("Algoritmo45Fun(CONCAT('CE',matricula), ".$fechaPago[0]->fechaPagoLinea.", total) AS lineaPago")
    ])
    ->from(DB::raw("(
                    SELECT 
                        carrera.descripcion AS nombreCarrera,
                        edo.uid,matricula,
                        CONCAT(
                            GROUP_CONCAT(DISTINCT CONCAT(s.descripcion, ' ') ORDER BY s.descripcion SEPARATOR ' + '),
                            ' '
                        ) AS servicios,
                        SUM(CASE WHEN tipomovto ='C' THEN importe ELSE importe * -1 END) AS total,
                        CONCAT(persona.primerApellido, ' ', persona.segundoApellido, ' ', persona.nombre) AS nombre
                    FROM edocta AS edo
                    INNER JOIN alumno AS al ON al.uid = edo.uid
                    INNER JOIN servicio AS s ON s.idServicio = edo.idServicio
                    INNER JOIN carrera ON carrera.idCarrera = al.idCarrera
                    INNER JOIN persona ON persona.uid = al.uid
                    LEFT JOIN configuracionTesoreria AS inscripcion 
                        ON inscripcion.idNivel = al.idNivel
                        AND inscripcion.idServicioInscripcion = s.idServicio
                    LEFT JOIN configuracionTesoreria AS colegiatura 
                        ON colegiatura.idNivel = al.idNivel
                        AND colegiatura.idServicioColegiatura = s.idServicio
                    WHERE parcialidad = ".$parcialidad." 
                    AND idPeriodo = ".$idPeriodo." 
                    AND al.idCarrera = ".$idCarrera."
                    GROUP BY 
                        carrera.descripcion,
                        edo.uid,
                        persona.primerApellido,
                        persona.segundoApellido,
                        persona.nombre,matricula
                ) AS CONS"))
                ->get();



    
    if ($datos->isEmpty()) {
        return response()->json(['status' => 404, 'message' => 'Datos no encontrados']);
    }

    $datosRecibos = $datos[0];   
    $fecha = Carbon::now('America/Mexico_City')->translatedFormat('d \d\e F \d\e Y');
    $fecha = strtoupper($fecha);
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
     $imageUrl = 'https://siaweb.com.mx/images/FichaPago.jpg';
        $pdf->Image($imageUrl, 0, 0, $pdf->getPageWidth(), $pdf->getPageHeight());


    $html = '<br><br><br><br><br><br><br><br>';
   
   
    $html .= '<br><br><br>
    <table border="0" cellpadding="1" style="font-family: Arial; font-size: 10pt;line-height: 1.5;">
       <br>
       <tr>
            <td style="width: 20cm; font-size: 11pt;"><b>FICHA DE PAGO</b></td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>LINEA DE CAPTURA:</b>' . $datosRecibos->lineaPago . '</td>
        </tr>
       <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>FECHA DE IMPRESIÓN:</b>' . $fecha . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>NOMBRE DEL ALUMNO:</b>'. $datosRecibos->nombre .'</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>CARRERA: </b>' . $datosRecibos->nombreCarrera . '</td>
        </tr> 
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>VENCIMIENTO: </b>'.$fechaPago[0]->fechaPago.'</td>
        </tr> 
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>IMPORTE:</b>$ ' . $totalFormateado . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>CONCEPTO:</b>' . $datosRecibos->servicios .' '.$fechaPago[0]->mes. '</td>
        </tr>
    </table>';
    
    if ($qrBase64) {
        $html .= '<br><div style="text-align:left;"><img src="data:image/png;base64,' . $qrBase64 . '" style="width: 75px;" /></div>';
    } 
   
    $html .= '
    <br><br><br><br><br><br><br><br><br><br>';
   
   
    $html .= '<br><br><br>
    <table border="0" cellpadding="1" style="font-family: Arial; font-size: 10pt;line-height: 1.5;">
       <br>
       <tr>
            <td style="width: 20cm; font-size: 11pt;"><b>FECHA DE PAGO</b></td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>LINEA DE CAPTURA:</b>' . $datosRecibos->lineaPago . '</td>
        </tr>
       <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>FICHA DE IMPRESIÓN:</b> ' . $fecha . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>NOMBRE DEL ALUMNO:</b>'. $datosRecibos->nombre .'</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>CARRERA: </b>' . $datosRecibos->nombreCarrera . '</td>
        </tr> 
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>VENCIMIENTO: </b>'.$fechaPago[0]->fechaPago.'</td>
        </tr> 
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>IMPORTE:</b>$ ' . $totalFormateado . '</td>
        </tr>
        <tr>
            <td style="width: 20cm; font-size: 9pt;"><b>CONCEPTO:</b>' . $datosRecibos->servicios .' '.$fechaPago[0]->mes. '</td>
        </tr>
    </table>';

    if ($qrBase64) {
        $html .= '<br><div style="text-align:left;"><img src="data:image/png;base64,' . $qrBase64 . '" style="width: 75px;" /></div>';
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
