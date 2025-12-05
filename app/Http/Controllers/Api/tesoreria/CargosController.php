<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\EstadoCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;  

class CargosController extends Controller  
{
    
    public function index($concentrado, $idPeriodo, $idNivel){ 
   
    $periodo = DB::table('periodo')
                    ->select('fechaInicio', 'fechaTermino')
                    ->where('idPeriodo', $idPeriodo)
                    ->where('idNivel', $idNivel) // <-- Corregido
                    ->first();

    if (!$periodo) {
        return response()->json([
            'status' => 500,
            'message' => 'No se encontró el periodo'
        ]);
    } 

    $inicio = new \DateTime($periodo->fechaInicio);
    $fin = new \DateTime($periodo->fechaTermino);

    // Asegurar inicio en primer día del mes
    $inicio->modify('first day of this month');
    $fin->modify('first day of next month');

    $meses = [];
    $mesesNombres = [
        1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO',
        4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
        7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE',
        10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
    ];

    $intervalo = new \DateInterval('P1M');
    $periodoMeses = new \DatePeriod($inicio, $intervalo, $fin);

    foreach ($periodoMeses as $m) {
        $mesNumero = (int)$m->format('m');
        $meses[$mesNumero] = $mesesNombres[$mesNumero];
    }

    if ($concentrado == 'N') {

        $results = DB::table('edocta as cta')
            ->select(
                'cta.uid',
                's.descripcion as servicio',
                'ca.descripcion as escuela',
                DB::raw("concat(pers.nombre,' ',pers.primerApellido,' ',pers.segundoApellido) AS nombre"),
                DB::raw("MONTH(FechaPago) AS mes"),
                DB::raw("SUM(importe) AS importe")
            )
            ->join('persona as pers', 'pers.uid', '=', 'cta.uid')
            ->join('alumno as al', function ($join) {
                $join->on('al.uid', '=', 'cta.uid')
                     ->on('al.secuencia', '=', 'cta.secuencia');
            })
            ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')       
            ->join('configuracionTesoreria as ct2', function ($join) {
                        $join->on('ct2.idServicioColegiatura', '=', 'cta.idServicio')
                        ->orOn('cta.idServicio', '=', 'ct2.idServicioInscripcion');
                                        })
            ->join('periodo as p', function ($join) {
                $join->on('p.idPeriodo', '=', 'cta.idPeriodo')
                     ->on('p.idNivel', '=', 'al.idNivel');
            })
            ->join('carrera as ca', function ($join) {
                $join->on('ca.idNivel', '=', 'al.idNivel')
                     ->on('ca.idCarrera', '=', 'al.idCarrera');
            })
            ->where('cta.tipomovto', 'C')
            ->where('p.idPeriodo', $idPeriodo)
            ->groupBy('cta.uid',
                      's.descripcion',
                      'pers.nombre', 
                      'pers.primerApellido', 
                      'pers.segundoApellido', 
                      'ca.descripcion', 
                      DB::raw("MONTH(FechaPago)"))
            ->orderBy('s.descripcion','asc')
            ->orderBy('cta.uid','asc')
            ->orderBy(DB::raw('MONTH(cta.FechaPago)'), 'asc')
            ->get();

        if ($results->count() == 0) {
            return response()->json([
                'status' => 500,
                'message' => 'No hay registros para mostrar'
            ]);
        }

        $pivot = [];

        foreach ($results as $row) {
            if (!isset($meses[$row->mes])) continue;
            $index = $row->uid . '|' . $row->servicio;
            $mesNombre = $meses[$row->mes];

            if (!isset($pivot[$index])) {
                $pivot[$index] = [
                    'uid' => $row->uid,
                    'nombre' => $row->nombre,
                    'escuela' => $row->escuela,
                    'servicio' => $row->servicio,
                ];

                foreach ($meses as $m) {
                    $pivot[$index][$m] = 0;
                }
                $pivot[$index]['total'] = 0;
            }
            $pivot[$index][$mesNombre] += $row->importe;
            $pivot[$index]['total'] += $row->importe;
        }

        $resultsArray = array_values($pivot);
        $headers = array_merge(
            ['UID', 'NOMBRE', 'ESCUELA'],
            array_values($meses),
            ['TOTAL']
        );

        $keys = array_merge(
            ['uid', 'nombre', 'escuela'],
            array_values($meses),
            ['total']
        );
        $columnWidths = [];
        $columnWidths[] = 50;  // UID
        $columnWidths[] = 150;  // NOMBRE
        $columnWidths[] = 50;  // ESCUELA
        $defaultWidth = 70;
        $columnCount = count($headers) - 3;

        for ($i = 0; $i < $columnCount; $i++) {
            $columnWidths[] = $defaultWidth;
        }

        return $this->generateReport(
            $resultsArray,
            $columnWidths,
            $keys,
            'REPORTE DE CARGOS ANALÍTICO',
            $headers,
            'L',
            'letter',
            'rptCargosAnalitico' . mt_rand(100, 999) . '.pdf'
        );
    }
    else {
       $results = DB::table('edocta as cta')
                        ->select(
                            'ca.descripcion as escuela',
                            's.descripcion AS servicio',
                            DB::raw("MONTH(FechaPago) AS mes"),
                            DB::raw('SUM(cta.importe) AS importe')
                        )
                        ->join('alumno as al', function ($join) {
                            $join->on('al.uid', '=', 'cta.uid')
                                ->on('al.secuencia', '=', 'cta.secuencia');
                        })
                        ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
                        ->join('configuracionTesoreria as ct2', function ($join) {
                            $join->on('ct2.idServicioColegiatura', '=', 'cta.idServicio')
                                ->orOn('cta.idServicio', '=', 'ct2.idServicioInscripcion');
                        })
                        ->join('periodo as p', function ($join) {
                            $join->on('p.idPeriodo', '=', 'cta.idPeriodo')
                                ->on('p.idNivel', '=', 'al.idNivel');
                        })
                        ->join('carrera as ca', function ($join) {
                            $join->on('ca.idNivel', '=', 'al.idNivel')
                                ->on('ca.idCarrera', '=', 'al.idCarrera');
                        })
                        ->where('cta.tipomovto', 'C')
                        ->where('p.idPeriodo', $idPeriodo)
                        ->groupBy('ca.descripcion', DB::raw("MONTH(FechaPago)"), 's.descripcion')
                        ->get();

                    if ($results->count() == 0) {
                        return response()->json([
                            'status'  => 500,
                            'message' => 'No hay registros para mostrar'
                        ]);
                    }

                   $pivot = [];

foreach ($results as $row) {

    if (!isset($meses[$row->mes])) continue;
    $mesNombre = $meses[$row->mes];

    $index = $row->escuela . '|' . $row->servicio;

    if (!isset($pivot[$index])) {

        $pivot[$index] = [
            'escuela'  => $row->escuela,
            'servicio' => $row->servicio
        ];

        foreach ($meses as $m) {
            $pivot[$index][$m] = 0;
        }

        $pivot[$index]['total'] = 0;
    }

    $pivot[$index][$mesNombre] += floatval($row->importe);
    $pivot[$index]['total']    += floatval($row->importe);
}

$data = array_values($pivot);
$headers = array_merge(['ESCUELA'], array_values($meses), ['TOTAL']);
$keys    = array_merge(['escuela'], array_values($meses), ['total']);

$columnWidths = [250]; // escuela
foreach ($meses as $m) $columnWidths[] = 60;
$columnWidths[] = 80; // total


        return $this->generateReportConcentrado(
            $data,
            $columnWidths,
            $keys,
            'REPORTE DE CARGOS POR ESCUELA CONCENTRADO',
            $headers,
            'L',
            'letter',
            'rptCargosConcentradoEscuela' . mt_rand(100, 999) . '.pdf'
            );
        }
    }

    
  public function generateReport($data, $columnWidths, $keys, $title, $headers, $orientation, $size, $nameReport){

    $imagePathEnc = public_path('images/encPag.png');
    $imagePathPie = public_path('images/piePag.png');

    $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
    $pdf->setHeaders(null, $columnWidths, $title);
    $pdf->setImagePaths($imagePathEnc, $imagePathPie, $orientation);

    $pdf->SetFont('helvetica', '', 14);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SIAWEB');

    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(TRUE, 25);

    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 8);

    $html2 = '<br><br><br><table border="0" cellpadding="1">';

    // Encabezados
    $html2 .= '<tr>';
    foreach ($headers as $index => $header) {
        if($index==2)
            continue;
        $align = ($index > 3) ? 'right' : 'left';
        $html2 .= '<td style="font-size: 9px;" width="' . $columnWidths[$index] . '" align="' . $align . '"><b>' . htmlspecialchars($header) . '</b></td>';
    }
    $html2 .= '</tr>';
    $html2 .= '<tr><td colspan="' . count($headers) . '"></td></tr>';

    // Variables para cortes
    $servicioActual = '';
    $escuelaActual = '';
    $totalesEscuela = [];
    $totalEscuela = 0;
    $totalesServicio = [];
    $totalServicio = 0;

    // Totales generales
    $totalesGenerales = [];
    $totalGeneral = 0;

    foreach ($data as $row) {

        if ($servicioActual != $row['servicio']) {
            if($servicioActual != ""){
                // Corte por servicio
                $html2 .= '<tr><td colspan="'.count($keys).'"><hr style="border:1px solid black;"></td></tr>';
                 $html2 .= '<tr style="font-weight:bold;font-size:8px;">';
                $html2 .= '<td colspan="2">TOTAL SERVICIO: ' . htmlspecialchars($servicioActual) . '</td>';

                foreach (array_slice($keys, 3) as $key) {
                    $sTotal = $totalesServicio[$key] ?? 0;
                    $html2 .= '<td align="right">$ ' . number_format($sTotal, 2, '.', ',') . '</td>';
                }
                $html2 .= '</tr>';
              
                $totalesServicio = [];
                $totalServicio = 0;
             }   

            $html2 .= '<tr><td colspan="' . count($keys) . '" 
                        style="font-size:8px; font-weight:bold; padding-top:10px;"><br><br>'
                    . 'SERVICIO: ' . htmlspecialchars($row["servicio"]) .
                    '</td></tr><br>';
        }
        
        if ($escuelaActual != $row['escuela']) {
            if($escuelaActual != ""){
                // Corte por servicio
                $html2 .= '<tr><td colspan="'.count($keys).'"><hr style="border:1px solid black;"></td></tr>';
                $html2 .= '<tr style="font-weight:bold;font-size:8px;">';
                $html2 .= '<td colspan="2">TOTAL ESCUEAL: ' . htmlspecialchars($escuelaActual) . '</td>';

                foreach (array_slice($keys, 3) as $key) {
                    $sTotal = $totalesEscuela[$key] ?? 0;
                    $html2 .= '<td align="right">$ ' . number_format($sTotal, 2, '.', ',') . '</td>';
                }
                $html2 .= '</tr>';              
                $totalesEscuela = [];
             }   
        }

        // Fila de alumno
        $html2 .= '<tr>';
        foreach ($keys as $index => $key) {
            if ($key !== 'escuela' && $key !== 'servicio') {
                $value = $row[$key] ?? '';
                if (!in_array($key, ['uid', 'nombre'])) {
                    $formatted = '$ ' . number_format((float)$value, 2, '.', ',');
                    $html2 .= '<td width="' . $columnWidths[$index] . '" align="right">' . htmlspecialchars($formatted) . '</td>';
                    $totalesEscuela[$key] = ($totalesEscuela[$key] ?? 0) + floatval($value);
                    $totalesServicio[$key] = ($totalesServicio[$key] ?? 0) + floatval($value);
                    $totalesGenerales[$key] = ($totalesGenerales[$key] ?? 0) + floatval($value);
                } else {
                    $html2 .= '<td width="' . $columnWidths[$index] . '">' . htmlspecialchars((string)$value) . '</td>';
                }
            }
        }
        $html2 .= '</tr>';

        $servicioActual = $row['servicio'];
        $escuelaActual = $row['escuela'];
    }

    // Último servicio
    if (!empty($servicioActual)) {
        $html2 .= '<tr><td colspan="' . count($keys) . '"><hr style="border:1px solid black;"></td></tr>';  
        $html2 .= '<tr style="font-weight:bold;font-size:8px;">';
        $html2 .= '<td colspan="2">TOTAL SERVICIO: ' . htmlspecialchars($servicioActual) . '</td>';
        foreach (array_slice($keys, 3) as $key) {
            $sTotal = $totalesServicio[$key] ?? 0;
            $html2 .= '<td align="right">$ ' . number_format($sTotal, 2, '.', ',') . '</td>';
        }
        $html2 .= '</tr>';
    }
    if (!empty($escuelaActual)) {
        // Corte por servicio
                $html2 .= '<br><br><tr style="font-weight:bold;font-size:8px;">';
                $html2 .= '<td colspan="2">TOTAL ESCUELA: ' . htmlspecialchars($escuelaActual) . '</td>';

                foreach (array_slice($keys, 3) as $key) {
                    $sTotal = $totalesEscuela[$key] ?? 0;
                    $html2 .= '<td align="right">$ ' . number_format($sTotal, 2, '.', ',') . '</td>';
                }
                $html2 .= '</tr>';              
                $totalesEscuela = [];
    }
    // Total general del reporte
    $html2 .= '<tr><td colspan="'.count($keys).'"><br><br></td></tr>';

    $html2 .= '<tr style="font-weight:bold;font-size:10px;">';
    $html2 .= '<td colspan="2">TOTAL GENERAL</td>';

    foreach (array_slice($keys, 3) as $key) {
        $gTotal = $totalesGenerales[$key] ?? 0;
        $html2 .= '<td align="right">$ ' . number_format($gTotal, 2, '.', ',') . '</td>';
    }
    $html2 .= '</tr>';
    $html2 .= '</table>';

    $pdf->writeHTML($html2, true, false, true, false, '');

    $filePath = $nameReport == null ? storage_path('app/public/reporte.pdf') : storage_path('app/public/' . $nameReport);
    $pdf->Output($filePath, 'F');

    return file_exists($filePath)
        ? response()->json(['status' => 200, 'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $nameReport])
        : response()->json(['status' => 500, 'message' => 'Error al generar el reporte']);
}

public function generateReportConcentrado($data, $columnWidths, $keys, $title, $headers, $orientation, $size, $nameReport)
{
    $imagePathEnc = public_path('images/encPag.png');
    $imagePathPie = public_path('images/piePag.png');

    $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
    $pdf->setHeaders(null, $columnWidths, $title);
    $pdf->setImagePaths($imagePathEnc, $imagePathPie, $orientation);

    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(TRUE, 25);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 8);

    $html = '<br><br><br><table border="0" cellpadding="2">';

    $servicioActual = "";
    $totalesServicio = [];
    $totalesGenerales = [];

    foreach ($data as $row) {

        // Cambio de servicio → impresión de total servicio anterior
        if ($servicioActual !== $row['servicio']) {

            if ($servicioActual !== "") {
                // Total del servicio anterior
                $html .= '<tr><td colspan="'.count($keys).'"><hr></td></tr>';
                $html .= '<tr style="font-weight:bold;">';
                $html .= '<td>TOTAL SERVICIO: '.htmlspecialchars($servicioActual).'</td>';

                foreach (array_slice($keys, 1) as $k) {
                    $t = $totalesServicio[$k] ?? 0;
                    $html .= '<td align="right">$ '.number_format($t,2,'.',',').'</td>';
                }

                $html .= '</tr><tr><td colspan="'.count($keys).'"><br></td></tr>';
                $totalesServicio = [];
            }

            // Nuevo título de servicio
            $html .= '<tr><td colspan="'.count($keys).'" style="font-weight:bold; font-size:10px;"><br>SERVICIO: '.htmlspecialchars($row['servicio']).'</td></tr>';

            // Encabezados
            $html .= '<tr>';
            foreach ($headers as $i => $h) {
                $align = ($i > 1) ? 'right' : 'left';
                $html .= '<td width="'.$columnWidths[$i].'" align="'.$align.'"><b>'.$h.'</b></td>';
            }
            $html .= '</tr>';
        }

        // Fila por escuela
        $html .= '<tr>';
        foreach ($keys as $i => $k) {
            if ($k === 'escuela') {
                $html .= '<td width="'.$columnWidths[$i].'" align="left">'.htmlspecialchars($row[$k]).'</td>';
            } else {
                $value = floatval($row[$k]);
                $html .= '<td width="'.$columnWidths[$i].'" align="right">$ '.number_format($value,2,'.',',').'</td>';

                // Totales del servicio
                $totalesServicio[$k] = ($totalesServicio[$k] ?? 0) + $value;

                // Totales globales
                $totalesGenerales[$k] = ($totalesGenerales[$k] ?? 0) + $value;
            }
        }
        $html .= '</tr>';

        $servicioActual = $row['servicio'];
    }

    // ÚLTIMO SERVICIO
    if ($servicioActual !== "") {
        $html .= '<tr><td colspan="'.count($keys).'"><hr></td></tr>';
        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td>TOTAL SERVICIO: '.htmlspecialchars($servicioActual).'</td>';

        foreach (array_slice($keys, 1) as $k) {
            $t = $totalesServicio[$k] ?? 0;
            $html .= '<td align="right">$ '.number_format($t,2,'.',',').'</td>';
        }
        $html .= '</tr>';
    }

    // TOTAL GENERAL
    $html .= '<tr><td colspan="'.count($keys).'"><br><br></td></tr>';
    $html .= '<tr style="font-weight:bold; font-size:10px;">';
    $html .= '<td>TOTAL GENERAL</td>';

    foreach (array_slice($keys, 1) as $k) {
        $t = $totalesGenerales[$k] ?? 0;
        $html .= '<td align="right">$ '.number_format($t,2,'.',',').'</td>';
    }
    $html .= '</tr>';

    $html .= '</table>';

    $pdf->writeHTML($html);

    $filePath = storage_path('app/public/' . $nameReport);
    $pdf->Output($filePath, 'F');

    return response()->json([
        'status' => 200,
        'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $nameReport
    ]);
}

private function renderTotalesEscuela($escuela, $totalesEscuela, $totalEscuela, $keys){

    $html = '<tr style="font-weight:bold; background-color:#f0f0f0;">';
    $html .= '<td colspan="3">TOTAL ESCUELA: ' . htmlspecialchars($escuela) . '</td>';

    foreach (array_slice($keys, 3) as $key) {
        $mesTotal = $totalesEscuela[$key] ?? 0;
        $html .= '<td align="right">$ ' . number_format($mesTotal, 2, '.', ',') . '</td>';
    }

    $html .= '</tr>';
    $html .= '<tr><td colspan="' . count($keys) . '"><hr style="border:0.5px dashed #999;"></td></tr>';

    return $html;
}      
}
