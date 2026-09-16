<?php

namespace App\Http\Controllers\Api\tesoreria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF;
use App\Http\Controllers\Api\serviciosGenerales\GenericExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;  

class CargosController extends Controller
{


    public function index($concentrado, $idPeriodo, $idNivel, $idServicio = null){
        return $this->generaReporte($concentrado, $idPeriodo, $idNivel, 0, $idServicio);
    }

    public function indexExcel($concentrado, $idPeriodo, $idNivel, $idServicio = null){
        return $this->generaReporte($concentrado, $idPeriodo, $idNivel, 1, $idServicio);
    }

    public function generaReporte($concentrado, $idPeriodo, $idNivel, $excel, $idServicio = null)
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

        $nivel = DB::table('nivel')
            ->where('idNivel', $idNivel)
            ->first();

        $descripcionNivel = $nivel->descripcion ?? $idNivel;
        $descripcionPeriodo = $periodo->descripcion ?? $idPeriodo;
        $tituloNivelReporte = 'NIVEL: ' . $descripcionNivel;
        $tituloPeriodoReporte = 'PERIODO: ' . $descripcionPeriodo;
        $subtituloReporte = "\nNIVEL: " . $descripcionNivel . "\nPERIODO: " . $descripcionPeriodo;
        $idServicioFiltro = ($idServicio !== null && (int)$idServicio > 0) ? (int)$idServicio : null;

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

            if ($idServicioFiltro === null) {
                return response()->json([
                    'status' => 422,
                    'message' => 'El idServicio es obligatorio para el reporte analítico.'
                ], 422);
            }

            $query = DB::table('edocta as cta')
                ->select(
                    'cta.uid',
                    'cta.idServicio',
                    's.descripcion as servicio',
                    'ca.descripcion as escuela',
                    DB::raw("CONCAT(pers.nombre,' ',pers.primerApellido,' ',pers.segundoApellido) AS nombre"),
                    DB::raw("MONTH(COALESCE(cta.FechaPago, cta.fechaVencimiento, cta.fechaMovto)) AS mes"),
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
                ->where('p.idPeriodo', $idPeriodo)
                ->where('ca.idNivel', $idNivel);

            if ($activo == 0) {
                $query->where('s.tipoEdoCta', 1);
            }

            if ($idServicioFiltro !== null) {
                $query->where('cta.idServicio', $idServicioFiltro);
            }

            $results = $query
                ->groupBy(
                    'cta.uid',
                    'cta.idServicio',
                    's.descripcion',
                    'ca.descripcion',
                    'pers.nombre',
                    'pers.primerApellido',
                    'pers.segundoApellido',
                    DB::raw("MONTH(COALESCE(cta.FechaPago, cta.fechaVencimiento, cta.fechaMovto))")
                )
                ->orderBy('cta.idServicio')
                ->orderBy('cta.uid')
                ->get();

                $results = $results->sortBy([
                            ['idServicio', 'asc'],
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

                $servicio = '(' . $r->idServicio . ') ' . $r->servicio;
                $key = $r->uid . '|' . $servicio;
                $mes = $meses[$r->mes];

                if (!isset($pivot[$key])) {
                    $pivot[$key] = [
                        'uid' => $r->uid,
                        'nombre' => $r->nombre,
                        'escuela' => $r->escuela,
                        'servicio' => $servicio,
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
                    Log::info('termino pivote 3:');
                  $dataFinal = $dataConCortes;
                  $path = storage_path('app/public/rptCargosAnalitico.xlsx');
                    $titleRowsExcel = [
                        'REPORTE DE CARGOS ANALÍTICO POR SERVICIO',
                        $tituloNivelReporte,
                        $tituloPeriodoReporte,
                    ];
                    Excel::store(new GenericExport($dataFinal, $headers, $keys, $titleRowsExcel),'rptCargosAnalitico.xlsx',  'public');
                
                    // Verifica si el archivo existe usando Storage de Laravel
                    if (file_exists($path))  {
                        return response()->json([
                            'status' => 200,  
                            'message' => 'https://reportes.siaweb.com.mx/storage/app/public/rptCargosAnalitico.xlsx' // URL pública para descargar el archivo
                        ]);
                        } else {
                            return response()->json([
                                'status' => 500,
                                'message' => 'Error al generar el reporte '
                            ]);
                        }
            }

            // ---------------- PDF ----------------
            $dataPdf = array_map(function ($row) {
                $row['uidNombre'] = trim(($row['uid'] ?? '') . ' - ' . ($row['nombre'] ?? ''));
                return $row;
            }, $data);

            $headersPdf = array_merge(['UID / NOMBRE','ESCUELA'], array_values($meses), ['TOTAL']);
            $keysPdf    = array_merge(['uidNombre','escuela'], array_values($meses), ['total']);

            $columnWidthsAnalitico = array_fill(0, count($headersPdf), 70);
            $columnWidthsAnalitico[0] = 250;

            return $this->generateReport(
                $dataPdf,
                $columnWidthsAnalitico,
                $keysPdf,
                'REPORTE DE CARGOS ANALÍTICO POR SERVICIO' . $subtituloReporte,
                $headersPdf,
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
                'cta.idServicio',
                's.descripcion as servicio',
                DB::raw("MONTH(COALESCE(cta.FechaPago, cta.fechaVencimiento, cta.fechaMovto)) AS mes"),
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
            ->where('p.idPeriodo', $idPeriodo)
            ->where('ca.idNivel', $idNivel);

        if ($activo == 0) {
            $query->where('s.tipoEdoCta', 1);
        }

        $results = $query
            ->groupBy('ca.descripcion','cta.idServicio','s.descripcion',DB::raw("MONTH(COALESCE(cta.FechaPago, cta.fechaVencimiento, cta.fechaMovto))"))
            ->get();
        $results = $results->sortBy([
                                    ['idServicio', 'asc'],
                                    ['escuela', 'asc'],
                                    ['mes', 'asc'],
                                ]);
        $pivot = [];

        foreach ($results as $r) {
            if (!isset($meses[$r->mes])) continue;

            $servicio = '(' . $r->idServicio . ') ' . $r->servicio;
            $key = $r->escuela . '|' . $servicio;
            $mes = $meses[$r->mes];

            if (!isset($pivot[$key])) {
                $pivot[$key] = [
                    'escuela' => $r->escuela,
                    'servicio' => $servicio
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
        $titleRowsExcel = [
            'REPORTE DE CARGOS CONCENTRADO POR SERVICIO',
            $tituloNivelReporte,
            $tituloPeriodoReporte,
        ];
        Excel::store(new GenericExport($dataFin, $headers, $keys, $titleRowsExcel),'rptCargosConcentrado.xlsx',  'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/rptCargosConcentrado.xlsx' // URL pública para descargar el archivo
            ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al generar el reporte '
                ]);
            }
        }

        $columnWidthsConcentrado = array_fill(0, count($headers), 65);
        $columnWidthsConcentrado[0] = 250;

        return $this->generateReportConcentrado(
            $data,
            $columnWidthsConcentrado,
            $keys,
            'REPORTE DE CARGOS CONCENTRADO POR SERVICIO' . $subtituloReporte,
            $headers,
            'L',
            'letter',
            'rptCargosConcentrado.pdf'
        );
    }

    public function serviciosDisponibles($idPeriodo, $idNivel)
    {
        $config = DB::table('configuracion')
            ->where('id_campo', 1)
            ->first();

        $activo = $config->valor ?? 0;

        $query = DB::table('edocta as cta')
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
            ->where('p.idPeriodo', $idPeriodo)
            ->where('ca.idNivel', $idNivel);

        if ($activo == 0) {
            $query->where('s.tipoEdoCta', 1);
        }

        $servicios = $query
            ->select(
                'cta.idServicio',
                's.descripcion',
                DB::raw("CONCAT('(', cta.idServicio, ') ', s.descripcion) AS servicio")
            )
            ->distinct()
            ->orderBy('cta.idServicio')
            ->get();

        return response()->json([
            'status' => 200,
            'servicios' => $servicios
        ]);
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

    set_time_limit(300);
    $imagePathEnc = public_path('images/encPag.png');
    $imagePathPie = public_path('images/piePag.png');

    $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
    $pdf->setHeaders(null, $columnWidths, $title);
    $pdf->setImagePaths($imagePathEnc, $imagePathPie, $orientation);

    $pdf->SetFont('helvetica', '', 14);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SIAWEB');
    $pdf->SetMargins(15, 55, 15);
    $pdf->SetAutoPageBreak(true, 25);

    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 8);

    $textKeys = ['uid', 'nombre', 'uidNombre'];
    $skipKeys = ['escuela', 'servicio'];
    $numericKeys = [];

    foreach ($keys as $key) {
        if (!in_array($key, $textKeys, true) && !in_array($key, $skipKeys, true)) {
            $numericKeys[] = $key;
        }
    }

    $totalLabelColspan = 0;
    foreach ($keys as $key) {
        if (in_array($key, $textKeys, true)) {
            $totalLabelColspan++;
        }
    }
    $totalLabelColspan = max(1, $totalLabelColspan);

    $html2 = '<table border="0" cellpadding="1"><thead>';

    // ================= ENCABEZADOS =================
    $html2 .= '<tr>';
    foreach ($headers as $index => $header) {

        $key = $keys[$index] ?? '';

        if (in_array($key, $skipKeys, true)) continue;

        $align = in_array($key, $numericKeys, true) ? 'right' : 'left';

        $html2 .= '<td style="font-size:9px;" width="' . $columnWidths[$index] . '" align="' . $align . '">
                        <b>' . htmlspecialchars($header) . '</b>
                   </td>';
    }
    $html2 .= '</tr>';
    $html2 .= '<tr><td colspan="' . count($headers) . '"></td></tr>';
    $html2 .= '</thead><tbody>';

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
                            <td colspan="' . $totalLabelColspan . '">TOTAL SERVICIO: ' . htmlspecialchars($servicioActual) . '</td>';

                foreach ($numericKeys as $key) {
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

            if (in_array($key, $skipKeys, true)) continue;

            $value = $row[$key] ?? '';

            if (in_array($key, $textKeys, true)) {
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

    if ($servicioActual !== '') {
        $html2 .= '<tr><td colspan="' . count($keys) . '"><hr></td></tr>';
        $html2 .= '<tr style="font-weight:bold;font-size:8px;">
                    <td colspan="' . $totalLabelColspan . '">TOTAL SERVICIO: ' . htmlspecialchars($servicioActual) . '</td>';

        foreach ($numericKeys as $key) {
            $html2 .= '<td align="right">$ ' .
                        number_format($totalesServicio[$key] ?? 0, 2) . '</td>';
        }

        $html2 .= '</tr>';
    }

    // ================= TOTAL GENERAL =================
    $html2 .= '<tr><td colspan="' . count($keys) . '"><br><br></td></tr>';
    $html2 .= '<tr style="font-weight:bold;font-size:8px;">
                <td colspan="' . $totalLabelColspan . '">TOTAL GENERAL</td>';

    foreach ($numericKeys as $key) {
        $html2 .= '<td align="right">$ ' .
                    number_format($totalesGenerales[$key] ?? 0, 2) . '</td>';
    }

    $html2 .= '</tr></tbody></table>';

    $pdf->writeHTML($html2);
Log::info('termino pivote 4ett45:');
    $filePath = storage_path('app/public/' . $nameReport);
    $pdf->Output($filePath, 'F');

    return response()->json([
        'status'  => 200,
        'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $nameReport
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

    $pdf->SetMargins(15, 55, 15);
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 8);

    $html = '<table border="0" cellpadding="2">';

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

    if ($servicioActual !== '') {
        $html .= '<tr><td colspan="' . count($keys) . '"><hr></td></tr>';
        $html .= '<tr style="font-weight:bold;">
                    <td>TOTAL SERVICIO: ' . htmlspecialchars($servicioActual) . '</td>';

        foreach (array_slice($keys, 1) as $k) {
            $html .= '<td align="right">$ ' .
                        number_format($totalesServicio[$k] ?? 0, 2) . '</td>';
        }

        $html .= '</tr>';
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
        'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $nameReport
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

     $datos = $this->obtenerSemestre($uid,$secuencia);       
        DB::statement("CALL ActualizaCargosInscrip(?, ?, ?, ?, ? ,?)", [$request->idNivel,$request->idPeriodo,
                                                         $request->uid,$request->secuencia, $datos->semestre, $datos->idTurno]);
        return $this->returnData('Cargos actualizados', null, 200);
    }

public function validaGeneraCargos(Request $request)
{
    $validator = Validator::make($request->all(), [
        'idNivel'    => 'required|max:255',
        'idPeriodo'  => 'required|max:255',
        'uid'        => 'required|max:255',
        'secuencia'  => 'required|max:255'
    ]);

    if ($validator->fails()) {
        return $this->returnEstatus(
            'Error en la validaciÃ³n de los datos',
            400,
            $validator->errors()
        );
    }

    $datos = $this->obtenerDatosCargoAlumno(
        $request->idNivel,
        $request->idPeriodo,
        $request->uid,
        $request->secuencia
    );

    if (!$datos) 
        return $this->returnEstatus(
            'No se encontro informacion de carrera, semestre y turno para el alumno',
            404,
            null
        );

        DB::statement(
        "CALL GeneraCargosInscrip(?, ?, ?, ?, ?, ?, ?)",
        [
            $request->idNivel,
            $request->idPeriodo,
            $datos->idCarrera,
            $datos->semestre,
            $request->uid,
            $request->secuencia,
            $datos->idTurno
        ]
    );

    DB::statement("CALL ActualizaCargosInscrip(?, ?, ?, ?, ?, ?)",
            [
                $request->idNivel,
                $request->idPeriodo,
                $request->uid,
                $request->secuencia,
                $datos->semestre,
                $datos->idTurno
            ]
        );

        return $this->returnData('resultado', [
            'message' => 'Cargos actualizados',
            'procedimiento' => 'ActualizaCargosInscrip'
        ], 200);
    
}

public function obtenerDatosCargoAlumno($idNivel,$idPeriodo,$uid,$secuencia)
{
    return DB::table('alumno as al')
        ->join('ciclos as cl', function($join) use ($idPeriodo) {
            $join->on('cl.uid', '=', 'al.uid')
                ->on('cl.secuencia', '=', 'al.secuencia')
                ->where('cl.idPeriodo', $idPeriodo)
                ->whereRaw('cl.indexCiclo = (
                    SELECT MIN(c2.indexCiclo)
                    FROM ciclos c2
                    WHERE c2.uid = al.uid
                    AND c2.secuencia = al.secuencia
                    AND c2.idPeriodo = cl.idPeriodo
                )');
        })
        ->join('turno as t', function($join) {
            $join->on('t.letra', '=', DB::raw(
                'SUBSTRING(cl.grupo, CASE WHEN LENGTH(cl.grupo) = 4 THEN 2 WHEN LENGTH(cl.grupo) = 5 THEN 3 ELSE 3 END, 1)'
            ));
        })
        ->where('al.idNivel', $idNivel)
        ->where('al.uid', $uid)
        ->where('al.secuencia', $secuencia)
        ->select('al.idCarrera', 'cl.semestre', 't.idTurno')
        ->first();
}
}
