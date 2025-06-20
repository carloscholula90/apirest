<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;  
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;  

class OfertaController extends Controller
{  /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
       
        $grupoB = $request->grupo;
        $capacidadB = $request->capacidad;
        $semestreB = $request->semestre;

        if (!is_array($semestreB)) 
                return response()->json(['error' => 'El campo semestre debe ser un arreglo', 'data' => $semestreB], 422);

        $cantidad = count($semestreB); 
          Log::info('cantidad :'.$cantidad);
              

        for ($indx = 0; $indx <$cantidad; $indx++){
                Log::info('indx :'.$indx);
                Log::info('grupo :'.$grupoB[$indx]);
                Log::info('capacidad :'.$capacidadB[$indx]);
                Log::info('semestre :'.$semestreB[$indx]);   
                $result = DB::select('CALL creaoferta(?, ?, ?, ?, ?, ?, ?, ?, ?)', 
                                                        [$request->nivel,$request->periodo, 
                                                         $request->escuela, $request->pl, 
                                                         $request->turno, $request->modalidad,$grupoB[$indx],
                                                         $semestreB[$indx],$capacidadB[$indx]]);
                Log::info('resultado :',$result);   

        }
        $data = ['msj' => 'Proceso exitoso','status' => 200];
    
        return response()->json($data, 200);
    }
}
