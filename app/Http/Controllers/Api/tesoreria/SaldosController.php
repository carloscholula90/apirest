<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;  
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;  
use App\Http\Controllers\Api\serviciosGenerales\GenericExport;

class SaldosController extends Controller{

    protected $pdfController;

    // Inyección de la clase PdfReportGenerator
    public function __construct(pdfController $pdfController)
    {
        $this->pdfController = $pdfController;
    }

    public function consulta($idNivel,$activo){

        $periodo = DB::table('periodo')
                       ->select('idPeriodo')
                       ->where('activo', 1)
                       ->where('idNivel', $idNivel)
                       ->first();

                       //Log::info('idPeriodo :'.$periodo->idPeriodo);
        $select = ['alumno.uid','car.descripcion as carerra','ciclos.grupo',
            DB::raw("consultaSaldo(alumno.uid, alumno.matricula, ".$periodo->idPeriodo.") AS saldo")
        ];
        // Solo agregamos servicios si NO está activo
        if ($activo == 0) 
            $select[] = DB::raw("consultaSaldo2(alumno.uid, alumno.matricula, ".$periodo->idPeriodo.") AS servicios");
        
        $selectFinal = ['persona.uid','cons.carerra',
                     DB::raw("CONCAT(persona.primerApellido, ' ', persona.segundoApellido, '  ', persona.nombre) AS nombre"),
                    'cons.saldo'
        ];
        // Solo agregamos servicios si NO está activo
        if ($activo == 0) 
            $selectFinal[] = 'cons.servicios';        

        $subQuery = DB::table('alumno')
                ->select($select)
                ->where('alumno.idNivel', $idNivel)

                ->join('carrera as car', function ($join) {
                    $join->on('car.idNivel', '=', 'alumno.idNivel')
                        ->on('car.idCarrera', '=', 'alumno.idCarrera');
                })

                ->leftJoin('ciclos', function ($join) use ($periodo) {
                    $join->on('ciclos.uid', '=', 'alumno.uid')
                        ->on('ciclos.secuencia', '=', 'alumno.secuencia')
                        ->where('ciclos.idPeriodo', '=', $periodo->idPeriodo);
                });


        $query = DB::query()
                ->fromSub($subQuery, 'cons')               
                ->join('persona', 'persona.uid', '=', 'cons.uid')                
                ->select($selectFinal)
                ->where(function ($q) use ($activo) {
                    // Siempre aplicar saldo
                    $q->where('cons.saldo', '>', 0);
                    // SOLO si servicios existe
                    if ($activo == 0) {
                        $q->orWhere('cons.servicios', '=', 0);
                    }
                })
                ->get();
        
          // Convertir los datos a un formato de arreglo asociativo
            $dataArray = $query->map(function ($item) {
            return (array) $item;
            })->toArray();   

        return $dataArray;
     }


     // Función para generar el reporte de personas
    public function generaReporte($idNivel){

       $config = DB::table('configuracion')
                    ->where('id_campo', 1)
                    ->first();

       $activo = $config->valor ?? 0;

       $dataArray= $this->consulta($idNivel,$activo);
         
       $headers = ['UID', 'NOMBRE', 'CARRERA', 'GRUPO','ADEUDO'];
       $columnWidths = [80, 200, 200, 100,100];
       $keys = ['uid', 'nombre', 'carerra', 'grupo','saldo'];

        if ($activo == 0) {
            $headers[] = 'ADEUDO SERVICIOS';
            $columnWidths[] = 100;
            $keys[] = 'servicios';
        }  
        return $this->pdfController->generateReport($dataArray,$columnWidths,$keys , 'REPORTE DE ADEUDOS', $headers,'L','letter','rptAdeudos.pdf');
     }  

     public function exportaExcel($idNivel) {
        $config = DB::table('configuracion')
                    ->where('id_campo', 1)
                    ->first();

        $activo = $config->valor ?? 0;

        $dataArray= $this->consulta($idNivel,$activo);
         
        $headers = ['UID', 'NOMBRE', 'CARRERA', 'GRUPO','ADEUDO'];
        $keys = ['uid', 'nombre', 'carerra', 'grupo','saldo'];

        if ($activo == 0) {
            $headers[] = 'ADEUDO SERVICIOS';            
            $keys[] = 'servicios';
        }  
        // Guardar el archivo en el disco público
        $path = storage_path('app/public/rptAdeudos.xlsx');
        Excel::store(new GenericExport($dataArray, $headers, $keys),'rptAdeudos.xlsx',  'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/rptAdeudos.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }
     }
}
