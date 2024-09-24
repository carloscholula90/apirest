<?php
namespace App\Http\Controllers\Api\serviciosGenerales;
use Illuminate\Http\Request;
use PHPJasper\PHPJasper;   
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class reporteController extends Controller{ 

    public function generateReport(Request $request){
        $jasper = new PHPJasper;
        try {
        Log::info('Este es un mensaje de depuración en Laravel. --->1 ', ['request' => $request->all()]);
     
        // Validar y obtener los datos de la solicitud
        $validatedData = $request->validate(['report_path' => 'required|string',
                                            'params' => 'nullable|array',
                                            'data' => 'nullable|array',
                                            'format' => 'nullable|string']);
                                              // Enviar la respuesta en formato JSON

         } catch (ValidationException $e) {
            Log::error('Errores de validación:', $e->errors());
            // Opcionalmente, puedes devolver una respuesta de error
            return response()->json(['errors' => $e->errors()], 422);
        }
    // Ruta al archivo jrxml
        $input = resource_path('reportes/test/'.$validatedData['report_path']);

        // Ruta donde se guardará el archivo generado
        $output = storage_path('app/reportes/test/'.pathinfo($validatedData['report_path'], PATHINFO_FILENAME));
        Log::info('Este es un mensaje de depuración en Laravel. 2 '.$output );
        $fontsPath = 'app/public/fonts/fonts.xml';
        Log::info('Este es un mensaje de depuración en Laravel. 2 '. $fontsPath );
       
        // Configurar opciones
        $options = ['format' => [$validatedData['format'] ?? 'pdf'],
                    'params' => array_merge($validatedData['params'] ?? [], [
                        'REPORT_IMAGE' => public_path('images\logo.png'),
                        'REPORT_ENC' => public_path('images\encPag.png'),
                        'REPORT_DTL' => public_path('images\piePag.png') 
                    ]),
                    'data' => $validatedData['data'] ?? [], // Los datos externos para el reporte
                    'locale' => 'es', // Configurar el locale para español
                    'charset' => 'UTF-8', // Configurar el charset para UTF-8,
                    [
                        'fonts_path' => $fontsPath // Parámetro adicional para especificar la ruta del archivo fonts.xml
                        // otros parámetros si es necesario
                    ]
        ];

        // Procesar el reporte
        $jasper->process($input, $output, $options)->execute() ;
      
        // Crear una respuesta
        $response = [
            "message" => "Reporte generado exitosamente ",
            "file_path" => $output.'.'.$options['format'][0]
        ];
        Log::info('Este es un mensaje de depuración en Laravel. 3 ');
 
        // Enviar la respuesta en formato JSON
        return response()->json($response);
    }
}