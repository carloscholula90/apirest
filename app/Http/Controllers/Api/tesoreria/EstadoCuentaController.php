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
use Illuminate\Support\Facades\Auth; 


class EstadoCuentaController extends Controller{


    public function validarQR($uid,$qr){
        $resultados = $this->obtenerEstadoCuenta($uid,null,null,null,$qr);
        return $this->returnData('movimientos',$resultados,200);
    }

    public function index($uid,$idPeriodo,$matricula,$tipoEdoCta)
    {
        $resultados = $this->obtenerEstadoCuenta($uid,$idPeriodo,$matricula,$tipoEdoCta);
        return $this->returnData('EstadoCuenta',$resultados,200);
    }

    public function obtenerFolios($uid,$tipoEdoCta){
            $datos = DB::table('edocta as edo')
                            ->select(
                                DB::raw('SUM(importe) as importe'),
                                'edo.folio',
                                'edo.fechaMovto'
                            )
                            ->join('servicio as s', 's.idServicio', '=', 'edo.idServicio')
                            ->where('edo.uid', $uid)       
                            ->where('s.tipoEdoCta', $tipoEdoCta)
                            ->whereNotNull('edo.folio')       
                            ->groupBy('edo.folio', 'edo.fechaMovto')
                            ->get();
     return $this->returnData('folios',$datos,200);
    }

    public function obtenerEstadoCuenta($uid, $idPeriodo, $matricula, $tipoEdoCta, $qr = null)
{
    // Ejecutar procedimiento almacenado
    if($tipoEdoCta==1)
        DB::statement("CALL saldo(?, ?, ?, @vencido, @total)", [$uid, $matricula, $idPeriodo]);
    else DB::statement("CALL saldo2(?, ?, ?, @vencido, @total)", [$uid, $matricula, $idPeriodo]);
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
                'traspaso.idServicioTraspasoSaldos1',
                'inscripcion.idServicioInscripcion',
                'reinscrip.idServicioReinscripcion',            
                'colegiatura.idServicioColegiatura',
                'recargo.idServicioRecargo',
                'notacargo.idServicioNotaCargo',
                'notacred.idServicioNotaCredito',
                'persona.primerapellido as apellidopat',
                'persona.segundoapellido as apellidomat',
                'fechaVencimiento AS fechaLimite',
                    DB::raw("CONCAT(s.descripcion, ' ',
                             CASE WHEN colegiatura.idServicioColegiatura = s.idServicio
                                OR recargo.idServicioRecargo = s.idServicio
                                THEN CASE CONVERT(SUBSTRING(edo.referencia, 4), UNSIGNED)
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
                                            ELSE ''
                                        END
                                    ) AS servicio
                                "),
                        'fp.descripcion as formaPago',
                        DB::raw("DATE_FORMAT(edo.fechaMovto, '%d/%m/%Y') as fechaPago"),
                        'edo.consecutivo',
                        'edo.idServicio',  
                        'traspaso.idServicioTraspasoSaldos1',
                        'inscripcion.idServicioInscripcion',
                        'colegiatura.idServicioColegiatura',
                        'bec.descripcion AS beca',
                        'beca.importeInsc',
                        'beca.importeCole',
                        DB::raw("CASE WHEN edo.tipomovto = 'C' THEN edo.importe ELSE NULL END AS cargo"),
                        DB::raw("CASE WHEN edo.tipomovto != 'C' THEN edo.importe ELSE NULL END AS abono")
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
                ->leftJoin('becaAlumno as beca', function ($join) {
                    $join->on('al.idNivel', '=', 'beca.idNivel')
                        ->on('al.uid', '=', 'beca.uid')
                        ->on('beca.idPeriodo', '=', 'edo.idPeriodo');
                })
                ->leftJoin('beca as bec', 'bec.idBeca', '=', 'beca.idBeca')               
                ->leftJoin('configuracionTesoreria as colegiatura', function ($join) {
                    $join->on('colegiatura.idNivel', '=', 'al.idNivel')
                        ->on('colegiatura.idServicioColegiatura', '=', 's.idServicio');
                })
                ->leftJoin('configuracionTesoreria as recargo', function ($join) {
                    $join->on('recargo.idNivel', '=', 'al.idNivel')
                        ->on('recargo.idServicioRecargo', '=', 's.idServicio');
                }) 
                ->leftJoin('configuracionTesoreria as traspaso', function ($join) {
                    $join->on('traspaso.idNivel', '=', 'al.idNivel')
                        ->on('traspaso.idServicioTraspasoSaldos1', '=', 's.idServicio');
                })               
                ->leftJoin('configuracionTesoreria as notacargo', function ($join) {
                    $join->on('notacargo.idNivel', '=', 'al.idNivel')
                        ->on('notacargo.idServicioNotaCargo', '=', 's.idServicio');
                })
                 ->leftJoin('configuracionTesoreria as notacred', function ($join) {
                    $join->on('notacred.idNivel', '=', 'al.idNivel')
                        ->on('notacred.idServicioNotaCredito', '=', 's.idServicio');
                })    
                ->leftJoin('configuracionTesoreria as reinscrip', function ($join) {
                    $join->on('reinscrip.idNivel', '=', 'al.idNivel')
                        ->on('reinscrip.idServicioReinscripcion', '=', 's.idServicio');
                })
                ->join('carrera', function ($join) {
                                        $join->on('carrera.idCarrera', '=', 'al.idCarrera')
                                            ->on('carrera.idNivel', '=', 'al.idNivel');
                                    })
                ->join('persona', 'persona.uid', '=', 'al.uid')
                ->where('edo.uid', $uid);

            // Condiciones adicionales
            if (!is_null($qr)) {
                $query->where('edo.comprobante', 'like', '%' . $qr . '%');
            } else {
                $query->where('edo.idPeriodo', $idPeriodo)
                    ->where('al.matricula', $matricula);
            }
            if (!is_null($tipoEdoCta))
            $query->where('s.tipoEdoCta', $tipoEdoCta);
            // Ordenar y obtener resultados
            $edocuenta = $query->orderByDesc('traspaso.idServicioTraspasoSaldos1')
                             ->orderByDesc('inscripcion.idServicioInscripcion')
                             ->orderByDesc('reinscrip.idServicioReinscripcion')            
                             ->orderByDesc('colegiatura.idServicioColegiatura')
                             ->orderByDesc('recargo.idServicioRecargo')
                             ->orderByDesc('notacargo.idServicioNotaCargo')
                             ->orderByDesc('notacred.idServicioNotaCredito')
                             ->orderBy('edo.parcialidad')
                             ->orderByDesc('edo.tipomovto')                          
                             ->distinct()
                             ->get();

            return $edocuenta;
    }

    public function generaReporte($uid,$idPeriodo,$matricula,$tipoEdoCta){

        $results = $this->obtenerEstadoCuenta($uid,$idPeriodo,$matricula,$tipoEdoCta);
       
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
                        'rptEstadoCta_'.$uid.'.pdf',$tipoEdoCta);
      
    }

    public function generateReport($data, $columnWidths, $keys, $title, $headers, $orientation, $size, $nameReport,$tipoEdoCta)
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
        
        if(isset($generalesRow['beca']))
            $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Beca:</b> '.$generalesRow['beca'].'</td></tr>';
        
        $valueInscripcion = isset($generalesRow['importeInsc']) ? $generalesRow['importeInsc'] : '0'; 
        $valueColegiatura = isset($generalesRow['importeCole']) ? $generalesRow['importeCole'] : '0'; 
       
        if(isset($generalesRow['beca']))
             if(number_format((float)$valueInscripcion, 2, '.', ',')>0)
                $html2 .= '<tr><td colspan="7" style="font-size: 9px;">
                                            <table>
                                                <tr>
                                                    <td width="15px"></td>
                                                    <td><b>Inscripcion:</b> '.$generalesRow['importeInsc'].'</td>
                                                </tr>
                                            </table>
                                        </td></tr>';
        
        if(isset($generalesRow['beca']))
             if(number_format((float)$valueColegiatura, 2, '.', ',')>0)
                  $html2 .= '<tr><td colspan="7" style="font-size: 9px;">
                                            <table>
                                                <tr>
                                                    <td width="15px"></td>
                                                    <td><b>Colegiatura:</b> '.$generalesRow['importeCole'].'</td>
                                                </tr>
                                            </table>
                                        </td></tr>';
       
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
                
                if($key=='cargo'|| $key == 'abono'){
                    if($key=='cargo')  
                        $total =   $total + isset($row[$key]) ? $row[$key] : 0;
                    else if($key=='abono')  
                        $total =   $total - isset($row[$key]) ? $row[$key] : 0;

                        $value = isset($row[$key]) ? $row[$key] : '';     
                   $html2 .= '<td align="right">$ '.($value !== null ? number_format((float)$value, 2, '.', ',') : '') . '</td>';
                  }
                else{
                    $value = isset($row[$key]) ? $row[$key] : '';     
                    $html2 .= '<td width="' . $columnWidths[$index] . '">' . ($value !== null ? htmlspecialchars((string)$value) : '') . '</td>';
                }
            }
            $html2 .= '</tr>';
        }

        $html2 .= '<tr><td colspan="7"></td></tr>';   
        $html2 .= '<tr><td colspan="7"><hr style="border: 1px dotted black; background-size: 20px 10px;"></td></tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';

        if($tipoEdoCta==1){
            $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>SALDO:</b>$ '.number_format($generalesRow['total'], 2, '.', ',') .'</td></tr>';
            $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>SALDO VENCIDO:$ </b>'.number_format($generalesRow['vencido'], 2, '.', ',') .'</td></tr>';
        }
        else  $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>SALDO:</b>$ '.number_format($generalesRow['total'], 2, '.', ',') .'</td></tr>';
      
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
                'message' => 'https://reportes.pruebas.com.mx/storage/app/public/'.$nameReport // Puedes devolver la ruta para fines de depuración
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte'
            ]);
        }    
    }

    private function obtenerServiciosTesoreria($uid,$secuencia){
        return DB::table('configuracionTesoreria as ct')
            ->join('alumno as al', function ($join) use ($uid, $secuencia) {
                $join->on('ct.idNivel', '=', 'al.idNivel')
                    ->where('al.uid', '=', $uid)
                    ->where('al.secuencia', '=', $secuencia);
            })
            ->select(
                'ct.idServicioColegiatura',
                'ct.idServicioInscripcion',
                'ct.idServicioRecargo',
                'ct.idServicioNotaCredito',
                'ct.idServicioTraspasoSaldos1'
            )
            ->first(); // Retorna un solo registro
    }


    public function store(Request $request){

        $data = $request->validate(['uid'         => 'required',
                                    'secuencia'   => 'required',
                                    'idPeriodo'   => 'required',
                                    'uidcajero'   => 'required',
                                    'movimientos' => 'required|array'
        ]);

        $uid = $data['uid'];
        $secuencia = $data['secuencia'];
        $fecha = Carbon::now();

        DB::beginTransaction();
        try {

            $folio = (EstadoCuenta::max('folio') ?? 0) + 1;
            $servicios = $this->obtenerServiciosTesoreria($uid, $secuencia);

            foreach ($data['movimientos'] as $movimiento) 
                $this->procesarMovimiento($movimiento, $servicios, $uid, $secuencia, $data['idPeriodo'], $data['uidcajero'], $fecha, $folio);
            
            DB::commit();
            return $this->returnData('folio', $folio, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->returnEstatus('Error al crear el registro', 500, $e->getMessage());
        }
    }

    private function calcularImporteInscripcion($uid, $secuencia, $idServicioInscripcion){
    // Obtenemos el importe de pagos y notas de crédito
        $importe = DB::table('edocta as edo')
            ->selectRaw("IFNULL(SUM(CASE 
                            WHEN edo.tipomovto = 'C' THEN edo.importe
                            ELSE -1 * edo.importe
                        END), 0)  AS importe")
            ->join('configuracionTesoreria as ct', function($join) {
                    $join->on('edo.idServicio', '=', 'ct.idServicioInscripcion')
                        ->orOn('edo.idServicio', '=', 'ct.idServicioNotaCredito');
                })
            ->join('alumno as al', function ($join) use ($uid, $secuencia) {
                $join->on('al.uid', '=', 'edo.uid')
                    ->on('al.secuencia', '=', 'edo.secuencia')
                    ->on('al.idNivel', '=', 'ct.idNivel')
                    ->where('al.uid', '=', $uid)
                    ->where('al.secuencia', '=', $secuencia);
            })
            ->join('periodo as per', function ($join) {
                $join->on('per.idNivel', '=', 'al.idNivel')
                    ->on('edo.idPeriodo', '=', 'per.idPeriodo')
                    ->where('per.activo', '=', 1);
            })
            ->where('edo.parcialidad',0)
            ->value('importe'); // Devuelve solo el valor
        return $importe ?? 0;
    }

    private function calcularImporteAdeudo($uid,$secuencia,$idServicio){
    // Obtenemos el importe de pagos y notas de crédito
     $importe = DB::table('edocta as edo')
            ->selectRaw("IFNULL(SUM(CASE 
                            WHEN edo.tipomovto = 'C' THEN edo.importe
                            ELSE -1 * edo.importe
                        END), 0)  AS importe")
            ->join('configuracionTesoreria as ct', function($join) {
                    $join->on('edo.idServicio', '=', 'ct.idServicioNotaCredito')
                        ->orOn('edo.idServicio', '=', 'ct.idServicioTraspasoSaldos1');
                })
            ->join('alumno as al', function ($join) use ($uid, $secuencia) {
                $join->on('al.uid', '=', 'edo.uid')
                    ->on('al.secuencia', '=', 'edo.secuencia')
                    ->on('al.idNivel', '=', 'ct.idNivel')
                    ->where('al.uid', '=', $uid)
                    ->where('al.secuencia', '=', $secuencia);
            })
            ->join('periodo as per', function ($join) {
                $join->on('per.idNivel', '=', 'al.idNivel')
                    ->on('edo.idPeriodo', '=', 'per.idPeriodo')
                    ->where('per.activo', '=', 1);
            })
            ->where('edo.parcialidad',999)
            ->value('importe'); // Devuelve solo el valor
        return $importe ?? 0;
    }
        private function obtieneReferenciaInscripcion($uid, $secuencia, $idServicioInscripcion){
        $referencia = DB::table('edocta as edo')
            ->join('configuracionTesoreria as ct', 'edo.idServicio', '=', 'ct.idServicioInscripcion')
            ->join('alumno as al', function ($join) use ($uid, $secuencia) {
                $join->on('al.uid', '=', 'edo.uid')
                    ->on('al.secuencia', '=', 'edo.secuencia')
                    ->where('al.uid', $uid)
                    ->where('al.secuencia', $secuencia);
            })
            ->join('periodo as per', function ($join) {
                $join->on('per.idNivel', '=', 'al.idNivel')
                    ->on('edo.idPeriodo', '=', 'per.idPeriodo')
                    ->where('per.activo', 1);
            })
            ->where('ct.idServicioInscripcion', $idServicioInscripcion) 
            ->value('edo.referencia');
 
        return $referencia ?? 0;
    }

    private function procesarMovimiento($movimiento, $servicios, $uid, $secuencia, $idPeriodo, $uidcajero, $fecha, $folio){
      
        if ($movimiento['idServicio'] == $servicios->idServicioTraspasoSaldos1) {
            $importeSaldo = $this->calcularImporteAdeudo($uid, $secuencia, $servicios->idServicioTraspasoSaldos1);
            if($importeSaldo>0){
                $pago = min($movimiento['importe'], $importeSaldo);
                $restante = $movimiento['importe'] - $importeSaldo;
                $this->crearMovimiento(array_merge($movimiento, ['uid' => $uid,
                                                            'secuencia' => $secuencia,
                                                            'consecutivo' => $this->siguienteConsecutivo($uid, $secuencia,$idPeriodo),
                                                            'importe' => $pago,
                                                            'idPeriodo' => $idPeriodo,
                                                            'fechaMovto' => $fecha,
                                                            'parcialidad' => 999,
                                                            'folio' => $folio,
                                                            'uidcajero' => $uidcajero
                ]));
                $movimiento['idServicio'] = $servicios->idServicioInscripcion;
                $movimiento['importe'] = $restante;
            }
        } 

        if($movimiento['importe'] >0){
        if ($movimiento['idServicio'] == $servicios->idServicioInscripcion) {
            $importeInscripcion = $this->calcularImporteInscripcion($uid, $secuencia, $servicios->idServicioInscripcion);
            $pagoInscripcion = min($movimiento['importe'], $importeInscripcion);
            $restante = $movimiento['importe'] - $pagoInscripcion;

            $this->crearMovimiento(array_merge($movimiento, ['uid' => $uid,
                                                            'secuencia' => $secuencia,
                                                            'consecutivo' => $this->siguienteConsecutivo($uid, $secuencia,$idPeriodo),
                                                            'importe' => $pagoInscripcion,
                                                            'idPeriodo' => $idPeriodo,
                                                            'referencia' =>  $this->obtieneReferenciaInscripcion($uid, $secuencia,$servicios->idServicioInscripcion),
                                                            'fechaMovto' => $fecha,
                                                            'parcialidad' => 0,
                                                            'folio' => $folio,
                                                            'uidcajero' => $uidcajero
            ]));
           if ($restante > 0) 
                $this->prorratearColegiaturaYCargos($uid, $secuencia, $idPeriodo, $servicios, $movimiento, $restante, $fecha, $folio, $uidcajero,0);
   
        } 
            else if($movimiento['idServicio'] == $servicios->idServicioColegiatura)
                $this->prorratearColegiaturaYCargos($uid, $secuencia, $idPeriodo, $servicios, $movimiento, $movimiento['importe'], $fecha, $folio, $uidcajero,0);
            else if($movimiento['idServicio'] == $servicios->idServicioNotaCredito) 
               $this->prorratearColegiaturaYCargos($uid, $secuencia, $idPeriodo, $servicios, $movimiento, $movimiento['importe'], $fecha, $folio, $uidcajero, $servicios->idServicioNotaCredito);
            else $this->procesarOtrosServicios($movimiento, $uid, $secuencia, $idPeriodo, $fecha, $folio, $uidcajero, $servicios);
        }
    }

    private function obtenerPendientes( $uid,  $secuencia){                        

     $abonosCole = DB::table('edocta')
                        ->select([
                            'parcialidad',
                            'referencia',
                            'idPeriodo',
                            DB::raw('SUM(importe) AS total'),
                        ])
                        ->where('tipomovto', 'A')
                        ->where('uid', $uid)
                        ->groupBy('parcialidad', 'referencia', 'idPeriodo');

            $abonosCargo = DB::table('edocta')
                            ->select([
                                'parcialidad',
                                'referencia',
                                'idPeriodo',
                                'idServicio',
                                DB::raw('SUM(importe) AS total'),
                            ])
                            ->where('tipomovto', 'A')
                            ->where('uid', $uid)
                            ->groupBy('parcialidad', 'referencia', 'idPeriodo', 'idServicio');

            return  DB::table('configuracionTesoreria as ct')
                        ->join('alumno as al', function ($join) use ($uid, $secuencia) {
                            $join->on('ct.idNivel', '=', 'al.idNivel')
                                ->where('al.uid', $uid)
                                ->where('al.secuencia', $secuencia);
                        })
                        ->join('periodo as per', function ($join) {
                            $join->on('per.idNivel', '=', 'al.idNivel')
                                ->where('per.activo', 1);
                        })
                        ->join('edocta as cta', function ($join) use ($uid, $secuencia) {
                            $join->on('cta.idServicio', '=', 'ct.idServicioColegiatura')
                                ->where('cta.uid', $uid)
                                ->where('cta.secuencia', $secuencia)
                                ->where('cta.tipomovto', 'C')
                                ->whereColumn('cta.idPeriodo', 'per.idPeriodo');
                        })
                        ->leftJoinSub($abonosCole, 'abonosCole', function ($join) {
                            $join->on('abonosCole.parcialidad', '=', 'cta.parcialidad')
                                ->on('abonosCole.referencia', '=', 'cta.referencia')
                                ->on('abonosCole.idPeriodo', '=', 'per.idPeriodo');
                        })
                        ->leftJoin('edocta as cargos', function ($join) use ($uid, $secuencia) {
                            $join->on('cargos.idServicio', '=', 'ct.idServicioRecargo')
                                ->on('cargos.parcialidad', '=', 'cta.parcialidad')
                                ->where('cargos.uid', $uid)
                                ->where('cargos.secuencia', $secuencia)
                                ->where('cargos.tipomovto', 'C')
                                ->whereColumn('cargos.idPeriodo', 'per.idPeriodo');
                        })
                        ->leftJoinSub($abonosCargo, 'abonosCargo', function ($join) {
                            $join->on('abonosCargo.parcialidad', '=', 'cta.parcialidad')
                                ->on('abonosCargo.referencia', '=', 'cargos.referencia')
                                ->on('abonosCargo.idPeriodo', '=', 'per.idPeriodo')
                                ->on('abonosCargo.idServicio', '=', 'ct.idServicioRecargo');
                        })
                        ->whereRaw('(cta.importe - IFNULL(abonosCole.total, 0)) > 0')
                        ->orderBy('cta.parcialidad')
                        ->select([
                            'cta.parcialidad', 'cta.referencia',
                            'cta.referencia AS referenciaCole',
                            'cargos.referencia AS referenciaCargos',
                            DB::raw('(cta.importe - IFNULL(abonosCole.total, 0)) AS monto'),
                            'cargos.idServicio AS idServicioCargo',
                            'ct.idServicioColegiatura',
                            DB::raw('(cargos.importe - IFNULL(abonosCargo.total, 0)) AS cargos'),
                        ])
                ->get();
    }

    private function siguienteConsecutivo($uid, $secuencia, $idServicio){
        return (int) DB::table('edocta')
            ->where('uid', $uid)
            ->where('secuencia', $secuencia)
            ->max('consecutivo') + 1;
    }

        private function crearMovimiento($data) {
            $movimiento = [ 'uid'         =>  $data['uid'],
                            'secuencia'   =>  $data['secuencia'],
                            'idServicio'  =>  $data['idServicio'],
                            'idPeriodo'   => $data['idPeriodo'],
                            'importe'     => round($data['importe'], 2),
                            'tipomovto'   => $data['tipomovto'],
                            'referencia'  => $data['referencia'] ?? null,
                            'parcialidad' => $data['parcialidad'] ?? 1,  
                            'fechaMovto'  => DB::raw("CONVERT_TZ(NOW(), '+00:00', '-06:00')"),
                            'FechaPago'   => DB::raw("CONVERT_TZ(NOW(), '+00:00', '-06:00')"),
                            'idformaPago' => $data['idformaPago'] ?? null,
                            'cuatrodigitos' => $data['cuatrodigitos'] ?? null,
                            'folio'       => $data['folio'] ?? null,
                            'uidcajero'   => $data['uidcajero'] ?? null,
        ];

        // Consecutivo automático
        $movimiento['consecutivo'] =
            $this->siguienteConsecutivo($data['uid'], $data['secuencia'], $data['idPeriodo']);

        return EstadoCuenta::create($movimiento);
    }


    private function prorratearColegiaturaYCargos($uid, $secuencia, $idPeriodo, $servicios, $movimiento, $importeRestante, $fecha, $folio, $uidcajero,$idServicioNotaCredito){
       
        if($movimiento['importe']>0){
            $pendientes = $this->obtenerPendientes($uid, $secuencia, $idPeriodo, $servicios);

        foreach ($pendientes as $registro) {
           
            if ($registro->cargos > 0 && $importeRestante > 0) {
                $pago = min($importeRestante, $registro->cargos);
                $idServicioNota = $idServicioNotaCredito >0?$idServicioNotaCredito:$servicios->idServicioRecargo;
                $this->crearMovimiento(['uid' => $uid,
                                        'secuencia' => $secuencia,
                                        'idServicio' => $idServicioNota,
                                        'importe' => $pago,
                                        'idPeriodo' => $idPeriodo,
                                        'fechaMovto' => $fecha,
                                        'idformaPago' => $movimiento['idformaPago'],
                                        'cuatrodigitos' => $movimiento['cuatrodigitos'],
                                        'tipomovto' => $movimiento['tipomovto'],
                                        'FechaPago' => $fecha,
                                        'folio' => $folio,
                                        'referencia' => $registro->referenciaCargos,
                                        'parcialidad' => $registro->parcialidad,
                                        'uidcajero' => $uidcajero
                ]);
                $importeRestante -= $pago;
            }

            // 2️⃣ Pagar colegiatura
            if ($registro->monto > 0 && $importeRestante > 0) {
                $idServicioNota = $idServicioNotaCredito >0?$idServicioNotaCredito:$registro->idServicioColegiatura;
              //  //Log::info('$importeRestante:'.$importeRestante); 
                $pago = min($importeRestante, $registro->monto);
                $this->crearMovimiento([
                                        'uid' => $uid,
                                        'secuencia' => $secuencia,
                                        'idServicio' => $idServicioNota,
                                         'importe' => $pago,
                                        'idPeriodo' => $idPeriodo,
                                        'fechaMovto' => $fecha,
                                        'idformaPago' => $movimiento['idformaPago'],
                                        'cuatrodigitos' => $movimiento['cuatrodigitos'],
                                        'tipomovto' => $movimiento['tipomovto'],
                                        'FechaPago' => $fecha,
                                        'folio' => $folio,
                                        'referencia' => $registro->referenciaCole,
                                        'parcialidad' => $registro->parcialidad,
                                        'uidcajero' => $uidcajero
                ]);
                $importeRestante -= $pago;
            }

            if ($importeRestante <= 0) break;
        }

        // 3️⃣ Sobrante: aplicarlo como pago adelantado
        if ($importeRestante > 0) {
            $this->crearMovimiento(array_merge($movimiento, [
                'uid' => $uid,
                'idServicio' => $servicios->idServicioNotaCredito,
                'secuencia' => $secuencia,
                 'importe' => $importeRestante,
                'idPeriodo' => $idPeriodo,
                'fechaMovto' => $fecha,
                'folio' => $folio,
                'uidcajero' => $uidcajero
            ]));
            }
        }
    }

    private function procesarOtrosServicios($movimiento, $uid, $secuencia, $idPeriodo, $fecha, $folio, $uidcajero, $servicios){
        //Validamos si es un movimiento de cargo y abono
        $servicio = DB::table('servicio as s')
                        ->where('s.idServicio',$movimiento['idServicio'])
                        ->where('s.cargoAutomatico', 1)
                        ->get();

        //Validamos en que parcialidad va para que se ordene de manera correcta
        $parcialidad = DB::table('edocta as edo')
                        ->select(DB::raw('IFNULL(MAX(edo.parcialidad), 0) + 1 AS parcialidad'))
                        ->where('edo.idServicio', $movimiento['idServicio'])
                        ->where('edo.uid', $uid)
                        ->where('edo.idPeriodo', $idPeriodo)
                        ->first();

        $parcialidad = $parcialidad->parcialidad;        

        $this->crearMovimiento(array_merge($movimiento, ['uid' => $uid,
                                                        'secuencia' => $secuencia,
                                                        'idPeriodo' => $idPeriodo,
                                                        'fechaMovto' => $fecha,
                                                        'folio' => $folio,
                                                        'uidcajero' => $uidcajero,
                                                        'parcialidad' => $parcialidad
        ]));
        
        if (!$servicio->isEmpty()) {
            $movimiento['tipomovto'] ='C';
            $this->crearMovimiento(array_merge($movimiento, ['uid' => $uid,
                                                        'secuencia' => $secuencia,
                                                        'idPeriodo' => $idPeriodo,
                                                        'fechaMovto' => $fecha,
                                                        'uidcajero' => $uidcajero,
                                                        'parcialidad' => $parcialidad
                                                        ]));
        }
    }



    public function guardarMovtos(Request $request){
        $movimientos = $request->all();
        $registrosMal = [];

        if (!is_array($movimientos)) {
            return response()->json(['error' => 'Datos inválidos, se espera un arreglo'], 400);
        }

        foreach ($movimientos as $index => $mov) {
            if (!isset($mov['dia'], $mov['concepto'], $mov['abono'], $mov['transaccion'])) {
                return response()->json([
                    'error' => "Falta campo en elemento $index",
                ], 400);
            }
            
            $transaccion = $mov['transaccion'];
            $abono = floatval($mov['abono']);
            $matricula = (int) substr($mov['concepto'], 0, 7);
            
            $result = DB::table('periodo')
                            ->join('alumno', 'periodo.idNivel', '=', 'alumno.idNivel')
                            ->leftJoin('edocta', function ($join) use ($transaccion) {
                                $join->on('edocta.idPeriodo', '=', 'periodo.idPeriodo')
                                    ->where('edocta.transaccion', '=',$transaccion);
                            })
                            ->where('periodo.activo', 1)
                            ->where('alumno.matricula', $matricula)
                            ->select('alumno.uid','alumno.secuencia', 'periodo.idPeriodo','edocta.transaccion')
                            ->first();

            if (!$result) {
                $registrosMal[] = [
                    'matricula' => $matricula,
                    'mensaje'   => 'No existe la matricula en el sistema',
                    'importe'   => $abono
                ];
                continue;
            }
 
            if (isset($result->transExistente)) {
                     $registrosMal[] = [
                            'matricula' => $matricula,
                            'mensaje'   => 'La transaccion ya se encuentra dada de alta en el periodo',
                            'importe'   => $abono
                            ];
                continue;
            }

           $servicios = $this->obtenerServiciosTesoreria($result->uid, $result->secuencia);
           $movimiento = ['importe'        => $abono,
                          'idformaPago'    => $mov['idformaPago'],
                          'idServicio'     => $servicios->idServicioTraspasoSaldos1,
                          'cuatrodigitos'  => null,
                          'tipomovto'      => 'A',
                          'cargoAut'       => 0,
                        ];

            $this->procesarMovimiento($movimiento, $servicios, $result->uid, $result->secuencia, 
                                          $result->idPeriodo, $mov['uidcajero'], $mov['dia'], 0);

        return response()->json([
            'message' => 'Registros guardados',
            'error'   => $registrosMal,
            'status'  => 200
        ], 200);
        }
   
    }
}

