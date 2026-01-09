<?php

namespace App\Http\Controllers\Api\tesoreria;  
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class ServicioController extends Controller
{
    

private function obtenerPendientes($uid, $secuencia){

    return DB::table('configuracionTesoreria as ct')
    ->join('alumno as al', function ($join) use ($uid, $secuencia) {
        $join->on('ct.idNivel', '=', 'al.idNivel')
             ->where('al.uid', '=', $uid)
             ->where('al.matricula', '=', $secuencia); 
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
    ->join('edocta as cta', function ($join) use ($uid) {
        $join->on('cta.idServicio', '=', 's.idServicio')
             ->on('cta.secuencia', '=', 'al.secuencia')
             ->where('cta.uid', '=', $uid)
             ->where('cta.tipomovto', '=', 'C')
             ->whereColumn('cta.idPeriodo', 'per.idPeriodo');
    })
    ->leftJoin('edocta as cargos', function ($join) use ($uid) {
        $join->on('cargos.idServicio', '=', 'ct.idServicioRecargo')
             ->on('cargos.parcialidad', '=', 'cta.parcialidad')
             ->on('cargos.secuencia', '=', 'al.secuencia')
             ->where('cargos.uid', '=', $uid)
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
        $join->on('ctaR.parcialidad', '=', 'cargos.parcialidad') 
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
        'niv.idNivel',
        'niv.descripcion as nivel',
        DB::raw("
            CONCAT(
                s.descripcion,
                CASE 
                    WHEN IFNULL(cargos.importe - IFNULL(ctaR.importe, 0), 0) > 0 
                    THEN ' + ' ELSE '' 
                END,
                CASE 
                    WHEN IFNULL(cargos.importe - IFNULL(ctaR.importe, 0), 0) > 0 
                    THEN IFNULL(r.descripcion, '') ELSE '' 
                END
            ) AS servicio
        "),
        's.efectivo',
        's.tarjeta',
        'per.idPeriodo',
        'cta.uid',
        'cta.consecutivo',
        'cta.secuencia',
        'cta.parcialidad',
        's.idServicio',
        's.tipoEdoCta',
        DB::raw('
            (cta.importe - IFNULL(ctaA.importe, 0) 
            + IFNULL(cargos.importe - IFNULL(ctaR.importe, 0), 0)) AS monto
        '),
        DB::raw('IFNULL(s.cargoAutomatico, 0) AS cargoAut')
    ])
    ->get(); 
}

private function obtenerPendientesPorPagar($uid, $secuencia){
   return DB::table('configuracionTesoreria as ct')
    ->join('alumno as al', function ($join) use ($uid, $secuencia) {
        $join->on('ct.idNivel', '=', 'al.idNivel')
             ->where('al.uid', '=', $uid)
             ->where('al.matricula', '=', $secuencia);
    })
    ->join('periodo as per', function ($join) {
        $join->on('per.idNivel', '=', 'al.idNivel')
             ->where('per.activo', '=', 1);
    })
    ->join('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
    ->join('edocta as cta', function ($join) use ($uid) {
        $join->on('cta.secuencia', '=', 'al.secuencia')
             ->where('cta.uid', '=', $uid)
             ->where('cta.tipomovto', '=', 'C')
             ->whereColumn('cta.idPeriodo', 'per.idPeriodo');
    })
    ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
    ->leftJoin('edocta as ctaA', function ($join) use ($uid) {
        $join->on('ctaA.parcialidad', '=', 'cta.parcialidad')
             ->where('ctaA.uid', '=', $uid)
             ->where('ctaA.tipomovto', '=', 'A')
             ->whereColumn('ctaA.idPeriodo', 'per.idPeriodo')
             ->whereColumn('ctaA.referencia', 'cta.referencia');
    })
    ->where(function ($q) {
        $q->whereColumn('ct.idServicioColegiatura', 's.idServicio')
          ->orWhereColumn('ct.idServicioRecargo', 's.idServicio');
    })
    ->whereRaw('(cta.importe - IFNULL(ctaA.importe, 0)) > 0')
    ->orderBy('cta.parcialidad')
    ->select([
        'niv.idNivel',
        'niv.descripcion as nivel',
        DB::raw("
                CONCAT(
                    s.descripcion, ' ',
                    CASE CONVERT(SUBSTRING(cta.referencia, 4), UNSIGNED)
                        WHEN 1 THEN 'ENE'
                        WHEN 2 THEN 'FEB'
                        WHEN 3 THEN 'MAR'
                        WHEN 4 THEN 'ABR'
                        WHEN 5 THEN 'MAY'
                        WHEN 6 THEN 'JUN'
                        WHEN 7 THEN 'JUL'
                        WHEN 8 THEN 'AGO'
                        WHEN 9 THEN 'SEP'
                        WHEN 10 THEN 'OCT'
                        WHEN 11 THEN 'NOV'
                        WHEN 12 THEN 'DIC'
                        ELSE ''
                    END
                ) AS servicio
            "),

        's.efectivo',
        's.tarjeta',
        'per.idPeriodo',
        'cta.uid',
        'cta.consecutivo',
        'cta.secuencia',
        'cta.parcialidad',
        's.idServicio',
        's.tipoEdoCta',
        DB::raw('(cta.importe - IFNULL(ctaA.importe, 0)) AS monto'),
        DB::raw('IFNULL(s.cargoAutomatico, 0) AS cargoAut'),
    ])
    ->get();

}

public function index($uid, $secuencia, $tipoEdoCta)
{
    // Validación básica de parámetros
    if (!is_numeric($uid) || !is_numeric($secuencia) || !is_numeric($tipoEdoCta)) {
        abort(400, 'Parámetros inválidos');
    }

    // Consulta base para inscripción (tipoEdoCta = 1)
    if ($tipoEdoCta == 1) {

        $inscripcion = DB::table('configuracionTesoreria as ct')
                                ->join('alumno as al', function ($join) use ($uid, $secuencia) {
                                            $join->on('ct.idNivel', '=', 'al.idNivel')
                                                ->where('al.uid', '=', $uid)
                                                ->where('al.matricula', '=', $secuencia);
                                })
                                ->join('periodo as per', function ($join) {
                                    $join->on('per.idNivel', '=', 'al.idNivel')
                                        ->where('per.activo', 1);
                                })
                                ->join('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
                                ->join('servicioCarrera as sc', function ($join) {
                                    $join->on('sc.idNivel', '=', 'ct.idNivel')
                                        ->on('sc.idPeriodo', '=', 'per.idPeriodo');
                                })
                                ->join('servicio as s', 's.idServicio', '=', 'sc.idServicio')
                                ->join('edocta as cta', function ($join) use ($uid){
                                    $join->on('cta.idServicio', '=', 's.idServicio')
                                        ->where('cta.uid', $uid)
                                        ->whereColumn('cta.secuencia', 'al.secuencia')
                                        ->where('cta.tipomovto', 'C')
                                        ->whereColumn('cta.idPeriodo', 'per.idPeriodo');
                                })
                                ->leftJoin('edocta as ctaA', function ($join) {
                                    $join->on('ctaA.referencia', '=', 'cta.referencia')
                                        ->on('ctaA.idPeriodo', '=', 'per.idPeriodo')
                                        ->on('ctaA.uid', '=', 'cta.uid')
                                        ->on('ctaA.secuencia', '=', 'al.secuencia')
                                        ->where('ctaA.tipomovto', 'A');
                                })
                                ->whereColumn('ct.idServicioInscripcion', 'sc.idServicio')
                                ->groupBy(
                                    'niv.idNivel',
                                    'niv.descripcion',
                                    's.descripcion',
                                    's.efectivo',
                                    's.tarjeta',
                                    'per.idPeriodo',
                                    's.idServicio',
                                    's.tipoEdoCta',
                                    'cta.importe',
                                    's.cargoAutomatico'
                                )
                                ->havingRaw('monto > 0')
                                ->select([
                                    'niv.idNivel',
                                    DB::raw('niv.descripcion AS nivel'),
                                    DB::raw('s.descripcion AS servicio'),
                                    's.efectivo',
                                    's.tarjeta',
                                    'per.idPeriodo',
                                    's.idServicio',
                                    's.tipoEdoCta',
                                    DB::raw('cta.importe - SUM(IFNULL(ctaA.importe, 0)) AS monto'),
                                    DB::raw('IFNULL(s.cargoAutomatico, 0) AS cargoAut'),
                                ])
                                ->get();

        // Si hay datos de inscripción pendientes, devolverlos; si no, usar pendientes generales
        if ($inscripcion->isNotEmpty()) {
            return $inscripcion;
        }

        return $this->obtenerPendientes($uid, $secuencia)->first();
    }

    // Consulta general para otros tipos de EdoCta
   return DB::table('servicio as s')
    ->join('alumno as al', function ($join) use ($uid, $secuencia) {
        $join->where('al.uid', '=', $uid)
             ->where('al.matricula', '=', $secuencia);
    })
    ->join('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
    ->join('periodo as per', function ($join) {
        $join->on('per.idNivel', '=', 'al.idNivel')
             ->where('per.activo', '=', 1);
    })
    ->leftJoin('servicioXPeriodo as sxp', function ($join) {
        $join->on('sxp.idNivel', '=', 'al.idNivel')
             ->on('sxp.idPeriodo', '=', 'per.idPeriodo')
             ->on('sxp.idServicio', '=', 's.idServicio');
    })
    ->where('s.tipoEdoCta', '=', 2)
    ->select([
        'niv.idNivel',
        'niv.descripcion as nivel',
        's.descripcion as servicio',
        's.efectivo',
        's.tarjeta',
        'per.idPeriodo',
        's.idServicio',
        's.tipoEdoCta',
        DB::raw('IFNULL(sxp.monto, 0) AS monto'),
        DB::raw('IFNULL(s.cargoAutomatico, 0) AS cargoAut'),
    ])
    ->get();

}

public function condonacion($uid, $secuencia, $tipoEdoCta){
    // Validación básica de parámetros
    if (!is_numeric($uid) || !is_numeric($secuencia)|| !is_numeric($tipoEdoCta)) 
        abort(400, 'Parámetros inválidos');
    
    if ($tipoEdoCta == 1) {
    // Consulta 1: Servicios de inscripción (todos los registros)
    $query1 = DB::table('configuracionTesoreria as ct')
                ->distinct()
                ->select([
                    'niv.idNivel',
                    'niv.descripcion as nivel',
                    DB::raw("
                            CONCAT(
                                s.descripcion, ' ',
                                CASE CONVERT(SUBSTRING(cta.referencia, 4), UNSIGNED)
                                    WHEN 1 THEN 'ENE'
                                    WHEN 2 THEN 'FEB'
                                    WHEN 3 THEN 'MAR'
                                    WHEN 4 THEN 'ABR'
                                    WHEN 5 THEN 'MAY'
                                    WHEN 6 THEN 'JUN'
                                    WHEN 7 THEN 'JUL'
                                    WHEN 8 THEN 'AGO'
                                    WHEN 9 THEN 'SEP'
                                    WHEN 10 THEN 'OCT'
                                    WHEN 11 THEN 'NOV'
                                    WHEN 12 THEN 'DIC'
                                    ELSE ''
                                END
                            ) AS servicio
                        "),

                    's.efectivo',
                    's.tarjeta',
                    'per.idPeriodo',
                    'cta.uid',
                    'cta.consecutivo',
                    'cta.secuencia',
                    's.idServicio',
                    's.tipoEdoCta',
                    DB::raw('cta.importe -IFNULL(ctaA.importe, 0) as monto'),
                    DB::raw('IFNULL(s.cargoAutomatico, 0) AS cargoAut')
                ])
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
                    ->where('cta.tipomovto', '=', 'C')
                    ->where('cta.secuencia', '=', $secuencia)
                    ->whereColumn('cta.idPeriodo', 'per.idPeriodo');
            })
            ->leftJoin('edocta as ctaA', function ($join) use ($uid, $secuencia) {
                $join->on('ctaA.referencia', '=', 'cta.referencia')
                     ->on('ctaA.idPeriodo', '=', 'per.idPeriodo')
                     ->on('ctaA.uid', '=', 'cta.uid')
                     ->on('ctaA.secuencia', '=', 'al.secuencia')
                     ->where('ctaA.tipomovto', '=', 'A');
            })
            ->whereColumn('ct.idServicioInscripcion', 'sc.idServicio')
            ->whereRaw('cta.importe - IFNULL(ctaA.importe, 0) > 0');
    
        $data1 = $query1->get();
        $data2 = $this->obtenerPendientesPorPagar($uid, $secuencia);

        return $data1->merge($data2);

    }else{

        return DB::table('configuracionTesoreria as ct')
            ->join('alumno as al', function ($join) use ($uid, $secuencia) {
                $join->on('ct.idNivel', '=', 'al.idNivel')
                    ->where('al.uid', '=', $uid)
                    ->where('al.matricula', '=', $secuencia);
            })
            ->join('periodo as per', function ($join) {
                $join->on('per.idNivel', '=', 'al.idNivel')
                    ->where('per.activo', '=', 1);
            })
            ->join('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
            ->join('edocta as cta', function ($join) use ($uid) {
                $join->on('cta.secuencia', '=', 'al.secuencia')
                    ->where('cta.uid', '=', $uid)
                    ->where('cta.tipomovto', '=', 'C')
                    ->whereColumn('cta.idPeriodo', 'per.idPeriodo');
            })
            ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
            ->leftJoin('edocta as ctaA', function ($join) use ($uid) {
                $join->on('ctaA.parcialidad', '=', 'cta.parcialidad')
                    ->where('ctaA.uid', '=', $uid)
                    ->where('ctaA.tipomovto', '=', 'A')
                    ->whereColumn('ctaA.idPeriodo', 'per.idPeriodo')
                    ->whereColumn('ctaA.referencia', 'cta.referencia');
            })
            ->where('tipoEdoCta', '=', $tipoEdoCta)
            ->whereRaw('(cta.importe - IFNULL(ctaA.importe, 0)) > 0')
            ->orderBy('cta.parcialidad')
            ->select([     
                'niv.idNivel',
                'niv.descripcion as nivel',
                's.descripcion as servicio',
                's.efectivo',
                'cta.uid',
                'cta.consecutivo',
                'cta.secuencia',
                's.tarjeta',
                'per.idPeriodo',
                'cta.parcialidad',
                's.idServicio',
                's.tipoEdoCta',
                DB::raw('(cta.importe - IFNULL(ctaA.importe, 0)) AS monto'),
                DB::raw('IFNULL(s.cargoAutomatico, 0) AS cargoAut'),
            ])
            ->get();
    }
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