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

    public function index($uid,$idPeriodo,$matricula,$tipoEdoCta)
    {
        $resultados = $this->obtenerEstadoCuenta($uid,$idPeriodo,$matricula,$tipoEdoCta);
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
                                            THEN
                                                CASE CONVERT(SUBSTRING(edo.referencia, 4), UNSIGNED)
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
            $query->where('s.tipoEdoCta', $tipoEdoCta);
            // Ordenar y obtener resultados
            $edocuenta = $query->orderByDesc('inscripcion.idServicioInscripcion')
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
                'ct.idServicioNotaCredito'
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
        $fecha = Carbon::now('America/Mexico_City')->format('Y-m-d');

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

    private function calcularImporteInscripcion(string $uid, string $secuencia, int $idServicioInscripcion){
    // Obtenemos el importe de pagos y notas de crédito
        $importe = DB::table('edocta as edo')
            ->selectRaw("IFNULL(SUM(CASE 
                            WHEN edo.tipomovto = 'C' THEN edo.importe
                            ELSE -1 * edo.importe
                        END), 0) - IFNULL(notaCred.importeCred, 0) AS importe")
            ->join('configuracionTesoreria as ct', 'edo.idServicio', '=', 'ct.idServicioInscripcion')
            ->join('alumno as al', function ($join) use ($uid, $secuencia) {
                $join->on('al.uid', '=', 'edo.uid')
                    ->on('al.secuencia', '=', 'edo.secuencia')
                    ->where('al.uid', '=', $uid)
                    ->where('al.secuencia', '=', $secuencia);
            })
            ->join('periodo as per', function ($join) {
                $join->on('per.idNivel', '=', 'al.idNivel')
                    ->on('edo.idPeriodo', '=', 'per.idPeriodo')
                    ->where('per.activo', '=', 1);
            })
            ->leftJoin(DB::raw("(
                SELECT edo.referencia AS referenciaNotaCred,
                    IFNULL(SUM(edo.importe), 0) AS importeCred
                FROM edocta AS edo
                INNER JOIN configuracionTesoreria AS ct
                    ON edo.idServicio = ct.idServicioNotaCredito
                INNER JOIN alumno AS al 
                    ON al.uid = edo.uid AND al.secuencia = edo.secuencia
                INNER JOIN periodo AS per 
                    ON per.idNivel = al.idNivel AND edo.idPeriodo = per.idPeriodo AND per.activo = 1
                WHERE al.uid = {$uid}
                AND al.secuencia = {$secuencia}
                GROUP BY edo.referencia
            ) AS notaCred"), 'edo.referencia', '=', 'notaCred.referenciaNotaCred')
            ->where('ct.idServicioInscripcion', $idServicioInscripcion)
            ->value('importe'); // Devuelve solo el valor
        return $importe ?? 0;
    }


    private function procesarMovimiento($movimiento, $servicios, $uid, $secuencia, $idPeriodo, $uidcajero, $fecha, $folio){

        $consecutivo = $this->siguienteConsecutivo($uid, $secuencia, $movimiento['idServicio']);

        if ($movimiento['idServicio'] == $servicios->idServicioInscripcion) {

            $importeInscripcion = $this->calcularImporteInscripcion($uid, $secuencia, $servicios->idServicioInscripcion);
            $pagoInscripcion = min($movimiento['importe'], $importeInscripcion);
            $restante = $movimiento['importe'] - $pagoInscripcion;

            $this->crearMovimiento(array_merge($movimiento, ['uid' => $uid,
                                                            'secuencia' => $secuencia,
                                                            'consecutivo' => $consecutivo,
                                                            'importe' => $pagoInscripcion,
                                                            'idPeriodo' => $idPeriodo,
                                                            'fechaMovto' => $fecha,
                                                            'folio' => $folio,
                                                            'uidcajero' => $uidcajero
            ]));

            if ($restante > 0) 
                $this->prorratearColegiaturaYCargos($uid, $secuencia, $idPeriodo, $servicios, $movimiento, $restante, $fecha, $folio, $uidcajero);

        } 
        else if($movimiento['idServicio'] == $servicios->idServicioColegiatura)
            $this->prorratearColegiaturaYCargos($uid, $secuencia, $idPeriodo, $servicios, $movimiento, $movimiento['importe'], $fecha, $folio, $uidcajero,0);
        else if($movimiento['idServicio'] == $servicios->idServicioNotaCredito) 
               $this->prorratearColegiaturaYCargos($uid, $secuencia, $idPeriodo, $servicios, $movimiento, $movimiento['importe'], $fecha, $folio, $uidcajero, $servicios->idServicioNotaCredito);
         else $this->procesarOtrosServicios($movimiento, $uid, $secuencia, $idPeriodo, $fecha, $folio, $uidcajero, $servicios);
    }

    private function obtenerPendientes( $uid,  $secuencia){

    return DB::table('configuracionTesoreria as ct')
        ->join('alumno as al', function ($join) use ($uid, $secuencia) {
            $join->on('ct.idNivel', '=', 'al.idNivel')
                 ->where('al.uid', '=', $uid)
                 ->where('al.secuencia', '=', $secuencia);
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
        ->join('edocta as cta', function ($join) use ($uid, $secuencia) {
            $join->on('cta.idServicio', '=', 's.idServicio')
                 ->where('cta.uid', '=', $uid)
                 ->where('cta.secuencia', '=', $secuencia)
                 ->where('cta.tipomovto', '=', 'C')
                 ->whereColumn('cta.idPeriodo', 'per.idPeriodo');
        })
        ->leftJoin('edocta as cargos', function ($join) use ($uid, $secuencia) {
            $join->on('cargos.idServicio', '=', 'ct.idServicioRecargo')
                 ->on('cargos.parcialidad', '=', 'cta.parcialidad')
                 ->where('cargos.uid', '=', $uid)
                 ->where('cargos.secuencia', '=', $secuencia)
                 ->where('cargos.tipomovto', '=', 'C')
                 ->whereColumn('cargos.idPeriodo', 'per.idPeriodo');
        })
        ->leftJoin('edocta as ctaA', function ($join) use ($uid) {
            $join->on('ctaA.parcialidad', '=', 'cta.parcialidad')
                 ->where('ctaA.uid', '=', $uid)
                 ->where('ctaA.tipomovto', '=', 'A')
                 ->whereColumn('ctaA.idPeriodo', 'per.idPeriodo')
                 ->whereColumn('ctaA.referencia', 'cta.referencia');
        })
        ->leftJoin('edocta as ctaR', function ($join) use ($uid) {
            $join->on('ctaR.parcialidad', '=', 'cta.parcialidad')
                 ->where('ctaR.uid', '=', $uid)
                 ->where('ctaR.tipomovto', '=', 'A')
                 ->whereColumn('ctaR.idPeriodo', 'per.idPeriodo')
                 ->whereColumn('ctaR.referencia', 'cargos.referencia');
        })
        ->leftJoin('servicio as r', 'r.idServicio', '=', 'cargos.idServicio')
        ->whereColumn('ct.idServicioColegiatura', 'sc.idServicio')
        ->whereRaw('(cta.importe - IFNULL(ctaA.importe, 0)) > 0')
        ->orderBy('cta.parcialidad')
        ->select([
            'cta.parcialidad',
            'cta.referencia as referenciaCole',
            'cargos.referencia as referenciaCargos',
            DB::raw('(cta.importe - IFNULL(ctaA.importe, 0)) AS monto'),
            DB::raw('cargos.idServicio AS idServicioCargo'),
            'ct.idServicioColegiatura',
            DB::raw('(cargos.importe - IFNULL(ctaR.importe, 0)) AS cargos'),
        ])
        ->get();
    }


    private function prorratearColegiaturaYCargos($uid, $secuencia, $idPeriodo, $servicios, $movimiento, $importeRestante, $fecha, $folio, $uidcajero,$idServicioNotaCredito){
        $pendientes = $this->obtenerPendientes($uid, $secuencia, $idPeriodo, $servicios);

        foreach ($pendientes as $registro) {
            // 1️⃣ Pagar recargos primero
            if ($registro->cargos > 0 && $importeRestante > 0) {
                $pago = min($importeRestante, $registro->cargos);
                $idServicioNota = $idServicioNotaCredito >0?$idServicioNotaCredito:$registro->idServicioCargo;
                $this->crearMovimiento(['uid' => $uid,
                                        'secuencia' => $secuencia,
                                        'idServicio' => $idServicioNota,
                                        'consecutivo' => $this->siguienteConsecutivo($uid, $secuencia, $registro->idServicioCargo),
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
                
                $pago = min($importeRestante, $registro->monto);
                $this->crearMovimiento([
                                        'uid' => $uid,
                                        'secuencia' => $secuencia,
                                        'idServicio' => $idServicioNota,
                                        'consecutivo' => $this->siguienteConsecutivo($uid, $secuencia, $registro->idServicioColegiatura),
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
                'consecutivo' => $this->siguienteConsecutivo($uid, $secuencia, $movimiento['idServicio']),
                'importe' => $importeRestante,
                'idPeriodo' => $idPeriodo,
                'fechaMovto' => $fecha,
                'folio' => $folio,
                'uidcajero' => $uidcajero
            ]));
        }
    }

    private function procesarOtrosServicios($movimiento, $uid, $secuencia, $idPeriodo, $fecha, $folio, $uidcajero, $servicios){
        
        $consecutivo = $this->siguienteConsecutivo($uid, $secuencia, $movimiento['idServicio']);
        $this->crearMovimiento(array_merge($movimiento, ['uid' => $uid,
                                                        'secuencia' => $secuencia,
                                                        'consecutivo' => $consecutivo,
                                                        'idPeriodo' => $idPeriodo,
                                                        'fechaMovto' => $fecha,
                                                        'folio' => $folio,
                                                        'uidcajero' => $uidcajero
        ]));
    }



    public function guardarMovtos(Request $request){
    
    DB::beginTransaction();
    try {

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

            $fecha = Carbon::createFromFormat('d-m-Y', $mov['dia'])->format('Y-m-d');
            $transaccion = $mov['transaccion'];
            $abono = floatval($mov['abono']);

            $matricula = (int) substr($mov['concepto'], 0, 7);
            $servicio = (int) substr($mov['concepto'], 8, 3);
        
            $result = DB::table('periodo')
                            ->join('alumno', 'periodo.idNivel', '=', 'alumno.idNivel')
                            ->leftJoin('edocta', function ($join) use ($transaccion) {
                                $join->on('edocta.idPeriodo', '=', 'periodo.idPeriodo')
                                    ->where('edocta.transaccion', '=',$transaccion);
                            })
                            ->where('periodo.activo', 1)
                            ->where('alumno.matricula', $matricula)
                            ->select('alumno.uid', 'periodo.idPeriodo','edocta.transaccion')
                            ->first();

            if (!$result) {
                $registrosMal[] = [
                    'matricula' => $matricula,
                    'mensaje'   => 'No existe la matricula en el sistema',
                    'importe'   => $abono
                ];
                continue;
            }

            $uid = $result->uid;
            $idPeriodo = $result->idPeriodo;
          
            if (isset($result->transExistente)) {
                     $registrosMal[] = [
                            'matricula' => $matricula,
                            'mensaje'   => 'La transaccion ya se encuentra dada de alta en el periodo',
                            'importe'   => $abono
                            ];
                continue;
            }

            while ($abono > 0) {
                    $datosEdo = $result = DB::table('edocta as ec')
                                ->select(
                                    's.idServicio',
                                    'ec.importe',
                                    'ec.parcialidad',
                                    'ec.idPeriodo',
                                    'ec.secuencia',
                                    'ec.uid',
                                    DB::raw("CONCAT('000000', DATE_FORMAT(ec.FechaPago, '%m')) as referencia")
                                )
                                ->join('servicio as s', 's.idServicio', '=', 'ec.idServicio')
                                ->join('configuracionTesoreria as ct', function ($join) {
                                    $join->on('ct.idServicioColegiatura', '=', 'ec.idServicio')
                                        ->orOn('ct.idServicioRecargo', '=', 'ec.idServicio')
                                        ->orOn('ec.idServicio', '=', 'ct.idServicioInscripcion');
                                })
                                ->joinSub(
                                    DB::table('edocta as ec2')
                                        ->select(
                                            'ec2.parcialidad as parc',
                                            's2.idServicio',
                                            DB::raw("SUM(CASE WHEN ec2.tipomovto = 'C' 
                                                            THEN ec2.importe 
                                                            ELSE -1 * ec2.importe END) AS total")
                                        )
                                        ->join('servicio as s2', 's2.idServicio', '=', 'ec2.idServicio')
                                        ->join('configuracionTesoreria as ct2', function ($join) {
                                            $join->on('ct2.idServicioColegiatura', '=', 'ec2.idServicio')
                                                ->orOn('ct2.idServicioRecargo', '=', 'ec2.idServicio')
                                                ->orOn('ec2.idServicio', '=', 'ct2.idServicioInscripcion');
                                        })
                                        ->where('ec2.uid', $uid)
                                        ->where('ec2.idPeriodo', $idPeriodo)
                                        ->groupBy('ec2.parcialidad', 's2.idServicio'),'p',

                                    function ($join) {
                                        $join->on('p.parc', '=', 'ec.parcialidad')
                                            ->on('p.idServicio', '=', 's.idServicio')
                                            ->where('p.total', '>', 0);
                                    }

                                )
                                ->where('ec.uid', $uid)
                                ->where('ec.idPeriodo', $idPeriodo)
                                ->where('ec.tipomovto', 'C')
                                ->where('p.total', '>', 0)
                                ->orderBy('ec.parcialidad', 'ASC')
                                ->orderBy('s.descripcion', 'DESC')
                                ->first();   

                // No quedan cargos por pagar
                if (!$datosEdo) {
                    break;
                }

                $importeAplicar = min($abono, $datosEdo->importe);

                $maxConsecutivo = DB::table('edocta')
                    ->where('uid', $uid)
                    ->where('idPeriodo', $idPeriodo)
                    ->max('consecutivo') ?? 0;

                DB::table('edocta')->insert([
                                    'uid' => $uid,
                                    'secuencia' => $datosEdo->secuencia,
                                    'idServicio' => $datosEdo->idServicio,
                                    'consecutivo' => $maxConsecutivo + 1,
                                    'importe' => $importeAplicar,
                                    'idPeriodo' => $datosEdo->idPeriodo,
                                    'fechaMovto' => $fecha,
                                    'tipomovto' => 'A',
                                    'parcialidad' => $datosEdo->parcialidad,
                                    'referencia' => $datosEdo->referencia,
                                    'transaccion' => $transaccion,
                                    'FechaPago' => $fecha,
                                    'idFormaPago' => $mov['idFormaPago']
                ]);
                $abono -= $importeAplicar;

            }
            DB::statement("CALL saldo(?, ?, ?, @vencido, @total)", [$uid, $matricula, $idPeriodo]);
            $saldoResult = DB::select("SELECT @vencido AS vencido, @total AS total");
            $vencido = $saldoResult[0]->vencido ?? 0;
    
            
            if($vencido==0)
                DB::table('bloqueoPersonas')
                    ->where('uid', $uid)
                    ->where('idBloqueo', 1)
                    ->delete();
        }
        DB::commit();

        return response()->json([
            'message' => 'Registros guardados',
            'error'   => $registrosMal,
            'status'  => 200
        ], 200);

    } catch (\Exception $e) {

        DB::rollBack();
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
    }
}

