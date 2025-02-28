<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\EdoCivil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EdoCivilController extends Controller
{
    public function index(){      
       
        $edoCiviles = EdoCivil::all();

        $data = [
            'edoCiviles' => $edoCiviles,
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

        $maxIdEdoCivil = EdoCivil::max('idEdoCivil');
        $newIdEdoCivil = $maxIdEdoCivil ? $maxIdEdoCivil + 1 : 1;
        try{
            $edoCiviles = EdoCivil::create([
                'idEdoCivil' => $newIdEdoCivil,
                'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El estado civi ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el estado civil',400,null);
        }

        if (!$edoCiviles) {
            $data = [
                'message' => 'Error al crear el estado Civil',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        $edoCiviles = EdoCivil::findOrFail($newIdEdoCivil);
    
        $data = [
            'edoCivil' => $edoCiviles,
            'status' => 201
        ];
        return response()->json($data, 201);
    }

    public function show($idEdoCivil){
        try {
            // Busca el estado civil por ID y lanza una excepción si no se encuentra
            $edoCiviles = EdoCivil::findOrFail($idEdoCivil);
    
            // Retorna el medio con estado 200
            $data = [
                'medio' => $edoCiviles,
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el estado civil no se encuentra, retorna un mensaje de error con estado 404
            $data = [
                'message' => 'Estado Civil no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    }
    
    public function destroy($idEdoCivil){
        $edoCiviles = EdoCivil::find($idEdoCivil);
        if (!$edoCiviles) {
            $data = [
                'message' => 'Estado Civil no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }        
        $edoCiviles->delete();
        $data = [
            'message' => 'Estado Civil eliminado',
            'status' => 200
        ];
        return response()->json($data, 200);
    }

    public function update(Request $request, $idEdoCivil)
    {
        $edoCiviles = EdoCivil::find($idEdoCivil);
        if (!edoCiviles) {
            return response()->json(['message' => 'Estado Civil no encontrado', 'status' => 404], 404);
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
    
        $edoCiviles->descripcion = strtoupper(trim($request->descripcion));
        $edoCiviles->save();
    
        return response()->json([
            'message' => 'Estado Civil actualizado',
            'estado Civil' => $edoCiviles,
            'status' => 200,
        ], 200);
    }

    public function generaReporte()
     {
        return $this->imprimeCtl('edoCivil',' estado civil ',null,null,'descripcion');
     }   

    public function exportaExcel() {
        return $this->exportaXLS('edoCivil','idEdoCivil',['CLAVE', 'DESCRIPCIÓN'],'descripcion');     
    }
}
