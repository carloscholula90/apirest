<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use App\Models\seguridad\PerfilAplicaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;

class PerfilAplicacionController extends Controller
{

    protected $pdfController;

    // Inyección de la clase PdfReportGenerator
    public function __construct(pdfController $pdfController)
    {
        $this->pdfController = $pdfController;
    }

    public function index(){
       return DB::table('perfilAplicaciones as perfilApl')
                    ->select('perfilApl.idPerfil',
                             'perfilApl.idAplicacion',
                             'perfil.descripcion as perfil',
                             'aplicaciones.descripcion as aplicacion')
                    ->join('perfil', 'perfilApl.idPerfil', '=', 'perfil.idPerfil')
                    ->join('aplicaciones', 'perfilApl.idAplicacion', '=', 'aplicaciones.idAplicacion')
                    ->orderBy('perfilApl.idPerfil', 'asc')
                    ->orderBy('perfilApl.idAplicacion', 'asc')
                    ->get(); 
    }

    public function index2(){
        return DB::table('integra')
                     ->select(  
                            'perfil.idPerfil',
                            'aplicaciones.idAplicacion',
                            'perfil.descripcion as perfil',
                            'aplicaciones.descripcion as aplicacion',
                            DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre')
                                
                     )
                     ->join('persona', 'persona.uid', '=', 'integra.uid')
                     ->join('perfilAplicaciones', 'perfilAplicaciones.idPerfil', '=', 'integra.idPerfil')
                     ->join('perfil', 'integra.idPerfil', '=', 'perfil.idPerfil')
                     ->join('aplicaciones', 'aplicaciones.idAplicacion', '=', 'perfilAplicaciones.idAplicacion')                     
                     ->get(); 
     }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
                    'idAplicacion' => 'required|max:255',
                    'idPerfil' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
       
            $existe = DB::table('perfilAplicaciones as perfilApl')
                                    ->select( 'perfilApl.idPerfil')
                                    ->where('idPerfil', $request->idPerfil)
                                    ->where('idAplicacion', $request->idAplicacion)
                                    ->get(); 
            $cantidad = $existe->count();
            if($cantidad>0)
               return $this->returnEstatus('La aplicacion ya existe en el perfil ',400,null); 

            $perfiles = PerfilAplicaciones::create(['idPerfil' => $request->idPerfil,
                                                    'idAplicacion' => $request->idAplicacion]);  

            if (!$perfiles) 
                return $this->returnEstatus('Error al asignar la aplicacion al perfil',500,null);
            return $this->returnEstatus('El registro se guardo con exito',200,null); 
    }

    public function destroy($idPerfil,$idAplicacion){   
         $perfiles = PerfilAplicaciones::where('idPerfil', $idPerfil)
                  ->where('idAplicacion', $idAplicacion)
                  ->delete();
        return $this->returnEstatus('Aplicacion eliminada del perfil',200,null); 
    }

    public function update(Request $request){
        $perfiles = Perfil::find($request->idPerfil);

        if (!$perfiles) 
            return $this->returnEstatus('Perfil no encontrado',404,null); 

        $validator = Validator::make($request->all(), [
                                'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails())
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $perfiles->idPerfil = $request->idPerfil;
        $perfiles->descripcion = strtoupper(trim($request->descripcion));

        $perfiles->save();
        return $this->returnEstatus('Perfil actualizado',200,null); 

    }

     // Función para generar el reporte de personas
     public function generaReporte()
     {
        $aplicaciones =  DB::table('perfilAplicaciones as perfilApl')
                                    ->select('perfilApl.idPerfil',
                                            'perfilApl.idAplicacion',
                                            'perfil.descripcion as perfil',
                                            'aplicaciones.descripcion as aplicacion')
                                    ->join('perfil', 'perfilApl.idPerfil', '=', 'perfil.idPerfil')
                                    ->join('aplicaciones', 'perfilApl.idAplicacion', '=', 'aplicaciones.idAplicacion')
                                    ->orderBy('perfilApl.idPerfil', 'asc')
                                    ->orderBy('perfilApl.idAplicacion', 'asc')
                                    ->get(); 
    
        // Si no hay personas, devolver un mensaje de error
        if ($aplicaciones->isEmpty())
            return $this->returnEstatus('No se encontraron datos para generar el reporte',404,null);
        
        $headers = ['ID PERFIL', 'PERFIL', 'ID APLICACION', 'APLICACION'];
        $columnWidths = [80,200,100, 200];   
        $keys = ['idPerfil','perfil','idAplicacion','aplicacion'];

        $aplicacionesArray = $aplicaciones->map(function ($aplicacion) {
            return (array) $aplicacion; // Convierte stdClass a array
        })->toArray();

        return $this->pdfController->generateReport($aplicacionesArray,$columnWidths,$keys , 'APLICACIONES POR PERFIL', $headers,'L','letter','rptPermisosPerfil'.mt_rand(1, 100).'.pdf');
      
    }  
    
    public function exportaExcel() {  
        // Ruta del archivo a almacenar en el disco público
        $path = storage_path('app/public/rptPermisosPerfil.xlsx');
        $selectColumns = ['perfilAplicaciones.idPerfil',
                          'perfil.descripcion as perfil',
                          'perfilAplicaciones.idAplicacion',                          
                          'aplicaciones.descripcion as aplicacion']; 
        $namesColumns = ['ID PERFIL', 'PERFIL', 'ID APLICACION', 'APLICACION'];
        $joins = [[ 'table' => 'perfil', // Tabla a unir
                    'first' => 'perfil.idPerfil', // Columna de la tabla principal
                    'second' => 'perfilAplicaciones.idPerfil', // Columna de la tabla unida
                    'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                 ],
                 [ 'table' => 'aplicaciones', // Tabla a unir
                    'first' => 'aplicaciones.idAplicacion', // Columna de la tabla principal
                    'second' => 'perfilAplicaciones.idAplicacion', // Columna de la tabla unida
                    'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                 ]             
             ];

        $export = new GenericTableExportEsp('perfilAplicaciones',null, [], ['perfil.idPerfil','perfilAplicaciones.idAplicacion'], ['asc','asc'], $selectColumns, $joins,$namesColumns);

        // Guardar el archivo en el disco público  
        Excel::store($export, 'rptPermisosPerfil.xlsx', 'public');
    
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/rptPermisosPerfil.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }  
}
}
  