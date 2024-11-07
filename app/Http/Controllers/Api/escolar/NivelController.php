<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Nivel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NivelController extends Controller
{
    public function index()
    {
        $niveles = Nivel::all();

        $data = [
            'niveles' => $niveles,
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

        $maxIdNivel = Nivel::max('idNivel');
        $newIdNivel = $maxIdNivel ? $maxIdNivel + 1 : 1;
        try{
            $niveles = Nivel::create([
                            'idNivel' => $newIdNivel,
                            'descripcion' => $request->descripcion
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El nivel ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el nivel',400,null);
        }

        if (!$niveles) {
            $data = [
                'message' => 'Error al crear el nivel',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        
        $niveles = Nivel::find($newIdNivel);

        $data = [
            'niveles' => $niveles,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($idNivel)
    {
        $niveles = Nivel::find($idNivel);

        if (!$niveles) {
            $data = [
                'message' => 'Nivel no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'niveles' => $niveles,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function destroy($idNivel)
    {
        $niveles = Nivel::find($idNivel);

        if (!$niveles) {
            $data = [
                'message' => 'Nivel no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $niveles->delete();

        $data = [
            'message' => 'Nivel eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $idNivel)
    {
        $niveles = Nivel::find($idNivel);

        if (!$niveles) {
            $data = [
                'message' => 'Nivel no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'idNivel' => 'required|numeric|max:255',
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

        $niveles->idNivel = $request->idNivel;
        $niveles->descripcion = $request->descripcion;

        $niveles->save();

        $data = [
            'message' => 'Nivel actualizado',
            'carreras' => $niveles,
            'status' => 200
        ];

        return response()->json($data, 200);

    }

    public function updatePartial(Request $request, $idNivel)
    {
        $niveles = Nivel::find($idNivel);

        if (!$niveles) {
            $data = [
                'message' => 'Nivel no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'idNivel' => 'required|numeric|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        if ($request->has('idNivel')) {
            $niveles->idNivel = $request->idNivel;
        }

        if ($request->has('descripcion')) {
            $niveles->descripcion = $request->descripcion;
        }

        $niveles->save();

        $data = [
            'message' => 'Nivel actualizado',
            'carreras' => $niveles,
            'status' => 200
        ];

        return response()->json($data, 200);
    }
}
