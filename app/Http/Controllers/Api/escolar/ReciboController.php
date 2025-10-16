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
            DB::raw("GROUP_CONCAT(DISTINCT CONCAT( s.descripcion, ' ',
                                            CASE WHEN colegiatura.idServicioColegiatura = s.idServicio THEN                            
                                            CASE edo.referencia
                                                    WHEN '10000001' THEN 'ENERO'
                                                    WHEN '10000002' THEN 'FEBRERO'
                                                    WHEN '10000003' THEN 'MARZO'
                                                    WHEN '10000004' THEN 'ABRIL'
                                                    WHEN '10000005' THEN 'MAYO'
                                                    WHEN '10000006' THEN 'JUNIO'
                                                    WHEN '10000007' THEN 'JULIO'
                                                    WHEN '10000008' THEN 'AGOSTO'
                                                    WHEN '10000009' THEN 'SEPTIEMBRE'
                                                    WHEN '10000010' THEN 'OCTUBRE'
                                                    WHEN '10000011' THEN 'NOVIEMBRE'
                                                    WHEN '10000012' THEN 'DICIEMBRE'
                                                    ELSE ''
                                                    END
                                else ''
                                end ) ORDER BY s.descripcion SEPARATOR ',') as servicios"),
            DB::raw('SUM(importe) as total'),
            DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre')
        ])
        ->join('alumno as al', 'al.uid', '=', 'edo.uid')
        ->join('servicio as s', 's.idServicio', '=', 'edo.idServicio')
        
        ->join('carrera', 'carrera.idCarrera', '=', 'al.idCarrera')
        ->join('persona', 'persona.uid', '=', 'al.uid')
        ->leftJoin('configuracionTesoreria AS inscripcion', function($join) {
                        $join->on('inscripcion.idNivel', '=', 'al.idNivel')
                            ->on(function($query) {
                                $query->on('inscripcion.idServicioInscripcion', '=', 's.idServicio');
                            });
                    })
                    ->leftJoin('configuracionTesoreria AS colegiatura', function($join) {
                        $join->on('colegiatura.idNivel', '=', 'al.idNivel')
                            ->on(function($query) {
                                $query->on('colegiatura.idServicioColegiatura', '=', 's.idServicio');
                            });
                    })
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
    $fecha = Carbon::now('America/Mexico_City')->translatedFormat('d \d\e F \d\e Y');
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
            <td style="width: 20cm; font-size: 9pt;">Puebla, Pue. a ' . $fecha . '</td>
        </tr>   
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
            <td style="width: 20cm; font-size: 9pt;">Puebla, Pue. a ' . $fecha . '</td>
        </tr>
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

 public function obtenerRecibos($sistema,$grupo, $idAlumno = null){
   
    $selects = ['edo.parcialidad',
                'al.uid',
                'edo.referencia',
                'al.idNivel',
                'al.idCarrera',
                'al.matricula',
                'edo.tipomovto',
                'nivel.descripcion as nivel',
                'carrera.descripcion as nombreCarrera',
                'persona.nombre',
                'persona.primerapellido as apellidopat',
                'persona.segundoapellido as apellidomat',
                DB::raw("CASE WHEN colegiatura.idServicioColegiatura = s.idServicio THEN
                    CASE WHEN edo.tipomovto = 'A' THEN
                        CASE edo.referencia
                            WHEN '10000001' THEN 'ENERO'
                            WHEN '10000002' THEN 'FEBRERO'
                            WHEN '10000003' THEN 'MARZO'
                            WHEN '10000004' THEN 'ABRIL'
                            WHEN '10000005' THEN 'MAYO'
                            WHEN '10000006' THEN 'JUNIO'
                            WHEN '10000007' THEN 'JULIO'
                            WHEN '10000008' THEN 'AGOSTO'
                            WHEN '10000009' THEN 'SEPTIEMBRE'
                            WHEN '10000010' THEN 'OCTUBRE'
                            WHEN '10000011' THEN 'NOVIEMBRE'
                            WHEN '10000012' THEN 'DICIEMBRE'
                            ELSE ''
                        END
                    ELSE
                        CASE MONTH(edo.FechaPago)
                            WHEN 1 THEN 'ENERO'
                            WHEN 2 THEN 'FEBRERO'
                            WHEN 3 THEN 'MARZO'
                            WHEN 4 THEN 'ABRIL'
                            WHEN 5 THEN 'MAYO'
                            WHEN 6 THEN 'JUNIO'
                            WHEN 7 THEN 'JULIO'
                            WHEN 8 THEN 'AGOSTO'
                            WHEN 9 THEN 'SEPTIEMBRE'
                            WHEN 10 THEN 'OCTUBRE'
                            WHEN 11 THEN 'NOVIEMBRE'
                            WHEN 12 THEN 'DICIEMBRE'
                            ELSE ''
                        END
                    END
                ELSE ''
                END AS servicio"),
                'fp.descripcion as formaPago',
                'edo.fechaPago',
                'edo.consecutivo',
                'edo.idServicio',
                'inscripcion.idServicioInscripcion',
                'colegiatura.idServicioColegiatura',
                DB::raw("CASE WHEN edo.tipomovto = 'C' THEN edo.importe ELSE null END as cargo"),
                DB::raw("CASE WHEN edo.tipomovto != 'C' THEN edo.importe ELSE null END as abono"),
            ];

           

            // Construcción del query completo
            $query = DB::table('edocta as edo')
                ->select($selects)
                ->join('servicio as s', 's.idServicio', '=', 'edo.idServicio')
                ->leftJoin('formaPago as fp', 'fp.idFormaPago', '=', 'edo.idformaPago')
                ->join('alumno as al', function ($join) {
                    $join->on('al.uid', '=', 'edo.uid')
                        ->on('al.secuencia', '=', 'edo.secuencia');
                })
                ->join('nivel', 'nivel.idNivel', '=', 'al.idNivel')
                ->leftJoin('configuracionTesoreria as inscripcion', function ($join) {
                    $join->on('inscripcion.idNivel', '=', 'al.idNivel')
                        ->on('inscripcion.idServicioInscripcion', '=', 's.idServicio');
                })
                ->leftJoin('configuracionTesoreria as colegiatura', function ($join) {
                    $join->on('colegiatura.idNivel', '=', 'al.idNivel')
                        ->on('colegiatura.idServicioColegiatura', '=', 's.idServicio');
                })
                ->join('carrera', 'carrera.idCarrera', '=', 'al.idCarrera')
                ->join('persona', 'persona.uid', '=', 'al.uid')
                ->where('edo.uid', $uid);

            // Condiciones adicionales
            if (!is_null($qr)) {
                $query->where('edo.comprobante', 'like', '%' . $qr . '%');
            } else {
                $query->where('edo.idPeriodo', $idPeriodo)
                    ->where('al.matricula', $matricula);
            }

            // Ordenar y obtener resultados
            $edocuenta = $query->orderByDesc('inscripcion.idServicioInscripcion')
                            ->orderByDesc('colegiatura.idServicioColegiatura')
                            ->orderBy('edo.idServicio')
                            ->orderBy('edo.parcialidad')
                            ->orderByDesc('edo.tipomovto')
                            ->distinct()
                            ->get();

            return $edocuenta;
    }

}
