<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\AceptaAviso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    }

    /*public function show($idAviso){
        try {
            // Busca el aviso de privacidad por ID y lanza una excepción si no se encuentra
            $avisos = AceptaAviso::findOrFail($idAviso);
    
            // Retorna el aviso de privacidad con estado 200
            $data = [
                'Avisos de Privacidad' => $aceptaAvisos
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el aviso de privacidad no se encuentra, retorna un mensaje de error con estado 404
            $data = [
                'message' => 'Aviso de privacidad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    }
    
    public function destroy($idAviso){
        $avisos = AceptaAviso::find($idAviso);

        if (!$avisos) {
            $data = [
                'message' => 'Aviso de privacidad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $avisos->delete();

        $data = [
            'message' => 'Aviso de privacidad eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $idAviso){

        $avisos = AceptaAviso::find($idAviso);
        if (!$avisos) {
            return response()->json(['message' => 'Aviso de privacidad no encontrado', 'status' => 404], 404);
        }

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255',
                    'activo' => 'required|max:255',
                    'archivo' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ], 400);
        }

        $avisos->idAviso = $request->idAviso;
        $avisos->descripcion = strtoupper(trim($request->descripcion));
        $avisos->activo = $request->activo;
        $avisos->archivo = strtolower(trim($request->archivo));
        $avisos->save();

        return response()->json([
            'message' => 'Aviso de privacidad actualizado',
            'aviso de privacidad' => $aceptaAvisos
            'status' => 200,
        ], 200);

    }*/
}
