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

        $data = [
            'Avisos de Privacidad' => $avisos,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'activo' => 'required|max:1'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $maxIdAviso = AvisosPrivacidad::max('idAviso');
        $newIdAviso = $maxIdAviso ? $maxIdAviso + 1 : 1;
        $avisos = AvisosPrivacidad::create([
            'idAviso' => $newIdAviso,
            'descripcion' => $request->descripcion
        ]);

        if (!$avisos) {
            $data = [
                'message' => 'Error al crear el aviso de privacidad',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        $avisos = AvisosPrivacidad::findOrFail($newIdAviso);
    
        $data = [
            'Aviso de Privacidad' => $avisos,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($idAviso){
        try {
            // Busca el aviso de privacidad por ID y lanza una excepción si no se encuentra
            $avisos = AvisosPrivacidad::findOrFail($idAviso);
    
            // Retorna el aviso de privacidad con estado 200
            $data = [
                'Avisos de Privacidad' => $avisos,
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
        $avisos = AvisosPrivacidad::find($idAviso);

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

        $avisos = AvisosPrivacidad::find($idAviso);
        if (!$avisos) {
            $data = [
                'message' => 'Aviso de privacidad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255',
                    'activo' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                    'message' => 'Error en la validación de los datos',
                    'errors' => $validator->errors(),
                    'status' => 400
            ];
            return response()->json($data, 400);
        }

        $avisos->idAviso = $request->idAviso;
        $avisos->descripcion = strtoupper(trim($request->descripcion));
        $avisos->activo = $request->activo;
        $avisos->save();

        $data = [
                'message' => 'Aviso de privacidad actualizado',
                'medio' => $avisos,
                'status' => 200
        ];
        return response()->json($data, 200);
    }
}
