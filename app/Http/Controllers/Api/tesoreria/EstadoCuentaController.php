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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

    public function buscarLogPorUidSecuencia($uid, $secuencia)
    {
        $movimientos = DB::table('edocta')
            ->select(
                'uid',
                'secuencia',
                'idPeriodo',
                'idServicio',
                'consecutivo',
                'importe',
                'tipomovto',
                'fechaMovto',
                'folio',
                'log'
            )
            ->where('uid', $uid)
            ->where('secuencia', $secuencia)
            ->orderBy('idPeriodo')
            ->orderBy('consecutivo')
            ->get()
            ->map(function ($movimiento) {
                $movimiento->logJson = $movimiento->log ? json_decode($movimiento->log, true) : null;
                return $movimiento;
            });

        if ($movimientos->isEmpty()) {
            return $this->returnEstatus('No se encontraron movimientos para el uid y secuencia indicados', 404, null);
        }

        return $this->returnData('movimientos', $movimientos, 200);
    }

    public function obtenerFolios($uid,$matricula,$tipoEdoCta){
            $datos = DB::table('edocta as edo')
                            ->select(
                                DB::raw('SUM(importe) as importe'),
                                'edo.folio',
                                'edo.fechaMovto'
                            )
                            ->join('servicio as s', 's.idServicio', '=', 'edo.idServicio')
                            ->join('alumno as a', function($join) {
                                $join->on('a.uid', '=', 'edo.uid')
                                    ->on('a.secuencia', '=', 'edo.secuencia');
                            })
                            ->join('periodo as per', function($join) {
                                $join->on('per.idNivel', '=', 'a.idNivel')
                                    ->on('per.idPeriodo', '=', 'edo.idPeriodo')
                                    ->where('per.activo', 1);
                            })
                            ->where('edo.uid', $uid)       
                            ->where('s.tipoEdoCta', $tipoEdoCta)
                            ->where('a.matricula', $matricula)
                            ->whereNotNull('edo.folio')       
                            ->groupBy('edo.folio', 'edo.fechaMovto')
                            ->get();
     return $this->returnData('folios',$datos,200);
    }

    public function obtenerEstadoCuenta($uid, $idPeriodo, $matricula, $tipoEdoCta, $qr = null)
{
    /*
     * Antes de calcular y mostrar el estado de cuenta, actualizar los
     * recargos del alumno para el periodo solicitado. La consulta por QR no
     * cuenta con periodo ni matrícula, por lo que no ejecuta este proceso.
     */
    if (is_null($qr) && !is_null($idPeriodo) && !is_null($matricula)) {
        $alumno = DB::table('alumno')
            ->where('uid', $uid)
            ->where('matricula', $matricula)
            ->select('idNivel', 'secuencia')
            ->first();

        if ($alumno) {
            DB::statement("SET @origen = 'LARAVEL'");
            DB::select('CALL GeneraRecargosIndividual(?, ?, ?, ?)', [
                (int) $alumno->idNivel,
                (int) $idPeriodo,
                (int) $uid,
                (int) $alumno->secuencia,
            ]);
        }
    }

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
                'ocs.orden as _ordenCobro',
                'edo.fechaMovto as _fechaMovimientoOrden',
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
                ->leftJoin('ordenCobroServicio as ocs', function ($join) {
                    $join->on('ocs.idNivel', '=', 'al.idNivel')
                        ->on('ocs.idServicio', '=', 'edo.idServicio');
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
            $edocuenta = $query
                ->orderByRaw('COALESCE(ocs.orden, 999999) ASC')
                ->orderByRaw('COALESCE(edo.parcialidad, 999999) ASC')
                ->orderBy('edo.referencia')
                ->orderByRaw("CASE WHEN edo.tipomovto = 'C' THEN 0 ELSE 1 END ASC")
                ->orderBy('edo.fechaMovto')
                ->orderBy('edo.consecutivo')
                ->distinct()
                ->get()
                ->each(function ($movimiento) {
                    unset(
                        $movimiento->_ordenCobro,
                        $movimiento->_fechaMovimientoOrden
                    );
                });

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
                        'rptEstadoCta_'.$uid.Str::random(8).'.pdf',$tipoEdoCta);
      
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
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/'.$nameReport // Puedes devolver la ruta para fines de depuración
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte'
            ]);
        }    
    }

    private function obtenerServiciosTesoreria($uid,$secuencia,$idPeriodo){
    return DB::table('configuracionTesoreria as ct')
        ->join('alumno as al', function ($join) use ($uid, $secuencia) {
            $join->on('ct.idNivel', '=', 'al.idNivel')
                ->where('al.uid', '=', $uid)
                ->where('al.secuencia', '=', $secuencia);
        })
        ->selectRaw("? as idPeriodo", [$idPeriodo])
        ->addSelect(
            'al.idNivel',
            'ct.idServicioColegiatura',
            'ct.idServicioInscripcion',
            'ct.idServicioRecargo',
            'ct.idServicioNotaCredito',
            'ct.idServicioTraspasoSaldos1'
        )
        ->first();
    }


    public function store(Request $request){

        $data = $request->validate(['uid'         => 'required|integer',
                                    'secuencia'   => 'required|integer',
                                    'idPeriodo'   => 'required|integer',
                                    'uidcajero'   => 'required|integer',
                                    'fechaPago'   => 'required|date_format:Y-m-d',
                                    'movimientos' => 'required|array|min:1',
                                    'movimientos.*.idServicio' => 'required|integer',
                                    'movimientos.*.importe' => 'required|numeric|gt:0',
                                    'movimientos.*.idformaPago' => 'nullable|integer',
                                    'movimientos.*.cuatrodigitos' => 'nullable',
                                    'movimientos.*.transaccion' => 'nullable|string',
        ]);

        $uid = $data['uid'];
        $secuencia = $data['secuencia'];
        $fecha = Carbon::createFromFormat(
            'Y-m-d',
            $data['fechaPago'],
            'America/Mexico_City'
        )->startOfDay();
        $identificadorCaja = sprintf(
            'CAJA-%s-%s',
            now('America/Mexico_City')->format('Ymd-His'),
            Str::uuid()
        );

        DB::beginTransaction();   
        try {

            $folio = (EstadoCuenta::max('folio') ?? 0) + 1;
            $servicios = $this->obtenerServiciosTesoreria($uid, $secuencia, $data['idPeriodo']);

            if (!$servicios) {
                throw new \RuntimeException(
                    'No existe configuración de Tesorería para el nivel del alumno.'
                );
            }

            foreach ($data['movimientos'] as $movimiento) {
                $this->procesarMovimiento(
                    $movimiento,
                    $servicios,
                    $uid,
                    $secuencia,
                    $data['idPeriodo'],
                    $data['uidcajero'],
                    $fecha,
                    $folio,
                    'CAJA',
                    $identificadorCaja
                );
            }

            $matricula = (int) DB::table('alumno')
                ->where('uid', $uid)
                ->where('secuencia', $secuencia)
                ->value('matricula');

            $this->desbloquearAlumnosProcesados([[
                'uid' => (int) $uid,
                'secuencia' => (int) $secuencia,
                'matricula' => $matricula,
                'idPeriodo' => (int) $data['idPeriodo'],
            ]]);
            
            DB::commit();
            return $this->returnData('folio', $folio, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->returnEstatus('Error al crear el registro', 500, $e->getMessage());
        }
    }

    private function procesarMovimiento(
        array $movimiento,
        object $servicios,
        int $uid,
        int $secuencia,
        int $idPeriodo,
        int $uidcajero,
        Carbon $fecha,
        int $folio,
        string $tipoOrigen = 'CAJA',
        ?string $identificadorOrigen = null
    ) {
        $identificadorOrigen ??= sprintf(
            'CAJA-%s-%s',
            now('America/Mexico_City')->format('Ymd-His'),
            Str::uuid()
        );

        $idServicio = (int) ($movimiento['idServicio'] ?? 0);
        $servicio = DB::table('servicio')
            ->select('idServicio', 'tipoCobro', 'cargoAutomatico')
            ->where('idServicio', $idServicio)
            ->first();

        if (!$servicio) {
            throw new \InvalidArgumentException(
                'El servicio indicado en el movimiento no existe.'
            );
        }

        $tipoCobro = strtoupper(trim((string) $servicio->tipoCobro));

        if ($tipoCobro === 'ESPECIFICO') {
            return $this->procesarServicioEspecifico(
                $movimiento,
                $servicio,
                $uid,
                $secuencia,
                $idPeriodo,
                $uidcajero,
                $fecha,
                $folio,
                $tipoOrigen,
                $identificadorOrigen
            );
        }

        return $this->procesarMovimientoPorSaldos(
            $movimiento,
            $servicios,
            $uid,
            $secuencia,
            $idPeriodo,
            $uidcajero,
            $fecha,
            $folio,
            $tipoOrigen,
            $identificadorOrigen
        );

    }

    private function procesarServicioEspecifico(
        array $movimiento,
        object $servicio,
        int $uid,
        int $secuencia,
        int $idPeriodo,
        int $uidcajero,
        Carbon $fechaPago,
        int $folio,
        string $tipoOrigen,
        string $identificadorOrigen
    ): array {
        $importe = round((float) ($movimiento['importe'] ?? 0), 2);

        if ($importe <= 0) {
            throw new \InvalidArgumentException(
                'El importe del servicio específico debe ser mayor a cero.'
            );
        }

        $idServicio = (int) $servicio->idServicio;
        $referencia = $this->generarReferenciaSaldoFavor(
            $idServicio,
            $fechaPago
        );

        $datosBase = [
            'uid' => $uid,
            'secuencia' => $secuencia,
            'idServicio' => $idServicio,
            'importe' => $importe,
            'idPeriodo' => $idPeriodo,
            'fechaMovto' => $fechaPago,
            'FechaPago' => $fechaPago,
            'idformaPago' => $movimiento['idformaPago'] ?? null,
            'cuatrodigitos' => $movimiento['cuatrodigitos'] ?? null,
            'folio' => $folio,
            'referencia' => $referencia,
            'parcialidad' => 99,
            'uidcajero' => $uidcajero,
            'transaccion' => $movimiento['transaccion'] ?? null,
            'tipoOrigen' => $tipoOrigen,
            'identificadorOrigen' => $identificadorOrigen,
        ];

        $aplicaciones = [];

        if ((int) ($servicio->cargoAutomatico ?? 0) === 1) {
            $this->crearMovimiento(array_merge($datosBase, [
                'tipomovto' => 'C',
            ]));

            $aplicaciones[] = [
                'idServicio' => $idServicio,
                'referencia' => $referencia,
                'importe' => $importe,
                'accion' => 'cargo_automatico',
            ];
        }

        $this->crearMovimiento(array_merge($datosBase, [
            'tipomovto' => 'A',
        ]));

        $aplicaciones[] = [
            'idServicio' => $idServicio,
            'referencia' => $referencia,
            'importeAplicado' => $importe,
            'accion' => 'abono_aplicado',
        ];

        return [
            'importeOriginal' => $importe,
            'importeAplicado' => $importe,
            'importeRestante' => 0.0,
            'aplicaciones' => $aplicaciones,
        ];
    }

    private function siguienteConsecutivo($uid, $secuencia, $idPeriodo){
        return (int) DB::table('edocta')
                        ->where('uid', $uid)
                        ->where('secuencia', $secuencia)
                        ->where('idPeriodo', $idPeriodo)
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
                            'fechaMovto'  => $data['fechaMovto'],
                            'FechaPago'   => $data['fechaMovto'],
                            'idformaPago' => $data['idformaPago'] ?? null,
                            'cuatrodigitos' => $data['cuatrodigitos'] ?? null,
                            'folio'       => $data['folio'] ?? null,
                            'uidcajero'   => $data['uidcajero'] ?? null,
                            'transaccion' => $data['transaccion'] ?? null,
                            'tipoOrigen'  => $data['tipoOrigen'] ?? null,
                            'identificadorOrigen' => $data['identificadorOrigen'] ?? null
        ];

        /*
         * Protección final para movimientos importados:
         * el servicio siempre corresponde a los tres primeros dígitos
         * de la referencia, sin importar el valor recibido en idServicio.
         */
        if (($movimiento['tipoOrigen'] ?? null) === 'ARCHIVO') {
            if (empty($movimiento['identificadorOrigen'])) {
                throw new \RuntimeException(
                    'El identificadorOrigen es obligatorio para movimientos de archivo.'
                );
            }

            $referencia = trim((string) ($movimiento['referencia'] ?? ''));

            if (!preg_match('/^[0-9]{3}/', $referencia)) {
                throw new \RuntimeException(
                    'La referencia del movimiento de archivo debe comenzar con tres dígitos de servicio.'
                );
            }

            $movimiento['idServicio'] = (int) substr($referencia, 0, 3);
        }

        // Consecutivo automático
        DB::statement("SET @origen = 'LARAVEL'");
        $movimiento['consecutivo'] =
            $this->siguienteConsecutivo($data['uid'], $data['secuencia'], $data['idPeriodo']);

        return EstadoCuenta::create($movimiento);
    }



    public function guardarMovtos(Request $request){

        $movimientos = $request->all();
        $registrosMal = [];
        $importe = 0;
        $importeTotal = 0;
        $noRegistros =0;



        if (!is_array($movimientos)) 
            return response()->json(['error' => 'Datos inválidos, se espera un arreglo'], 400);
        

        foreach ($movimientos as $index => $mov) {
            if (!isset($mov['dia'], $mov['concepto'], $mov['abono'], $mov['transaccion'])) {
                return response()->json([
                    'error' => "Falta campo en elemento $index",
                ], 400);
            }

            $transaccion = $mov['transaccion'];
            $abono = floatval($mov['abono']);
            $matricula = (int) substr($mov['concepto'], 0, 7);
            $importeTotal = $importeTotal + $abono;

            $result = DB::table('alumno')  
                            ->where('alumno.matricula', $matricula)
                            ->select('alumno.uid','alumno.secuencia')
                            ->first();

            if (!$result) {
                $registrosMal[] = [
                    'matricula' => $matricula,
                    'mensaje'   => 'No existe la matricula en el sistema',
                    'uid'   => null,
                    'importe'   => $abono
                ];
                continue;
            } 
        
            $periodo = DB::table('periodo as p')
                            ->join('alumno', 'p.idNivel', '=', 'alumno.idNivel')
                            ->where('p.activo', 1)
                            ->where('alumno.matricula', $matricula)
                            ->select('p.idPeriodo')
                            ->first();

            $idPeriodo = $mov['idPeriodo'] ?? $periodo->idPeriodo;
  
           $result = DB::table('periodo')  
                            ->join('alumno', 'periodo.idNivel', '=', 'alumno.idNivel')
                            ->leftJoin('edocta', function ($join) use ($transaccion) {
                                $join->on('edocta.idPeriodo', '=', 'periodo.idPeriodo')
                                    ->where('edocta.transaccion', '=',$transaccion);
                            })
                            ->where('periodo.idPeriodo', $idPeriodo)
                            ->where('alumno.matricula', $matricula)
                            ->select('alumno.uid','alumno.secuencia', 'periodo.idPeriodo','edocta.transaccion')
                            ->first();
         
            if (isset($result->transaccion)) {
                     $registrosMal[] = [
                            'matricula' => $matricula,
                            'mensaje'   => 'La transaccion ya se encuentra dada de alta en el periodo',
                            'importe'   => $abono,
                            'uid'   => $result->uid,
                            ];
                continue;
            }

           $importe = $importe + $abono;
           $noRegistros = $noRegistros + 1;
          
           $servicios = $this->obtenerServiciosTesoreria($result->uid, $result->secuencia,$idPeriodo);
           $movimiento = ['importe'        => $abono,
                          'idformaPago'    => $mov['idFormaPago'],
                          'idServicio'     => $servicios->idServicioTraspasoSaldos1,
                          'cuatrodigitos'  => null,
                          'tipomovto'      => 'A',
                          'cargoAut'       => 0,
                          'transaccion' => $transaccion,
                        ];
            //Validamos si se generò un recargo previo a la carga se elimina
            $fecha = Carbon::createFromFormat('d/m/Y', str_replace('-', '/', $mov['dia']));
            $folio = (EstadoCuenta::max('folio') ?? 0) + 1;
           
            $this->procesarMovimiento($movimiento, $servicios, $result->uid, $result->secuencia, $idPeriodo,
                                           $mov['uidcajero'],$fecha, $folio);  
        }

        if(isset($registrosMal)){        
            $imagePathEnc = public_path('images/encPag.png');
            $imagePathPie = public_path('images/piePag.png');  
            $columnWidths = [50, 50,50,50,300];
            $pdf = new CustomTCPDF('P', PDF_UNIT, 'letter', true, 'UTF-8', false);
            $pdf->setHeaders(null, $columnWidths, 'MOVIMIENTOS NO PROCESADOS');
            $pdf->setImagePaths($imagePathEnc, $imagePathPie,'P');
            $pdf->SetFont('helvetica', '', 14);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('SIAWEB');
            $pdf->SetMargins(15, 30, 15);
            $pdf->SetAutoPageBreak(TRUE, 25);
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 8);

            $html2 = '<br><br><br><table border="0" cellpadding="1">';
            $html2 .= '<tr><td style="font-size: 9px;" width="80"><b>MATRICULA</b></td><td style="font-size: 9px;" width="80"><b>UID</b></td><td style="font-size: 9px;" width="80"><b>IMPORTE</b></td><td style="font-size: 9px;" width="400"><b>MENSAJE DE ERROR</b></td></tr>';
        
            foreach ($registrosMal as $registro) 
                $html2 .='<tr><td width="80">'.$registro['matricula'].'</td><td width="80">'.$registro['uid'].'</td><td width="80">'.number_format($registro['importe'], 2, '.', ',').'</td><td width=400>'.$registro['mensaje'].'</td></tr>';
                
            $html2 .= '</table>';
            
            // Escribir la tabla en el PDF
            $pdf->writeHTML($html2, true, false, true, false, '');

            $nameReport= 'validacion'.rand(1, 100).'.pdf';
            $filePath = storage_path('app/public/'.$nameReport);  // Ruta donde se guardará el archivo       
            $pdf->Output($filePath, 'F');  

            if (file_exists($filePath)) 
                        return response()->json([
                            'message' => 'Registros guardados ('.$noRegistros.' de '.collect($movimientos)->count().') con un importe total de ( $ '.number_format($importe, 2, '.', ',').' de $'.number_format($importeTotal, 2, '.', ',').')',
                            'error'   => 'https://reportes.siaweb.com.mx/storage/app/public/'.$nameReport ,
                            'status'  => 200
                        ], 200);

        } else{    
            return response()->json([
                            'message' => 'Registros guardados ('.$noRegistros.' de '.collect($movimientos)->count().') con un importe total de ( $ '.number_format($importe, 2, '.', ',').' de $'.number_format($importeTotal, 2, '.', ',').')',
                            'error'   => null ,
                            'status'  => 200
                        ], 200);

        }
   
    }

    public function actualizaColegiatura(Request $request){
          
       $servicios = $this->obtenerServiciosTesoreria($request->uid, $request->secuencia,$request->idPeriodo);
       $fecha = Carbon::now('America/Mexico_City')->format('Y-m-d');

       if($request->montoInsc >0){
        //Validamos que no existan colegiaturas pagadas
            $inscripcionPagada = DB::table('edocta')
                ->where('uid', $request->uid)
                            ->where('idPeriodo',$servicios->idPeriodo)
                            ->where('secuencia', $request->secuencia)
                            ->where('idServicio', $servicios->idServicioInscripcion)
                            ->where('tipomovto','A')
                ->exists();

            if ($inscripcionPagada) {
                $data = [
                            'message' => 'No se puede realizar la actualizaciòn la inscripcion ya esta pagada, favor de eliminar el pago ',                
                            'status' => 400
                        ];
            return response()->json($data, 400);
            } 
        
        }  

        if($request->monto >0){
            $colegiaturasPagadas = DB::table('edocta')
                        ->where('uid', $request->uid)
                        ->where('idPeriodo',$servicios->idPeriodo)
                        ->where('secuencia', $request->secuencia)
                        ->where('idServicio', $servicios->idServicioColegiatura)
                        ->where('tipomovto','A')
                        ->exists();

            if ($colegiaturasPagadas) {
                $data = [
                            'message' => 'No se puede realizar la actualizaciòn ya existen colegiaturas pagadas',                
                            'status' => 400
                        ];
                return response()->json($data, 400);
            } 
        }

        DB::statement("SET @origen = 'LARAVEL'");
        if($request->monto >0){
            //Borramos los recargos
            
            DB::table('edocta')
                    ->where('uid', $request->uid)
                    ->where('idPeriodo', $servicios->idPeriodo)
                    ->where('secuencia', $request->secuencia)
                    ->where('idServicio', $servicios->idServicioRecargo)
                    ->where('tipomovto', 'A')
                    ->delete();

                    DB::table('edocta')
                            ->where('uid', $request->uid)
                            ->where('idPeriodo',$servicios->idPeriodo)
                            ->where('secuencia', $request->secuencia)
                            ->where('idServicio', $servicios->idServicioColegiatura)
                            ->update([
                                    'importe' => $request->monto,
                                    'fechaMovto' => $fecha,
                                    'uidcajero' => $request->uidcajero
                            ]);

         $result = DB::select('CALL GeneraRecargosIndividual(?, ?, ?, ?)', [   
                                                        $servicios->idNivel,
                                                        $servicios->idPeriodo,
                                                        $request->uid,
                                                        $request->secuencia
                                                        ]);
       
        }

        if($request->montoInsc >0){
          DB::table('edocta')
                            ->where('uid', $request->uid)
                            ->where('idPeriodo',$servicios->idPeriodo)
                            ->where('secuencia', $request->secuencia)
                            ->where('idServicio', $servicios->idServicioInscripcion)
                            ->update([
                                    'importe' => $request->montoInsc,
                                    'fechaMovto' => $fecha,
                                    'uidcajero' => $request->uidcajero
                            ]);
        }
       
        return $this->returnData('Registros actualizados',null,200);          
    }


    public function destroy($uid, $secuencia,  $idPeriodo,$consecutivo, $uidcajero){

        DB::transaction(function () use ($uid, $secuencia, $consecutivo, $uidcajero, $idPeriodo) {

        $servicios = $this->obtenerServiciosTesoreria($uid, $secuencia, $idPeriodo);

        DB::statement("SET @origen = 'LARAVEL'");
        DB::statement("SET @uidcajero = ?", [$uidcajero]);

        /* Eliminar el abono solicitado y todos los abonos posteriores. */
        EstadoCuenta::where('uid', $uid)
            ->where('secuencia', $secuencia)
            ->where('tipomovto', "A")
            ->where('idPeriodo', $idPeriodo)
            ->where('consecutivo', '>=', $consecutivo)
            ->delete();

        DB::select('CALL GeneraRecargosIndividual(?, ?, ?, ?)', [
            (int) $servicios->idNivel,
            (int) $idPeriodo,
            (int) $uid,
            (int) $secuencia,
        ]);
        });        

         $matricula = DB::table('alumno')
                    ->where('uid', $uid)
                    ->where('secuencia', $secuencia)
                    ->value('matricula'); // devuelve directamente string o int

        DB::statement("CALL saldo(?, ?, ?, @vencido, @total)", [$uid, $matricula, $idPeriodo]);

        $saldoResult = DB::select("SELECT @vencido AS vencido, @total AS total");
  
       if ($saldoResult[0]->vencido > 0) {
                    // Verificamos si ya existe el registro
                    $existe = DB::table('bloqueoPersonas')
                        ->where('uid', $uid)
                        ->where('secuencia', $secuencia)
                        ->where('idBloqueo', 1)
                        ->exists();

                    // Si no existe, insertamos
                    if (!$existe) {
                        DB::table('bloqueoPersonas')->insert([
                            'uid' => $uid,
                            'secuencia' => $secuencia,
                            'idBloqueo' => 1,
                            'uidBloqueador' => $uidcajero,
                            'secuenciaBloq' => 1,
                            'BloqueoActivo' => '1', // S o N, dependiendo de tu convención
                            'fechaBloqueo' => now(), // o date('Y-m-d')
                            'descripcion' => 'Adeudo'
                        ]);
                    }
        }
        else{
             DB::table('bloqueoPersonas')
                    ->where('uid', $uid)
                    ->where('secuencia', $secuencia)
                    ->where('idBloqueo', 1)
                    ->delete();

        }

         return $this->returnData('Registros eliminados',null,200);  
    }

    public function getAbonos($uid, $secuencia,$idPeriodo){

     $servicios = $this->obtenerServiciosTesoreria($uid, $secuencia, $idPeriodo);

     $resultados = DB::table('edocta as edo')
                            ->select(
                                'edo.importe',
                                'edo.consecutivo',
                                'edo.uid',
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
                                ")
                            )
                            ->join('servicio as s', 's.idServicio', '=', 'edo.idServicio')
                            ->join('alumno as al', function ($join) {
                                $join->on('al.uid', '=', 'edo.uid')
                                    ->on('al.secuencia', '=', 'edo.secuencia');
                            })
                            ->leftJoin('configuracionTesoreria as colegiatura', function ($join) {
                                $join->on('colegiatura.idNivel', '=', 'al.idNivel')
                                    ->on('colegiatura.idServicioColegiatura', '=', 's.idServicio');
                            })
                            ->leftJoin('configuracionTesoreria as recargo', function ($join) {
                                $join->on('recargo.idNivel', '=', 'al.idNivel')
                                    ->on('recargo.idServicioRecargo', '=', 's.idServicio');
                            })    
                            ->where('edo.uid', $uid)
                            ->where('edo.secuencia', $secuencia)
                            ->where('edo.idPeriodo', $servicios->idPeriodo)
                            ->where('edo.tipomovto', 'A')
                            ->orderBy('edo.consecutivo', 'asc')
                            ->get();

            return $this->returnData('abonos',$resultados,200);
    }


    public function guardarMovtosServicios(Request $request) {
        $movimientos = $this->validarMovimientosImportados($request);
        $catalogos = $this->precargarDatosDelLote($movimientos);
        $identificadorArchivo = sprintf('ARCHIVO-%s-%s', now('America/Mexico_City')->format('Ymd-His'),Str::uuid()->toString());

        $resultado = DB::transaction(function () use ($movimientos, $catalogos, $identificadorArchivo) {
                return $this->procesarLoteDeServicios(
                    $movimientos,
                    $catalogos,
                    $identificadorArchivo
                );
        });
        $reporte = $this->generarReporteRechazados(
            $resultado['rechazados'] ?? [],
            $identificadorArchivo
        );
        return $this->respuestaImportacion($resultado, $reporte);
    }   

    private function validarMovimientosImportados(Request $request): array {

        $movimientos = $request->all();

        if (!is_array($movimientos) ||  empty($movimientos) || !array_is_list($movimientos)) {
            throw ValidationException::withMessages([
                'movimientos' => ['Se espera un arreglo JSON de movimientos.'],]);
        }

        /*   
        * Normalizar fechas antes de aplicar date_format.
        * Permite tanto dd/mm/aaaa como dd-mm-aaaa.
        */
        foreach ($movimientos as $indice => $movimiento) {
            if ( is_array($movimiento) &&isset($movimiento['dia'])) {
                $movimientos[$indice]['dia'] = str_replace('-', '/',trim((string) $movimiento['dia']));
            }
        }

        $validator = Validator::make(
            ['movimientos' => $movimientos],
            [
                'movimientos' => [ 'required', 'array', 'min:1',],
                'movimientos.*' => [ 'required','array',],
                'movimientos.*.dia' => ['required','date_format:d/m/Y',],
                // El concepto se valida por registro para incluirlo en el reporte de rechazados.
                'movimientos.*.concepto' => ['nullable'],

                'movimientos.*.abono' => ['required','numeric','gt:0',],
                'movimientos.*.transaccion' => ['required','string','max:255',],
                'movimientos.*.idFormaPago' => ['required', 'integer',],
                'movimientos.*.uidcajero' => ['required', 'integer',],
                'movimientos.*.idPeriodo' => ['nullable','integer',],],
            [
                'movimientos.*.dia.required' =>'La fecha del movimiento es obligatoria.',
                'movimientos.*.dia.date_format' =>'La fecha debe tener el formato dd/mm/aaaa.',
                'movimientos.*.concepto.required' =>'El concepto es obligatorio.',
                'movimientos.*.concepto.string' =>'El concepto debe enviarse como texto de exactamente 10 dígitos.',
                'movimientos.*.concepto.regex' =>'El concepto debe contener exactamente 10 dígitos: siete de matrícula y tres de servicio, sin letras ni otros caracteres.',
                'movimientos.*.abono.required' =>'El importe del abono es obligatorio.',
                'movimientos.*.abono.numeric' =>'El importe del abono debe ser numérico.',
                'movimientos.*.abono.gt' =>'El importe del abono debe ser mayor que cero.',
                'movimientos.*.transaccion.required' =>'La transacción es obligatoria.',
                'movimientos.*.idFormaPago.required' =>'La forma de pago es obligatoria.',
                'movimientos.*.uidcajero.required' =>'El usuario cajero es obligatorio.',
            ]
        );

        $data = $validator->validate();

        return collect($data['movimientos']) ->map(function (array $movimiento) {
                $concepto = $movimiento['concepto'] ?? null;
                $conceptoValido = is_string($concepto) && preg_match('/\A[0-9]{10}\z/', $concepto) === 1;
                return ['dia' => $movimiento['dia'],'concepto' => is_string($concepto) ? $concepto : '',
                    'errorConcepto' => $conceptoValido ? null : 'El concepto debe contener exactamente 10 dígitos: siete de matrícula y tres de servicio, sin letras ni otros caracteres.',
                    'abono' => round( (float) $movimiento['abono'], 2), 'transaccion' => trim( $movimiento['transaccion'] ),
                    'idFormaPago' => (int) $movimiento['idFormaPago'],
                    'uidcajero' => (int) $movimiento['uidcajero'],
                    'idPeriodo' => isset($movimiento['idPeriodo']) ? (int) $movimiento['idPeriodo'] : null,
                    'matricula' => $conceptoValido ? (int) substr($concepto, 0, 7) : 0,
                    'idServicio' => $conceptoValido ? (int) substr($concepto, 7, 3) : 0,
                ];
            })
            ->all();
    }

    private function precargarDatosDelLote( array $movimientos): array {
        $coleccion = collect($movimientos);

        $matriculas = $coleccion
            ->pluck('matricula')
            ->filter()
            ->unique()
            ->values();

        $transacciones = $coleccion
            ->pluck('transaccion')
            ->filter()
            ->unique()
            ->values();

        $periodosSolicitados = $coleccion
            ->pluck('idPeriodo')
            ->filter()
            ->unique()
            ->values();

        $idsServicios = $coleccion
            ->pluck('idServicio')
            ->filter()
            ->unique()
            ->values();

        $alumnos = DB::table('alumno')
            ->whereIn('matricula', $matriculas)
            ->select('matricula','uid','secuencia','idNivel')
            ->get()
            ->keyBy(fn ($alumno) => (string) $alumno->matricula);

        $niveles = $alumnos
            ->pluck('idNivel')
            ->filter()
            ->unique()
            ->values();

        $periodosActivos = DB::table('periodo')
            ->whereIn('idNivel', $niveles)
            ->where('activo', 1)
            ->select('idPeriodo','idNivel')
            ->get()
            ->keyBy(fn ($periodo) => (string) $periodo->idNivel);
        
        $periodos = empty($periodosSolicitados->all())
            ? collect()
            : DB::table('periodo')
                ->whereIn('idPeriodo', $periodosSolicitados)
                ->select('idPeriodo', 'idNivel','activo')
                ->get()
                ->keyBy(fn ($periodo) => (string) $periodo->idPeriodo);

        $servicios = DB::table('configuracionTesoreria')
            ->whereIn('idNivel', $niveles)
            ->select('idNivel','idServicioColegiatura','idServicioInscripcion',
                'idServicioRecargo', 'idServicioNotaCredito','idServicioTraspasoSaldos1'
            )
            ->get()
            ->keyBy(fn ($servicio) => (string) $servicio->idNivel);

        $serviciosPorId = DB::table('servicio')
            ->whereIn('idServicio', $idsServicios)
            ->select('idServicio', 'descripcion', 'tipoCobro', 'cargoAutomatico')
            ->get()
            ->keyBy(fn ($servicio) => (string) $servicio->idServicio);
        
        $idsPeriodos = $periodosSolicitados
            ->merge($periodosActivos->pluck('idPeriodo'))
            ->filter()
            ->unique()
            ->values();

        $transaccionesExistentes = collect();

        if ($transacciones->isNotEmpty() && $idsPeriodos->isNotEmpty() ) {
            $transaccionesExistentes = DB::table('edocta')
                ->whereIn('transaccion', $transacciones)
                ->whereIn('idPeriodo', $idsPeriodos)
                ->select( 'idPeriodo','transaccion','uid', 'secuencia')
                ->get()
                ->keyBy( fn ($registro) => $registro->idPeriodo .'|' .$registro->transaccion);
        }

        return [
            'alumnos' => $alumnos,
            'periodosActivos' => $periodosActivos,
            'periodos' => $periodos,
            'servicios' => $servicios,
            'serviciosPorId' => $serviciosPorId,
            'transaccionesExistentes' => $transaccionesExistentes,
        ];
    }

    private function generarReporteRechazados(
        array $registros,
        string $identificadorArchivo
    ): string {

        $concentrado = DB::table('edocta as edo')
            ->join('servicio as ser', 'ser.idServicio', '=', 'edo.idServicio')
            ->where('edo.tipoOrigen', 'ARCHIVO')
            ->where('edo.identificadorOrigen', $identificadorArchivo)
            ->where('edo.tipomovto', 'A')
            ->select([
                'edo.idServicio',
                'ser.descripcion as servicio',
                DB::raw('SUM(edo.importe) AS total'),
            ])
            ->groupBy('edo.idServicio', 'ser.descripcion')
            ->orderBy('edo.idServicio')
            ->get();

        $totalGeneral = round((float) $concentrado->sum('total'), 2);

        $imagePathEnc = public_path('images/encPag.png');
        $imagePathPie = public_path('images/piePag.png');
        $columnWidths = [40, 70, 60, 70, 110, 260];
        $pdf = new CustomTCPDF( 'P',PDF_UNIT,'letter', true,'UTF-8', false);
        $pdf->setHeaders(null, $columnWidths, 'REPORTE DE IMPORTACION');
        $pdf->setImagePaths( $imagePathEnc, $imagePathPie,'P');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');
        $pdf->SetTitle('Reporte de importacion de movimientos');
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 7);
        $html = '<br><br><br>
                    <p><b>IDENTIFICADOR:</b> '.htmlspecialchars($identificadorArchivo, ENT_QUOTES, 'UTF-8').'</p>
                    <h3>MOVIMIENTOS NO PROCESADOS</h3>
                    <table width="100%" border="0" cellpadding="3">
                        <thead>
                            <tr style="font-weight:bold;">
                                <th width="5%">#</th>
                                <th width="12%">MATRÍCULA</th>
                                <th width="10%">UID</th>
                                <th width="12%">IMPORTE</th>
                                <th width="25%">TRANSACCIÓN</th>
                                <th width="36%">MOTIVO</th>
                            </tr>
                        </thead>
                        <tbody>';

        if (empty($registros)) {
            $html .= '<tr nobr="true"><td colspan="6">No existen movimientos rechazados.</td></tr>';
        }

        foreach ($registros as $registro) {
            $indice = isset($registro['indice']) ? ((int) $registro['indice'] + 1) : '';
            $matricula = htmlspecialchars((string) ($registro['matricula'] ?? ''), ENT_QUOTES, 'UTF-8');
            $uid = htmlspecialchars((string) ($registro['uid'] ?? ''),ENT_QUOTES,'UTF-8');
            $importe = number_format((float) ($registro['importe'] ?? 0), 2,'.',',');
            $transaccion = htmlspecialchars((string) ($registro['transaccion'] ?? ''),ENT_QUOTES,'UTF-8');
            $mensaje = htmlspecialchars((string) ($registro['mensaje'] ?? ''),ENT_QUOTES,'UTF-8');

            $html .= '
                <tr nobr="true">
                    <td width="5%">'.$indice.'</td>
                    <td width="12%">'.$matricula.'</td>
                    <td width="10%">'.$uid.'</td>
                    <td width="12%">$'.$importe.'</td>
                    <td width="25%">'.$transaccion.'</td>
                    <td width="36%">'.$mensaje.'</td>
                </tr>
            ';
        }
        $html .= '</tbody></table>
            <br><br>
            <h3>CONCENTRADO DE MOVIMIENTOS INGRESADOS</h3>
            <table border="0" cellpadding="3">
                <thead>
                    <tr style="font-weight:bold;">
                        <th width="75">ID SERVICIO</th>
                        <th width="200">DESCRIPCION DEL SERVICIO</th>
                        <th width="85" align="right">TOTAL</th>
                    </tr>
                </thead>
                <tbody>';

        if ($concentrado->isEmpty()) {
            $html .= '<tr nobr="true"><td colspan="3">No se ingresaron movimientos.</td></tr>';
        } else {
            foreach ($concentrado as $servicio) {
                $descripcion = htmlspecialchars(
                    (string) $servicio->servicio,
                    ENT_QUOTES,
                    'UTF-8'
                );

                $html .= '<tr nobr="true">
                    <td width="75">'.(int) $servicio->idServicio.'</td>
                    <td width="200">'.$descripcion.'</td>
                    <td width="85" align="right">$'.number_format((float) $servicio->total, 2, '.', ',').'</td>
                </tr>';
            }
        }

        $html .= '<tr style="font-weight:bold;">
                    <td width="275" colspan="2" align="right">TOTAL GENERAL</td>
                    <td width="85" align="right">$'.number_format($totalGeneral, 2, '.', ',').'</td>
                </tr>
                </tbody>
            </table>';
        $pdf->writeHTML( $html, true,false, true, false, '' );        
        $nombreArchivo = 'validacion-'.Str::uuid().'.pdf';
        $rutaAbsoluta = Storage::disk('public')->path( $nombreArchivo);
        $pdf->Output($rutaAbsoluta, 'F');

        if (!is_file($rutaAbsoluta)) {
            throw new \RuntimeException('No fue posible generar el reporte de movimientos rechazados.');
        }    
        return Storage::disk('public')->url($nombreArchivo);
    }

    /**
     * Genera un reporte independiente con el concentrado de los abonos
     * agrupados por servicio dentro de cada nivel y forma de pago.
     *
     * Este proceso no modifica el reporte generado durante la importacion.
     */
    public function generarReporteConcentradoMovimientos(Request $request) {
        $data = $request->validate([
            'fechaInicio' => ['required', 'date_format:Y-m-d'],
            'fechaFin' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:fechaInicio',
            ],
        ], [
            'fechaInicio.required' => 'La fecha inicial es obligatoria.',
            'fechaInicio.date_format' => 'La fecha inicial debe tener el formato Y-m-d.',
            'fechaFin.required' => 'La fecha final es obligatoria.',
            'fechaFin.date_format' => 'La fecha final debe tener el formato Y-m-d.',
            'fechaFin.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ]);

        $fechaInicio = Carbon::createFromFormat(
            'Y-m-d',
            $data['fechaInicio'],
            'America/Mexico_City'
        )->startOfDay();

        $fechaFin = Carbon::createFromFormat(
            'Y-m-d',
            $data['fechaFin'],
            'America/Mexico_City'
        )->endOfDay();

        $concentradoNivel = DB::table('edocta as edo')
            ->join('alumno as al', function ($join) {
                $join->on('al.uid', '=', 'edo.uid')
                    ->on('al.secuencia', '=', 'edo.secuencia');
            })
            ->leftJoin('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
            ->join('servicio as ser', 'ser.idServicio', '=', 'edo.idServicio')
            ->where('edo.tipomovto', 'A')
            ->whereBetween(DB::raw('COALESCE(edo.FechaPago, edo.fechaMovto)'), [
                $fechaInicio->toDateString(),
                $fechaFin->toDateString(),
            ])
            ->select([
                'al.idNivel',
                DB::raw("COALESCE(niv.descripcion, 'SIN NIVEL') AS nivel"),
                'edo.idServicio',
                'ser.descripcion as servicio',
                DB::raw('COUNT(*) AS movimientos'),
                DB::raw('SUM(edo.importe) AS total'),
            ])
            ->groupBy('al.idNivel', 'niv.descripcion', 'edo.idServicio', 'ser.descripcion')
            ->orderBy('al.idNivel')
            ->orderBy('edo.idServicio')
            ->get();

        $concentradoFormaPago = DB::table('edocta as edo')
            ->leftJoin('formaPago as fp', 'fp.idFormaPago', '=', 'edo.idformaPago')
            ->join('servicio as ser', 'ser.idServicio', '=', 'edo.idServicio')
            ->where('edo.tipomovto', 'A')
            ->whereBetween(DB::raw('COALESCE(edo.FechaPago, edo.fechaMovto)'), [
                $fechaInicio->toDateString(),
                $fechaFin->toDateString(),
            ])
            ->select([
                'edo.idformaPago',
                DB::raw("COALESCE(fp.descripcion, 'SIN FORMA DE PAGO') AS formaPago"),
                'edo.idServicio',
                'ser.descripcion as servicio',
                DB::raw('COUNT(*) AS movimientos'),
                DB::raw('SUM(edo.importe) AS total'),
            ])
            ->groupBy('edo.idformaPago', 'fp.descripcion', 'edo.idServicio', 'ser.descripcion')
            ->orderByRaw('edo.idformaPago IS NULL')
            ->orderBy('edo.idformaPago')
            ->orderBy('edo.idServicio')
            ->get();

        $totalMovimientos = (int) $concentradoNivel->sum('movimientos');
        $totalGeneral = round((float) $concentradoNivel->sum('total'), 2);

        $imagePathEnc = public_path('images/encPag.png');
        $imagePathPie = public_path('images/piePag.png');
        $columnWidths = [65, 230, 70, 90];

        $pdf = new CustomTCPDF('P', PDF_UNIT, 'letter', true, 'UTF-8', false);
        $pdf->setHeaders(
            null,
            $columnWidths,
            'CONCENTRADO DE MOVIMIENTOS INGRESADOS'
        );
        $pdf->setImagePaths($imagePathEnc, $imagePathPie, 'P');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');
        $pdf->SetTitle('Concentrado de movimientos ingresados');
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 8);

        $html = '<br><br><br>
            <p><b>FECHA INICIAL:</b> '.$fechaInicio->format('d/m/Y').'</p>
            <p><b>FECHA FINAL:</b> '.$fechaFin->format('d/m/Y').'</p>';

        $encabezadoServicios = '<table border="0" cellpadding="3">
            <thead><tr style="font-weight:bold;">
                <th width="65">ID SERVICIO</th>
                <th width="230">DESCRIPCION DEL SERVICIO</th>
                <th width="70" align="right">MOVIMIENTOS</th>
                <th width="90" align="right">TOTAL</th>
            </tr></thead><tbody>';

        $html .= '<br><h3>SERVICIOS AGRUPADOS POR NIVEL</h3>';
        if ($concentradoNivel->isEmpty()) {
            $html .= '<p>No existen movimientos en el rango solicitado.</p>';
        } else {
            foreach ($concentradoNivel->groupBy('idNivel') as $serviciosNivel) {
                $nivel = $serviciosNivel->first();
                $descripcionNivel = htmlspecialchars((string) $nivel->nivel, ENT_QUOTES, 'UTF-8');
                $html .= '<br><h4>NIVEL '.(int) $nivel->idNivel.' - '.$descripcionNivel.'</h4>';
                $html .= $encabezadoServicios;
                foreach ($serviciosNivel as $servicio) {
                    $descripcionServicio = htmlspecialchars((string) $servicio->servicio, ENT_QUOTES, 'UTF-8');
                    $html .= '<tr>
                        <td width="65">'.(int) $servicio->idServicio.'</td>
                        <td width="230">'.$descripcionServicio.'</td>
                        <td width="70" align="right">'.(int) $servicio->movimientos.'</td>
                        <td width="90" align="right">$'.number_format((float) $servicio->total, 2, '.', ',').'</td>
                    </tr>';
                }
                $html .= '<tr style="font-weight:bold;">
                    <td width="295" colspan="2" align="right">SUBTOTAL DEL NIVEL</td>
                    <td width="70" align="right">'.(int) $serviciosNivel->sum('movimientos').'</td>
                    <td width="90" align="right">$'.number_format((float) $serviciosNivel->sum('total'), 2, '.', ',').'</td>
                </tr></tbody></table>';
            }
        }
        $html .= '<p style="font-weight:bold;" align="right">TOTAL GENERAL: '
            .$totalMovimientos.' MOVIMIENTOS - $'.number_format($totalGeneral, 2, '.', ',').'</p>';

        $html .= '<br pagebreak="true" /><h3>SERVICIOS AGRUPADOS POR FORMA DE PAGO</h3>';
        if ($concentradoFormaPago->isEmpty()) {
            $html .= '<p>No existen movimientos en el rango solicitado.</p>';
        } else {
            foreach ($concentradoFormaPago->groupBy(function ($item) {
                return is_null($item->idformaPago) ? 'SIN_FORMA' : (string) $item->idformaPago;
            }) as $serviciosFormaPago) {
                $formaPago = $serviciosFormaPago->first();
                $idFormaPago = is_null($formaPago->idformaPago) ? '-' : (string) ((int) $formaPago->idformaPago);
                $descripcionForma = htmlspecialchars((string) $formaPago->formaPago, ENT_QUOTES, 'UTF-8');
                $html .= '<br><h4>FORMA DE PAGO '.$idFormaPago.' - '.$descripcionForma.'</h4>';
                $html .= $encabezadoServicios;
                foreach ($serviciosFormaPago as $servicio) {
                    $descripcionServicio = htmlspecialchars((string) $servicio->servicio, ENT_QUOTES, 'UTF-8');
                    $html .= '<tr>
                        <td width="65">'.(int) $servicio->idServicio.'</td>
                        <td width="230">'.$descripcionServicio.'</td>
                        <td width="70" align="right">'.(int) $servicio->movimientos.'</td>
                        <td width="90" align="right">$'.number_format((float) $servicio->total, 2, '.', ',').'</td>
                    </tr>';
                }
                $html .= '<tr style="font-weight:bold;">
                    <td width="295" colspan="2" align="right">SUBTOTAL DE FORMA DE PAGO</td>
                    <td width="70" align="right">'.(int) $serviciosFormaPago->sum('movimientos').'</td>
                    <td width="90" align="right">$'.number_format((float) $serviciosFormaPago->sum('total'), 2, '.', ',').'</td>
                </tr></tbody></table>';
            }
        }
        $html .= '<p style="font-weight:bold;" align="right">TOTAL GENERAL: '
            .(int) $concentradoFormaPago->sum('movimientos').' MOVIMIENTOS - $'
            .number_format((float) $concentradoFormaPago->sum('total'), 2, '.', ',').'</p>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $nombreArchivo = 'concentrado-movimientos-'.Str::uuid().'.pdf';
        $rutaAbsoluta = Storage::disk('public')->path($nombreArchivo);
        $pdf->Output($rutaAbsoluta, 'F');

        if (!is_file($rutaAbsoluta)) {
            throw new \RuntimeException(
                'No fue posible generar el concentrado de movimientos ingresados.'
            );
        }

        return response()->json([
            'message' => route(
                'estadoscuenta.reporte-concentrado.archivo',
                ['archivo' => $nombreArchivo]
            ),
            'data' => [
                'fechaInicio' => $fechaInicio->toDateString(),
                'fechaFin' => $fechaFin->toDateString(),
                'servicios' => $concentradoNivel->pluck('idServicio')->unique()->count(),
                'niveles' => $concentradoNivel->pluck('idNivel')->unique()->count(),
                'formasPago' => $concentradoFormaPago->pluck('idformaPago')->unique()->count(),
                'movimientos' => $totalMovimientos,
                'totalGeneral' => $totalGeneral,
            ],
            'error' => null,
            'status' => 200,
        ], 200);
    }

    public function descargarReporteConcentradoMovimientos(string $archivo) {
        if (!preg_match('/\Aconcentrado-movimientos-[0-9a-fA-F-]+\.pdf\z/', $archivo)) {
            abort(404);
        }

        $rutaAbsoluta = Storage::disk('public')->path($archivo);

        if (!is_file($rutaAbsoluta)) {
            abort(404);
        }

        return response()->file($rutaAbsoluta, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $archivo),
        ]);
    }

    private function procesarLoteDeServicios(array $movimientos, array $catalogos, string $identificadorArchivo): array {

            $rechazados = [];
            $guardados = 0;
            $importeGuardado = 0.0;
            $importeTotal = 0.0;
            $alumnosParaDesbloqueo = [];

            $ultimoFolio = EstadoCuenta::query()
                            ->whereNotNull('folio')
                            ->orderByDesc('folio')
                            ->lockForUpdate()
                            ->value('folio');

            $siguienteFolio = ((int) $ultimoFolio) + 1;

            $transaccionesProcesadas = collect(
                $catalogos['transaccionesExistentes']->all()
            );

            foreach ($movimientos as $indice => $movimiento) {
                $matricula = (int) $movimiento['matricula'];
                $abono = round((float) $movimiento['abono'], 2);
                $transaccion = trim($movimiento['transaccion']);
                $importeTotal += $abono;
                if (!empty($movimiento['errorConcepto'])) {
                    $rechazados[] = $this->crearRegistroRechazado(
                        $matricula, null, $abono, $movimiento['errorConcepto'], $indice, $transaccion
                    );
                    continue;
                }
                $alumno = $catalogos['alumnos']->get((string) $matricula);

                if (!$alumno) {
                    $rechazados[] = $this->crearRegistroRechazado(
                        $matricula, null, $abono, 'No existe la matrícula en el sistema',  $indice, $transaccion );
                    continue;
                }

                $idPeriodo = $this->resolverPeriodoMovimiento( $movimiento, $alumno, $catalogos);

                if (!$idPeriodo) {
                    $rechazados[] = $this->crearRegistroRechazado(
                        $matricula, (int) $alumno->uid, $abono,'No existe un periodo válido para el alumno', $indice, $transaccion
                    );
                    continue;
                }

                $idServicioArchivo = (int) $movimiento['idServicio'];
                $servicioArchivo = $catalogos['serviciosPorId']->get(
                    (string) $idServicioArchivo
                );

                if (!$servicioArchivo) {
                    $rechazados[] = $this->crearRegistroRechazado(
                                           $matricula, (int) $alumno->uid, $abono,
                                           'El servicio indicado en el concepto no existe',
                                           $indice,$transaccion
                    );
                    continue;
                }

                $tipoCobro = strtoupper(trim((string) $servicioArchivo->tipoCobro));
                $esCobroEspecifico = $tipoCobro === 'ESPECIFICO';
                $servicios = $catalogos['servicios']->get((string) $alumno->idNivel);

                if (!$servicios) {
                        $rechazados[] = $this->crearRegistroRechazado(
                        $matricula, (int) $alumno->uid, $abono,'No existe configuración de Tesorería para el nivel del alumno',
                        $indice, $transaccion);
                    continue;
                }               

                $claveTransaccion = $idPeriodo.'|'.$transaccion;

                if ($transaccionesProcesadas->has($claveTransaccion)) {
                    $rechazados[] = $this->crearRegistroRechazado(
                        $matricula, (int) $alumno->uid, $abono,'La transacción ya se encuentra dada de alta en el periodo',
                        $indice, $transaccion );
                    continue;
                }

                $fechaPago = Carbon::createFromFormat('d/m/Y',$movimiento['dia'])->startOfDay();
                $folio = $siguienteFolio;

                if ($esCobroEspecifico) {
                    $this->procesarServicioEspecifico(
                        [
                            'importe' => $abono,
                            'idformaPago' => (int) $movimiento['idFormaPago'],
                            'cuatrodigitos' => null,
                            'transaccion' => $transaccion,
                        ],
                        $servicioArchivo,
                        (int) $alumno->uid,
                        (int) $alumno->secuencia,
                        $idPeriodo,
                        (int) $movimiento['uidcajero'],
                        $fechaPago,
                        $folio,
                        'ARCHIVO',
                        $identificadorArchivo
                    );
                } else {
                /*
                * Estructura esperada por procesarMovimiento().
                */
                $datosMovimiento = [
                                    'importe' => $abono,
                                    'idformaPago' => (int) $movimiento['idFormaPago'],
                                    'idServicio' => (int)$servicios->idServicioTraspasoSaldos1,
                                    'cuatrodigitos' => null,
                                    'tipomovto' => 'A',
                                    'cargoAut' => 0,
                                    'transaccion' => $transaccion,
                ];
                /*
                * Este método distribuye el pago entre los conceptos
                * pendientes y contiene la regla de recargos posteriores
                * a la fecha efectiva del pago.
                */
                $distribucion = $this->procesarMovimientoPorSaldos(
                                $datosMovimiento,
                                $servicios,
                                (int) $alumno->uid,
                                (int) $alumno->secuencia,
                                $idPeriodo,
                                (int) $movimiento['uidcajero'],
                                $fechaPago,
                                $folio,
                                'ARCHIVO',
                                $identificadorArchivo 
                                );
                }

                $guardados++;
                $importeGuardado += $abono;
                $siguienteFolio++;

                $transaccionesProcesadas->put($claveTransaccion,(object) [
                        'idPeriodo' => $idPeriodo,
                        'transaccion' => $transaccion,
                        'uid' => (int) $alumno->uid,
                        'secuencia' => (int) $alumno->secuencia,
                    ]
                );

                $claveAlumnoPeriodo = $alumno->uid.'|'.$alumno->secuencia.'|'.$idPeriodo;
                $alumnosParaDesbloqueo[$claveAlumnoPeriodo] = [
                    'uid' => (int) $alumno->uid,
                    'secuencia' => (int) $alumno->secuencia,
                    'matricula' => (int) $matricula,
                    'idPeriodo' => (int) $idPeriodo,
                ];
            }

            $this->desbloquearAlumnosProcesados($alumnosParaDesbloqueo);

            return [
                'totalRegistros' => count($movimientos),
                'guardados' => $guardados,
                'rechazados' => $rechazados,
                'importeGuardado' => round($importeGuardado, 2),
                'importeTotal' => round($importeTotal, 2),
                'tipoOrigen' => 'ARCHIVO',
                'identificadorOrigen' => $identificadorArchivo,
            ];
    }

    private function desbloquearAlumnosProcesados(array $alumnos): void {
        if (empty($alumnos)) {
            return;
        }

        $alumnosSinSaldoVencido = [];

        foreach ($alumnos as $alumno) {
            DB::statement(
                'CALL saldo(?, ?, ?, @vencido, @total)',
                [
                    $alumno['uid'],
                    $alumno['matricula'],
                    $alumno['idPeriodo'],
                ]
            );

            $saldo = DB::selectOne(
                'SELECT @vencido AS vencido, @total AS total'
            );

            if ((float) ($saldo->vencido ?? 0) <= 0) {
                $claveAlumno = $alumno['uid'].'|'.$alumno['secuencia'];
                $alumnosSinSaldoVencido[$claveAlumno] = [
                    'uid' => $alumno['uid'],
                    'secuencia' => $alumno['secuencia'],
                ];
            }
        }

        if (empty($alumnosSinSaldoVencido)) {
            return;
        }

        DB::table('bloqueoPersonas')
            ->where('idBloqueo', 1)
            ->where(function ($query) use ($alumnosSinSaldoVencido) {
                foreach ($alumnosSinSaldoVencido as $alumno) {
                    $query->orWhere(function ($alumnoQuery) use ($alumno) {
                        $alumnoQuery
                            ->where('uid', $alumno['uid'])
                            ->where('secuencia', $alumno['secuencia']);
                    });
                }
            })
            ->delete();
    }

    private function procesarMovimientoPorSaldos(array $movimiento,object $servicios, int $uid, int $secuencia,
                                            int $idPeriodo,  int $uidcajero,Carbon $fechaPago, int $folio,
                                            $tipoOrigen, $identificadorOrigen,
                                            ?int $idServicioObjetivo = null,
                                            bool $registrarExcedente = true): array {
            $importeOriginal = round((float) $movimiento['importe'], 2 );

            $importeRestante = $importeOriginal;
            $aplicaciones = [];

            if ($importeOriginal <= 0) {
                throw new \InvalidArgumentException(
                    'El importe del movimiento debe ser mayor que cero.'
                );
            }

            DB::statement("SET @origen = 'LARAVEL'");
            DB::statement('SET @uidcajero = ?', [$uidcajero]);

            /*
            * Los saldos ya vienen ordenados por:
            *
            * 1. ordenCobroServicio.orden por nivel del alumno
            * 2. parcialidad
            * 3. referencia
            */
            do {
                $recalcularSaldos = false;
                $saldos = $this->consultarSaldosPorServicio(
                    $uid,
                    $secuencia,
                    $idPeriodo
                );

                /*
                 * El saldo anterior negativo funciona como crédito virtual.
                 * No genera movimientos: reduce, en el orden de cobro del
                 * nivel, el importe que realmente debe pagarse.
                 */
                $creditoSaldoAnterior = $this->obtenerCreditoSaldoAnterior(
                    $uid,
                    $secuencia,
                    $idPeriodo,
                    (int) $servicios->idServicioTraspasoSaldos1
                );

    foreach ($saldos as $saldo) {
        if ($importeRestante <= 0) {
            break;
        }

        $referenciaSaldo = trim((string) $saldo->referencia);

        if (!preg_match('/^[0-9]{3}/', $referenciaSaldo)) {
            throw new \RuntimeException(
                'La referencia del saldo debe comenzar con tres dígitos de servicio.'
            );
        }

        /*
         * El servicio aplicado siempre se obtiene de los tres primeros
         * dígitos de la referencia, independientemente de edocta.idServicio.
         */
        $idServicioSaldo = (int) substr($referenciaSaldo, 0, 3);
        $saldo->idServicio = $idServicioSaldo;

        /*
         * Un saldo anterior negativo se consume como credito virtual mediante
         * $creditoSaldoAnterior. Si el saldo es positivo, debe permanecer en
         * esta lista y recibir el pago conforme al orden de cobro configurado.
         */

        /*
         * Regla de recargos:
                *
                * Si el recargo fue generado después de la fecha
                * efectiva del pago, se elimina y no se le aplica abono.
                */
        if (
            $idServicioSaldo === (int) $servicios->idServicioRecargo
            && $saldo->fechaPagoRecargo
            && $fechaPago->lt(Carbon::parse($saldo->fechaPagoRecargo))
        ) {
                    $recargosEliminados = $this->eliminarRecargoPosteriorAlPago(
                        $uid,
                        $secuencia,
                        $idPeriodo,
                        $saldo,
                        $uidcajero,
                        $fechaPago
                    );
                    $aplicaciones[] = [
                                'idServicio' => $idServicioSaldo,
                                        'servicio' => $saldo->servicio,
                                        'referencia' => $saldo->referencia,
                                        'parcialidad' => $saldo->parcialidad,
                                        'accion' => 'recargo_eliminado',
                                        'importe' => 0,
                                        'registrosEliminados' => $recargosEliminados,
                    ];

                    if ($recargosEliminados > 0) {
                        $recalcularSaldos = true;
                        break;
                    }

                    continue;
                }

                $saldoPendiente = round((float) $saldo->saldo, 2);

                if ($saldoPendiente <= 0) {
                    continue;
                }

                /*
                 * Consume primero el crédito virtual. Se realiza antes de
                 * filtrar un servicio específico para respetar siempre el
                 * orden general de cobro del nivel.
                 */
                if ($creditoSaldoAnterior > 0) {
                    $creditoAplicado = min(
                        $creditoSaldoAnterior,
                        $saldoPendiente
                    );

                    $saldoPendiente = round(
                        $saldoPendiente - $creditoAplicado,
                        2
                    );
                    $creditoSaldoAnterior = round(
                        $creditoSaldoAnterior - $creditoAplicado,
                        2
                    );
                }

                if ($saldoPendiente <= 0) {
                    continue;
                }

                if (
                    $idServicioObjetivo !== null
                    && $idServicioSaldo !== $idServicioObjetivo
                ) {
                    continue;
                }
                
                $importeAplicado = min( $importeRestante, $saldoPendiente);
                $importeAplicado = round( $importeAplicado, 2);

                if ($importeAplicado <= 0) {
                    continue;
                }
                
                $this->crearMovimiento([
                                        'uid' => $uid,
                                        'secuencia' => $secuencia,
                                        'idServicio' => $idServicioSaldo,
                                        'importe' => $importeAplicado,
                                        'idPeriodo' => $idPeriodo,
                                        'fechaMovto' => $fechaPago,
                                        'FechaPago' => $fechaPago,
                                        'idformaPago' => $movimiento['idformaPago'] ?? null,
                                        'cuatrodigitos' => $movimiento['cuatrodigitos'] ?? null,
                                        'tipomovto' => 'A',
                                        'folio' => $folio,
                                        'referencia' => $saldo->referencia,
                                        'parcialidad' => $saldo->parcialidad,
                                        'uidcajero' => $uidcajero,
                                        'transaccion' => $movimiento['transaccion'] ?? null,
                                        'tipoOrigen' => $tipoOrigen,
                                        'identificadorOrigen' => $identificadorOrigen,
                ]);

                $importeRestante = round($importeRestante - $importeAplicado, 2 );
                $aplicaciones[] = [
                                    'idServicio' => $idServicioSaldo,
                                    'servicio' => $saldo->servicio,
                                    'referencia' => $saldo->referencia,
                                    'parcialidad' => $saldo->parcialidad,
                                    'saldoAnterior' => $saldoPendiente,
                                    'importeAplicado' => $importeAplicado,
                                    'saldoPosterior' => round($saldoPendiente - $importeAplicado, 2),
                                    'accion' => 'abono_aplicado',
                ];
            }
            } while ($recalcularSaldos && $importeRestante > 0);
            /*
            * Si después de pagar todos los adeudos queda dinero,
            * se registra como saldo a favor.
            */
            if ($importeRestante > 0 && $registrarExcedente) {
                $idServicioSaldoFavor =
                    $servicios->idServicioNotaCredito
                    ?? $servicios->idServicioTraspasoSaldos1;

                if (!$idServicioSaldoFavor) {
                    throw new \RuntimeException(
                        'No existe un servicio configurado para registrar el saldo a favor.'
                    );
                }

                $referenciaSaldoFavor = $this->generarReferenciaSaldoFavor(
                    (int) $idServicioSaldoFavor,
                    $fechaPago
                );

                $this->crearMovimiento([
                                'uid' => $uid,
                                'secuencia' => $secuencia,
                                'idServicio' => (int) $idServicioSaldoFavor,
                                'importe' => $importeRestante,
                                'idPeriodo' => $idPeriodo,
                                'fechaMovto' => $fechaPago,
                                'FechaPago' => $fechaPago,
                                'idformaPago' => $movimiento['idformaPago'] ?? null,
                                'cuatrodigitos' => $movimiento['cuatrodigitos'] ?? null,
                                'tipomovto' => 'A',
                                'folio' => $folio,
                                'referencia' => $referenciaSaldoFavor,
                                'parcialidad' => 999,
                                'uidcajero' => $uidcajero,
                                'transaccion' => $movimiento['transaccion'] ?? null,
                                'tipoOrigen' => $tipoOrigen,
                                'identificadorOrigen' => $identificadorOrigen,
                ]);

                $aplicaciones[] = [
                                    'idServicio' => (int) $idServicioSaldoFavor,
                                    'servicio' => 'Saldo a favor',
                                    'referencia' => $referenciaSaldoFavor,
                                    'parcialidad' => 999,
                                    'importeAplicado' => $importeRestante,
                                    'accion' => 'saldo_a_favor',];

                $importeRestante = 0.0;
            }

            return [
                'importeOriginal' => $importeOriginal,
                'importeAplicado' => round($importeOriginal - $importeRestante, 2 ),
                'importeRestante' => $importeRestante,
                'aplicaciones' => $aplicaciones,
            ];
        }

    private function eliminarRecargoPosteriorAlPago(int $uid, int $secuencia,int $idPeriodo, object $saldo, int $uidcajero, Carbon $fechaPago): int {
        
        DB::statement("SET @origen = 'LARAVEL'");
        DB::statement('SET @uidcajero = ?', [$uidcajero]);

        $query = DB::table('edocta')
                        ->where('uid', $uid)
                        ->where('secuencia', $secuencia)
                        ->where('idPeriodo', $idPeriodo)
                        ->where('tipomovto', 'C')
                        ->where('FechaPago', '>', $fechaPago->toDateString());

        if ($saldo->referencia === null) {
            $query->whereNull('referencia');
        } else {
            $query->where( 'referencia', $saldo->referencia);
        }

        if ($saldo->parcialidad === null) {
            $query->whereNull('parcialidad');
        } else {
            $query->where('parcialidad', $saldo->parcialidad);
        }
        return $query->delete();
    }

    private function generarReferenciaSaldoFavor(int $idServicio, Carbon $fechaPago): string {
        if ($idServicio < 0 || $idServicio > 999) {
            throw new \InvalidArgumentException(
                'El identificador del servicio debe tener como máximo tres dígitos.'
            );
        }
        return sprintf('%03d000%02d', $idServicio,(int) $fechaPago->format('m')
        );
    }

    private function consultarSaldosPorServicio(int $uid, int $secuencia, int $idPeriodo) {
        return DB::table('edocta as edo')
            ->join('alumno as al', function ($join) {
                $join->on('al.uid', '=', 'edo.uid')
                    ->on('al.secuencia', '=', 'edo.secuencia');
            })
            ->join(
                'servicio as ser',
                'ser.idServicio',
                '=',
                DB::raw('CAST(LEFT(edo.referencia, 3) AS UNSIGNED)')
            )
            ->leftJoin('ordenCobroServicio as ocs', function ($join) {
                $join->on('ocs.idNivel', '=', 'al.idNivel')
                    ->on(
                        'ocs.idServicio',
                        '=',
                        DB::raw('CAST(LEFT(edo.referencia, 3) AS UNSIGNED)')
                    );
            })
            ->where('edo.uid', $uid)
            ->where('edo.secuencia', $secuencia)
            ->where('edo.idPeriodo', $idPeriodo)
            ->select([
                DB::raw('CAST(LEFT(edo.referencia, 3) AS UNSIGNED) AS idServicio'),
                'ser.descripcion as servicio',
                'ocs.orden as ordenCobro',
                'edo.referencia',
                'edo.parcialidad',
                DB::raw("
                    MAX(
                        CASE
                            WHEN edo.tipomovto = 'C'
                            THEN edo.FechaPago
                            ELSE NULL
                        END
                    ) AS fechaPagoRecargo
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN edo.tipomovto = 'C'
                            THEN edo.importe
                            ELSE 0
                        END
                    ) AS cargos
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN edo.tipomovto = 'A'
                            THEN edo.importe
                            ELSE 0
                        END
                    ) AS abonos
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN edo.tipomovto = 'C'
                            THEN edo.importe
                            WHEN edo.tipomovto = 'A'
                            THEN -edo.importe
                            ELSE 0
                        END
                    ) AS saldo
                "),
            ])
            ->groupBy([
                'ser.descripcion',
                'ocs.orden',
                'edo.referencia',
                'edo.parcialidad',
            ])
            ->havingRaw("
                SUM( CASE WHEN edo.tipomovto = 'C'
                        THEN edo.importe
                        WHEN edo.tipomovto = 'A'
                        THEN -edo.importe
                        ELSE 0
                    END
                ) > 0
            ")
            ->orderByRaw('COALESCE(ocs.orden, 999999) ASC' )            
            ->orderByRaw( 'COALESCE(edo.parcialidad, 999999) ASC' )
            ->orderBy('edo.referencia')
            ->orderByRaw('CAST(LEFT(edo.referencia, 3) AS UNSIGNED) ASC')
            ->lockForUpdate()
            ->get();
    }

    private function obtenerCreditoSaldoAnterior(
        int $uid,
        int $secuencia,
        int $idPeriodo,
        int $idServicioSaldoAnterior
    ): float {
        if ($idServicioSaldoAnterior <= 0) {
            return 0.0;
        }

        $saldo = DB::table('edocta')
            ->where('uid', $uid)
            ->where('secuencia', $secuencia)
            ->where('idPeriodo', $idPeriodo)
            ->where('idServicio', $idServicioSaldoAnterior)
            ->selectRaw("\n                SUM(\n                    CASE\n                        WHEN tipomovto = 'C' THEN importe\n                        WHEN tipomovto = 'A' THEN -importe\n                        ELSE 0\n                    END\n                ) AS saldo\n            ")
            ->lockForUpdate()
            ->value('saldo');

        $saldo = round((float) ($saldo ?? 0), 2);

        return $saldo < 0 ? abs($saldo) : 0.0;
    }
    
    private function resolverPeriodoMovimiento( array $movimiento,  object $alumno,  array $catalogos): ?int {
    
        if (!empty($movimiento['idPeriodo'])) {
            $periodo = $catalogos['periodos']->get(
                (string) $movimiento['idPeriodo']
            );

            if (!$periodo) {
                return null;
            }
            
            if ((int) $periodo->idNivel !== (int) $alumno->idNivel) {
                return null;
            }

        return (int) $periodo->idPeriodo;
    }

    /*
     * Cuando el movimiento no proporciona periodo,
     * utilizar el periodo activo del nivel.
     */
    $periodoActivo = $catalogos['periodosActivos']->get((string) $alumno->idNivel);

    if (!$periodoActivo) {
        return null;
    }
    return (int) $periodoActivo->idPeriodo;
    }

    private function crearRegistroRechazado(int $matricula, ?int $uid, float $importe, string $mensaje, int $indice, string $transaccion): array {
        return [
            'indice' => $indice,
            'matricula' => $matricula,
            'uid' => $uid,
            'importe' => round( $importe, 2),
            'transaccion' => $transaccion,
            'mensaje' => $mensaje,
        ];
    }

    private function respuestaImportacion(array $resultado, ?string $reporte) {
        $totalRegistros = (int) ( $resultado['totalRegistros'] ?? 0 );
        $guardados = (int) ($resultado['guardados'] ?? 0 );
        $rechazados = $resultado['rechazados'] ?? [];
        $importeGuardado = round((float) ($resultado['importeGuardado'] ?? 0), 2 );
        $importeTotal = round((float) ($resultado['importeTotal'] ?? 0), 2);
        $mensaje = sprintf(
            'Registros guardados (%d de %d) con un importe total de ($%s de $%s)',
            $guardados,  $totalRegistros, number_format($importeGuardado,2, '.', ',' ), number_format(
                $importeTotal, 2, '.', ',' )
        );
        $urlReporte = $reporte
            ? 'https://reportes.siaweb.com.mx/storage/app/public/'
                .basename($reporte)
            : null;

        return response()->json([
            'message' => $urlReporte,
            'data' => [
                'resumen' => $mensaje,
                'totalRegistros' => $totalRegistros,
                'guardados' => $guardados,
                'rechazados' => count($rechazados),
                'importeGuardado' => $importeGuardado,
                'importeTotal' => $importeTotal,
                'tipoOrigen' => $resultado['tipoOrigen'] ?? 'ARCHIVO',
                'identificadorOrigen' =>
                    $resultado['identificadorOrigen']
                    ?? null,
                'reporteRechazados' => $urlReporte,
            ],
            'error' => null,
            'status' => 200,
        ], 200);
    }

    public function obtenerSaldosPorServicio(int $uid, int $secuencia, int $idPeriodo) {
        $saldos = DB::table('edocta as edo')
        ->join('alumno as al', function ($join) {
            $join->on('al.uid', '=', 'edo.uid')
                ->on('al.secuencia', '=', 'edo.secuencia');
        })
        ->join('servicio as ser','ser.idServicio', '=', 'edo.idServicio')
        ->leftJoin('ordenCobroServicio as ocs', function ($join) {
            $join->on('ocs.idNivel', '=', 'al.idNivel')
                ->on('ocs.idServicio', '=', 'edo.idServicio');
        })
        ->where('edo.uid', $uid)
        ->where('edo.secuencia', $secuencia)
        ->where('edo.idPeriodo', $idPeriodo)
        ->select([
                    'edo.uid',
                    'edo.secuencia',
                    'edo.idPeriodo',
                    'edo.idServicio',
                    'ser.descripcion as servicio',
                    'ocs.orden as ordenCobro',
                    'edo.referencia',
                    'edo.parcialidad',
            DB::raw(" SUM( CASE WHEN edo.tipomovto = 'C'
                        THEN edo.importe
                        ELSE 0
                    END
                ) AS cargos
            "),
            DB::raw(" SUM( CASE WHEN edo.tipomovto = 'A'
                        THEN edo.importe
                        ELSE 0
                    END
                ) AS abonos
            "),
            DB::raw(" SUM(
                      CASE
                        WHEN edo.tipomovto = 'C'
                        THEN edo.importe
                        WHEN edo.tipomovto = 'A'
                        THEN -edo.importe
                        ELSE 0
                    END
                ) AS saldo
            "),
        ])
        ->groupBy([
            'edo.uid', 'edo.secuencia', 'edo.idPeriodo','edo.idServicio', 'ser.descripcion',
            'ocs.orden','edo.referencia', 'edo.parcialidad',])
        ->havingRaw("
            SUM(
                CASE
                    WHEN edo.tipomovto = 'C'
                    THEN edo.importe
                    WHEN edo.tipomovto = 'A'
                    THEN -edo.importe
                    ELSE 0
                END
            ) > 0
        ")
        ->orderByRaw( 'COALESCE(ocs.orden, 999999) ASC' )
        ->orderByRaw( 'COALESCE(edo.parcialidad, 999999) ASC' )
        ->orderBy('edo.referencia')
        ->orderBy('edo.idServicio')
        ->get();

        return response()->json([
            'message' => 'Saldos pendientes por servicio',
            'data' => $saldos,
            'error' => null,
            'status' => 200,
        ], 200);
    }

}
