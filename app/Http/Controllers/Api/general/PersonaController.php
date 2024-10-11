<?php

namespace App\Http\Controllers\Api\general;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\general\Persona;
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

        if ($personas->isEmpty()) 
            return $this->returnEstatus('No se encontraron personas.',200,null);
        return $this->returnData('personas',$personas,200);  
    }

    // Crea una nueva persona
    public function store(Request $request){
        $validator = $this->validatePersona($request);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $maxId = Persona::max('uid');  
        $newId = $maxId ? $maxId + 1 : 1;  
        $persona = Persona::create([
                                'uid' => $newId,
                                'curp' => strtoupper(trim($request->curp)),
                                'nombre' => strtoupper(trim($request->nombre)),
                                'primerApellido' => strtoupper(trim($request->primerApellido)),
                                'segundoApellido' => strtoupper(trim($request->segundoApellido)),
                                'fechaNacimiento' => strtoupper(trim($request->fechaNacimiento)),
                                'sexo' => strtoupper(trim($request->sexo)),
                                'idPais' =>$request->idPais,
                                'idEstado' => $request->idEstado,
                                'idCiudad' => $request->idCiudad,
                                'idEdoCivil' => $request->idEdoCivil
                ]);

                if (!$persona) 
                    return $this->returnEstatus('Error al crear a la persona',500,null);         
            
                return $this->returnEstatus('Persona generada con éxito',200,null); 
    }

    // Muestra una persona por ID
    public function recovery($uid)
    {
        $tipo = 1; // Tipo de contacto oficial correo electrónico
    
        // Busca la persona con el tipo de contacto específico
        $persona = Persona::with(['contactos' => function ($query) use ($tipo) {
            $query->where('idTipoContacto', $tipo);
        }])->find($uid);
    
        if (!$persona)     
          return $this->returnEstatus('Persona no encontrada',404,null); 
                
        // Encuentra el máximo secuencial del contacto con el tipo específico
        $maxSecuencial = $persona->contactos
                                ->where('idTipoContacto', $tipo)
                                ->max('consecutivo');
    
        if ($maxSecuencial == null) 
            return $this->returnEstatus('No tiene contacto tipo 1',404,null); 
               
        // Obtén el contacto específico con el máximo secuencial
        $contacto = $persona->contactos
                            ->where('idTipoContacto', $tipo)
                            ->where('consecutivo', $maxSecuencial)
                            ->first();
    
        return response()->json([
                                'persona' => [
                                'curp' => strtoupper(trim($persona->curp)),
                                'nombre' => strtoupper(trim($persona->nombre)),
                                'primerApellido' => strtoupper(trim($persona->primerApellido)),
                                'segundoApellido' => strtoupper(trim($persona->segundoApellido)),
                                'sexo' => strtoupper($trim(persona->sexo)),
                                'correo' =>$contacto->dato
            ],
        'status' => 200
        ], 200);
    }    
    
     // Muestra una persona por ID
     public function show($uid){
        $persona = Persona::find($uid);

        if (!$persona) 
            return $this->returnEstatus('Persona no encontrada',404,null);
        return $this->returnData('persona',$persona,200);
    }

    // Elimina una persona por ID
    public function destroy($uid){
        $persona = Persona::find($uid);

        if (!$persona) 
            return $this->returnEstatus('Persona no encontrada',404,null);

        $persona->delete();
        return $this->returnEstatus('Persona eliminada exitosamente',200,null);         
    }
  
    // Actualiza una persona por ID
    public function update(Request $request, $uid){
        $persona = Persona::find($uid);

        if (!$persona) 
            return $this->returnEstatus('Persona no encontrada',404,null);

        $validator = $this->validatePersona($request);

        if ($request->has('curp')) 
            $persona->curp = strtoupper(trim($request->curp)); 
        if ($request->has('nombre')) 
            $persona->nombre = strtoupper(trim($request->nombre));         
        if ($request->has('primerApellido')) 
            $persona->primerApellido = strtoupper(trim($request->primerApellido)); 
        if ($request->has('segundoApellido')) 
            $persona->segundoApellido = strtoupper(trim($request->segundoApellido));  
        if ($request->has('fechaNacimiento')) 
            $persona->fechaNacimiento = $request->fechaNacimiento; 
        if ($request->has('sexo')) 
            $persona->sexo = strtoupper(trim($request->sexo));
        if ($request->has('idPais')) 
            $persona->idPais = $request->idPais;
        if ($request->has('idEstado')) 
            $persona->idEstado = $request->idEstado;
        if ($request->has('idCiudad')) 
            $persona->idCiudad = $request->idCiudad;
        if ($request->has('idEdoCivil')) 
            $persona->idEdoCivil = $request->idEdoCivil;
        $persona->save();  
        return $this->returnEstatus('El registro fue actualizado con éxito',200,null); 
    }  

    // Valida los datos de la solicitud
    private function validatePersona(Request $request){
        return Validator::make($request->all(), [
                                        'uid' => 'required|max:255',
                                        'curp' => 'required|min:18|max:18',
                                        'nombre' => 'required|max:255',
                                        'primerApellido' => 'required|max:255',
                                        'segundoApellido' => 'required|max:255',
                                        'fechaNacimiento' => 'required|date_format:Y-m-d',
                                        'sexo' => 'required|max:255',
                                        'idPais' => 'required|numeric|max:255',
                                        'idEstado' => 'required|numeric|max:255',
                                        'idCiudad' => 'required|numeric|max:255',
                                        'idEdoCivil' => 'required|numeric|max:255'
        ]);
    }

      // Función para generar el reporte de personas
    public function generaReportePersonas()
     {
        // Obtener las personas a través del método getPersonas()
        $reporteController = new reporteController();  
        $personas = $this->getPersonas();
    
        // Si no hay personas, devolver un mensaje de error
        if ($personas->isEmpty())
            return $this->returnEstatus('No se encontraron personas para generar el reporte',404,null);
            
        $personas = [
            [
               'uid' => '1244',
               'primerApellido' => 'Prueba',
               'segundoApellido' => 'Prueba 2'
            ]
         ];
         // Crear una solicitud simulada (Request) para el reporte
        $request = new Request([
            'report_path' => 'general', // Ruta del archivo .jrxml
            'params' => [
               'Titulo' => 'CATÁLOGO DE PERSONAS',
               'SubTitulo' => 'FECHA DE IMPRESIÓN '.now()->format('d/m/Y') // Genera la fecha actual automáticamente
            ],
            'name_report'=>'catalogo.jrxml',       
            'data' => $personas, // Los datos de las personas
            'format' => 'pdf' // El formato del reporte
            ]);   
    
        // Llamar al método de generación de reporte
        return $reporteController->generateReport($request);
    }
}
