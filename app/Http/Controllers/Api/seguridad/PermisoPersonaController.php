<?php
namespace App\Http\Controllers\Api\seguridad; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\seguridad\PermisoPersona;
use Illuminate\Support\Facades\DB;   
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;

class PermisoPersonaController extends Controller
{  
    /**
     * Display a listing of the resource.
     */
     protected $pdfController;

     // Inyección de la clase PdfReportGenerator
     public function __construct(pdfController $pdfController)
     {
         $this->pdfController = $pdfController;
     }

    public function index()
    {
       $permisos = DB::table('integra')
                    ->select('integra.uid',
                                DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre'),
                                'aplicaciones.descripcion as aplicacion','integra.secuencia','permisosPersona.idAplicacion')
                    ->join('permisosPersona', 'permisosPersona.uid', '=', 'integra.uid')
                    ->join('persona', 'persona.uid', '=', 'integra.uid')   
                    ->join('aplicaciones', 'aplicaciones.idAplicacion', '=', 'permisosPersona.idAplicacion')                      
                    ->get();
       
       DB::table('permisosPersona')
                        ->get();
        return $this->returnData('Permisos',$permisos,200);  
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required|max:255',
            'idAplicacion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
        return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxSeq = PermisoPersona::where('uid', $request->uid)->max('secuencia');        
        $nextSeq = ($maxSeq === null) ? 1 : $maxSeq + 1;

        $create= PermisoPersona::create([
                                    'uid' => $request->uid,
                                    'secuencia' => $nextSeq,
                                    'idAplicacion' => $request->idAplicacion 
                                ]);

        if ($create === 0) 
            return $this->returnEstatus('Error en la insercion', 404, null);
                
        return $this->returnEstatus('Registro creado',200,null); 
    }

    /**
     * Display the specified resource.
     */
    public function show($id,$idRol)
    {
          $permisos = DB::table('aplicacionesUsuario')
                        ->where('uid', $id)
                        ->where('idRol', $idRol)
                        ->get();

          if (!$permisos)
            return $this->returnEstatus('Sin aplicaciones en el rol. Favor de validar',400,null); 
          return $this->returnData('Permisos',$permisos,200);  
    }

       /**
     * Remove the specified resource from storage.
     */
    public function destroy($uid,$secuencia)
    {
        $destroy = DB::table('permisosPersona')
                            ->where('uid', $uid  )
                            ->where('secuencia', $secuencia)
                            ->delete();

        if ($destroy === 0) 
            return $this->returnEstatus('Error en la eliminacion', 404, null);
                
        return $this->returnEstatus('Registro eliminado',200,null); 
    }

    // Función para generar el reporte de personas
    public function generaReporte()
    {
        $permisos = DB::table('integra')
                            ->select('integra.uid',
                                        DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre'),
                                        'aplicaciones.descripcion as aplicacion')
                            ->join('permisosPersona', 'permisosPersona.uid', '=', 'integra.uid')
                            ->join('persona', 'persona.uid', '=', 'integra.uid')   
                            ->join('aplicaciones', 'aplicaciones.idAplicacion', '=', 'permisosPersona.idAplicacion')                      
                            ->get();
   
       // Si no hay personas, devolver un mensaje de error
       if ($permisos->isEmpty())
           return $this->returnEstatus('No se encontraron datos para generar el reporte',404,null);
       
       $headers = ['UID', 'NOMBRE', 'APLICACION'];
       $columnWidths = [80,300,100];   
       $keys = ['uid','nombre','aplicacion'];

       $aplicacionesArray = $permisos->map(function ($aplicacion) {
           return (array) $aplicacion; // Convierte stdClass a array
       })->toArray();

       return $this->pdfController->generateReport($aplicacionesArray,$columnWidths,$keys , 'APLICACIONES POR PERSONA', $headers,'L','letter','rptPermisosPersona'.mt_rand(1, 100).'.pdf');
     
   }  
   
   public function exportaExcel() {  
       // Ruta del archivo a almacenar en el disco público
       $path = storage_path('app/public/rptPermisosPersona.xlsx');
       $selectColumns = ['integra.uid',
                          DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre'),
                          'aplicaciones.descripcion as aplicacion']; 
       $namesColumns = ['UID', 'NOMBRE', 'APLICACION'];
       $joins = [[ 'table' => 'permisosPersona', // Tabla a unir
                   'first' => 'permisosPersona.uid', // Columna de la tabla principal
                   'second' => 'integra.uid', // Columna de la tabla unida
                   'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ],
                [ 'table' => 'persona', // Tabla a unir
                   'first' => 'persona.uid', // Columna de la tabla principal
                   'second' => 'integra.uid', // Columna de la tabla unida
                   'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ],
                [ 'table' => 'aplicaciones', // Tabla a unir
                   'first' => 'aplicaciones.idAplicacion', // Columna de la tabla principal
                   'second' => 'permisosPersona.idAplicacion', // Columna de la tabla unida
                   'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ]           
            ];

       $export = new GenericTableExportEsp('integra',null, [], ['integra.uid'], ['asc'], $selectColumns, $joins,$namesColumns);

       // Guardar el archivo en el disco público  
       Excel::store($export, 'rptPermisosPersona.xlsx', 'public');
   
       // Verifica si el archivo existe usando Storage de Laravel
       if (file_exists($path))  {
           return response()->json([
               'status' => 200,  
               'message' => 'https://reportes.pruebas.com.mx/storage/app/public/rptPermisosPersona.xlsx' // URL pública para descargar el archivo
           ]);
       } else {
           return response()->json([
               'status' => 500,
               'message' => 'Error al generar el reporte '
           ]);
       }  
}
}
