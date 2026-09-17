<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\EstadoCuenta;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;  
use App\Http\Controllers\Api\serviciosGenerales\GenericExport;

class CondonacionController extends Controller  
{

    public function guardar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required|integer',
            'secuencia' => 'required|integer',
            'idServicio' => 'required|integer',
            'consecutivoEdocta' => 'required|integer',
            'idPeriodo' => 'required|integer',
            'idAutorizacion' => 'required|integer',
            'parcialidad' => 'nullable|integer',
            'fechaMovto' => 'nullable|date',
            'importe' => 'nullable|numeric|min:0',
            'importeCondonar' => 'required|numeric|min:0',
            'motivo' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus(
                'Error en la validacion de los datos',
                400,
                $validator->errors()
            );
        }

        try {
            $condonacion = DB::transaction(function () use ($request) {
                $edocta = DB::table('edocta')
                    ->where('uid', $request->uid)
                    ->where('secuencia', $request->secuencia)
                    ->where('idServicio', $request->idServicio)
                    ->where('consecutivo', $request->consecutivoEdocta)
                    ->where('idPeriodo', $request->idPeriodo)
                    ->where('tipomovto', 'C')
                    ->lockForUpdate()
                    ->first();

                if (!$edocta) {
                    return null;
                }

                $importeAnterior = (float) ($edocta->importe ?? 0);
                $importeCondonar = (float) $request->importeCondonar;

                if ($importeCondonar > $importeAnterior) {
                    return false;
                }

                $importeNuevo = $importeAnterior - $importeCondonar;
                $idServicio = $edocta->idServicio;

                $consecutivo = ((int) DB::table('condonar')
                    ->where('uid', $request->uid)
                    ->where('secuencia', $request->secuencia)
                    ->where('idServicio', $idServicio)
                    ->lockForUpdate()
                    ->max('consecutivo')) + 1;

                $data = [
                    'uid' => $request->uid,
                    'secuencia' => $request->secuencia,
                    'idServicio' => $idServicio,
                    'consecutivoEdocta' => $request->consecutivoEdocta,
                    'consecutivo' => $consecutivo,
                    'idPeriodo' => $request->idPeriodo,
                    'idAutorizacion' => $request->idAutorizacion,
                    'parcialidad' => $request->parcialidad,
                    'fechaMovto' => $request->fechaMovto ?? now()->toDateString(),
                    'importe' => $importeAnterior,
                    'importeCondonar' => $importeCondonar,
                    'motivo' => $request->motivo,
                ];

                DB::table('condonar')->insert($data);
                DB::table('edocta')
                    ->where('uid', $request->uid)
                    ->where('secuencia', $request->secuencia)
                    ->where('idServicio', $idServicio)
                    ->where('consecutivo', $request->consecutivoEdocta)
                    ->where('idPeriodo', $request->idPeriodo)
                    ->where('tipomovto', 'C')
                    ->update(['importe' => $importeNuevo]);

                $data['importeNuevo'] = $importeNuevo;

                return $data;
            });

            if (!$condonacion) {
                if ($condonacion === false) {
                    return $this->returnEstatus(
                        'El importe a condonar no puede ser mayor al importe del estado de cuenta',
                        400,
                        null
                    );
                }

                return $this->returnEstatus(
                    'No se encontro el movimiento de estado de cuenta',
                    404,
                    null
                );
            }

            return $this->returnData('condonacion', $condonacion, 200);
        } catch (\Throwable $e) {
            Log::error('Error al guardar condonacion', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return $this->returnEstatus(
                'Error al guardar la condonacion',
                500,
                $e->getMessage()
            );
        }
    }

    public function datosCondonaciones($fechaInicio, $fechaFin)
    {
        return DB::table('condonar as c')
            ->select(
                'c.uid',
                'c.secuencia',
                DB::raw("CONCAT(p.primerApellido, ' ', p.segundoApellido, ' ', p.nombre) AS alumno"),
                'c.idPeriodo',
                'c.idAutorizacion',
                'c.idServicio',
                's.descripcion as servicio',
                'c.consecutivoEdocta',
                'c.consecutivo',
                'c.parcialidad',
                'c.fechaMovto',
                'c.importe',
                'c.importeCondonar',
                'c.motivo'
            )
            ->leftJoin('persona as p', 'p.uid', '=', 'c.uid')
            ->leftJoin('servicio as s', 's.idServicio', '=', 'c.idServicio')
            ->whereBetween('c.fechaMovto', [$fechaInicio, $fechaFin])
            ->orderBy('c.fechaMovto')
            ->orderBy('c.uid')
            ->orderBy('c.secuencia')
            ->orderBy('c.consecutivo')
            ->get();
    }

    public function reporteCondonaciones($fechaInicio, $fechaFin)
    {
        $datos = $this->datosCondonaciones($fechaInicio, $fechaFin);

        if ($datos->isEmpty()) {
            return $this->returnEstatus('No hay condonaciones para generar el reporte', 404, null);
        }

        $datosArray = $datos->map(function ($item) {
            return (array) $item;
        })->toArray();

        $headers = ['UID', 'ALUMNO', 'PERIODO', 'AUT', 'SERV', 'DESCRIPCION', 'PARC', 'FECHA', 'IMPORTE', 'CONDONAR', 'MOTIVO'];
        $columnWidths = [40, 140, 50, 40, 40, 110, 35, 60, 60, 60, 160];
        $keys = ['uid', 'alumno', 'idPeriodo', 'idAutorizacion', 'idServicio', 'servicio', 'parcialidad', 'fechaMovto', 'importe', 'importeCondonar', 'motivo'];

        return $this->downloadReportCondonaciones(
            $datosArray,
            $columnWidths,
            $keys,
            'REPORTE DE CONDONACIONES',
            $headers,
            'rptCondonaciones' . mt_rand(100, 999) . '.pdf'
        );
    }

    public function reporteCondonacionesExcel($fechaInicio, $fechaFin)
    {
        $datos = $this->datosCondonaciones($fechaInicio, $fechaFin);

        if ($datos->isEmpty()) {
            return $this->returnEstatus('No hay condonaciones para exportar', 404, null);
        }

        $datosArray = $datos->map(function ($item) {
            return (array) $item;
        })->toArray();

        $headers = ['UID', 'ALUMNO', 'PERIODO', 'AUTORIZACION', 'ID SERVICIO', 'SERVICIO', 'PARCIALIDAD', 'FECHA MOVTO', 'IMPORTE', 'IMPORTE CONDONAR', 'MOTIVO'];
        $keys = ['uid', 'alumno', 'idPeriodo', 'idAutorizacion', 'idServicio', 'servicio', 'parcialidad', 'fechaMovto', 'importe', 'importeCondonar', 'motivo'];
        $fileName = 'reporte_condonaciones_' . mt_rand(100, 999) . '.xlsx';

        Excel::store(new GenericExport($datosArray, $headers, $keys), $fileName, 'public');

        $fullPath = storage_path('app/public/' . $fileName);

        if (file_exists($fullPath)) {
            return response()->json([
                'status' => 200,
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $fileName
            ]);
        }

        return $this->returnEstatus('Error al generar el reporte', 500, null);
    }

    public function downloadReportCondonaciones($data, $columnWidths, $keys, $title, $headers, $nameReport)
    {
        $imagePathEnc = public_path('images/encPag.png');
        $imagePathPie = public_path('images/piePag.png');
        $pdf = new CustomTCPDF('L', PDF_UNIT, 'legal', true, 'UTF-8', false);
        $pdf->setHeaders(null, $columnWidths, $title);
        $pdf->setImagePaths($imagePathEnc, $imagePathPie, 'L');
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');
        $pdf->SetMargins(10, 30, 10);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 6);

        $html = '<br><br><br><table border="0" cellpadding="1">';
        $html .= '<tr>';
        foreach ($headers as $index => $header) {
            $html .= '<td style="font-size: 6px;" width="' . $columnWidths[$index] . '"><b>' . htmlspecialchars($header) . '</b></td>';
        }
        $html .= '</tr>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($keys as $index => $key) {
                $value = $row[$key] ?? '';
                if (in_array($key, ['importe', 'importeCondonar'])) {
                    $value = '$ ' . number_format((float) $value, 2);
                }
                $html .= '<td width="' . $columnWidths[$index] . '">' . htmlspecialchars((string) $value) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        $filePath = storage_path('app/public/' . $nameReport);
        $pdf->Output($filePath, 'F');

        if (file_exists($filePath)) {
            return response()->json([
                'status' => 200,
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $nameReport
            ]);
        }

        return $this->returnEstatus('Error al generar el reporte', 500, null);
    }

    public function data($idFchInicio, $idFechaFin, $idCajero = null){
    $config = DB::table('configuracion')
                    ->where('id_campo', 1)
                    ->first();

    $activo = $config->valor ?? 0;

    $data = DB::table('edocta as cta')
                            ->select('al.uid', 'cta.uidcajero','s.idServicio','s.descripcion as servicio',
                             DB::raw('CONCAT(p.primerApellido, " ", p.segundoApellido, " ", p.nombre) AS alumno'),
                             DB::raw("CONCAT(pers.nombre, ' ', pers.primerApellido, ' ', pers.segundoApellido) AS cajero"),
                             DB::raw("STR_TO_DATE(cta.fechaMovto, '%Y-%m-%d') AS fechaMovto")
                            )
                            ->join('alumno as al', function ($join) {
                                            $join->on('al.uid', '=', 'cta.uid')
                                                 ->on('al.secuencia', '=', 'cta.secuencia');
                            })  
                            ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio') 
                            ->join('persona as p', 'p.uid', '=', 'al.uid')   
                            ->join('persona as pers', 'pers.uid', '=', 'cta.uidcajero')  
                            ->where('cta.fechaMovto', '>=', DB::raw("STR_TO_DATE('" . $idFchInicio . "', '%Y-%m-%d')"))
                            ->where('cta.fechaMovto', '<=', DB::raw("STR_TO_DATE('" . $idFechaFin . "', '%Y-%m-%d')"))
                            ->where(function ($q) {
                                        $q->where('cta.importe', 0)
                                        ->orWhereNull('cta.importe');
                                    });

            if ($idCajero>0) 
                $data->where('cta.uidcajero', '=', $idCajero);             
            
            if ($activo==0) 
                $data->where('s.tipoEdoCta', 1);
        
            $results = $data->orderBy('cta.uidcajero', 'asc')->get();
   
        $resultsArray = $results->map(function ($item) {
            return (array) $item; // Convertir cada stdClass a un arreglo
        })->toArray();   
        return $resultsArray;

    }

    public function index($idFchInicio, $idFechaFin, $idCajero = null){
    
     $resultsArray  = $this->data($idFchInicio, $idFechaFin, $idCajero);
           
            
        $headers = ['ID', 'NOMBRE', 'SERVICIO', 'DESCRIPCIÓN','FECHA MOV'];
        $columnWidths = [50, 200, 50, 100,100];
        $keys = ['uid', 'alumno', 'idServicio', 'servicio','fechaMovto'];

        return $this->generateReport(
                        $resultsArray,
                        $columnWidths,
                        $keys,
                        'REPORTE DE CONDONACIÓN',
                         $headers,
                        'P',
                        'letter',
                        'rptCondonacion' . mt_rand(100, 999) . '.pdf'
                    );        
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
        $cajero='';  

        foreach ($data as $index2 => $row) {  
            
             if($cajero='' || $cajero!=$row['uidcajero']){   //corte por forma de pago
                        $html2 .= '<p style="font-weight: bold; font-size: 9px;"> CAJERO  '.$row['uidcajero'].' '.$row['cajero'].'</p><br><br>';
                        
                    }           
              $html2 .= '<tr>';   
              foreach ($keys as $index => $key) { 
                
                $value = isset($row[$key]) ? $row[$key] : '';    
                $html2 .= '<td width="' . $columnWidths[$index] . '">' . ($value !== null ? htmlspecialchars((string)$value) : '') . '</td>';
             }
            $html2 .= '</tr>';   

            if(isset($cajero['uidcajero']))        
                $cajero=$row['uidcajero'];           
        }

        $html2 .= '</table>';
          
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
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/'.$nameReport // Puedes devolver la ruta para fines de depuración
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte'
            ]);
        }    
    }


   public function indexExcel($idFchInicio, $idFechaFin, $idCajero = null)
{
    // 1️⃣ Obtener datos
    $results = collect(
        $this->data($idFchInicio, $idFechaFin, $idCajero)
    )->toArray();

    if (empty($results)) {
        return response()->json([
            'status' => 404,
            'message' => 'No hay datos para exportar'
        ]);
    }

    // 2️⃣ Configuración del Excel
    $headers = ['ID', 'NOMBRE', 'SERVICIO', 'DESCRIPCIÓN', 'FECHA MOV'];
    $keys    = ['uid', 'alumno', 'idServicio', 'servicio', 'fechaMovto'];

    $excelData = [];

    // Creamos el export (se volverá a instanciar al final con los datos)
    $export = new GenericExport([], $headers, $keys);

    $rowNumber = 2; // fila 1 = encabezados
    $cajeroActual = null;

    // 3️⃣ Construcción de filas + cortes
    foreach ($results as $row) {

        if ($cajeroActual !== $row['uidcajero']) {

            // Fila de corte
            $excelData[] = [
                'uid'        => 'CAJERO: ' . $row['uidcajero'] . ' - ' . $row['cajero'],
                'alumno'     => '',
                'idServicio' => '',
                'servicio'   => '',
                'fechaMovto' => '',
            ];

            $export->addCutRow($rowNumber);
            $rowNumber++;
            $cajeroActual = $row['uidcajero'];
        }

        // Fila normal
        $excelData[] = [
            'uid'        => $row['uid'],
            'alumno'     => $row['alumno'],
            'idServicio' => $row['idServicio'],
            'servicio'   => $row['servicio'],
            'fechaMovto' => $row['fechaMovto'],
        ];

        $rowNumber++;
    }

    // 4️⃣ Export FINAL (con datos reales)
    $export = new GenericExport($excelData, $headers, $keys);

    $fileName = 'reporte_condonacion_' . mt_rand(100, 999) . '.xlsx';

    Excel::store($export, $fileName, 'public');

    // 5️⃣ Verificar archivo
    $fullPath = storage_path('app/public/' . $fileName);

    if (file_exists($fullPath)) {
        return response()->json([
            'status'  => 200,
            'message' => asset('storage/' . $fileName)
        ]);
    }

    return response()->json([
        'status'  => 500,
        'message' => 'Error al generar el reporte'
    ]);
}

    
}
