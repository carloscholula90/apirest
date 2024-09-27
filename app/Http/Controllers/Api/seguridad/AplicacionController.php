<?php

namespace App\Http\Controllers\Api\seguridad;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\seguridad\Aplicacion;
use Illuminate\Support\Facades\Validator;

class AplicacionController extends Controller
{
    public function index()
    {
        $aplicacion = Aplicacion::all();

        $data = [
            'aplicacion' => $aplicacion,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'descripcion' =>'required|max:255',
            'activo' => 'required|numeric|max:255',
            'idModulo' => 'required|numeric|max:255',

        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $maxIdAplicacion = Aplicacion::max('idAplicacion');
        $newIdAplicacion = $maxIdAplicacion ? $maxIdAplicacion + 1 : 1;
      
        $aplicacion = Aplicacion::create([
                    'idAplicacion' =>  $newIdAplicacion,
                    'descripcion' => $request->descripcion,
                    'activo' => $request->activo,
                    'idModulo' => $request->idModulo
        ]);

        if (!$aplicacion) {
            $data = [
                'message' => 'Error al crear la aplicacion',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        $aplicacion = Aplicacion::findOrFail($newIdAplicacion);
        $data = [
            'aplicacion' => $aplicacion,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($id)
    {
        $aplicacion = Aplicacion::find($id);

        if (!$aplicacion) {
            $data = [
                'message' => 'Aplicacion no encontrada',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'aplicacion' => $aplicacion,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function destroy($id)
    {
        $aplicacion = Aplicacion::find($id);

        if (!$aplicacion) {
            $data = [
                'message' => 'Aplicación no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $aplicacion->delete();

        $data = [
            'message' => 'aplicación eliminada',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $id)
    {
        $aplicacion = Aplicacion::find($id);

        if (!$aplicacion) {
            $data = [
                'message' => 'Aplicación no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
                                    'idAplicacion' => $request->idAplicacion,
                                    'descripcion' => $request->descripcion,
                                    'activo' => $request->activo,
                                    'idModulo' => $request->idModulo
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $aplicacion->idAplicacion = $request->idAplicacion;
        $aplicacion->descripcion = $request->descripcion;
        $aplicacion->activo = $request->activo;
        $aplicacion->idModulo = $request->idModulo;

        $aplicacion->save();

        $data = [
            'message' => 'aplicacion actualizado',
            'aplicacion' => $aplicacion,
            'status' => 200
        ];

        return response()->json($data, 200);

    }

    public function updatePartial(Request $request, $id)
    {
        $aplicacion = Aplicacion::find($id);

        if (!$aplicacion) {
            $data = [
                'message' => 'rol de seguridad no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'idAplicacion' => 'required|numeric|max:255',
            'descripcion' =>'required|max:255',
            'activo' => 'required|numeric|max:255',
            'idModulo' => 'required|numeric|max:255',

        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        if ($request->has('idAplicacion')) {
            $aplicacion->idAplicacion = $request->idAplicacion;
        }

        if ($request->has('descripcion')) {
            $aplicacion->descripcion = $request->descripcion;
        }
        if ($request->has('activo')) {
            $aplicacion->activo = $request->activo;
        }

        if ($request->has('idModulo')) {
            $aplicacion->idModulo = $request->idModulo;
        }

        $aplicacion->save();

        $data = [
            'message' => 'aplicación actualizada',
            'asentamiento' => $aplicacion,
            'status' => 200
        ];

        return response()->json($data, 200);
    }
}
