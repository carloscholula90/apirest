<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;  
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;  

use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF;
use App\Http\Controllers\Api\serviciosGenerales\GenericExport;

class SaldosController extends Controller{

    protected $pdfController;

    // Inyección de la clase PdfReportGenerator
    public function __construct(pdfController $pdfController)
    {
        $this->pdfController = $pdfController;
    }

    public function consulta($idNivel,$activo){

        $periodo = DB::table('periodo')
                       ->select('idPeriodo')
                       ->where('activo', 1)
                       ->where('idNivel', $idNivel)
                       ->first();

        $query = DB::table('alumno')
                        ->join('persona', 'persona.uid', '=', 'alumno.uid')
                        ->join('carrera as car', function ($join) {
                            $join->on('car.idNivel', '=', 'alumno.idNivel')
                                ->on('car.idCarrera', '=', 'alumno.idCarrera');
                        })
                        ->join('configuracionTesoreria as ct', 'ct.idNivel', '=', 'alumno.idNivel')

                        ->leftJoin('ciclos', function ($join) use ($periodo) {
                            $join->on('ciclos.uid', '=', 'alumno.uid')
                                ->on('ciclos.secuencia', '=', 'alumno.secuencia')
                                ->where('ciclos.idPeriodo', '=', $periodo->idPeriodo);
                        })

                        ->leftJoin('edocta as e', function ($join) use ($periodo) {
                            $join->on('e.uid', '=', 'alumno.uid')
                                ->on('e.secuencia', '=', 'alumno.secuencia')
                                ->where('e.idPeriodo', '=', $periodo->idPeriodo);
                        })

                        ->leftJoin('servicio as s', function ($join) {
                            $join->on('s.idServicio', '=', 'e.idServicio')
                                ->where('s.tipoEdoCta', 1);
                        })

                        ->where('alumno.idNivel', $idNivel)

                        ->groupBy(
                            'alumno.uid',
                            'persona.primerApellido',
                            'persona.segundoApellido',
                            'persona.nombre',
                            'car.descripcion',
                            'ciclos.grupo'
                        )

                        ->select([
                            'persona.uid',
                            'car.descripcion as carrera',
                            'ciclos.grupo',
                            DB::raw("CONCAT(
                                persona.primerApellido,' ',
                                persona.segundoApellido,' ',
                                persona.nombre
                            ) AS nombre"),

                            // 🔹 SALDO
                            DB::raw("
                                IFNULL(SUM(
                                    CASE WHEN e.tipomovto = 'C'
                                    THEN e.importe ELSE 0 END
                                ),0)
                                -
                                IFNULL(SUM(
                                    CASE 
                                        WHEN e.tipomovto = 'A'
                                        AND e.idServicio IN (
                                            ct.idServicioInscripcion,
                                            ct.idServicioColegiatura,
                                            ct.idServicioRecargo,
                                            ct.idServicioBeca,
                                            ct.idServicioNotaCredito,
                                            ct.idServicioTraspasoSaldos1
                                        )
                                    THEN e.importe ELSE 0 END
                                ),0)
                                AS saldo
                            "),
                        ]);

                    /* 🔹 SERVICIOS (solo si NO está activo) */
                    if ($activo == 0) {
                        $query->addSelect(DB::raw("
                            IFNULL(SUM(
                                CASE 
                                    WHEN e.tipomovto = 'A'
                                    AND e.idServicio NOT IN (
                                        ct.idServicioInscripcion,
                                        ct.idServicioColegiatura,
                                        ct.idServicioRecargo,
                                        ct.idServicioBeca,
                                        ct.idServicioNotaCredito,
                                        ct.idServicioTraspasoSaldos1
                                    )
                                THEN e.importe ELSE 0 END
                            ),0) AS servicios
                        "));
                    }

                    /* 🔹 FILTROS FINALES */
                    $query->havingRaw(
                        $activo == 0
                            ? '(saldo > 0 OR servicios = 0)'
                            : 'saldo > 0'
                    );
Log::info('SQL', [
    'query' => $query->toSql(),
    'bindings' => $query->getBindings()
]);
                    $dataArray = $query->get()->map(fn($i) => (array)$i)->toArray();

                    return $dataArray;

     }


     // Función para generar el reporte de personas
    public function generaReporte($idNivel){

       $config = DB::table('configuracion')
                    ->where('id_campo', 1)
                    ->first();

       $activo = $config->valor ?? 0;

       $dataArray= $this->consulta($idNivel,$activo);
         
       $headers = ['UID', 'NOMBRE', 'CARRERA', 'GRUPO','ADEUDO'];
       $columnWidths = [80, 300, 200,100,100];
       $keys = ['uid', 'nombre', 'carrera', 'grupo','saldo'];

        if ($activo == 0) {
            $headers[] = 'ADEUDO SERVICIOS';
            $columnWidths[] = 100;
            $keys[] = 'servicios';
        }  

        $aleatorio =random_int(1, 1000);

        return $this->generateReport(
            $dataArray,
            $columnWidths,
            $keys,
            'REPORTE DE ADEUDOS',
            $headers,
            'L',
            'letter',
            'rptSaldos'.$aleatorio.'.pdf'
        );
    }  

     public function exportaExcel($idNivel) {
        $config = DB::table('configuracion')
                    ->where('id_campo', 1)
                    ->first();

        $activo = $config->valor ?? 0;

        $dataArray= $this->consulta($idNivel,$activo);

        $dataFinal = [];
        $cutRows   = [];

        $carreraActual = null;
        $totalCarrera  = 0;
        $totalGeneral  = 0;
        $rowExcel      = 2; // fila 1 son headers

    foreach ($dataArray as $row) {

        // ---------- CORTE DE CARRERA ----------
        if ($carreraActual !== $row['carrera']) {

                    // TOTAL CARRERA ANTERIOR
                    if ($carreraActual !== null) {
                        $dataFinal[] = [
                            'uid'     => '',
                            'nombre'  => 'TOTAL CARRERA ' . $carreraActual,
                            'carrera' => '',
                            'grupo'   => '',
                            'saldo'   => $totalCarrera,
                        ];
                        $cutRows[] = $rowExcel;
                        $rowExcel++;
                        $totalCarrera = 0;
                    }

                    // TITULO CARRERA
                    $dataFinal[] = [
                        'uid'     => '',
                        'nombre'  => 'CARRERA ' . $row['carrera'],
                        'carrera' => ' ',
                        'grupo'   => '',
                        'saldo'   => '',
                    ];
                    $cutRows[] = $rowExcel;
                    $rowExcel++;

                    $carreraActual = $row['carrera'];
                }

                // ---------- FILA NORMAL ----------
                $dataFinal[] = $row;
                $totalCarrera  += (float)$row['saldo'];
                $totalGeneral  += (float)$row['saldo'];
                $rowExcel++;
            }

            // ---------- ÚLTIMO TOTAL CARRERA ----------
            $dataFinal[] = [
                'uid'     => '',
                'nombre'  => 'TOTAL CARRERA ' . $carreraActual,
                'carrera' => '',
                'grupo'   => '',
                'saldo'   => $totalCarrera,
            ];
            $cutRows[] = $rowExcel;
            $rowExcel++;

            // ---------- TOTAL GENERAL ----------
            $dataFinal[] = [
                'uid'     => '',
                'nombre'  => 'TOTAL GENERAL',
                'carrera' => '',
                'grupo'   => '',
                'saldo'   => $totalGeneral,
            ];
            $cutRows[] = $rowExcel;

         
        $headers = ['UID', 'NOMBRE', 'CARRERA', 'GRUPO','ADEUDO'];
        $keys = ['uid', 'nombre', 'carerra', 'grupo','saldo'];

        if ($activo == 0) {
            $headers[] = 'ADEUDO SERVICIOS';            
            $keys[] = 'servicios';
        }  
        // Guardar el archivo en el disco público
        $aleatorio =random_int(1, 1000);
        $path = storage_path('app/public/rptAdeudos'.$aleatorio.'.xlsx');
        Excel::store(new GenericExport($dataFinal, $headers, $keys),'rptAdeudos'.$aleatorio.'.xlsx',  'public');
        
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.pruebas.com.mx/storage/app/public/rptAdeudos'.$aleatorio.'.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }
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

    $totalesEscuela   = 0;
    $totalesGenerales = 0;
    $escuela='';

    // ================= DATOS =================
    foreach ($data as $row) {

        // ---------- CORTE ESCUELA ----------
        if ($escuela !== $row['carrera']) {

            if ($escuela !== '') {
                $html2 .= '<tr><td colspan="' . count($keys) . '"><hr></td></tr>';
                $html2 .= '<tr style="font-weight:bold;font-size:8px;">
                            <td colspan="2">TOTAL CARRERA ' . htmlspecialchars($escuela) . '</td>';
                $html2 .= '<td align="right">$ ' .
                        number_format($totalesEscuela ?? 0, 2) . '</td>';                

                $html2 .= '</tr>';
                $totalesEscuela = 0;
            }

            $html2 .= '<tr>
                        <td colspan="' . count($keys) . '" style="font-weight:bold;font-size:8px;">
                        <br><br>CARRERA ' . htmlspecialchars($row['carrera']) . '
                        </td>
                       </tr>';
        }

        // ---------- FILA ----------
        $html2 .= '<tr>';
        foreach ($keys as $i => $key) {

            if (in_array($key, ['idCarrera','carrera'])) continue;

            $value = $row[$key] ?? '';

            if (in_array($key, ['uid', 'nombre', 'carerra', 'grupo'])) {
                $html2 .= '<td width="' . $columnWidths[$i] . '">' .
                            htmlspecialchars($value) . '</td>';
            } else {
                $html2 .= '<td width="' . $columnWidths[$i] . '" align="right">$ ' .
                            number_format((float)$value, 2) . '</td>';

                $totalesEscuela  = ($totalesEscuela ?? 0) + $value;
                $totalesGenerales = ($totalesGenerales ?? 0) + $value;
            }
        }
        $html2 .= '</tr>';
        $escuela  = $row['carrera'];
    }

    // ================= TOTAL GENERAL =================
    $html2 .= '<tr><td colspan="' . count($keys) . '"><hr></td></tr>';
    $html2 .= '<tr style="font-weight:bold;font-size:8px;">
                            <td colspan="2">TOTAL CARRERA ' . htmlspecialchars($escuela) . '</td>';
    $html2 .= '<td align="right">$ ' .
                        number_format($totalesEscuela ?? 0, 2) . '</td>';                

    $html2 .= '</tr>';
    $html2 .= '<tr><td colspan="' . count($keys) . '"><br><br></td></tr>';
    $html2 .= '<tr style="font-weight:bold;font-size:10px;">
                <td colspan="2">TOTAL GENERAL</td>';
    $html2 .= '<td align="right">$ ' .
                    number_format($totalesGenerales ?? 0, 2) . '</td>';

    $html2 .= '</tr></table>';

    $pdf->writeHTML($html2);

    $filePath = storage_path('app/public/' . $nameReport);
    $pdf->Output($filePath, 'F');

    return response()->json([
        'status'  => 200,
        'message' => 'https://reportes.pruebas.com.mx/storage/app/public/' . $nameReport
    ]);
}
}
