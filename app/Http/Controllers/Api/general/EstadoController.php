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

        $maxId = Estado::max($request->idPais);
        $newId = $maxId ? $maxId + 1 : 1;
        $estados = Estado::create([
                    'idPais' => $request->idPais,
                    'idEstado'=> $newId,
                    'descripcion' => $request->descripcion
        ]);
        
        if (!$estados) {
            $data = [
                'message' => 'Error al crear el estado',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        
        $data = [
            'estados' => $estados,
            'status' => 200
        ];
        return response()->json($data, 200);

    }

    public function show($idPais,$idEstado){
        try {
            // Busca el  por ID y lanza una excepción si no se encuentra
            $estados = Estado::join('pais', 'estado.idPais', '=', 'pais.idPais')
                                    ->select( 'pais.idPais',
                                            'pais.descripcion as paisDescripcion',
                                            'estado.idEstado',
                                            'estado.descripcion as estadoDescripcion'
                                    )
                                    ->where('estado.idPais', '=', $idPais)
                                    ->where('estado.idEstado', '=', $idEstado)
                                    ->get();
    
            // Retorna el  con estado 200
            $data = [
                'estados' => $estados,
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el  no se encuentra, retorna un mensaje de error con estado 404
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
      
        $estados = Estado::find($request->idPais,$request->idEstado);
        if (!$estados) {
            $data = [
                'message' => 'Estado no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
       
        $validator = Validator::make($request->all(), [
                                'idPais' => 'required|numeric|max:255',
                                'idEstado' => 'required|numeric|max:255',                                
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
        \Log::info('Datos de usuario procesados 1');

        $estados = Estado::where('idPais', $request->idPais)
                 ->where('idEstado', $request->idEstado)
                 ->first();
                 \Log::info('Datos de usuario procesados 2');

        if ($estados) {
            $estados->descripcion = $request->descripcion;
            $estados->save();
            $data = [
                'message' => 'Estado actualizado',
                'estados' => $estados,
                'status' => 200
            ];
        return response()->json($data, 200);
        } else {
            return response()->json(['error' => 'Estado no encontrado'], 404);
        }   
       
       
    }
}