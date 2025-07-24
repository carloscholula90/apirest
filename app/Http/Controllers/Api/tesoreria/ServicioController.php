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
    public function index()
    {
         return DB::table('serviciosPeriodo as sp')
                            ->select(
                                    'niv.idNivel',
                                    'niv.descripcion as nivel',
                                    's.descripcion as servicio',
                                    's.efectivo',
                                    's.tarjeta',
                                    'per.idPeriodo',
                                    'sp.monto')
                                ->join('nivel as niv', 'niv.idNivel', '=', 'sp.idNivel')
                                ->join('servicio as s', 's.idServicio', '=', 'sp.idServicio')
                                ->join('periodo as per', function ($join) {
                                            $join->on('per.idNivel', '=', 'sp.idNivel')
                                                ->on('per.idPeriodo', '=', 'sp.idPeriodo');
                                })    
                                ->where('per.activo',1)           
                                ->get();
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