<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\AceptaAviso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class AceptaAvisoController extends Controller
{
    public function index(){
       
        $aceptaAviso = AceptaAviso::all();

        return $this->returnData('aceptaAvisos',$aceptaAviso,200);
    }

    public function active(Request $request){

        $aceptaAviso = AceptaAviso::where('idAviso',$request->idAviso)
        ->where('uid',$request->uid)
        ->get();

        if ($aceptaAviso->isEmpty())
           return $this->returnEstatus('0',200,NULL);
        else
           return $this->returnEstatus('1',200,NULL);
    }

    public function store(Request $request) {

        try {
            $validator = Validator::make($request->all(), [
                'idAviso' => 'required|integer|regex:/^[0-9]{1,3}$/',
                'uid' => 'required|integer|regex:/^[0-9]{5,7}$/'
            ]);

            if ($validator->fails()) 
                return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors());

            $avisos = AceptaAviso::create([
                'idAviso' => $request->idAviso,
                'uid' => $request->uid,
                'ip' => $request->ip
            ]);

            if (!$avisos)
                return $this->returnEstatus('Error al crear el registro',500,null);
            return $this->returnData('Aviso de Privacidad',$avisos,201);

        } catch (QueryException $e) {

            if ($e->getCode() == 23000) {
                // Manejar la violación de restricción de integridad
                return $this->returnEstatus('Error al crear el registro por violación a la llave primaria',400,null);
            }
            return $this->returnEstatus('Error en la base de datos',500,null);
        }
    }

    public function destroy(Request $request){

        $avisos = AceptaAviso::where('idAviso',$request->idAviso)
                             ->where('uid',$request->uid)
                             ->first();

        if (!$avisos) 
            return $this->returnEstatus('Aviso de privacidad no encontrado',404,null);

        
        $deletedRows = AceptaAviso::where('idAviso', $request->idAviso)
                                  ->where('uid', $request->uid)
                                  ->delete();
        return $this->returnEstatus('Aviso de privacidad eliminado exitosamente',200,null);
    }
}
