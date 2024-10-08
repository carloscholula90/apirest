<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Modalidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModalidadController extends Controller
{
    public function index()
    {
        $modalidades = Modalidad::all();
  
        $data = [
            'modalidades' => $modalidades,
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

        $maxId = Modalidad::max('idModalidad');
        $newId = $maxId ? $maxId+ 1 : 1;

        $modalidades = Modalidad::create([
            'idModalidad' => $newId,
            'descripcion' => $request->descripcion
        ]);

        if (!$modalidades) {
            $data = [
                'message' => 'Error al crear la modalidad',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        $modalidades= Modalidad::findOrFail($newId);
        $data = [
            'modalidades' => $modalidades,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($id)
    {
        $modalidades = Modalidad::find($id);

        if (!$modalidades) {
            $data = [
                'message' => 'Modalidad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'modalidades' => $modalidades,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function destroy($id)
    {
        $modalidades = Modalidad::find($id);

        if (!$modalidades) {
            $data = [
                'message' => 'Modalidad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $modalidades->delete();

        $data = [
            'message' => 'Modalidad eliminada',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $id)
    {
        $modalidades = Modalidad::find($id);

        if (!$modalidades) {
            $data = [
                'message' => 'Modalidad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'idModalidad' => 'required|numeric|max:255',
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

        $modalidades->idModalidad = $request->idModalidad;
        $modalidades->descripcion = $request->descripcion;

        $modalidades->save();

        $data = [
            'message' => 'Modalidad actualizado',
            'modalidades' => $modalidades,
            'status' => 200
        ];

        return response()->json($data, 200);

    }

    public function updatePartial(Request $request, $id)
    {
        $modalidades = Modalidad::find($id);

        if (!$modalidades) {
            $data = [
                'message' => 'Modalidad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
           'idModalidad' => 'required|numeric|max:255',
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
        if ($request->has('idModalidad')) {
            $modalidades->idModalidad = $request->idModalidad;
        }

        if ($request->has('descripcion')) {
            $modalidades->descripcion = $request->descripcion;
        }

        $modalidades->save();

        $data = [
            'message' => 'Modalidad actualizado',
            'asentamiento' => $modalidades,
            'status' => 200
        ];

        return response()->json($data, 200);
    }
}
