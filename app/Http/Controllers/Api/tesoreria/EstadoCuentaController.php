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

class EstadoCuentaController extends Controller
{
    public function index($uid,$idPeriodo,$matricula)
    {
        $resultados = $this->obtenerEstadoCuenta($uid,$idPeriodo,$matricula);
        return $this->returnData('EstadoCuenta',$resultados,200);
    }

    public function validarQR($uid,$qr){
        $resultados = $this->obtenerEstadoCuenta($uid,null,null,$qr);
        return $this->returnData('movimientos',$resultados,200);
    }

    public function obtenerFolios($uid){
            $resultados = DB::table('edocta')
                    ->select(DB::raw('SUM(importe) as importe'), 'folio','fechaMovto')                        
                    ->where('uid', $uid)
                    ->groupBy('folio','fechaMovto')
                    ->get();
     return $this->returnData('folios',$resultados,200);
    }

    public function obtenerEstadoCuenta($uid, $idPeriodo, $matricula, $qr = null)
{
    // Ejecutar procedimiento almacenado
    DB::statement("CALL saldo(?, ?, ?, @vencido, @total)", [$uid, $matricula, $idPeriodo]);
    $saldoResult = DB::select("SELECT @vencido AS vencido, @total AS total");

    $vencido = $saldoResult[0]->vencido ?? 0;
    $total = $saldoResult[0]->total ?? 0;

            // Armar select dinámico
            $selects = [
                'edo.parcialidad',
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
                DB::raw("CONCAT( s.descripcion, ' ', 
                    CASE WHEN colegiatura.idServicioColegiatura = s.idServicio THEN
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
                ELSE 
                CASE WHEN s.idServicio = recargo.idServicioRecargo THEN
                    CASE edo.referencia
                            WHEN '00000001' THEN 'ENERO'
                            WHEN '00000002' THEN 'FEBRERO'
                            WHEN '00000003' THEN 'MARZO'
                            WHEN '00000004' THEN 'ABRIL'
                            WHEN '00000005' THEN 'MAYO'
                            WHEN '00000006' THEN 'JUNIO'
                            WHEN '00000007' THEN 'JULIO'
                            WHEN '00000008' THEN 'AGOSTO'
                            WHEN '00000009' THEN 'SEPTIEMBRE'
                            WHEN '00000010' THEN 'OCTUBRE'
                            WHEN '00000011' THEN 'NOVIEMBRE'
                            WHEN '00000012' THEN 'DICIEMBRE'
                            ELSE ''
                        END
                        ELSE ''
                END
                                                
                END) AS servicio"),
                'fp.descripcion as formaPago',
                'edo.fechaMovto as fechaPago',
                'edo.consecutivo',
                'edo.idServicio',
                'inscripcion.idServicioInscripcion',
                'colegiatura.idServicioColegiatura',
                DB::raw("CASE WHEN edo.tipomovto = 'C' THEN edo.importe ELSE null END as cargo"),
                DB::raw("CASE WHEN edo.tipomovto != 'C' THEN edo.importe ELSE null END as abono"),
            ];

            // Agregar vencido y total solo si existen (validados)
            if (!is_null($vencido)) {
                $selects[] = DB::raw($vencido . ' AS vencido');
            }

            if (!is_null($total)) {
                $selects[] = DB::raw($total . ' AS total');
            }

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
                ->leftJoin('configuracionTesoreria as recargo', function ($join) {
                    $join->on('recargo.idNivel', '=', 'al.idNivel')
                        ->on('recargo.idServicioRecargo', '=', 's.idServicio');
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

    public function generaReporte($uid,$idPeriodo,$matricula){

        $results = $this->obtenerEstadoCuenta($uid,$idPeriodo,$matricula);
       
    // Si no hay personas, devolver un mensaje de error
        if ($results->isEmpty())
            return $this->returnEstatus('No existen datos para generar el estado de cuenta',404,null);
        
        $headers = ['FECHA', 'FOLIO','CONCEPTO','CARGO','ABONO','FORMA DE PAGO'];
        $columnWidths = [50,70,150,50,50,80];   
        $keys = ['fechaPago','referencia','servicio','cargo','abono','formaPago'];
       
        $resultsArray = $results->map(function ($item) {
            return (array) $item; // Convertir cada stdClass a un arreglo
        })->toArray();       
       
        return $this->generateReport($resultsArray,$columnWidths,$keys , 'ESTADO DE CUENTA', $headers,'P','letter',
                        'rptEstadoCta_'.$uid.'.pdf');
      
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
        $html2 = '<table border="0" cellpadding="1">';
        $generalesRow = $data[0];

        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Nivel:</b> '.$generalesRow['nivel'].'</td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Carrera:</b> '.$generalesRow['nombreCarrera'].'</td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>UID:</b> '.$generalesRow['uid'].'</td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Matricula:</b> '.$generalesRow['matricula'].'</td></tr>';  
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Nombre:</b> '.$generalesRow['nombre'].' '.$generalesRow['apellidopat'].' '.$generalesRow['apellidomat'].'</td></tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $html2 .= '<tr>';
       
        foreach ($headers as $index => $header)
            $html2 .= '<td style="font-size: 9px;" width="' . $columnWidths[$index] . '"><b>' . htmlspecialchars($header) . '</b></td>';
        $html2 .= '</tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $total =0;
        $totalVencido =0;

        foreach ($data as $index2 => $row) {            
            $html2 .= '<tr>';   
            foreach ($keys as $index => $key) {  
                
                if($key=='cargo')  
                     $total =   $total + isset($row[$key]) ? $row[$key] : 0;
                else if($key=='abono')  
                     $total =   $total - isset($row[$key]) ? $row[$key] : 0;   

                Log::info('importe:'.$key.' '.$row[$key]); 
                $value = isset($row[$key]) ? $row[$key] : '';     
                $html2 .= '<td width="' . $columnWidths[$index] . '">' . ($value !== null ? htmlspecialchars((string)$value) : '') . '</td>';
            }
                $html2 .= '</tr>';
        }

        $html2 .= '<tr><td colspan="7"></td></tr>';   
        $html2 .= '<tr><td colspan="7"><hr style="border: 1px dotted black; background-size: 20px 10px;"></td></tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>TOTAL:</b>$ '.number_format($generalesRow['total'], 2, '.', ',') .'</td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>TOTAL VENCIDO:$ </b>'.number_format($generalesRow['vencido'], 2, '.', ',') .'</td></tr>';
     
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

/**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         $validator = Validator::make($request->all(), [
                                        'uid' => 'required|max:255',
                                        'secuencia' => 'required|max:255',
                                        'idPeriodo' => 'required|max:255',
                                        'uidcajero' => 'required|max:255',
                                        'movimientos' => 'required|array'              
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $fecha = Carbon::now('America/Mexico_City')->locale('es')->translatedFormat('Y-m-d');
        $maxId = EstadoCuenta::max('folio');  
        $newId = $maxId ? $maxId + 1 : 1; 
    
        try{
        $result = DB::table('configuracionTesoreria as ct')
                        ->join('alumno as al', function ($join) use ($request) {
                            $join->on('ct.idNivel', '=', 'al.idNivel')
                                ->where('al.uid', '=', $request->uid)
                                ->where('al.secuencia', '=', $request->secuencia);
                        })
                        ->select(
                            'ct.idServicioColegiatura',
                            'ct.idServicioInscripcion',
                            'ct.idServicioRecargo'
                        )
                        ->first(); // solo un registro

                    if ($result) {
                        $idServicioColegiatura = $result->idServicioColegiatura;
                        $idServicioInscripcion = $result->idServicioInscripcion;
                        $idServicioRecargo     = $result->idServicioRecargo;
                    }

       
        foreach ($request->movimientos as $movimiento) {
       
            $consecutivo = EstadoCuenta::where('uid', $request->uid)
                                        ->where('secuencia', $request->secuencia)
                                        ->where('idServicio', $movimiento['idServicio'])
                                        ->max('consecutivo');
            $consecutivo = $consecutivo ? $consecutivo + 1 : 1;
        
            //Validar si el registro corresponde a inscripcion 
            $importe = DB::table('edocta as edo')
                        ->join('configuracionTesoreria as ct', 'edo.idServicio', '=', 'ct.idServicioInscripcion')
                        ->join('alumno as al', function ($join) use ($request) {
                            $join->on('al.uid', '=', 'edo.uid')
                                ->on('al.secuencia', '=', 'edo.secuencia')
                                ->where('al.uid', '=', $request->uid)
                                ->where('al.secuencia', '=', $request->secuencia);
                        })
                        ->join('periodo as per', function ($join) {
                            $join->on('per.idNivel', '=', 'al.idNivel')
                                ->on('edo.idPeriodo', '=', 'per.idPeriodo')
                                ->where('per.activo', '=', 1);
                        })
                        ->where('ct.idServicioInscripcion', '=', $movimiento['idServicio'])
                        ->selectRaw("IFNULL(SUM(CASE WHEN edo.tipomovto = 'C' THEN edo.importe ELSE -1 * edo.importe END), 0) as importe")
                        ->value('importe'); // Devuelve solo el valor
            
        $importeProrratear = 0;  
        if($idServicioInscripcion==$movimiento['idServicio']) {       
            if($importe >= $movimiento['importe']){ // Indica que el servicio que se integrò se asocia a inscripciòn  
                //Solo es un abono
                $edoCta = EstadoCuenta::create([
                        'uid'=> $request->uid,
                        'secuencia'=> $request->secuencia,
                        'idServicio'=> $movimiento['idServicio'],
                        'consecutivo'=> $consecutivo,
                        'importe'=> $movimiento['importe'],
                        'idPeriodo'=> $request->idPeriodo,
                        'fechaMovto'=> $fecha,
                        'idformaPago'=> $movimiento['idformaPago'],
                        'cuatrodigitos'=> $movimiento['cuatrodigitos'],
                        'tipomovto'=> $movimiento['tipomovto'],
                        'FechaPago'=> $fecha,
                        'folio'=> $newId,
                        'uidcajero'=> $request->uidcajero
                    ]);
                    $importeProrratear = 0;
                    break; 
            } else {
                //pago completo
                $edoCta = EstadoCuenta::create([
                        'uid'=> $request->uid,
                        'secuencia'=> $request->secuencia,
                        'idServicio'=> $movimiento['idServicio'],
                        'consecutivo'=> $consecutivo,
                        'importe'=> $importe,
                        'idPeriodo'=> $request->idPeriodo,
                        'fechaMovto'=> $fecha,
                        'idformaPago'=> $movimiento['idformaPago'],
                        'cuatrodigitos'=> $movimiento['cuatrodigitos'],
                        'tipomovto'=> $movimiento['tipomovto'],
                        'FechaPago'=> $fecha,
                        'folio'=> $newId,
                        'uidcajero'=> $request->uidcajero
                    ]);
                    //Importe a prorratear
                $importeProrratear = $movimiento['importe'] - $importe;
            }//if($importe >= $movimiento['importe'])
            }// if($idServicioInscripcion==$movimiento['idServicio'])

        if($idServicioColegiatura==$movimiento['idServicio']){
            $importeProrratear = $movimiento['importe'];
        }
        
        if($importeProrratear > 0){ //Importe a prorratear en colegiatura
                    $resultados = DB::table('configuracionTesoreria as ct')
                            ->join('alumno as al', function ($join) use ($request) {
                                $join->on('ct.idNivel', '=', 'al.idNivel')
                                    ->where('al.uid', '=', $request->uid)
                                    ->where('al.secuencia', '=', $request->secuencia);
                            })
                            ->join('periodo as per', function ($join) {
                                $join->on('per.idNivel', '=', 'al.idNivel')
                                    ->where('per.activo', '=', 1);
                            })
                            ->join('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
                            ->join('servicioCarrera as sc', function ($join) {
                                $join->on('sc.idNivel', '=', 'ct.idNivel')
                                    ->on('sc.idPeriodo', '=', 'per.idPeriodo');
                            })
                            ->join('servicio as s', 's.idServicio', '=', 'sc.idServicio')
                            ->join('edocta as cta', function ($join) use ($request) {
                                $join->on('cta.idServicio', '=', 's.idServicio')
                                    ->where('cta.uid', '=', $request->uid)
                                    ->where('cta.tipomovto', '=', 'C')
                                    ->where('cta.secuencia', '=', $request->secuencia)
                                    ->whereColumn('cta.idPeriodo', 'per.idPeriodo');
                            })
                            ->leftJoin('edocta as ctaA', function ($join) use ($request) {
                                $join->on('ctaA.idServicio', '=', 's.idServicio')
                                    ->on('ctaA.parcialidad', '=', 'cta.parcialidad')
                                    ->where('ctaA.uid', '=', $request->uid)
                                    ->where('ctaA.tipomovto', '=', 'A')
                                    ->where('ctaA.secuencia', '=', $request->secuencia)
                                    ->whereColumn('ctaA.idPeriodo', 'per.idPeriodo');
                            })
                            ->leftJoin('edocta as cargos', function ($join) use ($request) {
                                $join->on('cargos.idServicio', '=', 'ct.idServicioRecargo')
                                    ->on('cargos.parcialidad', '=', 'cta.parcialidad')
                                    ->where('cargos.uid', '=', $request->uid)
                                    ->where('cargos.tipomovto', '=', 'C')
                                    ->where('cargos.secuencia', '=', $request->secuencia)
                                    ->whereColumn('cargos.idPeriodo', 'per.idPeriodo');
                            })
                            ->leftJoin('servicio as r', 'r.idServicio', '=', 'cargos.idServicio')
                            ->whereColumn('ct.idServicioColegiatura', 'sc.idServicio')
                            ->whereRaw('cta.importe - IFNULL(ctaA.importe, 0) > 0')
                            ->orderBy('cta.parcialidad')
                            ->select([
                                'cta.parcialidad',
                                's.idServicio',
                                DB::raw('(cta.importe - IFNULL(ctaA.importe, 0)) AS monto'),
                                DB::raw('cargos.idServicio AS idServicioCargo'),
                                DB::raw('IFNULL(cargos.importe, 0) AS cargos'),
                                DB::raw("CONCAT('100000', LPAD(MONTH(fechaVencimiento), 2, '0')) as mes")
                            ])
                            ->get();
        
            foreach ($resultados as $registro) {
                $consecutivo = EstadoCuenta::where('uid', $request->uid)
                                        ->where('secuencia', $request->secuencia)
                                        ->where('idServicio',$registro->idServicioCargo)
                                        ->max('consecutivo');
                $consecutivo = $consecutivo ? $consecutivo + 1 : 1;
  
                if($registro->cargos>0){
                    $consecutivo = $consecutivo ? $consecutivo + 1 : 1;
                    if($importeProrratear>$registro->cargos){ //Se cubren todos los cargos
                        $edoCta = EstadoCuenta::create([
                                                'uid'=> $request->uid,
                                                'secuencia'=> $request->secuencia,
                                                'idServicio'=> $registro->idServicioCargo,
                                                'consecutivo'=> $consecutivo,
                                                'importe'=>$registro->cargos,
                                                'idPeriodo'=> $request->idPeriodo,
                                                'fechaMovto'=> $fecha,
                                                'idformaPago'=> $movimiento['idformaPago'],
                                                'cuatrodigitos'=> $movimiento['cuatrodigitos'],
                                                'tipomovto'=> $movimiento['tipomovto'],
                                                'FechaPago'=> $fecha,
                                                'folio'=> $newId,
                                                'referencia'=>$registro->mes,
                                                'parcialidad'=> $registro->parcialidad,
                                                'uidcajero'=> $request->uidcajero
                        ]);
                        $importeProrratear = $importeProrratear-$registro->cargos;
                }
                else {
                    $edoCta = EstadoCuenta::create([
                                                'uid'=> $request->uid,
                                                'secuencia'=> $request->secuencia,
                                                'idServicio'=> $registro->idServicioCargo,
                                                'consecutivo'=> $consecutivo,
                                                'importe'=>$importeProrratear,
                                                'idPeriodo'=> $request->idPeriodo,
                                                'fechaMovto'=> $fecha,
                                                'idformaPago'=> $movimiento['idformaPago'],
                                                'cuatrodigitos'=> $movimiento['cuatrodigitos'],
                                                'tipomovto'=> $movimiento['tipomovto'],
                                                'FechaPago'=> $fecha,
                                                'folio'=> $newId,
                                                'referencia'=>$registro->mes,
                                                'parcialidad'=> $registro->parcialidad,
                                                'uidcajero'=> $request->uidcajero
                        ]);
                        $importeProrratear=0;
                        break;
        }//if($importeProrratear>$registro->cargos)
    }//if($registro->cargos>0)
    //No tiene cargos pendientes entonces el importe se va a la colegiatura
        
    if($importeProrratear >0){
            $consecutivo = EstadoCuenta::where('uid', $request->uid)
                                        ->where('secuencia', $request->secuencia)
                                        ->where('idServicio',$movimiento['idServicio'])
                                        ->max('consecutivo');
            $consecutivo = $consecutivo ? $consecutivo + 1 : 1;

            if($importeProrratear - $registro->monto>0){
                     $edoCta = EstadoCuenta::create([
                                                'uid'=> $request->uid,
                                                'secuencia'=> $request->secuencia,
                                                'idServicio'=> $registro->idServicioCargo,
                                                'consecutivo'=> $consecutivo,
                                                'importe'=>$registro->monto,
                                                'idPeriodo'=> $request->idPeriodo,
                                                'fechaMovto'=> $fecha,
                                                'idformaPago'=> $movimiento['idformaPago'],
                                                'cuatrodigitos'=> $movimiento['cuatrodigitos'],
                                                'tipomovto'=> $movimiento['tipomovto'],
                                                'FechaPago'=> $fecha,
                                                'folio'=> $newId,
                                                'referencia'=>$registro->mes,
                                                'parcialidad'=>$registro->parcialidad,
                                                'uidcajero'=>$request->uidcajero
                        ]);
                        $importeProrratear= $importeProrratear - $registro->monto;
  
    }
    else {
             $edoCta = EstadoCuenta::create([
                                                'uid'=> $request->uid,
                                                'secuencia'=> $request->secuencia,
                                                'idServicio'=> $registro->idServicioCargo,
                                                'consecutivo'=> $consecutivo,
                                                'importe'=>$importeProrratear,
                                                'idPeriodo'=> $request->idPeriodo,
                                                'fechaMovto'=> $fecha,
                                                'idformaPago'=> $movimiento['idformaPago'],
                                                'cuatrodigitos'=> $movimiento['cuatrodigitos'],
                                                'tipomovto'=> $movimiento['tipomovto'],
                                                'FechaPago'=> $fecha,
                                                'folio'=> $newId,
                                                'referencia'=>$registro->mes,
                                                'parcialidad'=>$registro->parcialidad,
                                                'uidcajero'=>$request->uidcajero
                        ]);
                        $importeProrratear= 0;
                        break;
  
    }
    }
    }//foreach ($resultados as $registro) 
    }// if($importeProrratear > 0)
    else {//Es cualquier otro servicio
        $consecutivo = EstadoCuenta::where('uid', $request->uid)
                                        ->where('secuencia', $request->secuencia)
                                        ->where('idServicio',$movimiento['idServicio'])
                                        ->max('consecutivo');
        

        if($movimiento['cargoAut']==1){
            $consecutivo = $consecutivo ? $consecutivo + 1 : 1;
            $edoCta = EstadoCuenta::create([
                                            'uid'=> $request->uid,
                                            'secuencia'=> $request->secuencia,
                                            'idServicio'=> $movimiento['idServicio'],
                                            'consecutivo'=> $consecutivo,
                                            'importe'=> $movimiento['importe'],
                                            'idPeriodo'=> $request->idPeriodo,
                                            'fechaMovto'=> $fecha,
                                            'tipomovto'=> 'C',
                                            'FechaPago'=> $fecha,
                                            'uidcajero'=> $request->uidcajero
                    ]);
                }
                $consecutivo = $consecutivo + 1 ;    
                $edoCta = EstadoCuenta::create([
                                            'uid'=> $request->uid,
                                            'secuencia'=> $request->secuencia,
                                            'idServicio'=> $movimiento['idServicio'],
                                            'consecutivo'=> $consecutivo,
                                            'importe'=> $movimiento['importe'],
                                            'idPeriodo'=> $request->idPeriodo,
                                            'fechaMovto'=> $fecha,
                                            'idformaPago'=> $movimiento['idformaPago'],
                                            'cuatrodigitos'=> $movimiento['cuatrodigitos'],
                                            'tipomovto'=> $movimiento['tipomovto'],
                                            'FechaPago'=> $fecha,
                                            'folio'=> $newId,
                                            'uidcajero'=> $request->uidcajero
                ]);
    }
           
                
        
        }

        } catch (QueryException $e) {
                    // Capturamos el error relacionado con las restricciones
                    if ($e->getCode() == '23000') 
                        // Código de error para restricción violada (por ejemplo, clave foránea)
                        return $this->returnEstatus('El registro ya se encuentra dado de alta',400,null);
                        
                    return $this->returnEstatus('Error al insertar el registro',400,null);
                }

        if (!$edoCta) 
            return $this->returnEstatus('Error al crear el registro',500,null); 
        return $this->returnData('folio',$newId,200);   
    }


    public function guardarMovtos(Request $request){
        DB::beginTransaction(); 
         try {   
                    
            $movimientos = $request->all();  
            
            if (!is_array($movimientos)) 
                return response()->json(['error' => 'Datos inválidos, se espera un arreglo'], 400);
           
           foreach ($movimientos as $index => $mov) {
            if (!isset($mov['dia'], $mov['concepto'], $mov['abono'])) 
                return response()->json(['error' => "Falta campo en elemento $index",], 400);
    
                $dia = $mov['dia'];
                $concepto = $mov['concepto'];
                $abono = $mov['abono'];

                $sinPrefijo = substr($concepto, 2);
                $matricula = (int) substr($sinPrefijo, 0, 8); 
                $servicio = (int) substr($sinPrefijo, 8, 3); 
                $datosAlumno = DB::table('alumno')
                        ->join('periodo', 'periodo.idNivel', '=', 'alumno.idNivel')
                        ->leftJoin('edocta', function($join) use ($servicio) {
                                                    $join->on('edocta.idPeriodo', '=', 'periodo.idPeriodo')
                                                        ->on('edocta.uid', '=', 'alumno.uid')
                                                        ->on('edocta.secuencia', '=', 'alumno.secuencia')
                                                        ->where('edocta.idServicio', $servicio);
                                                })
                        ->select(
                            'alumno.uid',
                            'alumno.secuencia',
                            'periodo.idNivel',
                            'periodo.idPeriodo',
                            'edocta.parcialidad',
                            'edocta.importe'
                        )
                        ->where('periodo.activo', 1)
                        ->where('alumno.matricula', $matricula)
                        ->orderByDesc('edocta.parcialidad')
                        ->get();
            
            $validacionLinea = DB::table('alumno')
                        ->join('periodo', 'periodo.idNivel', '=', 'alumno.idNivel')
                        ->leftJoin('edocta', function($join) use ($servicio) {
                                                    $join->on('edocta.idPeriodo', '=', 'periodo.idPeriodo')
                                                        ->on('edocta.uid', '=', 'alumno.uid')
                                                        ->on('edocta.secuencia', '=', 'alumno.secuencia')
                                                        ->where('edocta.idServicio', $servicio);
                                                })
                        ->select(
                            'alumno.uid',
                            'alumno.secuencia',
                            'periodo.idNivel',
                            'periodo.idPeriodo',
                            'edocta.parcialidad',
                            'edocta.importe'
                        )
                        ->where('periodo.activo', 1)
                        ->where('alumno.matricula', $matricula)
                        ->where('edocta.referencia', $concepto)
                        ->get();

                if (!$validacionLinea->isEmpty()) {
                    $data = [
                            'message' => 'Error, el archivo ya habia sido cargado de manera previa',
                            'status' => 400
                        ];
                        return response()->json($data, 400);
                }
              
                $fila = $datosAlumno->first(); // Devuelve el primer (y único) resultado o null
              
                if ($fila) {
                    $dataParcialidad = DB::table('alumno')
                        ->join('periodo', 'periodo.idNivel', '=', 'alumno.idNivel')
                        ->leftJoin('edocta', function($join) use ($servicio) {
                                                    $join->on('edocta.idPeriodo', '=', 'periodo.idPeriodo')
                                                        ->on('edocta.uid', '=', 'alumno.uid')
                                                        ->on('edocta.secuencia', '=', 'alumno.secuencia')
                                                        ->where('edocta.idServicio', $servicio);
                                                })
                        ->select('edocta.parcialidad')
                        ->where('periodo.activo', 1)
                        ->where('edocta.tipomovto', 'A')
                        ->where('alumno.matricula', $matricula)
                        ->where('edocta.referencia', $concepto)
                        ->get();

                    $parcialidad =0;    
                    if($dataParcialidad) {   
                        $fParcialidad = $dataParcialidad->first(); 
                        if(isset($fParcialidad->parcialidad))
                            $parcialidad =$fParcialidad->parcialidad;
                        else $parcialidad=1;
                    }
                    $fecha = \Carbon\Carbon::createFromFormat('d-m-Y', $dia)->format('Y-m-d'); 
                    $maxConsecutivo = DB::table('alumno')
                                    ->join('periodo', 'periodo.idNivel', '=', 'alumno.idNivel')
                                    ->leftJoin('edocta', function($join) use ($servicio) {
                                        $join->on('edocta.idPeriodo', '=', 'periodo.idPeriodo')
                                            ->on('edocta.uid', '=', 'alumno.uid')
                                            ->on('edocta.secuencia', '=', 'alumno.secuencia')
                                            ->where('edocta.idServicio', $servicio);
                                    })
                                    ->where('periodo.activo', 1)
                                    ->where('alumno.matricula', $matricula)
                                    ->max('consecutivo');

                if($fila->parcialidad > 0){
                    if($abono>$fila->importe){
                        DB::table('edocta')->insert([
                                    'uid' => $fila->uid,
                                    'secuencia' => $fila->secuencia,
                                    'idServicio' => $servicio,
                                    'consecutivo' => $maxConsecutivo+1,
                                    'importe' => $fila->importe,
                                    'idPeriodo' => $fila->idPeriodo,
                                    'fechaMovto' => $fecha,
                                    'tipomovto' => 'A',  
                                    'parcialidad' => $parcialidad,
                                    'referencia'=>$concepto,
                                    'FechaPago'=> $fecha
                                ]);

                        DB::table('edocta')->insert([
                                    'uid' => $fila->uid,
                                    'secuencia' => $fila->secuencia,
                                    'idServicio' => $servicio,
                                    'consecutivo' => $maxConsecutivo + 2,
                                    'importe' => $abono-$fila->importe,
                                    'idPeriodo' => $fila->idPeriodo,
                                    'fechaMovto' => $fecha,
                                    'tipomovto' => 'A',  
                                    'parcialidad' => ($parcialidad + 1),
                                    'referencia'=>$concepto,
                                    'FechaPago'=> $fecha
                                ]);  
                                }   
                        else
                             DB::table('edocta')->insert([
                                    'uid' => $fila->uid,
                                    'secuencia' => $fila->secuencia,
                                    'idServicio' => $servicio,
                                    'consecutivo' => $maxConsecutivo+1,
                                    'importe' => $abono,
                                    'idPeriodo' => $fila->idPeriodo,
                                    'fechaMovto' => $fecha,
                                    'tipomovto' => 'A',  
                                    'parcialidad' => $parcialidad,
                                    'referencia'=>$concepto,
                                    'FechaPago'=> $fecha
                                ]);
                }    
            } 
           }
            // 👉 Todas se ejecutan en la misma sesión hasta aquí
            DB::commit(); // 🔒 Confirma todos los cambios
            $data = [ 'message' => 'Registros guardados',
                       'status' => 400];
            return response()->json($data, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

 }

