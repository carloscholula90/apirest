<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Contacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;     
use App\Models\general\Persona;    
use App\Models\general\Empleado;    
use App\Models\general\Integra;
use App\Models\general\AceptaAviso;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;



class EmpleadoController extends Controller{

    public function index(){       
         // Realizar la consulta y devolver los resultados
            $personas = Persona::leftJoin('pais', 'persona.idPais', '=', 'pais.idPais')
                                ->join('empleado', 'empleado.uid', '=', 'persona.uid')
                                ->leftJoin('puestos as tc', 'tc.idPuesto', '=', 'empleado.idPuesto')                                
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
                'ciudad.descripcion',
                'empleado.fechainicio',
                'empleado.fechabaja',
                'empleado.idTipoContrato',
                'empleado.gradoestudios',
                'empleado.idPuesto',
                'tc.descripcion as puesto'
            )
            ->distinct()
            ->get();   

        if ($personas->isEmpty()) 
            return $this->returnEstatus('No se encontraron personas.', 200, null);
        
        return $this->returnData('personas', $personas, 200);
    }

    public function store(Request $request){
        $validator = $this->validatePersona($request);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
      
         //Valido si la persona ya existe
         $personas = Persona::select('persona.uid' )
                                        ->where('uid', $uid)
                                        ->get();   

        if ($personas->isEmpty()) {
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
            }            
         else { // Ya existe como empleado entonces si tiene creamos el integra
                $integra = Integra::create(['uid' => $newId,'secuencia' =>1,'idRol'=> 1]);
            } 
       
        $empleado = Empleado::create([
                                'uid'=> $newId,
                                'secuencia'=>1,
                                'fechainicio'=>$request->fechainicio,
                                'fechabaja'=>$request->fechabaja,
                                'gradoEstudios'=>$request->gradoEstudios,
                                'idTipoContrato'=>$request->idTipoContrato,
                                'idPuesto'=>$request->idPuesto
        ]);
        return response()->json([
                                'status' => 200,
                                'uid' => $newId,
                                'message' => 'Empleado creado con exito'
                                ]);  
    }

    private function validatePersona(Request $request){
        return Validator::make($request->all(), [
                                        'curp' => 'required|min:18|max:18',
                                        'nombre' => 'required|max:255',
                                        'primerApellido' => 'required|max:255',
                                        'segundoApellido' => 'required|max:255',
                                        'fechaNacimiento' => 'required|date_format:Y-m-d',
                                        'sexo' => 'required|max:255',
                                        'idPais' => 'required|numeric|max:255',
                                        'idEstado' => 'required|numeric|max:255',
                                        'idCiudad' => 'required|numeric|max:255',
                                        'idEdoCivil' => 'required|numeric|max:255',
                                        'fechainicio'=> 'required|date_format:Y-m-d',
                                        'gradoEstudios'=> 'required|max:5',
                                        'idPuesto'=> 'required|numeric|max:2',
                                        'idTipoContrato'=> 'required|numeric|max:2'
        ]);
    }

     public function update(Request $request, $uid){
        $validator = $this->validatePersona($request);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
      
        $persona = Persona::find($uid);    
        $persona->curp = $request->curp;
        $persona->nombre = strtoupper(trim($request->nombre));
        $persona->primerApellido = strtoupper(trim($request->primerApellido));
        $persona->segundoApellido = strtoupper(trim($request->segundoApellido));
        $persona->fechaNacimiento = $request->fechaNacimiento;
        $persona->sexo = $request->sexo;
        $persona->idPais = $request->idPais;
        $persona->idEstado = $request->idEstado;
        $persona->idCiudad = $request->idCiudad;
        $persona->idEdoCivil = $request->idEdoCivil;
        $persona->save();

        $empleado = Empleado::where('uid', $uid);
        $empleado->fechainicio = $request->fechainicio;
        $empleado->gradoEstudios = $request->gradoEstudios;
        $empleado->idPuesto = $request->idPuesto;
        $empleado->idTipoContrato = $request->idTipoContrato;
        $empleado->save();

        return $this->returnData('empleado',$empleado,200);        
     }
    
    public function destroy($uid){

       $count = Integra::where('uid', $uid)->where('idRol',1)->count();

        if ($count > 1) {
            // Hay más de un registro
        } else{
              $deletedRows = AceptaAviso::where('uid', $uid)->delete();
              $persona = Persona::find($uid);
        }                        
        $deletedRows = Empleado::where('uid', $uid)->delete();       
        $integra = Integra::where('uid', $uid)->delete();

        if (!$integra) 
            return $this->returnEstatus('Empleado no encontrada',404,null);       
        return $this->returnEstatus('Empleado eliminada exitosamente',200,null);   
    }

    public function obtenerDatos(){
    return Persona::join('empleado', 'empleado.uid', '=', 'persona.uid')
                                ->leftJoin('edoCivil', 'persona.idEdoCivil', '=', 'edoCivil.idEdoCivil')
                                ->leftJoin('puestos as tc', 'tc.idPuesto', '=', 'empleado.idPuesto')  
                                ->select(
                                        'persona.uid',
                                         DB::raw("CONCAT(persona.nombre, ' ', persona.primerApellido, ' ', persona.segundoApellido) AS nombre"),
                                        'persona.curp',
                                        'persona.fechaNacimiento',
                                        'edoCivil.descripcion as descripcionEdoCivil',
                                        'tc.descripcion as puesto'
                                    )
                                    ->distinct()
                                    ->get();      
    } 

    public function generaReporte(){
        $data = $this->obtenerDatos();

        if(empty($data)){
            return response()->json([
                'status' => 500,
                'message' => 'No hay datos para generar el reporte'
            ]);
        }

         // Convertir los datos a un formato de arreglo asociativo
         $dataArray = $data->map(function ($item) {
            return (array) $item;
        })->toArray();

         // Generar el PDF
         $pdfController = new pdfController();
         
         return $pdfController->generateReport($dataArray,  // Datos
                                               [50,200,100,150,100,100], // Anchos de columna
                                               ['uid','nombre','curp','fechaNacimiento','descripcionEdoCivil','puesto'], // Claves
                                               'CATÁLOGO DE EMPLEADOS', // Título del reporte
                                               ['UID','NOMBRE','CURP','FCH NACIMIENTO','EDO CIVIL','PUESTO'], 'L','letter',// Encabezados   ,
                                               'empleados_rpt.pdf'
         );
    } 
      
    public function exportaExcel() {  
        // Ruta del archivo a almacenar en el disco público
        $path = storage_path('app/public/empleados_rpt.xlsx');
        $selectColumns = ['persona.uid', DB::raw("CONCAT(persona.nombre, ' ', persona.primerApellido, ' ', persona.segundoApellido) AS nombre"), 'persona.fechaNacimiento',
                                        'edoCivil.descripcion as descripcionEdoCivil',
                                        'tc.descripcion as puesto']; // Seleccionar columnas específicas
        $namesColumns = ['UID','NOMBRE','CURP','FCH NACIMIENTO','EDO CIVIL','PUESTO']; // Seleccionar columnas específicas
        
        $joins = [[ 'table' => 'empleado', // Tabla a unir
                    'first' => 'empleado.uid', // Columna de la tabla principal
                    'second' => 'persona.uid', // Columna de la tabla unida
                    'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                    ],
                    ['table' => 'edoCivil', // Tabla a unir
                     'first' => 'persona.idEdoCivil', // Columna de la tabla principal
                     'second' => 'edoCivil.idEdoCivil', // Columna de la tabla unida
                     'type' => 'left' // Tipo de JOIN (en este caso LEFT JOIN)
                    ],
                    ['table' => 'puestos as tc', // Tabla a unir
                     'first' => 'tc.idPuesto', // Columna de la tabla principal
                     'second'=> 'empleado.idPuesto', // Columna de la tabla unida
                     'type' => 'left' // Tipo de JOIN (en este caso LEFT JOIN)
                    ]
            ]; 

        $export = new GenericTableExportEsp('persona', 'uid', [], ['persona.uid'], ['asc'], $selectColumns, $joins,$namesColumns);

        // Guardar el archivo en el disco público
        Excel::store($export, 'empleado_rpt.xlsx', 'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/empleados_rpt.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }  
    }
}
