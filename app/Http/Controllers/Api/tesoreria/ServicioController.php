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
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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