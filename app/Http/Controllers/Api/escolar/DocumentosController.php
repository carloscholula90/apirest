<?php
namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDSFormat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
  
class DocumentosController extends Controller{

    public function generaActa(){
        $orientation='P';
        $size='letter';
        $nameReport='actaDeExamen'.'_'.mt_rand(100, 999).'.pdf';

        // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)
        $pdf = new CustomTCPDSFormat($orientation, PDF_UNIT, $size, true, 'UTF-8', false);       
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');          
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(10, 4, 16); // Margenes 
        $pdf->SetAutoPageBreak(FALSE, 0);
        $pdf->AddPage();

         // Generar la tabla HTML para los datos
        $html2 = '<table border="0" cellpadding="1">  
            <tr>
                <td style="width: 5cm; height: 2cm;"></td>               
                <td style="width: 9cm; font-family: Arial; font-size: 8pt; font-weight: bold; text-align: center; vertical-align: middle;">
                                                    SECRETARÍA DE EDUCACIÓN DEL ESTADO<br>
                                                    SUBSECRETARÍA DE EDUCACIÓN SUPERIOR<br>
                                                    DIRECCION DE EDUCACIÓN SUPERIOR PARTICULAR<br>
                                                    UNIVERSIDAD ALVA EDISON<br>
                                                    21MSU1022U</td>
                <td></td>    
            </tr>
            <tr>
                <td style="height: 1cm;"></td>               
                <td style="font-family: Arial; font-size: 12pt; font-weight: bold; text-align: center; vertical-align: middle;">
                                                    ACTA DE EXAMEN</td>
                <td></td>
            </tr>
            <tr>  
                <td style="height: 2.5cm; font-size: 8pt;" colspan="2">CARRERA:<br>MODALIDAD EDUCATIVA:<br>EXAMEN:<br>ASIGNATURA:<br>DOCENTE DE LA ASIGNATURA:</td>
                <td style="font-family: Arial; font-size: 8pt; vertical-align: middle;">
                                                        RVOE:<br>
                                                        FECHA:<br>
                                                        CICLO ESCOLAR:<br>
                                                        SEMESTRE:<br>
                                                        GRUPO:</td>
            </tr>
            <tr>   
                <td style="height: 2cm; font-size: 10pt;" colspan="3">El dia de -- de -- a las -- horas, se reunio el H. Jurado del Examen y procedio a efectuar las pruebas correspondientes, sustentadas por -- alumnos obteniendo cada uno de ellos, la calificacion que a continuacion se asienta.</td>
            </tr>';        
        $html2 .= '</table>';
        $html2 .= '<table border="0.5" cellpadding="0" style="font-size: 8pt; vertical-align: middle; text-align: center; line-height: .5cm;">  
            <tr>
                <td style="height: .5cm; width: 1cm;" rowspan="2">N/P</td>
                <td style="width: 8.1cm;" rowspan="2">Apellido parterno, Apellido materno y Nombre(s)</td>
                <td style="width: 4.2cm;" colspan="2">CALIFICACION</td>
                <td style="width: 5.7cm;" rowspan="2">OBSERVACIONES</td>    
            </tr>
            <tr>    
                <td style="height: .5cm; width: 2.1cm; text-align: center; vertical-align: middle;">NUMERO</td>
                <td style="height: .5cm; width: 2.1cm; text-align: center; vertical-align: middle;">LETRA</td>      
            </tr>
        ';
        for ($i = 1; $i <= 27; $i++) 
            $html2 .= '<tr><td>'.$i.'</td><td></td><td></td><td></td><td></td></tr>';

        $html2 .= '</table>';

        $html2 .= '<br><p style="font-size: 10pt;">Esta acta autoriza -- sustentantes, con un total de -- alumnos aprobados y -- no aprobados.<br>';
        $html2 .='El acto termino a las -- horas del dia y para constancia firman los miembros del H. Jurado</p>';

        $html2 .= '<table border="0" style="font-size: 8pt; text-align: center; vertical-align: middle;">';
        $html2 .= '<tr>
                    <td style="width: 7cm; height: 1.5cm;"></td>
                    <td style="width: 7cm;"></td>
                    <td></td>
                  </tr>';
        $html2 .= '<tr>
                    <td style="width: 7cm; text-align: center;">
                        <hr style="width: 4cm; border: 1px solid black; margin: 0;">
                    </td>
                    <td style="width: 7cm; text-align: center;">
                        <hr style="width: 4cm; border: 1px solid black; margin: 0;">
                    </td>
                    <td style="text-align: center;">
                        <hr style="width: 4cm; border: 1px solid black; margin: 0;">
                    </td>
                </tr>';
        $html2 .= '<tr>
                    <td style="width: 4cm; text-align: center;">SECRETARIO</td>
                    <td style="width: 9.3cm; text-align: center;">PRESIDENTE</td>
                    <td style="text-align: center;">VOCAL</td>
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

    public function generatePaseLista(){

        $orientation='P';
        $size='letter';
        $nameReport='paseLista'.'_'.mt_rand(100, 999).'.pdf';

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

        $html2 .= '<table border="0.2" cellpadding="0" style="font-size: 6pt; vertical-align: middle; text-align: center; line-height: .5cm;">  
            <tr>
                <td style="height: .5cm; width: .4cm;" rowspan="2">No</td>
                <td style="width: 1.4cm;" rowspan="2">MATRICULA</td>
                <td style="width: 4cm;" rowspan="2">NOMBRE DEL ALUMNO</td>
                <td style="width: 6cm;" colspan="30">ASISTENCIAS</td>
                <td style="width: 1cm;" rowspan="2">FALTAS</td> 
                <td style="width: 3cm;" colspan="2">CALIFICACIÓN</td>    
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
        $html2 .='</table>';
        $html2 .='<table border="0" cellpadding="0" style="font-size: 7pt;"><tr><td style="width: 5.9cm;"></td><td style="width: 6cm;"></td><td>APROVECHAMIENTO</td></tr>';
        $html2 .='<tr><td style="width: 5.9cm;font-size: 6pt;">PARCIAL No.</td><td style="width: 7cm;font-size: 7pt;">No. DE ALUMNOS APROBADOS<br>TOTAL ALUMNOS</td><td>89.87847</td></tr>';
        $html2 .= '</table><br><br>';

        $html2 .='<table border="0.5" cellpadding="0" style="font-size: 6pt;">
                 <tr>
                    <td style="height: 1.5cm; width: 3.975cm;">ELABORÓ:</td>
                    <td style="width: 3.975cm;">REVISÓ:</td>
                    <td rowspan="2" style="width: 3.975cm;">FECHA:<br><br><br></td>
                    <td rowspan="2" style="width: 3.975cm;text-align: center; vertical-align: middle;font-weight: bold;"><br><br><br><br>UAEL01</td>
                 </tr>';
        $html2 .='<tr><td style="height: 0.5cm; width: 3.975cm;">Aqui nombre y firma del Docente</td>
                      <td style="height: 0.5cm; width: 3.975cm;">Prof. Raymundo Hernandez T.</td>
                      </tr></table>';
        
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
