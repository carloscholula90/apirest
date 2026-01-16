<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;  

class CierreController extends Controller  
{
    public function generaCierre(Request $request){
         $validator = Validator::make($request->all(), [
                                    'idNivel' => 'required|max:255',
                                    'idPeriodo' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 


        DB::statement("CALL CierrePeriodoEdoCta1(?, ?)", [$request->idNivel,$request->idPeriodo]);
        return $this->returnData('Cierre realizado',null,200); 
    }
}
