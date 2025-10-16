<?php

namespace App\Http\Controllers\Api\tesoreria;  
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ServicioController extends Controller
{
     /**
     * Display a listing of the resource.
     */
 public function index($uid, $secuencia)
{
    // Validación básica de parámetros
    if (!is_numeric($uid) || !is_numeric($secuencia)) 
        abort(400, 'Parámetros inválidos');
    
    // Consulta 1: Servicios de inscripción (todos los registros)
    $query1 = DB::table('configuracionTesoreria as ct')
                ->distinct()
                ->select([
                    'niv.idNivel',
                    'niv.descripcion as nivel',
                    's.descripcion as servicio',
                    's.efectivo',
                    's.tarjeta',
                    'per.idPeriodo',
                    's.idServicio',
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
                $join->on('ctaA.idServicio', '=', 's.idServicio')
                     ->on('ctaA.idPeriodo', '=', 'per.idPeriodo')
                     ->on('ctaA.uid', '=', 'cta.uid')
                     ->on('ctaA.secuencia', '=', 'al.secuencia')
                     ->where('ctaA.tipomovto', '=', 'A');
            })
            ->whereColumn('ct.idServicioInscripcion', 'sc.idServicio')
            ->whereRaw('cta.importe - IFNULL(ctaA.importe, 0) > 0');
    
    // Consulta 2: Primer colegiatura pendiente (una sola parcialidad mínima)
    $query2 = DB::table('configuracionTesoreria as ct')
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
            $join->on('ctaA.idServicio', '=', 's.idServicio')
                ->on('ctaA.parcialidad', '=', 'cta.parcialidad')
                ->where('ctaA.uid', '=', $uid)
                ->where('ctaA.tipomovto', '=', 'A')
                ->where('ctaA.secuencia', '=', $secuencia)
                ->whereColumn('ctaA.idPeriodo', 'per.idPeriodo');
        })
        ->leftJoin('edocta as cargos', function ($join) use ($uid, $secuencia) {
            $join->on('cargos.idServicio', '=', 'ct.idServicioRecargo')
                ->on('cargos.parcialidad', '=', 'cta.parcialidad')
                ->where('cargos.uid', '=', $uid)
                ->where('cargos.tipomovto', '=', 'C')
                ->where('cargos.secuencia', '=', $secuencia)
                ->whereColumn('cargos.idPeriodo', 'per.idPeriodo');
        })
        ->leftJoin('servicio as r', 'r.idServicio', '=', 'cargos.idServicio')
        ->whereColumn('ct.idServicioColegiatura', 'sc.idServicio')
        ->whereRaw('cta.importe - IFNULL(ctaA.importe, 0) > 0')        
        ->whereRaw('cta.parcialidad = (
                SELECT MIN(ctaSub.parcialidad)
                FROM edocta AS ctaSub
                LEFT JOIN edocta AS abonos
                    ON abonos.idServicio = ctaSub.idServicio
                    AND abonos.uid = ctaSub.uid
                    AND abonos.idPeriodo = ctaSub.idPeriodo
                    AND abonos.tipomovto = "A"
                    AND abonos.secuencia = ctaSub.secuencia
                    AND abonos.parcialidad = ctaSub.parcialidad
                WHERE ctaSub.uid = ?
                    AND ctaSub.idServicio = cta.idServicio
                    AND ctaSub.idPeriodo = cta.idPeriodo
                    AND ctaSub.tipomovto = "C"
                    AND abonos.uid IS NULL
            )', [$uid])
            ->select([
                'niv.idNivel',
                'niv.descripcion as nivel',
                DB::raw("CONCAT(s.descripcion, CASE WHEN r.descripcion IS NOT NULL THEN ' + ' ELSE '' END, IFNULL(r.descripcion, '')) AS servicio"),
                's.efectivo',
                's.tarjeta',
                'per.idPeriodo',
                'cta.parcialidad',
                's.idServicio',
                DB::raw('(cta.importe - IFNULL(ctaA.importe, 0) + IFNULL(cargos.importe, 0)) AS monto'),
                DB::raw('IFNULL(s.cargoAutomatico, 0) AS cargoAut')
            ])
            ->first(); // solo un registro de colegiatura pendiente

    // Ejecutar ambas y combinar
    $data1 = $query1->get();
    $data2 = $query2 ? collect([$query2]) : collect();

    if ($data1->isNotEmpty()) 
        return $data1;
    else return $data2;
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
    
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
  }