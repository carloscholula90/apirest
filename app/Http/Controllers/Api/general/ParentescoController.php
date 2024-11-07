<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Parentesco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParentescoController extends Controller
{
    public function index(){
       
       
        $parentesco = Parentesco::all();

        $data = [
            'parentesco' => $parentesco,
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

        $maxIdParentesco = Parentesco::max('idParentesco');
        $newIdParentesco = $maxIdParentesco ? $maxIdParentesco + 1 : 1;
        try{
            $parentesco = Parentesco::create([
                'idParentesco' => $newIdParentesco,
                'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El parentesco ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el parentesco',400,null);
        }

        if (!$parentesco) {
            $data = [
                'message' => 'Error al crear el parentesco',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        $parentesco = Parentesco::findOrFail($newIdParentesco);
    
        $data = [
            'parentesco' => $parentesco,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($idParentesco){
        try {
            // Busca el parentesco por ID y lanza una excepción si no se encuentra
            $parentesco = Parentesco::findOrFail($idParentesco);
    
            // Retorna el medio con estado 200
            $data = [
                'parentesco' => $parentesco,
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el parentesco no se encuentra, retorna un mensaje de error con estado 404
            $data = [
                'message' => 'Parentesco no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    }
    
    public function destroy($idParentesco){
        $parentesco = Parentesco::find($idParentesco);

        if (!$parentesco) {
            $data = [
                'message' => 'Parentesco no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $parentesco->delete();

        $data = [
            'message' => 'Parentesco eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $idParentesco){

        $parentesco = Parentesco::find($idParentesco);
        if (!$parentesco) {
            return response()->json(['message' => 'Parentesco no encontrado', 'status' => 404], 404);
        }

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ], 400);
        }

        $parentesco->idParentesco = $request->idParentesco;
        $parentesco->descripcion = strtoupper(trim($request->descripcion));
        $parentesco->save();

        return response()->json([
            'message' => 'Parentesco actualizado',
            'parentesco' => $avisos,
            'status' => 200,
        ], 200);
    }
}
