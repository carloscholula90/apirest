<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Asentamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AsentamientoController extends Controller
{
    public function index()
    {
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

    public function show($id)
    {
        $asentamiento = Asentamiento::find($id);

        if (!$Asentamiento) {
            $data = [
                'message' => 'Asentamiento no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'asentamiento' => $asentamiento,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function destroy($id)
    {
        $asentamiento = Asentamiento::find($id);

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

    public function update(Request $request, $id)
    {
        $asentamiento = Asentamiento::find($id);

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

    public function updatePartial(Request $request, $id)
    {
        $asentamiento = Asentamiento::find($id);

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
