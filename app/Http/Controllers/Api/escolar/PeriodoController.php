<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Periodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;

class PeriodoController extends Controller{

    public function index(){       
        $periodos = Periodo::all();
        return $this->returnData('periodos',$periodos,200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                                        'idNivel' =>'required|numeric|max:255',
                                        'idPeriodo' =>'required|numeric|max:255',
                                        'descripcion' => 'required|max:255',
                                        'activo' =>'required|numeric|max:255',
                                        'inscripciones' =>'required|numeric|max:255',
                                        'fechaInicio' =>'required|date',
                                        'fechaTermino' =>'required|date',
                                        'inmediato' =>'required|numeric|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
    
        try {
            $periodos = Periodo::create([
                            'idPeriodo' => $request->idPeriodo,
                            'idNivel' => $request->idNivel,
                            'descripcion' => strtoupper(trim($request->descripcion)),
                            'activo' => $request->activo,
                            'inscripciones' => $request->inscripciones,
                            'fechaInicio' => $request->fechaInicio,
                            'fechaTermino' => $request->fechaTermino,
                            'inmediato' => $request->inmediato
                        ]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') 
                return $this->returnEstatus('El Periodo ya se encuentra dado de alta',400,null);
            return $this->returnEstatus('Error al insertar el Periodo',400,null);
        }  

        if (!$periodos) 
            return $this->returnEstatus('Error al crear el Periodo',500,null); 
        return $this->returnData('$periodos',$periodos,201);   
    }

    public function show($idPeriodo,$idNivel){
        try {
            $periodos = Periodo::find($idNivel, $idPeriodo);
            return $this->returnData('$periodos',$periodos,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Periodo no encontrado',404,null); 
        }
    }
    
    public function destroy($idPeriodo,$idNivel){
        $periodos = Periodo::find($idNivel, $idPeriodo);
          
        if (!$periodos) 
            return $this->returnEstatus('Periodo no encontrado',404,null);   

        $deletedRows = Periodo::where('idNivel', $idNivel)
                       ->where('idPeriodo', $idPeriodo)
                       ->delete();

        return $this->returnEstatus('Periodo eliminado',200,null); 
    }

    public function update(Request $request, $idPeriodo, $idNivel){

        $periodos = Periodo::find($idNivel, $idPeriodo);
        
        if (!$periodos)      
            return $this->returnEstatus('Periodo no encontrado periodo ',404,null);             

        $validator = Validator::make($request->all(), [  
                                'descripcion' => 'required|max:255',
                                'activo' =>'required|numeric|max:255',
                                'inscripciones' =>'required|numeric|max:255',
                                'fechaInicio' =>'required|date',
                                'fechaTermino' =>'required|date',
                                'inmediato' =>'required|numeric|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $periodos->idPeriodo = $idPeriodo;
        $periodos->idNivel = $idNivel;    
        $periodos->descripcion = strtoupper(trim($request->descripcion));
        $periodos->activo = $request->activo;
        $periodos->inscripciones = $request->inscripciones;
        $periodos->fechaInicio = $request->fechaInicio;
        $periodos->fechaTermino = $request->fechaTermino;
        $periodos->inmediato = $request->inmediato;

        $periodos->save();
        return $this->returnData('periodo',$periodos,200);
    }

    public function updatePartial(Request $request, $idPeriodo,$idNivel){

        $periodos = Periodo::find($idNivel, $idPeriodo);
        
        if (!$periodos) 
            return $this->returnEstatus('Periodo no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idNivel' =>'required|numeric|max:255',
                                    'idPeriodo' =>'required|numeric|max:255',
                                    'descripcion' => 'required|max:255',
                                    'activo' =>'required|numeric|max:255',
                                    'inscripciones' =>'required|numeric|max:255',
                                    'fechaInicio' =>'required|date',
                                    'fechaTermino' =>'required|date',
                                    'inmediato' =>'required|numeric|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idPeriodo')) 
            $periodos->idPeriodo = $request->idPeriodo;        

        if ($request->has('descripcion')) 
            $periodos->descripcion = strtoupper(trim($request->descripcion));        

        $periodos->save();
        return $this->returnEstatus('Periodo actualizado',200,null);    
    }

    public function obtenerDatos(){
        return DB::table('periodo as per')
                            ->select(
                                'per.idPeriodo',
                                'per.descripcion',
                                'per.fechaInicio',
                                'per.fechaTermino',
                                'niv.descripcion as nivel',
                                DB::raw('CASE WHEN per.activo = 1 THEN "S" ELSE "N" END as activo'),
                                DB::raw('CASE WHEN per.inmediato = 1 THEN "S" ELSE "N" END as inmediato'),
                                DB::raw('CASE WHEN per.inscripciones = 1 THEN "S" ELSE "N" END as inscripciones')
                            )
                            ->join('nivel as niv', 'niv.idNivel', '=', 'per.idNivel')
                            ->orderBy('per.idNivel', 'asc')
                            ->orderBy('per.idPeriodo', 'asc')
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
                                          [100,80,100,100,100,80,80,100], // Anchos de columna
                                          ['nivel','idPeriodo','descripcion','fechaInicio','fechaTermino','activo','inmediato','inscripciones'], // Claves
                                          'CATÁLOGO DE PERIODOS', // Título del reporte
                                          ['NIVEL','ID PERIODO', 'DESCRIPCION', 'FECHA INICIO','FECHA FIN','ACTIVO','INMEDIATO','INSCRIPCIONES'], 'L','letter',// Encabezados   ,
                                          'rptPeriodos.pdf'
                                        );
    } 
        
  public function exportaExcel() {  
           // Ruta del archivo a almacenar en el disco público
           $path = storage_path('app/public/periodos_rpt.xlsx');
           $selectColumns = [   'nivel.descripcion as nivel',
                                'periodo.idPeriodo',
                                'periodo.descripcion',
                                'periodo.fechaInicio',
                                'periodo.fechaTermino',                                
                                DB::raw('CASE WHEN activo = 1 THEN "SI" ELSE "NO" END as activo'),
                                DB::raw('CASE WHEN inmediato = 1 THEN "SI" ELSE "NO" END as inmediato'),
                                DB::raw('CASE WHEN inscripciones = 1 THEN "SI" ELSE "NO" END as inscripciones')
            ]; 
           $namesColumns = ['NIVEL','ID PERIODO', 'DESCRIPCION', 'FECHA INICIO','FECHA FIN','ACTIVO','INMEDIATO','INSCRIPCIONES']; // Seleccionar columnas específicas
           
           $joins = [[ 'table' => 'nivel', // Tabla a unir
                       'first' => 'nivel.idNivel', // Columna de la tabla principal
                       'second' => 'periodo.idNivel', // Columna de la tabla unida
                       'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                   ]];    

           $export = new GenericTableExportEsp('periodo', 'nivel.idNivel', [], ['nivel.idNivel','periodo.idPeriodo'], ['asc','desc'], $selectColumns, $joins,$namesColumns);

           // Guardar el archivo en el disco público  
           Excel::store($export, 'periodos_rpt.xlsx', 'public');
       
           // Verifica si el archivo existe usando Storage de Laravel
           if (file_exists($path))  {
               return response()->json([
                   'status' => 200,  
                   'message' => 'https://reportes.siaweb.com.mx/storage/periodos_rpt.xlsx' // URL pública para descargar el archivo
               ]);
           } else {
               return response()->json([
                   'status' => 500,
                   'message' => 'Error al generar el reporte '
               ]);
           }  
   }
}
