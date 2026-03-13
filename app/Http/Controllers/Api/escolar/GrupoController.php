<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Grupo;
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GrupoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       $grupos = Grupo::all();
       return $this->returnData('grupos',$grupos,200);
    }
     /**
     * Display a listing of the resource.
     */
    public function show($idNivel,$idPeriodo,$idCarrera)
    {
       $carreraFormatted = str_pad($idCarrera, 2, '0', STR_PAD_LEFT);
       $grupos = DB::table('grupos')
                                ->distinct()  
                                ->select('grupo')
                                ->where('idNivel', $idNivel)
                                ->where('idPeriodo',$idPeriodo)
                                ->where('grupo', 'like', $carreraFormatted.'%')
                                ->get();
       return $this->returnData('grupos',$grupos,200);
    }

    public function cambioGrupo(Request $request){
      $periodo = DB::table('periodo')
                           ->select('idPeriodo')
                           ->join('turno as t', function($join) {
                                $join->on('t.letra', '=', DB::raw(
                                'SUBSTRING(cl.grupo, CASE WHEN LENGTH('.$request->grupo.') = 4 THEN 2 WHEN LENGTH('.$request->grupo.') = 5 THEN 3 ELSE 3 END, 1)'
                                 ));
                            })
                           ->where('activo', 1)
                           ->where('idNivel', $request->idNivel)
                           ->first();
      //Validar el turno de para ver si cambia de costos
      
      foreach ($request->grupos as $grupos){
                  DB::table('ciclos')
                        ->where('idPeriodo', $periodo)
                        ->where('grupo',$grupos->idPeriodo)
                        ->where('secuencia', $grupos->secuencia)
                        ->update(['grupo' => $grupos->newGrupo]);


      }
               
        return $this->returnData('Registros actualizados',null,200);

    }
}
