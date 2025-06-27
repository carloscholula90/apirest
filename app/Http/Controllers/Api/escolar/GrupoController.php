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
}
