<?php

namespace App\Http\Controllers\Api\general; 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\general\Integra;
use Illuminate\Support\Facades\Log;


class IntegraController extends Controller{

    private $campos = ['uid','secuencia','idRol','activo','fechainicio','fechabaja','matriculae'];
    
    // retorna los roles de personas

    public function index()
    {
        $integra = Integra::all();

        if ($integra->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron roles de personas.',
                'status' => 404
            ], 404);
        }

        return response()->json([
            'integra' => $integra,
            'status' => 200
        ], 200);

        
        

        return response()->json($data, 200);
    }

    

    // Crea una nueva persona
    public function store(Request $request){
        $validator = $this->validatePersona($request);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error en la validación de los datos.',
                'errors' => $validator->errors(),
                'status' => 400
            ], 400);
        }

        $integra = Integra::create($validator->validated());

        return response()->json([
            'usuario' => $persona,
            'status' => 201
        ], 201);
    }
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $integra = Integra::find($uid);

        if (!$persona) {
            return response()->json([
                'message' => 'Persona no encontrada.',
                'status' => 404
            ], 404);
        }

        return response()->json([
            'persona' => $persona,
            'status' => 200
        ], 200);
    }

   

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $integra = Integra::find($uid);

        if (!$persona) {
            return response()->json([
                'message' => 'Pintegra no encontrada.',
                'status' => 404
            ], 404);
        }

        $validator = $this->validatePersona($request);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error en la validación de los datos.',
                'errors' => $validator->errors(),
                'status' => 400
            ], 400);
        }

        $integra->update($validator->validated());

        return response()->json([
            'message' => 'Persona actualizada exitosamente.',
            'usuario' => $integra,
            'status' => 200
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $integra = Integra::find($secuencia);

        if (!$integra) {
            $data = [
                'message' => 'integra no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $integra->delete();

        $data = [
            'message' => 'integra eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }
    
     // Valida los datos de la solicitud
     private function validateIntegra(Request $request){
        return Validator::make($request->all(), [
                                        'uid' => 'required|numeric|max:255',
                                        'secuencia' => 'required|max:255',
                                        'idRol' => 'required|numeric|max:255',
                                        'activo' => 'required|max:255',
                                        'fechainicio' => 'required|date_format:Y-m-d',
                                        'fechabaja' => 'required|date_format:Y-m-d',
                                        'matriculae' => 'required|numeric|max:255'
        ]);
     }
}
