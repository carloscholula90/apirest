<?php

namespace App\Http\Controllers\Api\seguridad;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\seguridad\RolSeg;
use Illuminate\Support\Facades\Validator;

class RolSegController extends Controller
{
    public function index()
    {
        $rolesseg = RolSeg::all();

        $data = [
            'rolesseg' => $rolesseg,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $maxIdRol = RolSeg::max('idRol');
        $newIdRol = $maxIdRol ? $maxIdRol + 1 : 1;
        $rolSeg = RolSeg::create([
            'idRol' => $newIdRol,
            'nombre' => $request->nombre
        ]);

        if (!$rolSeg) {
            $data = [
                'message' => 'Error al crear el rol de seguridad',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        $rolSeg = Medio::findOrFail($newIdRol);
        $data = [
            'rolSeg' => $rolSeg,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($id)
    {
        $rolSeg = RolSeg::find($id);

        if (!$RolSeg) {
            $data = [
                'message' => 'Rol de seguridad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'rolSeg' => $rolSeg,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function destroy($id)
    {
        $rolSeg = RolSeg::find($id);

        if (!$rolSeg) {
            $data = [
                'message' => 'Rol de seguridad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $rolSeg->delete();

        $data = [
            'message' => 'rol de seguridad eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $id)
    {
        $rolSeg = RolSeg::find($id);

        if (!$rolSeg) {
            $data = [
                'message' => 'rol de seguridad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'idRol' => 'required|numeric|max:255',
            'nombre' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $rolSeg->idRol = $request->idRol;
        $rolSeg->nombre = $request->nombre;

        $rolSeg->save();

        $data = [
            'message' => 'rol de seguridad actualizado',
            'rolSeg' => $rolSeg,
            'status' => 200
        ];

        return response()->json($data, 200);

    }

    public function updatePartial(Request $request, $id)
    {
        $rolSeg = RolSeg::find($id);

        if (!$rolSeg) {
            $data = [
                'message' => 'rol de seguridad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
           'idRol' => 'required|numeric|max:255',
            'nombre' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        if ($request->has('idRol')) {
            $rolSeg->idRol = $request->idRol;
        }

        if ($request->has('nombre')) {
            $rolSeg->nombre = $request->nombre;
        }

        $rolSeg->save();

        $data = [
            'message' => 'rol de seguridad actualizado',
            'rolSeg' => $rolSeg,
            'status' => 200
        ];

        return response()->json($data, 200);
    }
}
