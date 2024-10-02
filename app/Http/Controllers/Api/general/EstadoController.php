<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Estado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EstadoController extends Controller{
    public function index(){     
        
         $estados = Estado::join('pais', 'estado.idPais', '=', 'pais.idPais')
                                    ->select( 'pais.idPais',
                                            'pais.descripcion as paisDescripcion',
                                            'estado.idEstado',
                                            'estado.descripcion as estadoDescripcion'
                                    )
                                    ->get();

        $data = [
            'estados' => $estados,
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

        $maxIdMedio = Estado::max('idEstado');
        $newIdMedio = $maxIdMedio ? $maxIdMedio + 1 : 1;
        $estados = Estado::create([
            'idEstado' => $newIdMedio,
            'descripcion' => $request->descripcion
        ]);

        if (!$estados) {
            $data = [
                'message' => 'Error al crear el medio',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        $estados = Estado::findOrFail($newIdMedio);
    
        $data = [
            'estado' => $estados,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($idPais,$idEstado){
        try {
            // Busca el medio por ID y lanza una excepción si no se encuentra
            $estados = Estado::join('pais', 'estado.idPais', '=', 'pais.idPais')
                                    ->select( 'pais.idPais',
                                            'pais.descripcion as paisDescripcion',
                                            'estado.idEstado',
                                            'estado.descripcion as estadoDescripcion'
                                    )
                                    ->where('estado.idPais', '=', $idPais)
                                    ->where('estado.idEstado', '=', $idEstado)
                                    ->get();
    
            // Retorna el medio con estado 200
            $data = [
                'estado' => $estados,
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el medio no se encuentra, retorna un mensaje de error con estado 404
            $data = [
                'message' => 'Estado no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    }
    
    public function destroy($idPais,$idEstado){
        $estados = Estado::find($idPais,$idEstado);

        if (!$estados) {
            $data = [
                'message' => 'Estado no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $estados->delete();

        $data = [
            'message' => 'Estado eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request){

        $estados = Estado::find($request->$idPais,$request->$idEstado);
        if (!$estados) {
            $data = [
                'message' => 'Estado no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
                                'idEstado' => 'required|numeric|max:255',
                                'idPais' => 'required|numeric|max:255',
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

        $estados->idEstado = $request->idEstado;
        $estados->idPais =   $request->idPais;
        $estados->descripcion = $request->descripcion;
        $estados->save();

        $data = [
                'message' => 'Estado actualizado',
                'estado' => $estados,
                'status' => 200
        ];
        return response()->json($data, 200);
    }
}