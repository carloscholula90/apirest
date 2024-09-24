<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use App\Models\seguridad\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModuloController extends Controller
{
    public function index()
    {
        $modulos = Modulo::all();

        $data = [
            'modulos' => $modulos,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'idModulo' => 'required|numeric|max:255',
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

        $modulos = Modulo::create([
            'idModulo' => $request->idModulo,
            'descripcion' => $request->descripcion
        ]);

        if (!$modulos) {
            $data = [
                'message' => 'Error al crear el asentamiento',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        $data = [
            'modulos' => $modulos,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($id)
    {
        $modulos = Modulo::find($id);

        if (!$Modulos) {
            $data = [
                'message' => 'Modulos no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'modulos' => $modulos,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function destroy($id)
    {
        $modulos = Modulo::find($id);

        if (!$modulos) {
            $data = [
                'message' => 'Modulos no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $modulos->delete();

        $data = [
            'message' => 'Modulos eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $id)
    {
        $modulos = Modulo::find($id);

        if (!$modulos) {
            $data = [
                'message' => 'Modulos no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'idModulo' => 'required|numeric|max:255',
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

        $modulos->idModulo = $request->idModulo;
        $modulos->descripcion = $request->descripcion;

        $modulos->save();

        $data = [
            'message' => 'Modulos actualizado',
            'asentamiento' => $modulos,
            'status' => 200
        ];

        return response()->json($data, 200);

    }

    public function updatePartial(Request $request, $id)
    {
        $modulos = Modulo::find($id);

        if (!$modulos) {
            $data = [
                'message' => 'Modulos no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
           'idModulo' => 'required|numeric|max:255',
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
        if ($request->has('idModulo')) {
            $modulos->idModulo = $request->idModulo;
        }

        if ($request->has('descripcion')) {
            $modulos->descripcion = $request->descripcion;
        }

        $modulos->save();

        $data = [
            'message' => 'Modulos actualizado',
            'asentamiento' => $modulos,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

}
