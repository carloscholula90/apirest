<?php
namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDSFormat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
  
class PaseListaController extends Controller
{

    public function generaReporte()
     {
       
        return $this->generateReport('P','letter','paseLista'.'_'.mt_rand(100, 999).'.pdf');
      
    }

    public function generateReport(string $orientation, string $size ,string $nameReport)
    {
        // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)
        $pdf = new CustomTCPDSFormat($orientation, PDF_UNIT, $size, true, 'UTF-8', false);       
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');          
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(30, 20, 16); // Margenes 
        $pdf->SetAutoPageBreak(FALSE, 0);
        $pdf->AddPage();

         // Generar la tabla HTML para los datos
        $html2 = '<table border="0" cellpadding="1" style ="font-family: Arial; font-size: 9pt; font-weight: bold; text-align: center; vertical-align: middle;">    
            <tr>
                <td style="width: 2cm;"></td>               
                <td style="width: 12cm;">
                                                    UNIVERSIDAD ALVA EDISON<br>
                                                    DIRECCIÓN DE LICENCIATURAS-CLAVE SEP 21MSU1022U
                                                    </td>
                <td></td>    
            </tr>
            <tr>  
                <td style ="height: 1.2cm;"></td>                             
                <td style ="height: 1.2cm; font-family: Arial; font-size: 7pt; font-weight: bold; text-align: center; vertical-align: middle;">LISTA DE EVALUACIÓN Y ASISTENCIAS PARCIALES</td>  
                <td></td>            
            </tr>
            <tr>
                <td style="height: .6cm; width: 9cm; font-family: Arial; font-size: 7pt; font-weight: bold; text-align: left; vertical-align: middle;">ASIGNATURA O MATERIA:</td>               
                <td style="height: .6cm; width: 3cm; font-family: Arial; font-size: 7pt; font-weight: bold; text-align: left; vertical-align: middle;">GRUPO:</td>
                <td style="height: .6cm; width: 5cm; font-family: Arial; font-size: 7pt; font-weight: bold; text-align: left; vertical-align: middle;">SEMESTRE:</td>
            </tr>
            <tr>
                <td style="height: .6cm; width: 7cm; font-family: Arial; font-size: 7pt; font-weight: bold; text-align: left; vertical-align: middle;">CARRERA:</td>               
                <td style="height: .6cm; width: 3cm; font-family: Arial; font-size: 7pt; font-weight: bold; text-align: left; vertical-align: middle;">TURNO:</td>
                <td style="height: .6cm; width: 5cm; font-family: Arial; font-size: 7pt; font-weight: bold; text-align: left; vertical-align: middle;">No HRS.:</td>
            </tr>';         
            $html2 .= '</table><br><br>';

        $html2 .= '<table border="0.3" cellpadding="0" style="font-size: 6pt; vertical-align: middle; text-align: center; line-height: .5cm;">  
            <tr>
                <td style="height: .5cm; width: .4cm;" rowspan="2">No</td>
                <td style="width: 1.4cm;" rowspan="2">MATRICULA</td>
                <td style="width: 4cm;" rowspan="2">NOMBRE DEL ALUMNO</td>
                <td style="width: 6cm;" colspan="30">ASISTENCIAS</td>
                <td style="width: 1cm;" rowspan="2">FALTAS</td> 
                <td style="width: 3cm;" colspan="2">CALIFICACION</td>    
            </tr>
            <tr>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>

                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>

                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="height: .5cm; width: .2cm;"></td>
                <td style="width: 1cm;">No</td>
                <td style="width: 2cm;">LETRA</td>    
            </tr>';
        
        for ($i = 1; $i <= 30; $i++) 
            $html2 .= '<tr><td style="height: .25cm;">'.$i.'</td><td></td><td></td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>';
          
        $html2 .= '</table>';

        
        // Escribir la tabla en el PDF
        $pdf->writeHTML($html2, true, false, true, false, '');
        $filePath = storage_path('app/public/'.$nameReport);  // Ruta donde se guardará el archivo
       
        $pdf->Output($filePath, 'F');  // 'F' para guardar el archivo en el servidor
    
        // Ahora puedes verificar si el archivo se ha guardado correctamente en la ruta especificada.
        if (file_exists($filePath)) {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/'.$nameReport // Puedes devolver la ruta para fines de depuración
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte'
            ]);
        }    
    }
}
