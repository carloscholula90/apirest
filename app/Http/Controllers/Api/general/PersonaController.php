<?php

namespace App\Http\Controllers\Api\general;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\general\Persona;
use Illuminate\Support\Facades\Log;

class PersonaController extends Controller{
    
    private $campos = ['uid','curp','nombre','primerApellido','segundoApellido','fechaNacimiento','sexo',
                        'idPais','idEstado','idCiudad','idEdoCivil','rfc'];

    // Retorna todas las personas
    public function index(){
        $personas = Persona::all();

        if ($personas->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron personas.',
                'status' => 404
            ], 404);
        }

        return response()->json([
            'personas' => $personas,
            'status' => 200
        ], 200);
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

        $persona = Persona::create($validator->validated());

        return response()->json([
            'usuario' => $persona,
            'status' => 201
        ], 201);
    }

   
     // Muestra una persona por ID
     public function show($uid){
        Log::info('Entra a buscar el contacto: '.$uid);
        $tipo = 1; // Tipo de contacto oficial correo electrónico
    
        // Busca la persona con el tipo de contacto específico
        $persona = Persona::with(['contactos' => function ($query) use ($tipo) {
            $query->where('idTipoContacto', $tipo);
        }])->find($uid);
    
        if (!$persona) {
            return response()->json([
                'message' => 'Persona no encontrada',
                'status' => 404
            ], 404);
        }
    
        // Encuentra el máximo secuencial del contacto con el tipo específico
        $maxSecuencial = $persona->contactos
                                ->where('idTipoContacto', $tipo)
                                ->max('consecutivo');
    
        if ($maxSecuencial === null) {
            return response()->json([
                'message' => 'No tiene contacto tipo 1.',
                'status' => 404
            ], 404);
        }
    
        // Obtén el contacto específico con el máximo secuencial
        $contacto = $persona->contactos
                            ->where('idTipoContacto', $tipo)
                            ->where('consecutivo', $maxSecuencial)
                            ->first();
    
        return response()->json([
                                'persona' => [
                                'uid' => $persona->uid,
                                'curp' => $persona->curp,
                                'nombre' => $persona->nombre,
                                'primerApellido' => $persona->primerApellido,
                                'segundoApellido' => $persona->segundoApellido,
                                'sexo' => $persona->sexo,
                                'rfc' => $persona->rfc,
                                'correo' =>$contacto->dato
            ],
        'status' => 200
        ], 200);      
    }

    // Actualiza una persona por ID
    public function update(Request $request, $uid){
        $persona = Persona::find($uid);

        if (!$persona) {
            return response()->json([
                'message' => 'Persona no encontrada.',
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

        $persona->update($validator->validated());

        return response()->json([
            'message' => 'Persona actualizada exitosamente.',
            'usuario' => $persona,
            'status' => 200
        ], 200);
    }

    // Valida los datos de la solicitud
    private function validatePersona(Request $request){
        return Validator::make($request->all(), [
                                        'uid' => 'required|numeric|max:255',
                                        'curp' => 'required|max:255',
                                        'nombre' => 'required|max:255',
                                        'primerApellido' => 'required|max:255',
                                        'segundoApellido' => 'required|max:255',
                                        'fechaNacimiento' => 'required|date_format:Y-m-d',
                                        'sexo' => 'required|max:255',
                                        'idPais' => 'required|numeric|max:255',
                                        'idEstado' => 'required|numeric|max:255',
                                        'idCiudad' => 'required|numeric|max:255',
                                        'idEdoCivil' => 'required|numeric|max:255',
                                        'rfc' => 'required|max:255'
        ]);
    }
}