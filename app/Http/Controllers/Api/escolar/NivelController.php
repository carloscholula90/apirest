<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Nivel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;
use Illuminate\Support\Facades\DB;

class NivelController extends Controller
{
    public function index()
    {
        $niveles = Nivel::all();

        $data = [
            'niveles' => $niveles,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
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

        $maxIdNivel = Nivel::max('idNivel');
        $newIdNivel = $maxIdNivel ? $maxIdNivel + 1 : 1;
        try{
            $niveles = Nivel::create([
                            'idNivel' => $newIdNivel,
                            'descripcion' => $request->descripcion
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El nivel ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el nivel',400,null);
        }

        if (!$niveles) {
            $data = [
                'message' => 'Error al crear el nivel',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        
        $niveles = Nivel::find($newIdNivel);

        $data = [
            'niveles' => $niveles,
            'status' => 200
        ];

        return response()->json($data, 200);

    }

    public function show($idNivel)
    {
        $niveles = Nivel::find($idNivel);

        if (!$niveles) {
            $data = [
                'message' => 'Nivel no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'niveles' => $niveles,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function destroy($idNivel)
    {
        $niveles = Nivel::find($idNivel);

        if (!$niveles) {
            $data = [
                'message' => 'Nivel no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

         try {
             $niveles->delete();
            return $this->returnEstatus('Nivel eliminada',200,null);  
        } catch (QueryException $e) {
        if ($e->getCode() == '23000') {
            // Este es el código de error para integridad referencial
            return $this->returnEstatus('No se puede eliminar el nivel ya esta siendo utilizado',400,null); 
        } 
        }
    }

    public function update(Request $request, $idNivel)
    {
        $niveles = Nivel::find($idNivel);

        if (!$niveles) {
            $data = [
                'message' => 'Nivel no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'idNivel' => 'required|numeric|max:255',
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

        $niveles->idNivel = $request->idNivel;
        $niveles->descripcion = $request->descripcion;

        $niveles->save();

        $data = [
            'message' => 'Nivel actualizado',
            'carreras' => $niveles,
            'status' => 200
        ];

        return response()->json($data, 200);

    }

    public function updatePartial(Request $request, $idNivel)
    {
        $niveles = Nivel::find($idNivel);

        if (!$niveles) {
            $data = [
                'message' => 'Nivel no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'idNivel' => 'required|numeric|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        if ($request->has('idNivel')) {
            $niveles->idNivel = $request->idNivel;
        }

        if ($request->has('descripcion')) {
            $niveles->descripcion = $request->descripcion;
        }

        $niveles->save();

        $data = [
            'message' => 'Nivel actualizado',
            'carreras' => $niveles,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function obtenerDatos(){
        return DB::table('nivel')
                            ->select(
                                    'nivel.descripcion',
                                    'nivel.idNivel',
                                    DB::raw('CASE WHEN activo = 1 THEN "S" ELSE "N" END as activo'))
                                            ->orderBy('nivel.descripcion', 'asc')
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
                                               [100,300,100], // Anchos de columna
                                               ['idNivel','descripcion','activo'], // Claves
                                               'CATÁLOGO DE NIVELES', // Título del reporte
                                               ['NIVEL','DESCRIPCIÓN','ACTIVO'], 'L','letter',// Encabezados   ,
                                               'rptNiveles'.mt_rand(1, 100).'.pdf'
         );
    } 
      
    public function exportaExcel() {  
        // Ruta del archivo a almacenar en el disco público
        $path = storage_path('app/public/niveles_rpt.xlsx');
        $selectColumns =  ['idNivel','descripcion','activo']; // Seleccionar columnas específicas
        $namesColumns = ['NIVEL','DESCRIPCIÓN','ACTIVO']; // Seleccionar columnas específicas
        
        $joins = [];

        $export = new GenericTableExportEsp('nivel', 'descripcion', [], ['nivel.descripcion'], ['asc'], $selectColumns, $joins,$namesColumns);

        // Guardar el archivo en el disco público
        Excel::store($export, 'niveles_rpt.xlsx', 'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.pruebas.com.mx/storage/app/public/niveles_rpt.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }  
    }
}
