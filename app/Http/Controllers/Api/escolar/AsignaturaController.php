<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Asignatura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Illuminate\Support\Facades\DB;  
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;

class AsignaturaController extends Controller{

    public function index(){       
        $asignaturas = Asignatura::all();
        return $this->returnData('asignaturas',$asignaturas,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Asignatura::max('idAsignatura');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $asignaturas = Asignatura::create([
                            'idAsignatura' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('La asignatura ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar la asignatura',400,null);
        }

        if (!$asignaturas) 
            return $this->returnEstatus('Error al crear la asignatura',500,null); 
        return $this->returnData('asignaturas',$asignaturas,200);   
    }

    public function show($idAsignatura){
        try {
            $asignaturas = Asignatura::findOrFail($idAsignatura);
            return $this->returnData('asignaturas',$asignaturas,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Asignatura no encontrada',404,null); 
        }
    }
    
    public function destroy($idAsignatura){
        $asignatura = Asignatura::find($idAsignatura);

        if (!$asignatura) 
            return $this->returnEstatus('Asignatura no encontrada',404,null);             
        try {
            $asignatura->delete();
            return $this->returnEstatus('Asignatura eliminada',200,null); 
        } catch (QueryException $e) {
        if ($e->getCode() == '23000') {
            // Este es el código de error para integridad referencial
            return $this->returnEstatus('No se puede eliminar la asignatura, esta siendo utilizada ya en un plan de estudios',400,null); 
        } 
        }    
    }

    public function update(Request $request, $idAsignatura){

        $asignatura = Asignatura::find($idAsignatura);
        
        if (!$asignatura) 
            return $this->returnEstatus('Asignatura no encontrada',404,null);             

        $validator = Validator::make($request->all(), [
                    'idAsignatura' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $asignatura->idAsignatura = $request->idAsignatura;
        $asignatura->descripcion = strtoupper(trim($request->descripcion));
        $asignatura->save();
        return $this->returnData('Asignatura',$asignatura,200);
    }

    public function updatePartial(Request $request, $idAsignatura){

        $asignatura = Asignatura::find($idAsignatura);
        
        if (!$asignatura) 
            return $this->returnEstatus('Asignatura no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idAsignatura' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idAsignatura')) 
            $asignatura->idAsignatura = $request->idAsignatura;        

        if ($request->has('descripcion')) 
            $asignatura->descripcion = strtoupper(trim($request->descripcion));        

        $asignatura->save();
        return $this->returnEstatus('Asignatura actualizado',200,null);    
    }

     
    public function generaReporte()
    {
       $data= DB::table('asignatura')
                            ->select(
                                    'idAsignatura',
                                    'descripcion',
                                    DB::raw("DATE_FORMAT(fechaAlta, '%d/%m/%Y') as fechaAlta"),
                                    DB::raw("DATE_FORMAT(fechaModificacion, '%d/%m/%Y') as fechaModificacion")
                                    )
                            ->orderBy('descripcion', 'asc')
                            ->get();

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
                                               [150,300,100,200], // Anchos de columna
                                               ['idAsignatura','descripcion','fechaAlta','fechaModificacion'], // Claves
                                               'CATÁLOGO DE ASIGNATURA', // Título del reporte
                                               ['CVE ASIGNATURA','DESCRIPCIÓN','FECHA ALTA','FECHA MODIFICACIÓN'], 'L','letter',// Encabezados   ,
                                               'rptAsignaturas.pdf'
         );
    } 

    public function exportaExcel() {
         // Ruta del archivo a almacenar en el disco público
        $path = storage_path('app/public/asignatura.xlsx');
        $selectColumns = ['idAsignatura',
                                    'descripcion',
                                    DB::raw("DATE_FORMAT(fechaAlta, '%d/%m/%Y') as fechaAlta"),
                                    DB::raw("DATE_FORMAT(fechaModificacion, '%d/%m/%Y') as fechaModificacion")  ];
        $namesColumns = ['CVE ASIGNATURA','DESCRIPCIÓN','FECHA ALTA','FECHA MODIFICACIÓN']; // Seleccionar columnas específicas
       

        $export = new GenericTableExportEsp('asignatura', 'descripcion', [], ['descripcion'], ['asc'], $selectColumns,[],$namesColumns);

        // Guardar el archivo en el disco público
        Excel::store($export, 'asignatura.xlsx', 'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/asignatura.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }  
   }   
}
