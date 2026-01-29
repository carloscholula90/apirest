<?php

namespace App\Http\Controllers\Api\tesoreria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF;
use App\Http\Controllers\Api\serviciosGenerales\GenericExport;
use Maatwebsite\Excel\Facades\Excel;

class CargosController extends Controller
{


    public function index($concentrado, $idPeriodo, $idNivel){
        return $this->generaReporte($concentrado, $idPeriodo, $idNivel,0);
    }

    public function indexExcel($concentrado, $idPeriodo, $idNivel){
        return $this->generaReporte($concentrado, $idPeriodo, $idNivel,1);
    }

    public function generaReporte($concentrado, $idPeriodo, $idNivel,$excel)
    {
        // ==========================
        // CONFIGURACIÓN
        // ==========================
        $config = DB::table('configuracion')
            ->where('id_campo', 1)
            ->first();

        $activo = $config->valor ?? 0;

        $periodo = DB::table('periodo')
            ->where('idPeriodo', $idPeriodo)
            ->where('idNivel', $idNivel)
            ->first();

        if (!$periodo) {
            return response()->json([
                'status' => 500,
                'message' => 'No se encontró el periodo'
            ]);
        }

        // ==========================
        // MESES
        // ==========================
        $inicio = new \DateTime($periodo->fechaInicio);
        $fin    = new \DateTime($periodo->fechaTermino);

        $inicio->modify('first day of this month');
        $fin->modify('first day of next month');

        $mesesNombres = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO',
            4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
            7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
        ];

        $meses = [];
        foreach (new \DatePeriod($inicio, new \DateInterval('P1M'), $fin) as $m) {
            $meses[(int)$m->format('m')] = $mesesNombres[(int)$m->format('m')];
        }

        // =========================================================
        // ===================== ANALÍTICO =========================
        // =========================================================
        if ($concentrado === 'N') {

            $query = DB::table('edocta as cta')
                ->select(
                    'cta.uid',
                    's.descripcion as servicio',
                    'ca.descripcion as escuela',
                    DB::raw("CONCAT(pers.nombre,' ',pers.primerApellido,' ',pers.segundoApellido) AS nombre"),
                    DB::raw("MONTH(cta.FechaPago) AS mes"),
                    DB::raw("SUM(cta.importe) AS importe")
                )
                ->join('persona as pers', 'pers.uid', '=', 'cta.uid')
                ->join('alumno as al', function ($j) {
                    $j->on('al.uid', '=', 'cta.uid')
                      ->on('al.secuencia', '=', 'cta.secuencia');
                })
                ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
                ->join('periodo as p', function ($j) {
                    $j->on('p.idPeriodo', '=', 'cta.idPeriodo')
                      ->on('p.idNivel', '=', 'al.idNivel');
                })
                ->join('carrera as ca', function ($j) {
                    $j->on('ca.idNivel', '=', 'al.idNivel')
                      ->on('ca.idCarrera', '=', 'al.idCarrera');
                })
                ->where('cta.tipomovto', 'C')
                ->where('p.idPeriodo', $idPeriodo);

            if ($activo == 0) {
                $query->where('s.tipoEdoCta', 1);
            }

            $results = $query
                ->groupBy(
                    'cta.uid',
                    's.descripcion',
                    'ca.descripcion',
                    'pers.nombre',
                    'pers.primerApellido',
                    'pers.segundoApellido',
                    DB::raw("MONTH(cta.FechaPago)")
                )
                ->orderBy('servicio')
                ->orderBy('cta.uid')
                ->get();

                $results = $results->sortBy([
                            ['servicio', 'asc'],
                            ['escuela', 'asc'],
                            ['mes', 'asc'],
                        ]);

            if ($results->isEmpty()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'No hay registros'
                ]);
            }

            // ---------------- PIVOT ----------------
            $pivot = [];

            foreach ($results as $r) {
                if (!isset($meses[$r->mes])) continue;

                $key = $r->uid . '|' . $r->servicio;
                $mes = $meses[$r->mes];

                if (!isset($pivot[$key])) {
                    $pivot[$key] = [
                        'uid' => $r->uid,
                        'nombre' => $r->nombre,
                        'escuela' => $r->escuela,
                        'servicio' => $r->servicio,
                    ];
                    foreach ($meses as $m) $pivot[$key][$m] = 0;
                    $pivot[$key]['total'] = 0;
                }

                $pivot[$key][$mes] += $r->importe;
                $pivot[$key]['total'] += $r->importe;
            }

            $data = array_values($pivot);

            $headers = array_merge(['UID','NOMBRE','ESCUELA'], array_values($meses), ['TOTAL']);
            $keys    = array_merge(['uid','nombre','escuela'], array_values($meses), ['total']);

            // ---------------- EXCEL ----------------
            if ($excel == 1) {

                    $dataConCortes = [];
                    $servicioActual = null;
                    $totalesServicio = [];

                    foreach ($data as $row) {

                        if ($servicioActual !== $row['servicio']) {

                            // Inserta total del servicio anterior
                            if ($servicioActual !== null) {
                                $totalRow = [
                                    'uid' => '',
                                    'nombre' => 'TOTAL SERVICIO',
                                    'escuela' => '',
                                    'servicio' => $servicioActual,
                                ];

                                foreach ($meses as $m) {
                                    $totalRow[$m] = $totalesServicio[$m] ?? 0;
                                }
                                $totalRow['total'] = array_sum($totalesServicio);

                                $dataConCortes[] = $totalRow;
                                $totalesServicio = [];
                            }

                            // Fila título de servicio
                            $dataConCortes[] = [
                                'uid' => '',
                                'nombre' => 'SERVICIO: ' . $row['servicio'],
                                'escuela' => '',
                                'servicio' => '',
                            ];

                            $servicioActual = $row['servicio'];
                        }

                        // Acumula totales
                        foreach ($meses as $m) {
                            $totalesServicio[$m] = ($totalesServicio[$m] ?? 0) + $row[$m];
                        }

                        $dataConCortes[] = $row;
                    }

                    // Último total
                    if ($servicioActual !== null) {
                        $totalRow = [
                            'uid' => '',
                            'nombre' => 'TOTAL SERVICIO',
                            'escuela' => '',
                            'servicio' => $servicioActual,
                        ];
                        foreach ($meses as $m) {
                            $totalRow[$m] = $totalesServicio[$m] ?? 0;
                        }
                        $totalRow['total'] = array_sum($totalesServicio);

                        $dataConCortes[] = $totalRow;
                    }
                 $dataFinal = $dataConCortes;
                 $path = storage_path('app/public/rptCargosAnalitico.xlsx');
                    Excel::store(new GenericExport($dataFinal, $headers, $keys),'rptCargosAnalitico.xlsx',  'public');
                
                    // Verifica si el archivo existe usando Storage de Laravel
                    if (file_exists($path))  {
                        return response()->json([
                            'status' => 200,  
                            'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/rptCargosAnalitico.xlsx' // URL pública para descargar el archivo
                        ]);
                        } else {
                            return response()->json([
                                'status' => 500,
                                'message' => 'Error al generar el reporte '
                            ]);
                        }
            }

            // ---------------- PDF ----------------
            return $this->generateReport(
                $data,
                array_fill(0, count($headers), 60),
                $keys,
                'REPORTE DE CARGOS ANALÍTICO',
                $headers,
                'L',
                'letter',
                'rptCargosAnalitico.pdf'
            );
        }

        // =========================================================
        // ==================== CONCENTRADO ========================
        // =========================================================
        $query = DB::table('edocta as cta')
            ->select(
                'ca.descripcion as escuela',
                's.descripcion as servicio',
                DB::raw("MONTH(cta.FechaPago) AS mes"),
                DB::raw("SUM(cta.importe) AS importe")
            )
            ->join('alumno as al', function ($j) {
                $j->on('al.uid', '=', 'cta.uid')
                  ->on('al.secuencia', '=', 'cta.secuencia');
            })
            ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
            ->join('periodo as p', function ($j) {
                $j->on('p.idPeriodo', '=', 'cta.idPeriodo')
                  ->on('p.idNivel', '=', 'al.idNivel');
            })
            ->join('carrera as ca', function ($j) {
                $j->on('ca.idNivel', '=', 'al.idNivel')
                  ->on('ca.idCarrera', '=', 'al.idCarrera');
            })
            ->where('cta.tipomovto', 'C')
            ->where('p.idPeriodo', $idPeriodo);

        if ($activo == 0) {
            $query->where('s.tipoEdoCta', 1);
        }

        $results = $query
            ->groupBy('ca.descripcion','s.descripcion',DB::raw("MONTH(cta.FechaPago)"))
            ->get();
        $results = $results->sortBy([
                                    ['servicio', 'asc'],
                                    ['escuela', 'asc'],
                                    ['mes', 'asc'],
                                ]);
        $pivot = [];

        foreach ($results as $r) {
            if (!isset($meses[$r->mes])) continue;

            $key = $r->escuela . '|' . $r->servicio;
            $mes = $meses[$r->mes];

            if (!isset($pivot[$key])) {
                $pivot[$key] = [
                    'escuela' => $r->escuela,
                    'servicio' => $r->servicio
                ];
                foreach ($meses as $m) $pivot[$key][$m] = 0;
                $pivot[$key]['total'] = 0;
            }

            $pivot[$key][$mes] += $r->importe;
            $pivot[$key]['total'] += $r->importe;
        }

        $data = array_values($pivot);

        $headers = array_merge(['ESCUELA'], array_values($meses), ['TOTAL']);
        $keys    = array_merge(['escuela'], array_values($meses), ['total']);

        if ($excel == 1) {
            $dataConCortes = [];
            $servicioActual = null;
            $totalesServicio = [];

            foreach ($data as $row) {

                if ($servicioActual !== $row['servicio']) {

                    // Inserta total del servicio anterior
                    if ($servicioActual !== null) {
                        $totalRow = [
                            'uid' => '',
                            'nombre' => 'TOTAL SERVICIO',
                            'escuela' => '',
                            'servicio' => $servicioActual,
                        ];

                        foreach ($meses as $m) {
                            $totalRow[$m] = $totalesServicio[$m] ?? 0;
                        }
                        $totalRow['total'] = array_sum($totalesServicio);

                        $dataConCortes[] = $totalRow;
                        $totalesServicio = [];
                    }

                    // Fila título de servicio
                    $dataConCortes[] = [
                        'uid' => '',
                        'nombre' => 'SERVICIO: ' . $row['servicio'],
                        'escuela' => '',
                        'servicio' => '',
                    ];

                    $servicioActual = $row['servicio'];
                }

                // Acumula totales
                foreach ($meses as $m) {
                    $totalesServicio[$m] = ($totalesServicio[$m] ?? 0) + $row[$m];
                }

                $dataConCortes[] = $row;
            }

            // Último total
            if ($servicioActual !== null) {
                $totalRow = [
                    'uid' => '',
                    'nombre' => 'TOTAL SERVICIO',
                    'escuela' => '',
                    'servicio' => $servicioActual,
                ];
                foreach ($meses as $m) {
                    $totalRow[$m] = $totalesServicio[$m] ?? 0;
                }
                $totalRow['total'] = array_sum($totalesServicio);

                $dataConCortes[] = $totalRow;
            }

        $dataFin = $dataConCortes;
        $path = storage_path('app/public/rptCargosConcentrado.xlsx');
        Excel::store(new GenericExport($dataFin, $headers, $keys),'rptCargosConcentrado.xlsx',  'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/rptCargosConcentrado.xlsx' // URL pública para descargar el archivo
            ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al generar el reporte '
                ]);
            }
        }

        return $this->generateReportConcentrado(
            $data,
            array_fill(0, count($headers), 70),
            $keys,
            'REPORTE DE CARGOS CONCENTRADO',
            $headers,
            'L',
            'letter',
            'rptCargosConcentrado.pdf'
        );
    }

public function generateReport(
    $data,
    $columnWidths,
    $keys,
    $title,
    $headers,
    $orientation,
    $size,
    $nameReport
) {

    $imagePathEnc = public_path('images/encPag.png');
    $imagePathPie = public_path('images/piePag.png');

    $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
    $pdf->setHeaders(null, $columnWidths, $title);
    $pdf->setImagePaths($imagePathEnc, $imagePathPie, $orientation);

    $pdf->SetFont('helvetica', '', 14);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SIAWEB');
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(true, 25);

    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 8);

    $html2 = '<br><br><br><table border="0" cellpadding="1">';

    // ================= ENCABEZADOS =================
    $html2 .= '<tr>';
    foreach ($headers as $index => $header) {

        if ($index == 2) continue;

        $align = ($index > 3) ? 'right' : 'left';

        $html2 .= '<td style="font-size:9px;" width="' . $columnWidths[$index] . '" align="' . $align . '">
                        <b>' . htmlspecialchars($header) . '</b>
                   </td>';
    }
    $html2 .= '</tr>';
    $html2 .= '<tr><td colspan="' . count($headers) . '"></td></tr>';

    // ================= VARIABLES =================
    $servicioActual = '';
    $escuelaActual  = '';

    $totalesEscuela   = [];
    $totalesServicio  = [];
    $totalesGenerales = [];

    // ================= DATOS =================
    foreach ($data as $row) {

        // ---------- CORTE SERVICIO ----------
        if ($servicioActual !== $row['servicio']) {

            if ($servicioActual !== '') {
                $html2 .= '<tr><td colspan="' . count($keys) . '"><hr></td></tr>';
                $html2 .= '<tr style="font-weight:bold;font-size:8px;">
                            <td colspan="2">TOTAL SERVICIO: ' . htmlspecialchars($servicioActual) . '</td>';

                foreach (array_slice($keys, 3) as $key) {
                    $html2 .= '<td align="right">$ ' .
                        number_format($totalesServicio[$key] ?? 0, 2) . '</td>';
                }

                $html2 .= '</tr>';
                $totalesServicio = [];
            }

            $html2 .= '<tr>
                        <td colspan="' . count($keys) . '" style="font-weight:bold;font-size:8px;">
                        <br><br>SERVICIO: ' . htmlspecialchars($row['servicio']) . '
                        </td>
                       </tr>';
        }

        // ---------- FILA ----------
        $html2 .= '<tr>';
        foreach ($keys as $i => $key) {

            if (in_array($key, ['escuela', 'servicio'])) continue;

            $value = $row[$key] ?? '';

            if (in_array($key, ['uid', 'nombre'])) {
                $html2 .= '<td width="' . $columnWidths[$i] . '">' .
                            htmlspecialchars($value) . '</td>';
            } else {
                $html2 .= '<td width="' . $columnWidths[$i] . '" align="right">$ ' .
                            number_format((float)$value, 2) . '</td>';

                $totalesServicio[$key]  = ($totalesServicio[$key] ?? 0) + $value;
                $totalesGenerales[$key] = ($totalesGenerales[$key] ?? 0) + $value;
            }
        }
        $html2 .= '</tr>';

        $servicioActual = $row['servicio'];
        $escuelaActual  = $row['escuela'];
    }

    // ================= TOTAL GENERAL =================
    $html2 .= '<tr><td colspan="' . count($keys) . '"><br><br></td></tr>';
    $html2 .= '<tr style="font-weight:bold;font-size:10px;">
                <td colspan="2">TOTAL GENERAL</td>';

    foreach (array_slice($keys, 3) as $key) {
        $html2 .= '<td align="right">$ ' .
                    number_format($totalesGenerales[$key] ?? 0, 2) . '</td>';
    }

    $html2 .= '</tr></table>';

    $pdf->writeHTML($html2);

    $filePath = storage_path('app/public/' . $nameReport);
    $pdf->Output($filePath, 'F');

    return response()->json([
        'status'  => 200,
        'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/' . $nameReport
    ]);
}
  

public function generateReportConcentrado(
    $data,
    $columnWidths,
    $keys,
    $title,
    $headers,
    $orientation,
    $size,
    $nameReport
) {
    $imagePathEnc = public_path('images/encPag.png');
    $imagePathPie = public_path('images/piePag.png');

    $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
    $pdf->setHeaders(null, $columnWidths, $title);
    $pdf->setImagePaths($imagePathEnc, $imagePathPie, $orientation);

    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 8);

    $html = '<br><br><br><table border="0" cellpadding="2">';

    $servicioActual   = '';
    $totalesServicio  = [];
    $totalesGenerales = [];

    foreach ($data as $row) {

        if ($servicioActual !== $row['servicio']) {

            if ($servicioActual !== '') {
                $html .= '<tr><td colspan="' . count($keys) . '"><hr></td></tr>';
                $html .= '<tr style="font-weight:bold;">
                            <td>TOTAL SERVICIO: ' . htmlspecialchars($servicioActual) . '</td>';

                foreach (array_slice($keys, 1) as $k) {
                    $html .= '<td align="right">$ ' .
                        number_format($totalesServicio[$k] ?? 0, 2) . '</td>';
                }

                $html .= '</tr><tr><td colspan="' . count($keys) . '"><br></td></tr>';
                $totalesServicio = [];
            }

            $html .= '<tr>
                        <td colspan="' . count($keys) . '" style="font-weight:bold;font-size:10px;">
                        <br>SERVICIO: ' . htmlspecialchars($row['servicio']) . '
                        </td>
                      </tr>';

            $html .= '<tr>';
            foreach ($headers as $i => $h) {
                $align = ($i > 1) ? 'right' : 'left';
                $html .= '<td width="' . $columnWidths[$i] . '" align="' . $align . '">
                            <b>' . $h . '</b>
                          </td>';
            }
            $html .= '</tr>';
        }

        $html .= '<tr>';
        foreach ($keys as $i => $k) {

            if ($k === 'escuela') {
                $html .= '<td width="' . $columnWidths[$i] . '">' .
                            htmlspecialchars($row[$k]) . '</td>';
            } else {
                $value = (float)$row[$k];
                $html .= '<td width="' . $columnWidths[$i] . '" align="right">$ ' .
                            number_format($value, 2) . '</td>';

                $totalesServicio[$k]  = ($totalesServicio[$k] ?? 0) + $value;
                $totalesGenerales[$k] = ($totalesGenerales[$k] ?? 0) + $value;
            }
        }
        $html .= '</tr>';

        $servicioActual = $row['servicio'];
    }

    $html .= '<tr><td colspan="' . count($keys) . '"><hr></td></tr>';
    $html .= '<tr style="font-weight:bold;">
                <td>TOTAL GENERAL</td>';

    foreach (array_slice($keys, 1) as $k) {
        $html .= '<td align="right">$ ' .
                    number_format($totalesGenerales[$k] ?? 0, 2) . '</td>';
    }

    $html .= '</tr></table>';

    $pdf->writeHTML($html);
    $filePath = storage_path('app/public/' . $nameReport);
    $pdf->Output($filePath, 'F');

    return response()->json([
        'status'  => 200,
        'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/' . $nameReport
    ]);
}

public function actualizaCargos(Request $request)
{
    $validator = Validator::make($request->all(), [
        'idNivel'    => 'required|max:255',
        'idPeriodo'  => 'required|max:255',
        'uid'        => 'required|max:255',
        'secuencia'  => 'required|max:255'
    ]);

    if ($validator->fails()) {
        return $this->returnEstatus(
            'Error en la validación de los datos',
            400,
            $validator->errors()
        );
    }

    DB::statement(
        "CALL ActualizaCargosInscrip(?, ?, ?, ?)",
        [
            $request->idNivel,
            $request->idPeriodo,
            $request->uid,
            $request->secuencia
        ]
    );

    return $this->returnData('Cargos actualizados', null, 200);
}


}
