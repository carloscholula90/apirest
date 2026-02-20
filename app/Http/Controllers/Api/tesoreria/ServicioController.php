<?php

namespace App\Http\Controllers\Api\tesoreria;  
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class ServicioController extends Controller
{


public function index($uid, $matricula, $tipoEdoCta)
{
    // Validación básica de parámetros
    if (!is_numeric($uid) || !is_numeric($matricula) || !is_numeric($tipoEdoCta)) {
        abort(400, 'Parámetros inválidos');
    }

    $data = $this->condonacion($uid,$matricula,$tipoEdoCta);

    if($tipoEdoCta == 2 && isset($data))
         $data = DB::table('servicio as s')
                       ->join('alumno as al', function ($join) use ($uid, $matricula) {
                            $join->where('al.uid', '=', $uid)
                                ->where('al.matricula', '=', $matricula);
                        })
                        ->join('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
                        ->join('periodo as per', function ($join) {
                            $join->on('per.idNivel', '=', 'al.idNivel')
                                ->where('per.activo', 1);
                        })
                        ->join('ciclos as cl', function ($join) {
                            $join->on('cl.uid', '=', 'al.uid')
                                ->on('cl.secuencia', '=', 'al.secuencia')
                                ->on('cl.idPeriodo', '=', 'per.idPeriodo')
                                ->whereRaw('cl.indexCiclo = (
                                        SELECT MIN(c2.indexCiclo)
                                        FROM ciclos c2
                                        WHERE c2.uid = al.uid
                                        AND c2.secuencia = al.secuencia
                                        AND c2.idPeriodo = per.idPeriodo
                                )');
                        })
                        ->join('servicioCarrera as sxp', function ($join) {
                            $join->on('sxp.idNivel', '=', 'al.idNivel')
                                ->on('sxp.idPeriodo', '=', 'per.idPeriodo')
                                ->on('sxp.idServicio', '=', 's.idServicio');
                        })
                        ->join('turno as t', function ($join) {
                            $join->on('t.letra', '=', DB::raw('SUBSTRING(cl.grupo, 3, 1)'));
                        })
                        ->where('s.tipoEdoCta', 2)
                        // (sxp.idTurno = 0 OR sxp.idTurno = t.idTurno)
                        ->where(function ($q) {
                            $q->where('sxp.idTurno', 0)
                            ->orWhereColumn('sxp.idTurno', 't.idTurno');
                        })
                        ->where(function ($q) {
                            $q->whereColumn('sxp.semestre', 'cl.semestre')
                            ->orWhere('sxp.semestre', 0);
                        })
                         ->where(function ($q) {
                            $q->whereColumn('sxp.idCarrera', 'cl.idCarrera')
                            ->orWhere('sxp.idCarrera', 0);
                        })
                        ->select([
                                'niv.idNivel',
                                'niv.descripcion as nivel',
                                's.descripcion as servicio',
                                's.efectivo',
                                's.tarjeta',
                                'per.idPeriodo',
                                's.idServicio',
                                's.tipoEdoCta',
                                DB::raw('IFNULL(sxp.monto, 0) as monto'),
                                DB::raw('IFNULL(s.cargoAutomatico, 0) as cargoAut')
                        ])
                        ->get();
    return $data->first();
}

public function condonacion($uid, $matricula, $tipoEdoCta){
    // Validación básica de parámetros
    if (!is_numeric($uid) || !is_numeric($matricula)|| !is_numeric($tipoEdoCta)) 
        abort(400, 'Parámetros inválidos');
    
    $query = DB::table(DB::raw("(
                SELECT 
                    al.idNivel,
                    niv.descripcion as nivel,
                    s.efectivo,
                    s.tarjeta,
                    per.idPeriodo,
                    s.idServicio,
                    s.tipoEdoCta,
                    cta.uid,
                    al.matricula,
                    cta.parcialidad,
                    cta.secuencia,

                    GROUP_CONCAT(DISTINCT CONCAT(
                        s.descripcion, ' ',
                        CASE 
                            WHEN s.descripcion LIKE '%INSCRIP%' THEN ''
                            ELSE CASE CONVERT(SUBSTRING(cta.referencia, 4), UNSIGNED)
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
                    ) ORDER BY s.descripcion SEPARATOR ' + ') AS servicios,
                     GROUP_CONCAT(DISTINCT CONCAT(
                        s.descripcion, ' ',
                        CASE 
                            WHEN s.descripcion LIKE '%INSCRIP%' THEN ''
                            ELSE CASE CONVERT(SUBSTRING(cta.referencia, 4), UNSIGNED)
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
                    ) ORDER BY s.descripcion SEPARATOR ' + ') AS servicio,

                    SUM(
                        CASE 
                            WHEN cta.tipomovto = 'C' THEN cta.importe
                            WHEN cta.tipomovto = 'A' THEN -cta.importe
                            ELSE 0
                        END
                    ) AS monto,

                    MAX(CASE WHEN cta.tipomovto = 'C' THEN cta.fechaVencimiento END) AS fechaVencimiento,
                    s.cargoAutomatico AS cargoAut,
                    MAX(CASE WHEN cta.tipomovto = 'C' THEN cta.consecutivo END) AS consecutivo
                  
                FROM configuracionTesoreria ct
                INNER JOIN alumno al ON ct.idNivel = al.idNivel
                INNER JOIN periodo per ON per.idNivel = al.idNivel AND per.activo = 1
                INNER JOIN nivel niv ON niv.idNivel = al.idNivel
                INNER JOIN servicio s ON s.tipoEdoCta = 1
                INNER JOIN edocta cta 
                    ON cta.idServicio = s.idServicio
                    AND cta.uid = al.uid
                    AND cta.secuencia = al.secuencia
                    AND cta.idPeriodo = per.idPeriodo
                WHERE al.uid =". $uid.
                " AND al.matricula = ".$matricula.
                " AND s.tipoEdoCta=".$tipoEdoCta.
                " GROUP BY
                    al.idNivel,
                    s.efectivo,
                    s.tarjeta,
                    per.idPeriodo,
                    s.idServicio,
                    s.tipoEdoCta,
                    cta.uid,
                    al.matricula,
                    cta.parcialidad,
                    cta.secuencia,
                    s.cargoAutomatico,
                    niv.descripcion
            ) AS t"))   // 👈 alias obligatorio
            ->leftJoin('configuracionTesoreria as saldoant', function ($join) {
                $join->on('saldoant.idServicioTraspasoSaldos1', '=', 't.idServicio')
                    ->on('saldoant.idNivel', '=', 't.idNivel');
            })

            ->leftJoin('configuracionTesoreria as inscripcion', function ($join) {
                $join->on('inscripcion.idServicioInscripcion', '=', 't.idServicio')
                    ->on('inscripcion.idNivel', '=', 't.idNivel');
            })

            ->leftJoin('configuracionTesoreria as recargo', function ($join) {
                $join->on('recargo.idServicioRecargo', '=', 't.idServicio')
                    ->on('recargo.idNivel', '=', 't.idNivel');
            })

            ->leftJoin('configuracionTesoreria as colegiatura', function ($join) {
                $join->on('colegiatura.idServicioColegiatura', '=', 't.idServicio')
                    ->on('colegiatura.idNivel', '=', 't.idNivel');
            })

            ->where('monto', '>', 0)

            ->orderBy('matricula')
            ->orderByDesc('saldoant.idServicioTraspasoSaldos1')
            ->orderByDesc('inscripcion.idServicioInscripcion')
            ->orderByDesc('recargo.idServicioRecargo')
            ->orderByDesc('colegiatura.idServicioColegiatura')
            ->orderBy('fechaVencimiento')
            ->get();
        return $query;
}

public function store(Request $request){

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Beca::max('idBeca');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $becas = Beca::create([
                            'idBeca' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion)),
                            'aplicaInscripcion' => $request->aplicaInscripcion,
                            'aplicaColegiatura' => $request->aplicaColegiatura,
                            'fechaAlta' => Carbon::now(),
                            'fechaModificacion' => Carbon::now()
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('La Beca ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar la Beca',400,null);
        }

        if (!$becas) 
            return $this->returnEstatus('Error al crear la Beca',500,null); 
        return $this->returnData('becas',$becas,200);   
    }


    public function condonar(Request $request){

        $data = $request->validate(['movimientos' => 'required|array']);
        $fecha = Carbon::now('America/Mexico_City')->format('Y-m-d');
        DB::beginTransaction();

        try {

            foreach ($data['movimientos'] as $movimiento) 
            DB::table('edocta')
                ->where('uid', $movimiento['uid'])
                ->where('secuencia', $movimiento['secuencia'])
                ->where('idServicio', $movimiento['idServicio'])
                ->where('consecutivo', $movimiento['consecutivo'])
                ->update([
                    'importe' => 0,
                    'fechaMovto' => $fecha,
                    'uidcajero' => $movimiento['uidcajero']
                ]);
            DB::commit();
            return $this->returnData('mensaje', 'condonacion exitosa', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->returnEstatus('Error actualizar el registro', 500, $e->getMessage());
        }
    }
    
  }