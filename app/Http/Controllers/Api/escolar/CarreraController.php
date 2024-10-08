<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Carrera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CarreraController extends Controller
{
    public function index()
    {
        $carreras = Carrera::all();

        $data = [
            'carreras' => $carreras,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'letra' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $maxIdCarrera = Carrera::max('idCarrera');
        $newIdCarrera = $maxIdCarrera ? $maxIdCarrera + 1 : 1;

        $carreras = Carrera::create([
                        'idCarrera' => $newIdCarrera,
                        'descripcion' => $request->descripcion,
                        'letra'=> $request->letra,
                        'diaInicioCargo'=>$request->diaInicioCargo,
                        'diaInicioRecargo'=>$request->diaInicioRecargo
        ]);

        if (!$carreras) {
            $data = [
                'message' => 'Error al crear la carrera',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        
        $carreras = Carrera::find($newIdCarrera);

        $data = [
            'carreras' => $carreras,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($idCarrera)
    {
        $carreras = Carrera::find($idCarrera);

        if (!$carreras) {
            $data = [
                'message' => 'Carrera no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'carreras' => $carreras,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function destroy($idCarrera)
    {
        $carreras = Carrera::find($idCarrera);

        if (!$carreras) {
            $data = [
                'message' => 'Carrera no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $carreras->delete();

        $data = [
            'message' => 'Carrera eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $idCarrera)
    {
        $carreras = Carrera::find($idCarrera);

        if (!$carreras) {
            $data = [
                'message' => 'Carrera no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'idCarrera' => 'required|numeric|max:255',
            'descripcion' => 'required|max:255',
            'letra' => 'required|max:255',
            'diaInicioCargo' => 'required|date',
            'diaInicioRecargo' => 'required|date',
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $carreras->idCarrera = $request->idCarrera;
        $carreras->descripcion = $request->descripcion;
        $carreras->letra = $request->letra;
        $carreras->diaInicioCargo = $request->diaInicioCargo;
        $carreras->iaInicioRecargo = $request->diaInicioRecargo;

        $carreras->save();

        $data = [
            'message' => 'Carrera actualizado',
            'carreras' => $carreras,
            'status' => 200
        ];

        return response()->json($data, 200);

    }

    public function updatePartial(Request $request, $idCarrera)
    {
        $carreras = Carrera::find($idCarrera);

        if (!$carreras) {
            $data = [
                'message' => 'Carrera no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'idCarrera' => 'required|numeric|max:255',
            'descripcion' => 'required|max:255',
            'letra' => 'required|max:255',
            'diaInicioCargo' => 'required|date',
            'diaInicioRecargo' => 'required|date'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        if ($request->has('idCarrera')) {
            $carreras->idCarrera = $request->idCarrera;
        }

        if ($request->has('descripcion')) {
            $carreras->descripcion = $request->descripcion;
        }

        if ($request->has('letra')) {
            $carreras->letra = $request->letra;
        }

        if ($request->has('diaInicioCargo')) {
            $carreras->diaInicioCargo = $request->diaInicioCargo;
        }

        if ($request->has('diaInicioRecargo')) {
            $carreras->diaInicioRecargo = $request->diaInicioRecargo;
        }

        $carreras->save();

        $data = [
            'message' => 'Carrera actualizado',
            'carreras' => $carreras,
            'status' => 200
        ];

        return response()->json($data, 200);
    }
}
