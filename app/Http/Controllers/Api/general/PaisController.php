<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Pais;
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\Validator;

class PaisController extends Controller
{
    public function index(){
             
        $pais = Pais::all();

        $data = [
            'pais' => $pais,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255',
                    'nacionalidad' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $maxIdPais = Pais::max('idPais');
        $newIdPais = $maxIdPais ? $maxIdPais + 1 : 1;
        $pais = Pais::create([
            'idPais' => $newIdPais,
            'descripcion' => $request->descripcion,
            'nacionalidad' => $request->nacionalidad
        ]);

        if (!$pais) {
            $data = [
                'message' => 'Error al crear el pais',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        $pais = Pais::findOrFail($newIdPais);
    
        $data = [
            'pais' => $pais,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($idPais){
        try {
            // Busca el pais por ID y lanza una excepción si no se encuentra
            $pais = Pais::findOrFail($idPais);
    
            // Retorna el pais con estado 200
            $data = [
                'pais' => $pais,
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el pais no se encuentra, retorna un mensaje de error con estado 404
            $data = [
                'message' => 'Pais no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    }
    
    public function destroy($idPais){
        $pais = Pais::find($idPais);

        if (!$pais) {
            $data = [
                'message' => 'Pais no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $pais->delete();

        $data = [
            'message' => 'Pais eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $idPais){

        $pais = Pais::find($idPais);
        if (!$pais) {
            $data = [
                'message' => 'Pais no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
                    'idPais' => 'required|numeric|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                    'message' => 'Error en la validación de los datos',
                    'errors' => $validator->errors(),
                    'status' => 400
            ];
            return response()->json($data, 400);
        }

        $pais->idPais = $request->idPais;
        $pais->descripcion = $request->descripcion;
        $pais->descripcion = $request->nacionalidad;
        $pais->save();

        $data = [
                'message' => 'Pais actualizado',
                'pais' => $pais,
                'status' => 200
        ];
        return response()->json($data, 200);
    }

    public function updatePartial(Request $request, $idPais){

        $pais = Pais::find($idPais);
        if (!$pais) {
            $data = [
                'message' => 'Pais no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
                    'idPais' => 'required|numeric|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        if ($request->has('idPais')) {
            $pais->idPais = $request->idPais;
        }

        if ($request->has('descripcion')) {
            $pais->descripcion = $request->descripcion;
        }

        if ($request->has('nacionalidad')) {
            $pais->nacionalidad = $request->nacionalidad;
        }
        $pais->save();

        $data = [
            'message' => 'Pais actualizado',
            'pais' => $pais,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function nacionalidad(){             
        $nacionalidades = Pais::select('nacionalidad')->get();

        // Transforma la colección a un array de nacionalidades en minúsculas
        $nacionalidadesArray = $nacionalidades->map(function ($pais) {
            return $pais->nacionalidad;
        })->toArray();
    
        $data = [
            'nacionalidad' => $nacionalidadesArray,
            'status' => 200
        ];
        return response()->json($data, 200);
    }
    
    public function mostrarNacionalidad($idPais){
        try {
            // Busca el pais por ID y lanza una excepción si no se encuentra
            $pais = Pais::findOrFail($idPais);
    
            // Retorna el pais con estado 200
            $data = [
                'pais' => $pais,
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el pais no se encuentra, retorna un mensaje de error con estado 404
            $data = [
                'message' => 'Pais no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    }

    public function buscaNacionalidad($idPais){
        try {
            // Busca el pais por ID y lanza una excepción si no se encuentra
            $pais = Pais::findOrFail($idPais);
    
            // Retorna el pais con estado 200
            $data = [
                'nacionalidad' => $pais->nacionalidad,
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el pais no se encuentra, retorna un mensaje de error con estado 404
            $data = [
                'message' => 'Pais no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    }
}
