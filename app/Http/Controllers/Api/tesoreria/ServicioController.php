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
            'cta.importe as monto'
        ])
        ->selectRaw('IFNULL(s.cargoAutomatico, 0) AS cargoAut')
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
                ->whereColumn('cta.idPeriodo', 'per.idPeriodo')
                ->where('cta.tipomovto', '=', 'C')
                ->whereColumn('cta.secuencia', 'al.secuencia');
        })
        ->where(function ($query) {
            $query->whereColumn('ct.idServicioColegiatura', 'sc.idServicio')
                  ->orWhereColumn('ct.idServicioInscripcion', 'sc.idServicio');
        });

    $query2 = DB::table('configuracionTesoreria as ct')
        ->distinct()
        ->select([
            'niv.idNivel',
            'niv.descripcion as nivel',
            's.descripcion as servicio',
            's.efectivo',
            's.tarjeta',
            'per.idPeriodo',
            's.idServicio',
            'sc.monto'            
        ])
        ->selectRaw('IFNULL(s.cargoAutomatico, 0) AS cargoAut')
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
        ->join('servicio as s', function ($join) {
            $join->on('s.idServicio', '=', 'sc.idServicio')
                ->whereColumn('ct.idServicioColegiatura', '<>', 'sc.idServicio')
                ->whereColumn('ct.idServicioInscripcion', '<>', 'sc.idServicio');
        });

    return $query1->unionAll($query2)->get();
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