<?php
namespace App\Http\Controllers\Api\general;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\general\Salud;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;
     
class SaludController extends Controller
{
    public function index(){                        
        $salud = Salud::all();
        return $this->returnData('salud',$salud,200);
    }     

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                                        'uid' => 'required|numeric',
                                        'enfermedad' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
                           
            $maxId = Salud::where('uid', $request->uid)  
                                  ->max('secuencia');
  
            $newId = $maxId ? $maxId + 1 : 1; 
            try {
                $salud = Salud::create([
                                'secuencia' => $newId,
                                'uid' => $request->uid, 
                                'enfermedad' => trim($request->enfermedad),
                                'medico' => trim($request->medico),
                                'telefono' => trim($request->telefono)     
                ]);  
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('Error al crear el registro',400,null);                
            return $this->returnEstatus('Error al crear el registro',400,null);
        }

        if (!$salud) 
            return $this->returnEstatus('Error al crear el registro',500,null); 
        return $this->returnData('salud',$salud,200);   
    }

    public function show($uid){
        try {
            $salud = Salud::select('uid','secuencia','enfermedad','medico','telefono')
                            ->where('uid',$uid)
                            ->get();      
            if ($salud) 
                return $this->returnData('salud',$salud,200);     
            else return $this->returnEstatus('Registro no encontrado',404,null); 
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Salud no encontrado',404,null); 
        }  
    }
    
    public function destroy($uid,$secuencia){
        $salud = Salud::where('uid', $uid)
                        ->where('secuencia',$secuencia); 
        $salud->delete();

        if (!$salud) 
            return $this->returnEstatus('Salud no encontrado',404,null);  
        return $this->returnEstatus('Salud eliminado',200,null); 
    }

    public function obtenerDatos(){
        return DB::table('salud as salud')
                       ->select(
                               'p.uid',
                               'nombre',
                               'primerApellido',
                               'segundoApellido',
                               'enfermedad',
                               'medico',
                               'telefono'
                              )
                             ->join('persona as p', 'p.uid', '=', 'salud.uid')
                             ->orderBy('p.uid', 'asc')
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
                                               [80,100,100,100,100,100,200], // Anchos de columna
                                               ['uid','nombre','primerApellido','segundoApellido','enfermedad','medico','telefono'], // Claves
                                               'CONTACTOS', // Título del reporte
                                               ['UID','NOMBRE', 'APELLIDO PATERNO', 'APELLIDO MATERNO','ENFERMEDAD','MEDICO','TELEFONO'], 'L','letter',// Encabezados   ,
                                               'rptSalud.pdf'
         );
     } 
             
       public function exportaExcel() {  
                // Ruta del archivo a almacenar en el disco público
                $path = storage_path('app/public/salud_rpt.xlsx');
                $selectColumns = ['p.uid',
                               'nombre',
                               'primerApellido',
                               'segundoApellido',
                               'enfermedad',
                               'medico',
                               'telefono']; 
                $namesColumns =  ['UID','NOMBRE', 'APELLIDO PATERNO', 'APELLIDO MATERNO','ENFERMEDAD','MEDICO','TELEFONO']; // Seleccionar columnas específicas
                $joins = [[ 'table' => 'persona as p', // Tabla a unir
                            'first' => 'p.uid', // Columna de la tabla principal
                            'second' => 'salud.uid', // Columna de la tabla unida
                            'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                         ]          
                     ];
     
                $export = new GenericTableExportEsp('salud', 'uid', [], ['p.uid'], ['asc'], $selectColumns, $joins,$namesColumns);
     
                // Guardar el archivo en el disco público  
                Excel::store($export, 'salud_rpt.xlsx', 'public');
            
                // Verifica si el archivo existe usando Storage de Laravel
                if (file_exists($path))  {
                    return response()->json([
                        'status' => 200,  
                        'message' => 'https://reportes.pruebas.com.mx/storage/app/public/salud_rpt.xlsx' // URL pública para descargar el archivo
                    ]);
                } else {
                    return response()->json([
                        'status' => 500,
                        'message' => 'Error al generar el reporte '
                    ]);
                }  
        }
     
}