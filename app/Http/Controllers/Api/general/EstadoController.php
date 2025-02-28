<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Estado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;


class EstadoController extends Controller{
    public function index(){     
        
         $estados = Estado::join('pais', 'estado.idPais', '=', 'pais.idPais')
                                    ->select( 'pais.idPais',
                                            'pais.descripcion as paisDescripcion',
                                            'estado.idEstado',
                                            'estado.descripcion as estadoDescripcion'
                                    )
                                    ->get();

        $data = [
            'estados' => $estados,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                                'idPais' => 'required|numeric|max:255',
                                'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                        'message' => 'Error en la validación de los datos',
                        'errors' => $validator->errors(),
                        'status' => 400
            ];
            return response()->json($data, 400);
        }

        $maxId = Estado::max($request->idPais);
        $newId = $maxId ? $maxId + 1 : 1;
        $estados = Estado::create([
                    'idPais' => $request->idPais,
                    'idEstado'=> $newId,
                    'descripcion' => $request->descripcion
        ]);
        
        if (!$estados) {
            $data = [
                'message' => 'Error al crear el estado',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        
        $data = [
            'estados' => $estados,
            'status' => 200
        ];
        return response()->json($data, 200);

    }

    public function show($idPais,$idEstado){
        try {
            // Busca el  por ID y lanza una excepción si no se encuentra
            $estados = Estado::join('pais', 'estado.idPais', '=', 'pais.idPais')
                                    ->select( 'pais.idPais',
                                            'pais.descripcion as paisDescripcion',
                                            'estado.idEstado',
                                            'estado.descripcion as estadoDescripcion'
                                    )
                                    ->where('estado.idPais', '=', $idPais)
                                    ->where('estado.idEstado', '=', $idEstado)
                                    ->get();
    
            // Retorna el  con estado 200
            $data = [
                'estados' => $estados,
                'status' => 200
            ];
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el  no se encuentra, retorna un mensaje de error con estado 404
            $data = [
                'message' => 'Estado no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    }
    
    public function destroy($idPais,$idEstado){
        $estados = Estado::find($idPais,$idEstado);

        if (!$estados) {
            $data = [
                'message' => 'Estado no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $estados->delete();

        $data = [
            'message' => 'Estado eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request){
      
        $estados = Estado::find($request->idPais,$request->idEstado);
        if (!$estados) {
            $data = [
                'message' => 'Estado no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
       
        $validator = Validator::make($request->all(), [
                                'idPais' => 'required|numeric|max:255',
                                'idEstado' => 'required|numeric|max:255',                                
                                'descripcion' => 'required|max:255'
        ]);   

        if ($validator->fails()) {
            $data = [
                    'message' => 'Error en la validación de los datos',
                    'errors' => $validator->errors(),
                    'status' => 400
            ];
            return response()->json($data, 400);
        }
        \Log::info('Datos de usuario procesados 1');

        $estados = Estado::where('idPais', $request->idPais)
                 ->where('idEstado', $request->idEstado)
                 ->first();
                 \Log::info('Datos de usuario procesados 2');

        if ($estados) {
            $estados->descripcion = $request->descripcion;
            $estados->save();
            $data = [
                'message' => 'Estado actualizado',
                'estados' => $estados,
                'status' => 200
            ];
        return response()->json($data, 200);
        } else {
            return response()->json(['error' => 'Estado no encontrado'], 404);
        }  
    }

    public function obtenerDatos(){
        return DB::table('estado as est')
                            ->select(
                                'est.idEstado',
                                'est.descripcion',
                                'pais.descripcion as pais')
                            ->join('pais as pais', 'pais.idPais', '=', 'est.idPais')
                            ->orderBy('pais.descripcion', 'asc')
                            ->orderBy('est.descripcion', 'asc')
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
                                          [100,100,150], // Anchos de columna
                                          ['pais','idEstado','descripcion'], // Claves
                                          'CATÁLOGO DE ESTADOS', // Título del reporte
                                          ['PAIS','CVE ESTADO', 'DESCRIPCION'], 'L','letter',// Encabezados   ,
                                          'rptEstados.pdf');
    }   
        
    public function exportaExcel() {  
           // Ruta del archivo a almacenar en el disco público
           $path = storage_path('app/public/estado_rpt.xlsx');
           $selectColumns = [  
                                'pais.descripcion as pais',
                                'est.idEstado',
                                'est.descripcion'                                
                            ]; 
           $namesColumns =  ['PAIS','CVE ESTADO', 'DESCRIPCION']; // Seleccionar columnas específicas
           
           $joins = [[ 'table' => 'pais', // Tabla a unir
                       'first' => 'pais.idPais', // Columna de la tabla principal
                       'second' => 'est.idPais', // Columna de la tabla unida
                       'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                   ]];    

           $export = new GenericTableExportEsp('estado as est', 'est.idEstado', [], ['pais.descripcion','est.descripcion'], ['asc','asc'], $selectColumns, $joins,$namesColumns);

           // Guardar el archivo en el disco público  
           Excel::store($export, 'estado_rpt.xlsx', 'public');
       
           // Verifica si el archivo existe usando Storage de Laravel
           if (file_exists($path))  {
               return response()->json([
                   'status' => 200,  
                   'message' => 'https://reportes.siaweb.com.mx/storage/app/public/estado_rpt.xlsx' // URL pública para descargar el archivo
               ]);
           } else {
               return response()->json([
                   'status' => 500,
                   'message' => 'Error al generar el reporte '
               ]);
           }  
   }
}