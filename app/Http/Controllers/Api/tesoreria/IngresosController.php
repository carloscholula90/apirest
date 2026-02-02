<?php

namespace App\Http\Controllers\Api\tesoreria;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF;
use App\Http\Controllers\Api\serviciosGenerales\GenericExport;
use Maatwebsite\Excel\Facades\Excel;

class IngresosController extends Controller
{
    // ===============================
    // PDF
    // ===============================
    public function index($concentrado, $inicio, $fin, $idCajero = null, $idCarrera = null)
    {
        return $this->generaReporte($concentrado, $inicio, $fin, $idCajero, $idCarrera, false);
    }

    // ===============================
    // EXCEL
    // ===============================
    public function indexExcel($concentrado, $inicio, $fin, $idCajero = null, $idCarrera = null)
    {
        return $this->generaReporte($concentrado, $inicio, $fin, $idCajero, $idCarrera, true);
    }

    // =====================================================
    // MÉTODO GENERAL
    // =====================================================
    private function generaReporte($concentrado, $inicio, $fin, $idCajero, $idCarrera, $excel)
    {
        $activo = DB::table('configuracion')
            ->where('id_campo', 1)
            ->value('valor') ?? 0;

        /*
        =====================================================
        1️⃣ ANALÍTICO (CORTE POR FORMA DE PAGO)
        =====================================================
        */
        if ($concentrado === 'N') {

            $query = DB::table('edocta as cta')
                ->select(
                    'cta.FechaPago',
                    'cta.uidcajero',
                    'ca.descripcion as carrera',
                    'p.descripcion as periodo',
                    'cta.idServicio',
                    'cta.uid',
                    'pers.nombre',
                    'pers.primerApellido',
                    'pers.segundoApellido',
                    'cta.importe',
                    'fp.descripcion as formadep'
                )
                ->join('persona as pers', 'pers.uid', '=', 'cta.uid')
                ->join('formaPago as fp', 'fp.idFormaPago', '=', 'cta.idformaPago')
                ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
                ->join('alumno as al', function ($j) {
                    $j->on('al.uid', '=', 'cta.uid')
                      ->on('al.secuencia', '=', 'cta.secuencia');
                })
                ->join('periodo as p', function ($j) {
                    $j->on('p.idPeriodo', '=', 'cta.idPeriodo')
                      ->on('p.idNivel', '=', 'al.idNivel');
                })
                ->join('carrera as ca', function ($j) {
                    $j->on('ca.idNivel', '=', 'al.idNivel')
                      ->on('ca.idCarrera', '=', 'al.idCarrera');
                })
                ->whereBetween('cta.FechaPago', [$inicio, $fin])
                ->where('cta.tipomovto', 'A')
                ->orderBy('fp.descripcion');

            if ($idCajero > 0) $query->where('cta.uidcajero', $idCajero);
            if ($idCarrera > 0) $query->where('ca.idCarrera', $idCarrera);
            if ($activo == 0) $query->where('s.tipoEdoCta', 1);

            $results = $query->get();
            if ($results->isEmpty()) {
                return response()->json(['status' => 500, 'message' => 'No hay registros']);
            }

            $headers = [
                'FECHA','CAJERO','CARRERA','PERIODO','SERVICIO',
                'UID','NOMBRE','APELLIDO P','APELLIDO M','IMPORTE'
            ];
            $keys = [
                'FechaPago','uidcajero','carrera','periodo','idServicio',
                'uid','nombre','primerApellido','segundoApellido','importe'
            ];

            if ($excel) {
                            // ===== DATA + CORTES =====
            $dataExcel = [];
            $cutRows = [];
            $rowExcel = 2;

            $formaPagoActual = null;
            $totalFormaPago = 0;

            foreach ($results as $r) {
                $row = (array)$r;

                if ($formaPagoActual !== null && $formaPagoActual !== $row['formadep']) {
                    $dataExcel[] = [
                        'FechaPago' => 'TOTAL ' . $formaPagoActual,
                        'importe'   => $totalFormaPago
                    ];
                    $cutRows[] = $rowExcel++;
                    $totalFormaPago = 0;
                }

                $dataExcel[] = $row;
                $rowExcel++;

                $totalFormaPago += $row['importe'];
                $formaPagoActual = $row['formadep'];
            }

            // último total
            $dataExcel[] = [
                'FechaPago' => 'TOTAL ' . $formaPagoActual,
                'importe'   => $totalFormaPago
            ];
            $cutRows[] = $rowExcel++;


                $export = new GenericExport($dataExcel, $headers, $keys);
                foreach ($cutRows as $r) $export->addCutRow($r);

                Excel::store($export, 'rptIngresosAnalitico.xlsx', 'public');

                return response()->json([
                    'status' => 200,
                    'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/rptIngresosAnalitico.xlsx'
                ]);
            }
            else{
                $resultsArray = $results->map(function ($item) {
                            return (array) $item; // Convertir cada stdClass a un arreglo
                        })->toArray();
            $columnWidths = [50, 50, 150, 80, 50, 50, 80, 80, 80, 80];
                        return $this->generateReport(
                            $resultsArray,
                            $columnWidths,
                            $keys,
                            'REPORTE DE INGRESOS ANALÍTICO',
                            $headers,
                            'L',
                            'letter',
                            'rptIngresos' . mt_rand(100, 999) . '.pdf'
                        );
            }
        }

        /*
        =====================================================
        2️⃣ CONCENTRADO POR CAJERO
        =====================================================
        */
        if ($concentrado === 'SC') {

            $results = DB::table('edocta as cta')
                ->select(
                    'cta.uidcajero',
                    DB::raw("CONCAT(pers.nombre,' ',pers.primerApellido,' ',pers.segundoApellido) nombre"),
                    DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%EFECTIVO%' THEN cta.importe ELSE 0 END) efectivo"),
                    DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%TARJETA%' THEN cta.importe ELSE 0 END) tarjeta")
                )
                ->join('persona as pers', 'pers.uid', '=', 'cta.uidcajero')
                ->join('formaPago as fp', 'fp.idFormaPago', '=', 'cta.idformaPago')
                ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
                ->whereBetween('cta.FechaPago', [$inicio, $fin])
                ->where('cta.tipomovto', 'A')
                ->groupBy('cta.uidcajero','pers.nombre','pers.primerApellido','pers.segundoApellido')
                ->get();

            
            $headers = ['CAJERO','NOMBRE','EFECTIVO','TRANSFERENCIA'];
            $keys = ['uidcajero','nombre','efectivo','tarjeta'];

            if ($excel) {
                $dataExcel = [];
            $cutRows = [];
            $rowExcel = 2;

            foreach ($results as $r) {
                $dataExcel[] = ['uidcajero' => 'CAJERO: ' . $r->uidcajero];
                $cutRows[] = $rowExcel++;

                $dataExcel[] = (array)$r;
                $rowExcel++;

                $dataExcel[] = [
                    'uidcajero' => 'TOTAL CAJERO',
                    'efectivo'  => $r->efectivo,
                    'tarjeta'   => $r->tarjeta
                ];
                $cutRows[] = $rowExcel++;
            }

                $export = new GenericExport($dataExcel, $headers, $keys);
                foreach ($cutRows as $r) $export->addCutRow($r);

                Excel::store($export, 'rptIngresosCajero.xlsx', 'public');

                return response()->json([
                    'status' => 200,
                    'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/rptIngresosCajero.xlsx'
                ]);
            }
            else{

                $columnWidths = [50, 150, 100, 100];
                $resultsArray = $results->map(function ($item) {
                    return (array) $item; // Convertir cada stdClass a un arreglo
                })->toArray();

                return $this->generateReport(
                    $resultsArray,
                    $columnWidths,
                    $keys,
                    'REPORTE DE INGRESOS CONCENTRADO POR CAJERO',
                    $headers,
                    'P',
                    'letter',
                    'rptIngresos' . mt_rand(100, 999) . '.pdf'
                );
            }
        }

        /*
        =====================================================
        3️⃣ CONCENTRADO POR CARRERA
        =====================================================
        */
        $results = DB::table('edocta as cta')
            ->select(
                'ca.idCarrera',
                'ca.descripcion',
                DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%EFECTIVO%' THEN cta.importe ELSE 0 END) efectivo"),
                DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%TARJETA%' THEN cta.importe ELSE 0 END) tarjeta")
            )
            ->join('alumno as al', function ($j) {
                $j->on('al.uid', '=', 'cta.uid')
                  ->on('al.secuencia', '=', 'cta.secuencia');
            })
            ->join('carrera as ca', function ($j) {
                $j->on('ca.idNivel', '=', 'al.idNivel')
                  ->on('ca.idCarrera', '=', 'al.idCarrera');
            })
            ->join('formaPago as fp', 'fp.idFormaPago', '=', 'cta.idformaPago')
            ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
            ->whereBetween('cta.FechaPago', [$inicio, $fin])
            ->where('cta.tipomovto', 'A')
            ->groupBy('ca.idCarrera','ca.descripcion')
            ->get();

        
        $headers = ['ID','CARRERA','EFECTIVO','TRANSFERENCIA'];
        $keys = ['idCarrera','descripcion','efectivo','tarjeta'];

        if ($excel) {
            $dataExcel = [];
        $cutRows = [];
        $rowExcel = 2;

        foreach ($results as $r) {
            $dataExcel[] = ['idCarrera' => 'CARRERA: ' . $r->descripcion];
            $cutRows[] = $rowExcel++;

            $dataExcel[] = (array)$r;
            $rowExcel++;

            $dataExcel[] = [
                'idCarrera' => 'TOTAL CARRERA',
                'efectivo'  => $r->efectivo,
                'tarjeta'   => $r->tarjeta
            ];
            $cutRows[] = $rowExcel++;
        }

            $export = new GenericExport($dataExcel, $headers, $keys);
            foreach ($cutRows as $r) $export->addCutRow($r);

            Excel::store($export, 'rptIngresosCarrera.xlsx', 'public');

            return response()->json([
                'status' => 200,
                'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/rptIngresosCarrera.xlsx'
            ]);
        }
        else{
            $columnWidths = [50, 200, 100, 100];
            $resultsArray = $results->map(function ($item) {
            return (array) $item; // Convertir cada stdClass a un arreglo
        })->toArray();   

        return $this->generateReport(
                        $resultsArray,
                        $columnWidths,
                        $keys,
                        'REPORTE DE INGRESOS CONCENTRADO POR CARRERA',
                        $headers,
                        'P',
                        'letter',
                        'rptIngresos' . mt_rand(100, 999) . '.pdf'
                    );

        }
    }

    public function generateReport($data, $columnWidths, $keys, $title, $headers, $orientation, $size, $nameReport)
    {
        // Rutas de las imágenes para el encabezado y pie
        $imagePathEnc = public_path('images/encPag.png');
        $imagePathPie = public_path('images/piePag.png');
        // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)
        $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);

        // Configurar los encabezados, las rutas de las imágenes y otros parámetros
        $pdf->setHeaders(null, $columnWidths, $title);
        $pdf->setImagePaths($imagePathEnc, $imagePathPie,$orientation);

        // Configurar las fuentes
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');

        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();

        // Establecer fuente para el cuerpo del documento
        $pdf->SetFont('helvetica', '', 8);
         // Generar la tabla HTML para los datos
        $html2 = '<br><br><br><table border="0" cellpadding="1">';
        $html2 .= '<tr>';  
        foreach ($headers as $index => $header)
            $html2 .= '<td style="font-size: 9px;" width="' . $columnWidths[$index] . '"><b>' . htmlspecialchars($header) . '</b></td>';
        $html2 .= '</tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $total = 0;
        $totalEfectivo = 0;
        $totalTarjeta = 0;
        $tipoPago='';  

        foreach ($data as $index2 => $row) {  

             if($tipoPago!="" && $tipoPago!=$row['formadep']){   //corte por forma de pago
                        $html2 .= '<tr><td colspan="10"><hr style="border: 1px dotted black; background-size: 20px 10px;"></td></tr>';
                        $html2 .= '<p style="font-weight: bold; font-size: 12px;"> TOTAL $ '.$tipoPago.' '.number_format($total, 2, '.', ',').'</p><br><br>';
                        $total=0;
                    }           
              $html2 .= '<tr>';   
              foreach ($keys as $index => $key) { 
                if($key!='formadep'){
                    if($key=='importe'){
                        $total += $row[$key] ?? 0;
                    }
                    if($key=='efectivo'){
                        $totalEfectivo += $row[$key] ?? 0;
                    }
                    if($key=='tarjeta'){
                        $totalTarjeta += $row[$key] ?? 0;
                    }
                $value = isset($row[$key]) ? $row[$key] : '';    
                $html2 .= '<td width="' . $columnWidths[$index] . '">' . ($value !== null ? htmlspecialchars((string)$value) : '') . '</td>';
             }}
            $html2 .= '</tr>';   

            if(isset($row['formadep']))        
                $tipoPago=$row['formadep'];           
        }

        $html2 .= '<tr><td colspan="10"><hr style="border: 1px dotted black; background-size: 20px 10px;"></td></tr>';
        $html2 .= '</table>';
          if($total>0)
           $html2 .= '<br><p style="font-weight: bold; font-size: 12px;"> TOTAL $ '.$tipoPago.' '.number_format($total, 2, '.', ',').'</p><br><br>';

        if($totalEfectivo>0)
           $html2 .= '<br><p style="font-weight: bold; font-size: 12px;"> TOTAL EFECTIVO $ '.number_format($totalEfectivo, 2, '.', ',').'</p>';

         if($totalTarjeta>0)
           $html2 .= '<p style="font-weight: bold; font-size: 12px;"> TOTAL TARJETA $ '.number_format($totalTarjeta, 2, '.', ',').'</p>';

        // Escribir la tabla en el PDF
        $pdf->writeHTML($html2, true, false, true, false, '');

        if($nameReport==null)
            $filePath = storage_path('app/public/reporte.pdf');  // Ruta donde se guardará el archivo
        else $filePath = storage_path('app/public/'.$nameReport);  // Ruta donde se guardará el archivo

        $pdf->Output($filePath, 'F');  // 'F' para guardar el archivo en el servidor

        // Ahora puedes verificar si el archivo se ha guardado correctamente en la ruta especificada.
        if (file_exists($filePath)) {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/'.$nameReport // Puedes devolver la ruta para fines de depuración
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte'
            ]);
        }    
    }
}
