<?php

namespace App\Http\Controllers\Api\general;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;    
use Illuminate\Support\Facades\DB;

class ProfesorController extends Controller{    

    public function getPersonasLike($grupoSec){            
        $datos = DB::table('empleado')
                    ->join('persona', 'persona.uid', '=', 'empleado.uid')
                    ->join('grupos', 'grupos.uidProfesor', '=', 'persona.uid')
                    ->select(
                        'grupos.gruposec',
                        'persona.uid',
                        'persona.nombre',
                        'persona.primerApellido',
                        'persona.segundoApellido'
                    )
                    ->where('grupos.gruposec', $grupoSec)
                    ->get();   
    
        //Log::info('Número de personas encontradas: ' . $personas->count());        
        if ($datos->isEmpty()) {
            return $this->returnEstatus('No se encontraron personas.', 200, null);
        }
        return $this->returnData('profesor', $datos, 200);
    }

    public function getPersonas($var){
            
        $datos = DB::table('empleado')
                    ->join('persona', 'persona.uid', '=', 'empleado.uid')
                    ->select(                        
                        'persona.uid',
                        'persona.nombre',
                        'persona.primerApellido',
                        'persona.segundoApellido',
                        'empleado.secuencia'
                    )
                    ->where(function($query) use ($var) {
                                $query->where(
                                    DB::raw("CONCAT(persona.nombre, ' ', persona.primerApellido, ' ', persona.segundoApellido)"), 'LIKE', '%'.$var.'%')
                                    ->orWhere(
                                        DB::raw("CONCAT(persona.primerApellido, ' ', persona.segundoApellido, ' ', persona.nombre)"), 'LIKE', '%'.$var.'%')
                                    ->orWhere('persona.nombre', 'LIKE', '%'.$var.'%')
                                    ->orWhere('persona.primerApellido', 'LIKE', '%'.$var.'%')
                                    ->orWhere('persona.segundoApellido', 'LIKE', '%'.$var.'%')
                                    ->orWhere('persona.uid', 'LIKE', '%'.$var.'%');
                            })
                             ->whereNull('empleado.fechabaja')
                    ->distinct()
                    ->take(50)
                    ->get();   
    
        //Log::info('Número de personas encontradas: ' . $personas->count());        
        if ($datos->isEmpty()) {
            return $this->returnEstatus('No se encontraron personas.', 200, null);
        }
        return $this->returnData('profesores', $datos, 200);
    }
    
  
}
