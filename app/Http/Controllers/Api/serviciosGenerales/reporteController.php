<?php
namespace App\Http\Controllers\Api\serviciosGenerales;
use Illuminate\Http\Request;
use PHPJasper\PHPJasper;   
use App\Http\Controllers\Controller;    
use Illuminate\Support\Facades\Log;

class reporteController extends Controller{ 

    public function generateReport(Request $request){
     
        $jasper = new PHPJasper;
        try {       
           
                $validatedData = $request->validate(['report_path' => 'required|string',
                                                    'params' => 'nullable|array',
                                                    'name_report'=>'required|string',   
                                                    'jsonFilePath' => 'required|string',
                                                    'format' => 'nullable|string']);
                                                     
        } catch (ValidationException $e) {          
             return response()->json(['errors' => $e->errors()], 422);
        }
       
        $input = resource_path('reportes/'.$validatedData['report_path'].'/'.$validatedData['name_report']);
        $output = '/home1/magentad/siaweb.com.mx/temp/personas/' . pathinfo($validatedData['report_path'], PATHINFO_FILENAME);
        
        $options = ['format' => [$validatedData['format'] ?? 'pdf'],
                    'params' => array_merge($validatedData['params'] ?? [], [ 
                        'REPORT_IMAGE' => public_path('images\logo.png'),
                        'REPORT_ENC' => public_path('images\encPag.png'),
                        'REPORT_DTL' => public_path('images\piePag.png')
                    ]),    
                    'locale' => 'es', // Configurar el locale para español
                    'charset' => 'UTF-8'  ,
                    'db_connection' => [
                                    'data_file' => $validatedData['jsonFilePath'], 
                                    'json_query' => 'data', 
                                    'driver' => 'json'  
                                ]
                    ];
    
        $jasper->process($input, $output, $options)->execute();        

        $response = [
                    "message" => "Reporte generado exitosamente ",
                    "file_path" => $output.'.'.$options['format'][0]
                    ];        
        return response()->json($response);
    }
}