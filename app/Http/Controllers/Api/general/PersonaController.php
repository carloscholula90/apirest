<?php

namespace App\Http\Controllers\Api\general;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\general\Persona;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\serviciosGenerales\reporteController;

class PersonaController extends Controller{


    public function getPersonas()
    {
        // Realizar la consulta y devolver los resultados
        $personas = Persona::leftJoin('pais', 'persona.idPais', '=', 'pais.idPais')
            ->leftJoin('edoCivil', 'persona.idEdoCivil', '=', 'edoCivil.idEdoCivil')
            ->leftJoin('estado', function($join) {
                $join->on('persona.idPais', '=', 'estado.idPais')
                     ->on('persona.idEstado', '=', 'estado.idEstado');
            })
            ->leftJoin('ciudad', function($join) {
                $join->on('persona.idPais', '=', 'ciudad.idPais')
                     ->on('persona.idEstado', '=', 'ciudad.idEstado')
                     ->on('persona.idCiudad', '=', 'ciudad.idCiudad');
            })
            ->select(
                'persona.uid',
                'persona.curp',
                'persona.nombre',
                'persona.primerApellido',
                'persona.segundoApellido',
                'persona.fechaNacimiento',
                'persona.sexo',
                'edoCivil.idEdoCivil',
                'edoCivil.descripcion as descripcionEdoCivil',
                'pais.idPais',
                'pais.descripcion as paisDescripcion',
                'estado.idEstado',
                'estado.descripcion as estadoDescripcion',
                'ciudad.idCiudad',
                'ciudad.descripcion'
            )
            ->get();

        return $personas;
    }

    
    // Retorna todas las personas
    public function index(){
    
        $personas = getPersonas();

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
    public function recovery($uid)
    {
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

     // Muestra una persona por ID
     public function show($uid){
        $persona = Persona::find($uid);

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

    // Elimina una persona por ID
    public function destroy($uid){
        $persona = Persona::find($uid);

        if (!$persona) {
            return response()->json([
                'message' => 'Persona no encontrada.',
                'status' => 404
            ], 404);
        }

        $persona->delete();

        return response()->json([
            'message' => 'Persona eliminada exitosamente.',
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

      // Función para generar el reporte de personas
        public function generaReportePersonas()
        {
            // Obtener las personas a través del método getPersonas()
            $reporteController = new reporteController();  
            $personas = $this->getPersonas();
    
            // Si no hay personas, devolver un mensaje de error
            if ($personas->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron personas para generar el reporte.',
                    'status' => 404
                ], 404);
            }
    
            // Crear una solicitud simulada (Request) para el reporte
            $request = new Request([
                'report_path' => 'general/', // Ruta del archivo .jrxml
                'params' => [
                    'titulo' => 'CATÁLOGO DE PERSONAS',
                    'fecha' => 'FECHA DE IMPRESIÓN '.now()->format('dd/MM/YYYY') // Genera la fecha actual automáticamente
                ],
                'name_report'=>'catalogo.jrxml',     
                'data' => $personas->toArray(), // Los datos de las personas
                'format' => 'pdf' // El formato del reporte
            ]);   
    
            // Llamar al método de generación de reporte
            return $reporteController->generateReport($request);
        }
}
