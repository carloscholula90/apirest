<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Puestos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PuestoController extends Controller
{
    public function index(){
       
       
        $puestos = Puestos::all();

        $data = [
            'puestos' => $puestos,
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

        $maxIdPuesto = Puestos::max('idPuesto');
        $newIdPuesto = $maxIdPuesto ? $maxIdPuesto + 1 : 1;
        try{
            $puestos = Puestos::create([
                'idPuesto' => $newIdPuesto,
                'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El puesto ya se encuentra dado de alta',400,null);
            return $this->returnEstatus('Error al insertar el puesto',400,null);
        }

        if (!$puestos) {
            $data = [
                'message' => 'Error al crear el puesto',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        $puestos = Puestos::findOrFail($newIdPuesto);
    
        $data = [
            'puesto' => $puestos,
            'status' => 201
        ];

        return response()->json($data, 201);

    }

    public function show($idPuesto){
        try {
            // Busca el puesto por ID y lanza una excepción si no se encuentra
            $puestos = Puestos::findOrFail($idPuesto);
    
            // Retorna el puesto con estado 200
            $data = [
                'Puesto' => $puestos,
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el puesto no se encuentra, retorna un mensaje de error con estado 404
            $data = [
                'message' => 'Puesto no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    }
    
    public function destroy($idPuesto){
        $puestos = Puestos::find($idPuesto);

        if (!$puestos) {
            $data = [
                'message' => 'Puesto no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $puestos->delete();

        $data = [
            'message' => 'Puesto eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $idPuesto)
    {
        $puestos = Puestos::find($idPuesto);
        if (!$puestos) {
            return response()->json(['message' => 'Puesto no encontrado', 'status' => 404], 404);
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
    
        $puestos->descripcion = strtoupper(trim($request->descripcion));
        $puestos->save();
    
        return response()->json([
            'message' => 'Puesto actualizado',
            'puesto' => $puestos,
            'status' => 200,
        ], 200);
    }

    public function exportaExcel() {
        return $this->exportaXLS('puestos','idPuesto',['CLAVE', 'DESCRIPCIÓN'],'descripcion');     
    }

    public function generaReporte()
     {
        return $this->imprimeCtl('puestos','puestos',null,null,'descripcion');
    }
    
}
