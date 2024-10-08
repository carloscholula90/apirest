<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Asentamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AsentamientoController extends Controller{

    public function index(){
       
        $asentamientos = Asentamiento::all();

        $data = [
            'asentamientos' => $asentamientos,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'idAsentamiento' => 'required|numeric|max:255',
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

        $asentamiento = Asentamiento::create([
            'idAsentamiento' => $request->idAsentamiento,
            'descripcion' => $request->descripcion
        ]);

        if (!$asentamiento) {
            $data = [
                'message' => 'Error al crear el asentamiento',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        $data = [
            'asentamiento' => $asentamiento,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($idAsentamiento){
        try {
            // Busca el asentamiento por ID y lanza una excepción si no se encuentra
            $asentamiento = Asentamiento::findOrFail($idAsentamiento);
    
            // Retorna el asentamiento con estado 200
            $data = [
                'asentamiento' => $asentamiento,
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el asentamiento no se encuentra, retorna un mensaje de error con estado 404
            $data = [
                'message' => 'Asentamiento no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    }
    
    public function destroy($idAsentamiento){
        $asentamiento = Asentamiento::find($idAsentamiento);

        if (!$asentamiento) {
            $data = [
                'message' => 'Asentamiento no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $asentamiento->delete();

        $data = [
            'message' => 'Asentamiento eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $idAsentamiento){

        $asentamiento = Asentamiento::find($idAsentamiento);
        if (!$asentamiento) {
            $data = [
                'message' => 'Asentamiento no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
                    'idAsentamiento' => 'required|numeric|max:255',
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

        $asentamiento->idAsentamiento = $request->idAsentamiento;
        $asentamiento->descripcion = $request->descripcion;
        $asentamiento->save();

        $data = [
                'message' => 'Asentamiento actualizado',
                'asentamiento' => $asentamiento,
                'status' => 200
        ];
        return response()->json($data, 200);
    }

    public function updatePartial(Request $request, $idAsentamiento){

        $asentamiento = Asentamiento::find($idAsentamiento);
        if (!$asentamiento) {
            $data = [
                'message' => 'Asentamiento no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
                    'idAsentamiento' => 'required|numeric|max:255',
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
        if ($request->has('idAsentamiento')) {
            $asentamiento->idAsentamiento = $request->idAsentamiento;
        }

        if ($request->has('descripcion')) {
            $asentamiento->descripcion = $request->descripcion;
        }

        $asentamiento->save();

        $data = [
            'message' => 'Asentamiento actualizado',
            'asentamiento' => $asentamiento,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

}
