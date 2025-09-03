<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Carrera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class CarreraController extends Controller
{
    public function index()
    {
        $carreras = Carrera::all();
        return $this->returnData('carreras',$carreras,200);        
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                        'descripcion' => 'required|max:255',
                        'letra' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
        
         $maxIdCarrera = Carrera::max('idCarrera');
         $newIdCarrera = $maxIdCarrera ? $maxIdCarrera + 1 : 1;

         $carreras = Carrera::create([
                                    'idCarrera' => $newIdCarrera,
                                    'descripcion' => strtoupper(trim($request->descripcion)),
                                    'letra'=> strtoupper(trim($request->letra)),
                                    'diaInicioCargo'=>$request->diaInicioCargo
        ]);

        if (!$carreras) 
            return $this->returnEstatus('Error al crear la carrera',500,null);         
        
        $carreras = Carrera::find($newIdCarrera);
        return $this->returnData('carreras',$carreras,200);   
    }

    public function show($idCarrera)
    {
        $carreras = Carrera::find($idCarrera);

        if (!$carreras) 
            return $this->returnEstatus('Carrera no encontrada',400,null);  

        return $this->returnData('carreras',$carreras,200);
    }

    public function destroy($idCarrera)
    {
        $carreras = Carrera::find($idCarrera);

        if (!$carreras) 
            return $this->returnEstatus('Carrera no encontrada',400,null);  
        try {

        $carreras->delete();
        return $this->returnEstatus('Carrera eliminada',200,null);  
        } catch (QueryException $e) {
        if ($e->getCode() == '23000') {
            // Este es el código de error para integridad referencial
            return $this->returnEstatus('No se puede eliminar esta carrera porque está siendo utilizada por otros registros (como alumnos).',400,null); 
        }  
    }        
    }

    public function updatePartial(Request $request, $idCarrera)
    {
        if ($idCarrera==null) 
            return $this->returnEstatus('Agregue un Id de carrera válido',400,null); 
               
        $carreras = Carrera::find($idCarrera);

        if (!$carreras) 
            return $this->returnEstatus('Carrera no encontrado',400,null); 
        
        $carreras->idCarrera = $idCarrera;        

        if ($request->has('descripcion')) 
            $carreras->descripcion = $request->descripcion;
   
        if ($request->has('letra')) 
            $carreras->letra = $request->letra;

        if ($request->has('diaInicioCargo')) 
            $carreras->diaInicioCargo = $request->diaInicioCargo;
 
          $carreras->save();
        return $this->returnEstatus('Carrera actualizada',200,null); 
    }

    public function obtenerDatos(){
        return DB::table('carrera as c')
                            ->select(
                                    'niv.descripcion as NivelAcad',
                                    'c.idCarrera',
                                    'c.descripcion as carrera',
                                    'c.diaInicioCargo',
                                    'c.diaInicioRecargo',
                                    DB::raw('CASE WHEN c.activo = 1 THEN "S" ELSE "N" END as activo'))
                                            ->join('nivel as niv', 'niv.idNivel', '=', 'c.idNivel')
                                            ->orderBy('c.descripcion', 'asc')
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
                                               [100,100,300,100,100,100], // Anchos de columna
                                               ['NivelAcad','idCarrera','carrera','diaInicioCargo','diaInicioRecargo','activo'], // Claves
                                               'CATÁLOGO DE CARRERAS', // Título del reporte
                                               ['NIVEL','ID CARRERA','DESCRIPCIÓN','DIA INICIO DE CARGO','DIA INICIO DE RECARGO','ACTIVO'], 'L','letter',// Encabezados   ,
                                               'rptCarreras.pdf'
         );
    } 
      
    public function exportaExcel() {  
        // Ruta del archivo a almacenar en el disco público
        $path = storage_path('app/public/carrera_rpt.xlsx');
        $selectColumns = ['nivel.descripcion AS nivelDescripcion', 'carrera.idCarrera', 'carrera.descripcion','carrera.diaInicioCargo','carrera.diaInicioRecargo', DB::raw('CASE WHEN carrera.activo = 1 THEN "S" ELSE "N" END as activo')]; // Seleccionar columnas específicas
        $namesColumns = ['NIVEL', 'ID-- CARRERA', 'CARRERA','DIA INICIO CARGO','DIA INICIO RECARGO','ACT']; // Seleccionar columnas específicas
        
        $joins = [[ 'table' => 'nivel', // Tabla a unir
                    'first' => 'carrera.idNivel', // Columna de la tabla principal
                    'second' => 'nivel.idNivel', // Columna de la tabla unida
                    'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                 ]];

        $export = new GenericTableExportEsp('carrera', 'descripcion', [], ['carrera.descripcion'], ['asc'], $selectColumns, $joins,$namesColumns);

        // Guardar el archivo en el disco público
        Excel::store($export, 'carrera_rpt.xlsx', 'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/carrera_rpt.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }  
    }
    
}
