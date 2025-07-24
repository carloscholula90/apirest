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

    public function obtenerEstadoCuenta($uid,$idPeriodo,$matricula)
    {
        return DB::table('edocta as edo')
                    ->select([
                            'al.uid',
                            'al.idNivel',
                            'al.idCarrera',
                            'al.matricula',
                            'nivel.descripcion as nivel',
                            'carrera.descripcion as nombreCarrera',                          
                            'persona.nombre',
                            'persona.primerapellido as apellidopat',
                            'persona.segundoapellido as apellidomat',  
                            's.descripcion as servicio',
                            'edo.referencia',
                            'fp.descripcion as formaPago',
                            'edo.fechaPago',
                             DB::raw("CASE WHEN edo.tipomovto = 'C' THEN edo.importe ELSE null END as cargo"),
                             DB::raw("CASE WHEN edo.tipomovto != 'C' THEN edo.importe ELSE null END as abono")
                            ])
                    ->join('servicio as s', 's.idServicio', '=', 'edo.idServicio')
                    ->leftJoin('formaPago as fp', 'fp.idFormaPago', '=', 'edo.idformaPago')
                    ->join('alumno as al', 'al.uid', '=', 'edo.uid')
                    ->join('nivel', 'nivel.idNivel', '=', 'al.idNivel')
                    ->join('carrera', 'carrera.idCarrera', '=', 'al.idCarrera')
                    ->join('persona', 'persona.uid', '=', 'al.uid')                  
                    ->where('edo.uid', $uid)
                    ->where('edo.idPeriodo', $idPeriodo)
                    ->where('al.matricula', $matricula)
                    ->get();
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

              if ($key === 'fechaPago' && !empty($row[$key])) {
    try {
        $fecha = Carbon::parse($row[$key])->startOfDay();
        $hoy = Carbon::today();

        Log::info('validar fechas: ' . $fecha->toDateString() . ' > ' . $hoy->toDateString() . ' ? ' . ($fecha->greaterThan($hoy) ? 'sí' : 'no'));

        if ($hoy->greaterThan($fecha
        )) {
            $totalVencido += $row['cargo'];
        }
    } catch (\Exception $e) {
        Log::warning('fechaPago inválida: ' . $row[$key]);
    }
}

                            $value = isset($row[$key]) ? $row[$key] : '';     
                $html2 .= '<td width="' . $columnWidths[$index] . '">' . ($value !== null ? htmlspecialchars((string)$value) : '') . '</td>';
            }
                $html2 .= '</tr>';
        }

        $html2 .= '<tr><td colspan="7"></td></tr>';   
        $html2 .= '<tr><td colspan="7"><hr style="border: 1px dotted black; background-size: 20px 10px;"></td></tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>TOTAL:</b>$ '.number_format($totalVencido, 2, '.', ',') .'</td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>TOTAL VENCIDO:$ </b>'.number_format($total, 2, '.', ',') .'</td></tr>';
     
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
                                        'movimientos' => 'required|array'                   
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $fecha = Carbon::now()->locale('es')->translatedFormat('y-m-d');
        $maxId = EstadoCuenta::max('folio');  
        $newId = $maxId ? $maxId + 1 : 1; 
       
        foreach ($request->movimientos as $movimiento) {
       
            $consecutivo = EstadoCuenta::where('uid', $request->uid)
                                    ->where('secuencia', $request->secuencia)
                                    ->where('idServicio', $movimiento['idServicio'])
                                    ->max('consecutivo');
            $consecutivo = $consecutivo ? $consecutivo + 1 : 1;
        
            try{
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
                                            'folio'=> $newId
            ]);
                } catch (QueryException $e) {
                    // Capturamos el error relacionado con las restricciones
                    if ($e->getCode() == '23000') 
                        // Código de error para restricción violada (por ejemplo, clave foránea)
                        return $this->returnEstatus('El registro ya se encuentra dado de alta',400,null);
                        
                    return $this->returnEstatus('Error al insertar el registro',400,null);
                }
        }

        if (!$edoCta) 
            return $this->returnEstatus('Error al crear el registro',500,null); 
        return $this->returnData('edoCta',$edoCta,200);   
    }
    
}
