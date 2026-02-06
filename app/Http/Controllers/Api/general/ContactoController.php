<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Contacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;

class ContactoController extends Controller{

    public function index(){       
        $contactos = Contacto::all();
        return $this->returnData('contactos',$contactos,200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                                        'uid' => 'required|numeric',
                                        'idParentesco' => 'required|numeric',
                                        'idTipoContacto' => 'required|numeric',
                                        'dato' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
        
            $contactos = Contacto::where('idParentesco', $request->idParentesco)
                                    ->where('idTipoContacto',$request->idTipoContacto)
                                    ->where('uid', $request->uid)
                                    ->where('dato',trim($request->dato));   
            
            if ($contactos) 
                return $this->returnEstatus('El dato con el tipo de contacto ya existe ',404,null);  
                         

            $maxId = Contacto::where('idParentesco', $request->idParentesco)
                                    ->where('idTipoContacto', $request->idTipoContacto)
                                    ->where('uid', $request->uid)
                                    ->max('consecutivo');
  
            $newId = $maxId ? $maxId + 1 : 1; 
            try {
                $contactos = Contacto::create([
                                'consecutivo' => $newId,
                                'idParentesco' => $request->idParentesco, 
                                'idTipoContacto' => $request->idTipoContacto,
                                'uid' => $request->uid,
                                'dato' => trim($request->dato)
                ]);  
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El Contacto ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el Contacto',400,null);
        }

        if (!$contactos) 
            return $this->returnEstatus('Error al crear el Contacto',500,null); 
        return $this->returnData('contactos',$contactos,200);   
    }

    public function show($uid,$idParentesco,$idTipoContacto){
        try {
            $contactos = Contacto::where('idParentesco', $idParentesco)
                                ->where('idTipoContacto',$idTipoContacto)
                                ->where('uid', $uid);

            return $this->returnData('$contactos',$contactos,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Contacto no encontrado',404,null); 
        }
    }
    
    public function destroy($uid,$idParentesco,$idTipoContacto,$consecutivo){
        $contactos = Contacto::where('idParentesco', $idParentesco)
                              ->where('idTipoContacto',$idTipoContacto)
                              ->where('uid', $uid)
                              ->where('consecutivo',$consecutivo); 
        $contactos->delete();

        if (!$contactos) 
            return $this->returnEstatus('Contacto no encontrado',404,null);  
        return $this->returnEstatus('Contacto eliminado',200,null); 
    }

    public function obtenerDatos(){
        return DB::table('contacto as contacto')
                       ->select(
                               'p.uid',
                               'nombre',
                               'primerApellido',
                               'segundoApellido',
                               'parentesco.descripcion as descripcionP',
                               'tc.descripcion as tcontacto',
                               'dato'
                              )
                             ->join('persona as p', 'p.uid', '=', 'contacto.uid')
                             ->join('parentesco as parentesco', 'parentesco.idParentesco', '=', 'contacto.idParentesco')
                             ->join('tipoContacto as tc', 'tc.idTipoContacto', '=', 'contacto.idTipoContacto')                                      
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
                                          ['uid','nombre','primerApellido','segundoApellido','descripcionP','tcontacto','dato'], // Claves
                                          'CONTACTOS', // Título del reporte
                                          ['UID','NOMBRE', 'APELLIDO PATERNO', 'APELLIDO MATERNO','PARENTESCO','TIPO DE CONTACTO','DATO'], 'L','letter',// Encabezados   ,
                                          'rptContactos.pdf'
    );
} 
        
  public function exportaExcel() {  
           // Ruta del archivo a almacenar en el disco público
           $path = storage_path('app/public/contactos_rpt.xlsx');
           $selectColumns = ['p.uid',
                               'nombre',
                               'primerApellido',
                               'segundoApellido',
                               'parentesco.descripcion as descripcionP',
                               'tc.descripcion as tcontacto',
                               'dato']; 
           $namesColumns = ['UID','NOMBRE', 'APELLIDO PATERNO', 'APELLIDO MATERNO','PARENTESCO','TIPO DE CONTACTO','DATO']; // Seleccionar columnas específicas
           $joins = [[ 'table' => 'persona as p', // Tabla a unir
                       'first' => 'p.uid', // Columna de la tabla principal
                       'second' => 'contacto.uid', // Columna de la tabla unida
                       'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                    ],
                    [ 'table' => 'parentesco as parentesco', // Tabla a unir
                       'first' => 'parentesco.idParentesco', // Columna de la tabla principal
                       'second' => 'contacto.idParentesco', // Columna de la tabla unida
                       'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                    ],
                    [ 'table' => 'tipoContacto as tc', // Tabla a unir
                       'first' => 'tc.idTipoContacto', // Columna de la tabla principal
                       'second' => 'contacto.idTipoContacto', // Columna de la tabla unida
                       'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                    ]                
                ];

           $export = new GenericTableExportEsp('contacto', 'uid', [], ['p.uid'], ['asc'], $selectColumns, $joins,$namesColumns);

           // Guardar el archivo en el disco público  
           Excel::store($export, 'contactos_rpt.xlsx', 'public');
       
           // Verifica si el archivo existe usando Storage de Laravel
           if (file_exists($path))  {
               return response()->json([
                   'status' => 200,  
                   'message' => 'https://reportes.pruebas.com.mx/storage/app/public/contactos_rpt.xlsx' // URL pública para descargar el archivo
               ]);
           } else {
               return response()->json([
                   'status' => 500,
                   'message' => 'Error al generar el reporte '
               ]);
           }  
   }

}
