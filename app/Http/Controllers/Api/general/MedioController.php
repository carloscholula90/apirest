<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Medio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedioController extends Controller
{
    public function index(){
       
       
        $medios = Medio::all();

        $data = [
            'medios' => $medios,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $maxIdMedio = Medio::max('idMedio');
        $newIdMedio = $maxIdMedio ? $maxIdMedio + 1 : 1;
        $medios = Medio::create([
            'idMedio' => $newIdMedio,
            'descripcion' => $request->descripcion
        ]);

        if (!$medios) {
            $data = [
                'message' => 'Error al crear el medio',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        $medios = Medio::findOrFail($newIdMedio);
    
        $data = [
            'medio' => $medios,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($idMedio){
        try {
            // Busca el medio por ID y lanza una excepción si no se encuentra
            $medios = Medio::findOrFail($idMedio);
    
            // Retorna el medio con estado 200
            $data = [
                'medio' => $medios,
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el medio no se encuentra, retorna un mensaje de error con estado 404
            $data = [
                'message' => 'Medio no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    }
    
    public function destroy($idMedio){
        $medios = Medio::find($idMedio);

        if (!$medios) {
            $data = [
                'message' => 'Medio no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $medios->delete();

        $data = [
            'message' => 'Medio eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $idMedio){

        $medios = Medio::find($idMedio);
        if (!$medios) {
            $data = [
                'message' => 'Medio no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
                    'idMedio' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                    'message' => 'Error en la validación de los datos',
                    'errors' => $validator->errors(),
                    'status' => 400
            ];
            return response()->json($data, 400);
        }

        $medios->idMedio = $request->idMedio;
        $medios->descripcion = $request->descripcion;
        $medios->save();

        $data = [
                'message' => 'Medio actualizado',
                'medio' => $medios,
                'status' => 200
        ];
        return response()->json($data, 200);
    }
}
