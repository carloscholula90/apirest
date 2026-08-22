<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;


class PerfilesPersonaController extends Controller
{

    protected $pdfController;

    // Inyección de la clase PdfReportGenerator
    public function __construct(pdfController $pdfController)
    {
        $this->pdfController = $pdfController;
    }

    public function index()
    {
        $perfilesPersona= DB::table('integra')
                        ->select('integra.uid',
                                  DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre'),
                                 'perfil.descripcion as perfil')
                        ->join('perfil', 'integra.idPerfil', '=', 'perfil.idPerfil')
                        ->join('persona', 'persona.uid', '=', 'integra.uid')                        
                        ->get(); 
        return $this->returnData('perfilesPersona',$perfilesPersona,200);
    }

    public function destroy($uid)
    {
        $actualiza = DB::table('integra')
                        ->where('uid', $uid)
                         ->update(['idPerfil' => null]);

        return $this->returnEstatus('Perfil eliminado',200,null); 
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                            'idPerfil' => 'required|max:255'

        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $actualiza = DB::table('integra')
                        ->where('uid', $request->uid)
                        ->update(['idPerfil' => $request->idPerfil]);
        
        if ($actualiza === 0) 
            return $this->returnEstatus('No se encontró el perfil o no hubo cambios', 404, null);
                        
        return $this->returnEstatus('Perfil actualizado',200,null); 
    }

    public function update($uid,$idPerfil)
    {
        $actualiza = DB::table('integra')
                        ->where('uid', $uid)  
                        ->update(['idPerfil' => $idPerfil]);
        
        if ($actualiza === 0) 
            return $this->returnEstatus('No se encontró el perfil o no hubo cambios', 404, null);
                        
        return $this->returnEstatus('Perfil actualizado',200,null); 
    }
    // Función para generar el reporte de personas
    public function generaReporte()
    {
        $perfilesPersona= DB::table('integra')
                                ->select('integra.uid',
                                            DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre'),
                                            'perfil.descripcion as perfil')
                                ->join('perfil', 'integra.idPerfil', '=', 'perfil.idPerfil')
                                ->join('persona', 'persona.uid', '=', 'integra.uid')                        
                                ->get(); 
   
       // Si no hay personas, devolver un mensaje de error
       if ($perfilesPersona->isEmpty())
           return $this->returnEstatus('No se encontraron datos para generar el reporte',404,null);
       
       $headers = ['UID', 'NOMBRE', 'PERFIL'];
       $columnWidths = [80,200,100];   
       $keys = ['uid','nombre','perfil'];

       $perfilesPersonasArray = $perfilesPersona->map(function ($perfil) {
           return (array) $perfil; // Convierte stdClass a array
       })->toArray();

       return $this->pdfController->generateReport($perfilesPersonasArray,$columnWidths,$keys , 'PERFIL POR PERSONA', $headers,'L','letter','rptPersonaPerfil'.mt_rand(1, 100).'.pdf');
     
   }  
   
   public function exportaExcel() {  
       // Ruta del archivo a almacenar en el disco público
       $path = storage_path('app/public/rptPersonaPerfil.xlsx');
       $selectColumns = ['integra.uid',
                          DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre'),
                         'perfil.descripcion']; 
       $namesColumns = ['UID', 'NOMBRE', 'PERFIL'];
       $joins = [[ 'table' => 'perfil', // Tabla a unir
                   'first' => 'perfil.idPerfil', // Columna de la tabla principal
                   'second' => 'integra.idPerfil', // Columna de la tabla unida
                   'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ],
                [ 'table' => 'persona', // Tabla a unir
                   'first' => 'persona.uid', // Columna de la tabla principal
                   'second' => 'integra.uid', // Columna de la tabla unida
                   'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ]             
            ];

       $export = new GenericTableExportEsp('integra',null, [], ['integra.uid'], ['asc'], $selectColumns, $joins,$namesColumns);

       // Guardar el archivo en el disco público  
       Excel::store($export, 'rptPersonaPerfil.xlsx', 'public');
   
       // Verifica si el archivo existe usando Storage de Laravel
       if (file_exists($path))  {
           return response()->json([
               'status' => 200,  
               'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/rptPersonaPerfil.xlsx' // URL pública para descargar el archivo
           ]);
       } else {
           return response()->json([
               'status' => 500,
               'message' => 'Error al generar el reporte '
           ]);
       }  
}
}
