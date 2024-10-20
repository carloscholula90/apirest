<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\AvisosPrivacidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvisosPrivacidadController extends Controller
{
    public function index(){
       
        $avisos = avisosPrivacidad::all();

        return $this->returnData('avisos',$avisos,200);
    }

    public function active(){

        $avisos = avisosPrivacidad::where('activo',1)->get();

        return $this->returnData('avisos',$avisos,200);
    }

    public function store(Request $request) {
        
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'activo' => 'required|integer|regex:/^[0-1]{1}$/',
            'archivo' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        //Actualiza todos los registro de activo 1 a 0 para que solo exista uno en 1
        if ($request->activo == 1) {
            $actualizados = AvisosPrivacidad::where('activo',1)->update(['activo' => 0]);
        }

        $maxIdAviso = AvisosPrivacidad::max('idAviso');
        $newIdAviso = $maxIdAviso ? $maxIdAviso + 1 : 1;
        $avisos = AvisosPrivacidad::create([
            'idAviso' => $newIdAviso,
            'descripcion' => strtoupper(trim($request->descripcion)),
            'activo' => $request->activo,
            'archivo' => strtolower(trim($request->archivo))
        ]);

        if (!$avisos) 
            return $this->returnEstatus('Error al crear el aviso de privacidad',500,null);         

        $avisos = AvisosPrivacidad::findOrFail($newIdAviso);
    
        $data = [
            'Aviso de Privacidad' => $avisos,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

     // Elimina un aviso de privacidad por ID
    public function destroy($idAviso){
        $avisos = AvisosPrivacidad::find($idAviso);

        if (!$avisos) 
            return $this->returnEstatus('Aviso de privacidad no encontrado',404,null);

        $avisos->delete();
            return $this->returnEstatus('Aviso de privacidad eliminado exitosamente',200,null);
    }

    // Actualiza un aviso de privacidad por ID
    public function update(Request $request, $idAviso){

        $avisos = AvisosPrivacidad::find($idAviso);
        if (!$avisos) {
            return response()->json(['message' => 'Aviso de privacidad no encontrado', 'status' => 404], 404);
        }

        //Actualiza todos los registro de activo 1 a 0 para que solo exista uno en 1
        if ($request->activo == 1) {
            $actualizados = AvisosPrivacidad::where('activo',1)->update(['activo' => 0]);
        }

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255',
                    'activo' => 'required|integer|regex:/^[0-1]{1}$/',
                    'archivo' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors());

        $avisos->idAviso = $request->idAviso;
        $avisos->descripcion = strtoupper(trim($request->descripcion));
        $avisos->activo = $request->activo;
        $avisos->archivo = strtolower(trim($request->archivo));
        $avisos->save();

        return $this->returnEstatus('El registro fue actualizado con éxito',200,null);
    }
}
