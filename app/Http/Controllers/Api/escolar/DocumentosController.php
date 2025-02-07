<?php
namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDSFormat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
  
class DocumentosController extends Controller{


    public function generaAdeudoDoctos(){
        $orientation='P';
        $size='letter';
        $nameReport='adeudoDoctos'.'_'.mt_rand(100, 999).'.pdf';

        $pdf = new CustomTCPDSFormat($orientation, PDF_UNIT, $size, true, 'UTF-8', false);       
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');          
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(30, 10, 15); // Margenes 
        $pdf->SetAutoPageBreak(FALSE, 0);
        $pdf->AddPage();
        $imageUrl = 'https://pruebas.siaweb.com.mx/images/logos/logoSEP1617.png';
        $pdf->Image($imageUrl, 150, 10, 35);
            // Generar la tabla HTML para los datos
        $html = '<table border="0" cellpadding="1" style="font-family: Arial; font-size: 10pt;line-height: 1.5;">  
            <tr>
                <td style="width: 8cm; height: 2.5cm;"></td>               
                <td style="width: 5cm; "></td>
                <td></td>
            </tr>
            <tr>
                <td style="width: 8cm; height: 2.5cm; font-family: Arial; font-size: 8pt;">UAECE-ofnot-29-23<br>VERSIÓN 2</td>               
                <td style="width: 5cm; "></td>
                <td></td>
            </tr>
            <tr>
                <td style="width: 5cm; height: 1.5cm;"></td>                  
                <td style="width: 16.5cm;" colspan="2">HEROICA PUEBLA DE ZARAGOZA A -- DE -------- DEL ----</td>
            </tr>
            <tr>
                <td style="width: 5cm; height: 2cm;"></td>               
                <td style="width: 3cm; "></td>
                <td style="width: 13.5cm;">ASUNTO: <b>ADEUDO DE DOCUMENTOS</b></td>
            </tr>
            
            <tr>
                <td style="width: 14.5cm;" colspan="3">
                <p style="text-align: justify;">
                POR MEDIO DE LA PRESENTE SE LE INFORMA AL C. ----- ALUMNO/A DE LA UNIVERSIDAD ALVA EDISON 
                DE LA ---- DE ------- CON MATRÍCULA ---- SE NOTIFICO EL CIRCULAR ESTUDIANTIL CON NÚMERO DE
                OFICIO ---- CON LA FECHA --- SE LE INFORMÓ DE LA DOCUMENTACIÓN REQUERIDA DE LOS CUALES NO HA ENTREGADO:
                CURP, ACTA DE NACIMIENTO, CERTIFICADO DE BACHILLER ORIGINAL LEGALIZADO, COPIA INE Y EXÁMEN 
                MEDICO. OTORGANDOLE COMO FECHA LÍMITE HASTA EL ---- DE ---, PARA NO CAUSAR BAJA EN NUESTRO SISTEMA Y PODER DARLO
                DE ALTA EN SEP. SERIA LA SEGUNDA NOTIFICACIÓN , POR LO CUAL VOLVEMOS A SOLICITAR EL DOCUMENTO ORIGINAL PARA SU VALIDACIÓN
                DE SUS ESTUDIOS SIN CAUSAR LA VIOLACIÓN DE CICLOS ESCOLARES, SI HACE CASO OMISO DE ESTÁ
                LA UNIVERSIDAD SE DESLINDA DE CUALQUIEN INCONVENIENTE EN SUS ESTUDIOS Y POR LO MISMO CAUSA DEFINITIVA.
                <br><br><br>SIN MÁS POR EL MOMENTO.<br><br>
                </p></td>
            </tr>
             <tr>
                <td style="width: 5cm; height: 5.5cm;"></td>               
                <td style="font-size: 12pt"><b>A T E N T A M E N T E<br><br><br><br>CONTROL ESCOLAR</b></td>
                <td></td>
            </tr>
             <tr>
                <td style="width: 10cm; height: 3cm;" colspan="2"></td>            
                <td style="font-size: 10pt; text-align: center;"><b>NOMBRE ALUMNO <br>FIRMA DE ENTERADA(O)</b></td>
            </tr>';
        $html .= '</table>';  

        
        // Escribir la tabla en el PDF
        $pdf->writeHTML($html, true, false, true, false, '');
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

    public function obtenerAlumnos(){
        $resultsB = DB::table('grupos as g')
                        ->select(
                            'g.idNivel as NivelAcad',
                            'g.idPeriodo as ciclo',
                            'g.idAsignatura as ClaveAsig',
                            'a.descripcion as NomAsig',
                            'g.grupo as grupo',
                            'g.idModalidad as Modalidad',
                            'p.nombre as nombreProf',
                            'p.primerApellido as PrimerApellidoProf',
                            'p.segundoApellido as SegundoApellidoProf',
                            'Nal.nombre as NombreAl',
                            'Nal.primerApellido as PrimerApellidoAl',
                            'Nal.segundoApellido as SegundoapellidoAl',
                            'c.Parcial1 as Parcial1',
                            'c.Parcial2 as Parcial2',
                            'c.Parcial3 as Parcial3',
                            'c.CF as califFinal',
                            'g.idAsignatura as idAsignatura',
                            'e.descripcion as TipoExamen',
                            'Nsecre.nombre as Nombresecre',
                            'Nsecre.primerApellido as PrimerApellidosecre',
                            'Nsecre.segundoApellido as Segundoapellidosecre',
                            'Npresi.nombre as NombrePresi',
                            'Npresi.primerApellido as PrimerApellidoPresi',
                            'Npresi.segundoApellido as SegundoapellidoPresi',
                            'NSup.nombre as NombreSup',
                            'NSup.primerApellido as PrimerApellidoSup',
                            'g.grupo as grupo',
                            'NSup.segundoApellido as Segundoapellidosup',
                            'plan.rvoe as rvoe',
                            DB::raw('CONLETRA(c.CF) as califConLetra'))
                                ->join('asignatura as a', 'a.idAsignatura', '=', 'g.idAsignatura')
                                ->join('calificaciones as c', 'c.grupoSec', '=', 'g.grupoSec')
                                ->join('tipoExamen as e', 'e.idExamen', '=', 'c.idExamen')
                                ->join('ciclos as cl', 'cl.indexCiclo', '=', 'c.indexCiclo')
                                ->join('alumno as al', function ($join) {
                                    $join->on('al.uid', '=', 'cl.uid')
                                        ->where('al.secuencia', '=', 'cl.secuencia');
                                })
                                ->join('plan as plan', function ($join) {
                                    $join->on('plan.idPlan', '=', 'al.idPlan')
                                        ->where('plan.idNivel', '=', 'al.idNivel')
                                        ->where('plan.idCarrera', '=', 'al.idCarrera');
                                })
                                ->join('detasignatura as det', function ($join) {
                                    $join->on('al.idPlan', '=', 'det.idPlan')
                                        ->where('al.idCarrera', '=', 'det.idCarrera')
                                        ->where('det.idAsignatura', '=', 'g.idAsignatura');
                                })
                                ->join('persona as Nal', 'Nal.uid', '=', 'cl.uid')
                                ->leftJoin('persona as p', 'p.uid', '=', 'g.uidProfesor')
                                ->join('persona as Nsecre', 'Nsecre.uid', '=', 'g.uidSecretario')
                                ->join('persona as Npresi', 'Npresi.uid', '=', 'g.uidPresidente')
                                ->join('persona as NSup', 'NSup.uid', '=', 'g.uidSupervisor')
                                ->where('g.idNivel', 5)
                                ->where('g.idPeriodo', 100)
                                ->where('g.idAsignatura', 'UAE04.V')
                                ->where('g.grupo', '06S5A')
                                ->get();
            
            if ($resultsB->isEmpty())
                return $resultsB;   
        
                $results = $resultsB->map(function ($item) {
                        return (array) $item; // Convertir cada stdClass a un arreglo
            })->toArray(); 
        return $results;
    }
     
    public function generaActa(){

        $results = $this->obtenerAlumnos();
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
        $imageUrl = 'https://pruebas.siaweb.com.mx/images/logos/logoSEP05.png';
        $pdf->Image($imageUrl, 10, 5, 50);

        if (!empty($results)) {
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
                <td style="height: 2.5cm; font-size: 8pt;" colspan="2">CARRERA:<br>MODALIDAD EDUCATIVA: '.$results[0]['TipoExamen'].'<br>EXAMEN: '.$results[0]['TipoExamen'].'<br>ASIGNATURA: '.$results[0]['idAsignatura'].'<br>DOCENTE DE LA ASIGNATURA: '
                            .$results[0]['nombreProf'].' '.$results[0]['PrimerApellidoProf'].' '.$results[0]['SegundoApellidoProf'].' '.'</td>
                <td style="font-family: Arial; font-size: 8pt; vertical-align: middle;">
                                                        RVOE: '.$results[0]['rvoe'].'<br>
                                                        FECHA: '.'<br>
                                                        CICLO ESCOLAR: '.$results[0]['grupo'].'<br>
                                                        SEMESTRE: '.'<br>
                                                        GRUPO: '.$results[0]['grupo'].'</td>
            </tr>
            <tr>   
                <td style="height: 2cm; font-size: 10pt;" colspan="3">El dia de '.$results[0]['grupo'].' de '.$results[0]['grupo'].' a las '.$results[0]['grupo'].' horas, se reunio el H. Jurado del Examen y procedio a efectuar las pruebas correspondientes, sustentadas por '.$results[0]['grupo'].' alumnos obteniendo cada uno de ellos, la calificacion que a continuacion se asienta.</td>
            </tr>';        
        $html2 .= '</table>';
        $html2 .= '<table border="0.5" cellpadding="0" style="font-size: 8pt; vertical-align: middle; text-align: center; line-height: .5cm;">  
            <tr>
                <td style="height: .5cm; width: 1cm;" rowspan="2">N/P</td>
                <td style="width: 8.1cm;" rowspan="2">Apellido parterno, Apellido materno y Nombre(s)</td>
                <td style="width: 4.2cm;" colspan="2">CALIFICACIÓN</td>
                <td style="width: 5.7cm;" rowspan="2">OBSERVACIONES</td>    
            </tr>
            <tr>    
                <td style="height: .5cm; width: 2.1cm; text-align: center; vertical-align: middle;">NUMERO</td>
                <td style="height: .5cm; width: 2.1cm; text-align: center; vertical-align: middle;">LETRA</td>      
            </tr>
        ';
        $indexAct=1;
        $indexAprobado=0;
        $indexReprobado=0;

        foreach ($results as $index2 => $row) {     
            $html2 .= '<tr><td>'.$indexAct.'</td><td style="text-align: left;">'.' '.$row['PrimerApellidoAl'].' '.$row['SegundoapellidoAl'].' '.$row['NombreAl'].'</td><td>'.$row['califFinal'].'</td><td>'.$row['califConLetra'].'</td><td></td></tr>';
            $indexAct++;
            
            if($row['califFinal']>=7)
                $indexAprobado++;
            else $indexReprobado++;
        }

        for ($i = $indexAct; $i <= 27; $i++) 
        $html2 .= '<tr><td>'.$i.'</td><td></td><td></td><td></td><td></td></tr>';

        $html2 .= '</table>';

        $html2 .= '<br><p style="font-size: 10pt;">Esta acta autoriza '.($indexAprobado+$indexReprobado).' sustentantes, con un total de '.$indexAprobado.' alumnos aprobados y '.$indexReprobado.' no aprobados.<br>';
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
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'No existe el acta'
            ]);
        }   
    }

    public function generatePaseLista(){

        $orientation='P';
        $size='letter';
        $nameReport='paseLista'.'_'.mt_rand(100, 999).'.pdf';
        $results = $this->obtenerAlumnos();
       
        // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)
        $pdf = new CustomTCPDSFormat($orientation, PDF_UNIT, $size, true, 'UTF-8', false);       
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');          
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(30, 20, 16); // Margenes 
        $pdf->SetAutoPageBreak(FALSE, 0);
        $pdf->AddPage();

        if (!empty($results)) {

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

        $html2 .= '<table border="0.2" cellpadding="0" style="font-size: 5.5pt; vertical-align: middle; text-align: center; line-height: .5cm;">  
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
        
        foreach ($results as $index2 => $row) {
            $html2 .= '<tr><td style="height: .25cm;">'.$index2.'</td><td></td><td style="text-align: left;">'.' '.$row['PrimerApellidoAl'].' '.$row['SegundoapellidoAl'].' '.$row['NombreAl'].
                        '</td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td>
                           <td></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>';
                    }
        $index2++;
        for ($i = $index2; $i <= 27; $i++) 
        $html2 .= '<tr><td style="height: .25cm;">'.$i.'</td><td style="text-align: left;">
                    </td><td></td><td></td><td></td>
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
    else {
        return response()->json([
            'status' => 500,
            'message' => 'No hay informacion para mostrar el grupo'
        ]);
       }   
    }


    public function cuadroIncripcion(){

        $results = $this->obtenerAlumnos();
        $orientation='L';
        $size='Legal';
        $nameReport='cuadroInscripcion'.'_'.mt_rand(100, 999).'.pdf';
        // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)
        $pdf = new CustomTCPDSFormat($orientation, PDF_UNIT, array(216, 330), true, 'UTF-8', false);       
        $pdf->SetCreator(PDF_CREATOR);   
        $pdf->SetAuthor('SIAWEB');          
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(10, 4, 16); // Margenes 
        $pdf->SetAutoPageBreak(FALSE, 0);
        $pdf->AddPage();
        $imageUrl = 'https://pruebas.siaweb.com.mx/images/logos/logoSEP05.png';
        $pdf->Image($imageUrl, 10, 5, 50);

        if (!empty($results)) {
                // Generar la tabla HTML para los datos
                $html2 = '<table border="0" cellpadding="1">  
                    <tr>
                        <td style="width: 5cm; height: 2cm;"></td>               
                        <td style="width: 22cm; font-family: Arial; font-size: 8pt; font-weight: bold; text-align: center; vertical-align: middle;">
                                                            SECRETARÍA DE EDUCACIÓN DEL ESTADO<br>
                                                    SUBSECRETARÍA DE EDUCACIÓN SUPERIOR<br>
                                                    DIRECCION DE EDUCACIÓN SUPERIOR PARTICULAR<br>
                                                    <b>UNIVERSIDAD ALVA EDISON</b><br>
                                                    <b>CUADRO DE INSCRIPCIÓN</b></td>
                <td></td>    
            </tr>';
        $html2 .= '</table><br><br>';
        $html2 .= '<table border="0.5" cellpadding="0" style="font-size: 8pt; vertical-align: middle; line-height: .5cm;">  
            <tr>
                <td style="height: .5cm; width: 24.2cm;" colspan="4"> NOMBRE DE LA CARRERA</td>
                <td style="width: 5.9cm;"> CLAVE:<b>21MSU1022U</b></td>
            </tr>
            <tr>    
                <td style="height: .5cm; width: 12cm;"> GRADO:</td>
                <td style="width: 3.5cm;"> GRUPO:</td>  
                <td style="width: 4.7cm;"> ZONA:</td>  
                <td style="width: 4cm;"> TURNO:</td>  
                <td> CICLO ESCOLAR:</td>      
            </tr>
            <tr>    
                <td style="height: .5cm; width: 12cm;"> MUNICIPIO: PUEBLA</td>
                <td style="width: 6.5cm;" colspan="2"> MODALIDAD:</td>  
                <td style="width: 5.7cm;"> RVOES:</td>    
                <td> FECHA:</td>       
            </tr>
        ';
        $html2 .= '</table><br><br>';
        $html2 .= '<table border="0.5" cellpadding="0" style="font-size: 7pt; vertical-align: middle; line-height: .5cm; text-align: center;">  
            <tr>    
                <td style="height: 1.2cm; width: .9cm; vertical-align: middle;"> NP</td>
                <td style="width: 3.9cm; vertical-align: middle;"> CURP</td>  
                <td style="width: 7.2cm; vertical-align: middle;"> APELLIDO PATERNO, APELLIDO MATERNO, NOMBRE (S) </td>  
                <td style="width: 1.7cm; vertical-align: middle;"> ACTA DE NACIMIENTO</td> 
                <td style="width: 1.7cm; vertical-align: middle;"> CURP</td>  
                <td style="width: 4.6cm; vertical-align: middle;"> CERTIFICADO DE ESTUDIOS DEL NIVEL INMEDIATO ANTERIOR</td> 
                <td style="width: 2.1cm; vertical-align: middle;"> EQUIVALENCIA DE ESTUDIOS</td> 
                <td style="width: 2cm; vertical-align: middle;"> REVALIDACIÓN DE ESTUDIOS</td> 
                <td style="width: 6cm; vertical-align: middle;"> OBSERVACIONES</td> 
        </tr>';

        for ($i =1; $i <= 19; $i++) 
        $html2 .= '<tr><td>'.$i.'</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';

        $html2 .= '</table>';
        $html2 .= '<p style="font-size: 10pt; text-align: right;">PUEBLA , PUEBLA A ___________ DE ______________________ DE _________             </p>';
        $html2 .= '<br><br><br>';
        $html2 .= '<table border="0" style="font-size: 8pt; text-align: center; vertical-align: middle;">';
        $html2 .= ' <tr>
                    <td style="height: 1cm;"></td>
                    </tr>
                    <tr>
                    <td style="width: 9.4cm; text-align: center;">RESPONSABLE DE CONTROL ESCOLAR</td>
                    <td style="width: 9.4cm; text-align: center;">RECTOR DE LA ESCUELA</td>
                    <td style="text-align: center;">SUPERVISORA DE LA ZONA 021</td>
                    </tr>
                    <tr>
                    <td style="height: 2cm;"></td>
                    </tr>
                    <tr>
                    <td style="height: 1.2cm; width:9.4cm; text-align: center;">
                        <span style="border-top: .5px solid black; padding-top: 2px;">LAURA RODRIGUEZ TAPIA</span>
                    </td>
                    <td style="width:9.4cm; text-align: center;">
                        <span style="border-top: .5px solid black; padding-top: 2px;">JOSE LEÓN VAZQUEZ</span>
                    </td>
                    <td style="text-align: center;">
                       <span style="border-top: .5px solid black; padding-top: 2px;">MARIA DEL CARMEN LOBATO MIÑÓN</span>
                     </td>
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
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'No existe el acta'
            ]);
        }   
    }

    public function solicituDesfase(){
        $orientation='P';
        $size='letter';
        $nameReport='solicitudDesfase'.'_'.mt_rand(100, 999).'.pdf';

        $pdf = new CustomTCPDSFormat($orientation, PDF_UNIT, $size, true, 'UTF-8', false);       
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');          
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(30, 10, 30); // Margenes 
        $pdf->SetAutoPageBreak(FALSE, 0);
        $pdf->AddPage();
        $imageUrl = 'https://pruebas.siaweb.com.mx/images/logos/logoSEP1617.png';
        $pdf->Image($imageUrl, 150, 10, 35);
            // Generar la tabla HTML para los datos
        $html = '<table border="0" cellpadding="1" style="font-family: Arial; font-size: 10pt;line-height: 1.5;">  
            <tr>
                <td colspan="3">
                <p style="text-align: right; line-height: 2;">  
                <b>Heroica Puebla de Zaragoza a -- de -------- de ----<br>Asunto: </b>Solicitud de desfase</p>
                </td>
            </tr>
            <tr>
                <td style="width: 5cm; height: 2cm;"></td>               
                <td style="width: 3cm; "></td>
                <td style="width: 15.5cm;"></td>
            </tr>
             <tr>
                <td style="height: 2cm;" colspan="3"><b>Jorge León Vázquez<br>Rector de Universidad Alva Edison</b>
                </td>               
            </tr>
            
            <tr>
                <td style="width: 14.5cm;" colspan="3">
                <p style="text-align: justify; line-height: 2;">
                Por medio de la presente reciba un cordial saludo, al mismo tiempo, le solicito de la manera más
                atenta me permita continuar estudiando, ya que por fecha de examen (es) extraordinario (s) incurro en
                violación de ciclos escolares, debido a ello la Dirección de Profesiones no admitirá mi expediente para 
                titulación a la conclusión de mis estudios universitarios. </p>
                <p style="text-align: justify; line-height: 2; text-indent: 20px;">
                    Por ello me comprometo a esperar al periodo de un año para mi inscripción ante la Dirección de Control
                Escolar de la Secretaría de Educación en el ciclo escolar -------, siendo consciente de que el termino
                de mi licenciatura será durante el ciclo escolar ----- si soy alumno regular ( No haberme dado de baja
                temporal ni haber reprobado alguna materia).
                <br><br>
                    Una vez concluida el programa de estudios y validado ante la Secretaría de Educación, llevaré a cabo mi
                tramite de titulación el cual tiene un periodo de entraga de diez a nueve meses.
                <br><br>
                    Nota: Me comprometo a asistir a todas las clases y obtener calificaciones aprobatorias, en caso de que no
                aprobar las materias durante el ciclo escolar ------, no se tendrá información que reportar y tendre que 
                reinscribirme a primer semestre durante el ciclo ------------- sin ninguna responsabilidad para la Universidad.
                <br></p>
                <br>Nombre:   ____________________________________
                <br>Matrícula:____________________________________
                <br>Grupo:    ____________________________________
                <br>Número de celular ____________________________ 
                </td>
                </tr>
                <tr>
                    <td colspan="3" style="height: 2cm;"></td>
                </tr>
                <tr>
                    <td style="width: 10cm;" colspan="2">Firma de conformidad Alumno</td>
                    <td>Firma de conformidad tutor</td>
                </tr>

            ';
        $html .= '</table>';  

        
        // Escribir la tabla en el PDF
        $pdf->writeHTML($html, true, false, true, false, '');
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
