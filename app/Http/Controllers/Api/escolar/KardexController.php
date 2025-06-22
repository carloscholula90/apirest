<?php
namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
  
class KardexController extends Controller
{

    public function generaReporte($id,$idNivel,$idCarrera,$tipoKardex){

       if($tipoKardex!='F')
       $results = DB::table('ciclos as cl')
                        ->join('calificaciones as ca', 'ca.indexCiclo', '=', 'cl.indexCiclo')
                        ->join('detasignatura as asig', 'asig.secPlan', '=', 'ca.secPlan')
                        ->join('asignatura as a', 'a.idAsignatura', '=', 'asig.idAsignatura')
                        ->join('alumno', function ($join) {
                                                $join->on('alumno.uid', '=', 'cl.uid')
                                                    ->on('alumno.secuencia', '=', 'cl.secuencia');
                        })
                        ->join('persona as p', 'p.uid', '=', 'alumno.uid')
                        ->join('carrera', function ($join) {
                                        $join->on('carrera.idCarrera', '=', 'alumno.idCarrera')
                                            ->on('carrera.idNivel', '=', 'alumno.idNivel');
                        })
                        ->join('periodo as per', function ($join) {
                                            $join->on('per.idNivel', '=', 'cl.idNivel')
                                                ->on('per.idPeriodo', '=', 'cl.idPeriodo');
                        })
                        ->join('tipoExamen as e', 'e.idExamen', '=', 'ca.idExamen')
                        ->join('nivel as n', 'n.idNivel', '=', 'cl.idNivel')
                        ->select(
                                    'n.descripcion',
                                    'alumno.matricula',
                                    'carrera.descripcion as carrera',
                                    'per.descripcion as periodo',
                                    'alumno.UID as estudiante',
                                    'p.nombre',
                                    'p.primerApellido as apellidopat',
                                    'p.segundoApellido as apellidomat',
                                    'asig.idAsignatura as idAsignatura',
                                    'asig.ordenk',
                                    'a.descripcion as asignatura',
                                    'per.idPeriodo',
                                    'asig.creditos',
                                    'asig.semestre as semestre',
                                    'ca.cf as calificacion',
                                    'e.descripcion as tipo',
                                    DB::raw('CONLETRA(ca.cf) as califConLetra')
                        )
                        ->where('cl.uid', $id)
                        ->where('alumno.idNivel', $idNivel)
                        ->where('alumno.idCarrera', $idCarrera)
                        ->orderBy('asig.ordenk')->get();                        

                       // Si la variable $order es igual a 'C', entonces realizamos el ordenamiento
        else  $results =  DB::table('alumno as al')
                                ->join('detasignatura as det', function ($join) use ($id) {
                                    $join->on('det.idPlan', '=', 'al.idPlan')
                                        ->on('det.idCarrera', '=', 'al.idCarrera');
                                })
                                ->join('persona as pers', 'al.uid', '=', 'pers.uid')
                                ->join('nivel', 'nivel.idNivel', '=', 'al.idNivel')
                                ->join('carrera', 'carrera.idCarrera', '=', 'al.idCarrera')
                                ->join('asignatura as asig', 'asig.idAsignatura', '=', 'det.idAsignatura')
                                ->where('al.uid', $id)
                                ->whereNotIn('det.idAsignatura', function ($query) use ($id, $idNivel, $idCarrera) {
                                    $query->select('grupos.idasignatura')
                                        ->from('alumno as al2')
                                        ->join('ciclos', function ($join) {
                                            $join->on('ciclos.uid', '=', 'al2.uid')
                                                ->on('ciclos.idNivel', '=', 'al2.idNivel');
                                        })
                                        ->join('grupos', function ($join) {
                                            $join->on('grupos.grupo', '=', 'ciclos.grupo')
                                                ->on('grupos.idPeriodo', '=', 'ciclos.idPeriodo');
                                        })
                                        ->join('calificaciones as calif', function ($join) {
                                            $join->on('calif.indexCiclo', '=', 'ciclos.indexCiclo')
                                                ->on('calif.grupoSec', '=', 'grupos.grupoSec');
                                        })
                                        ->where('al2.uid', $id)
                                        ->where('al2.idNivel', $idNivel)
                                        ->where('al2.idCarrera', $idCarrera);
                                        })
                        ->select([
                            'nivel.descripcion',
                            'det.ordenk',
                            'det.creditos',
                            'al.matricula',
                            'pers.nombre',
                            'pers.primerApellido as apellidopat',
                            'pers.segundoApellido as apellidomat',
                            'pers.uid as estudiante',
                            'det.idAsignatura',
                            'asig.descripcion as asignatura',
                            'det.semestre',
                            'al.idPlan',
                            'al.idCarrera',
                            'carrera.descripcion as carrera',
                            'nivel.descripcion as nivel'
                        ])->orderBy('det.semestre')->get();
       
    // Si no hay personas, devolver un mensaje de error
        if ($results->isEmpty())
            return $this->returnEstatus('No existen datos para generar el kardex id '.$id.' idNivel '.$idNivel.' idCarrera '.$idCarrera,404,null);
        
        $headers = ['Clave', 'Asignatura','Créditos','Calif','Letra','Tipo','Periodo'];
        $columnWidths = [40,120,50,50,80,80,80];   
        $keys = ['idAsignatura','asignatura','creditos','calificacion','califConLetra','tipo','periodo'];
       
        if($tipoKardex == 'F'){
                    $headers = ['Clave', 'Asignatura','Créditos','Semestre'];
                    $columnWidths = [40,300,50,80];   
                    $keys = ['idAsignatura','asignatura','creditos','semestre'];     
        }    

        $resultsArray = $results->map(function ($item) {
            return (array) $item; // Convertir cada stdClass a un arreglo
        })->toArray();       
        if($tipoKardex == 'AP')
        return $this->generateReport($resultsArray,$columnWidths,$keys , 'KARDEX TIPO AP', $headers,'P','letter',
                        'rptKardex_'.$id.'_'.mt_rand(100, 999).'.pdf',$tipoKardex);
        else if($tipoKardex == 'F')
         return $this->generateReport($resultsArray,$columnWidths,$keys , 'ASIGNATURAS PENDIENTES POR CURSAR',$headers,'P','letter',
                        'rptPendientesCursar_'.$id.'_'.mt_rand(100, 999).'.pdf',$tipoKardex);      
        else return $this->generateReport($resultsArray,$columnWidths,$keys , 'KARDEX TIPO C', $headers,'P','letter',
                        'rptKardex_'.$id.'_'.mt_rand(100, 999).'.pdf',$tipoKardex);
      
    }

    public function generateReport(array $data, array $columnWidths = null, array $keys = null, string $title = 'Kardex simple', array $headers = null, string $orientation = 'L', string $size = 'letter',string $nameReport=null,string $tipoKardex)
    {
        // Rutas de las imágenes para el encabezado y pie
        $imagePathEnc = public_path('images/encPag.png');
        $imagePathPie = public_path('images/piePag.png');
        // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)
        $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
        
        // Configurar los encabezados, las rutas de las imágenes y otros parámetros
        $pdf->setHeaders(null, $columnWidths, $title);
        $pdf->setImagePaths($imagePathEnc, $imagePathPie,$orientation);
        
        // Configurar las fuentes
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');
        
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();

        // Establecer fuente para el cuerpo del documento
        $pdf->SetFont('helvetica', '', 8);
         // Generar la tabla HTML para los datos
        $html2 = '<table border="0" cellpadding="1">';
        $generalesRow = $data[0];

        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Nivel:</b> '.$generalesRow['descripcion'].'</td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Carrera:</b> '.$generalesRow['carrera'].'</td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>UID:</b> '.$generalesRow['estudiante'].'</td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Matricula:</b> '.$generalesRow['matricula'].'</td></tr>';  
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Nombre:</b> '.$generalesRow['nombre'].' '.$generalesRow['apellidopat'].' '.$generalesRow['apellidomat'].'</td></tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $html2 .= '<tr>';
       
        foreach ($headers as $index => $header)
            $html2 .= '<td style="font-size: 9px;" width="' . $columnWidths[$index] . '"><b>' . htmlspecialchars($header) . '</b></td>';
        $html2 .= '</tr>';

        $matAprobadas=0;
        $matReprobadas=0;
        $promedio =0;
        $corte = 0;

        foreach ($data as $index2 => $row) {
            $period = 0;
            $actualPeriodo = isset($row['semestre']) ? $row['semestre'] : 0;  // Acceder directamente a 'periodo'
            Log::info('Semestre '.$row['semestre']);
            // Si no es la última fila de los datos, obtiene el 'periodo' de la siguiente fila
            if ($index2 + 1 < count($data)) {
                $nextRow = $data[$index2 + 1];
                $period = isset($nextRow['semestre']) ? $nextRow['semestre'] : 0; 
            }
            if($corte == 1) {
                $html2 .= '<tr><td colspan="7"></td></tr>';
                $html2 .= '<tr><td colspan="7"><hr style="border: 1px dotted black; background-size: 20px 10px;"></td></tr>';
                $html2 .= '<tr><td colspan="7"></td></tr>'; 
                $corte = 0; 
            }    
     
            if ($actualPeriodo != $period)   
                $corte = 1;
          
            $html2 .= '<tr>';

            $valueCalif = isset($row['calificacion']) ? $row['calificacion'] : ''; 
            if($tipoKardex=='AP' && (float)$valueCalif < 7)
                continue;
         
            foreach ($keys as $index => $key) {                
                $value = isset($row[$key]) ? $row[$key] : '';     
                $html2 .= '<td width="' . $columnWidths[$index] . '">' . htmlspecialchars((string)$value) . '</td>';

                if ($key == 'calificacion') {
                    if ((float)$value >= 7) { 
                        $matAprobadas++; 
                        $promedio += (float)$value; // Suma la calificación al promedio (si es necesario)
                    }
                else $matReprobadas++;
                }
            }

        $html2 .= '</tr>';
        }
        //detalle
        if($tipoKardex!='F'){
            $promedioFinal = $matAprobadas>0?round($promedio/$matAprobadas, 2):0;
            $html2 .= '<tr><td colspan="7"></td></tr>';
            $html2 .= '<tr><td colspan="7"><hr style="border: 1px dotted black; background-size: 20px 10px;"></td></tr>';
            $html2 .= '<tr><td colspan="7"></td></tr>';
            $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Materias cursadas:</b> '.count($data).'</td></tr>';
            $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Materias aprobadas:</b> '.$matAprobadas.'</td></tr>';
            
            if($tipoKardex=='C')
                $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Materias reprobadas:</b> '.$matReprobadas.'</td></tr>';
                $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Promedio:</b> '.$promedioFinal.'</td></tr>';
                $html2 .= '<tr><td colspan="7"></td></tr>';   
                $html2 .= '<tr><td colspan="7"></td></tr>';
        }
        $html2 .= '</table>';

        // Escribir la tabla en el PDF
        $pdf->writeHTML($html2, true, false, true, false, '');

        if($nameReport==null)
            $filePath = storage_path('app/public/reporte.pdf');  // Ruta donde se guardará el archivo
        else $filePath = storage_path('app/public/'.$nameReport);  // Ruta donde se guardará el archivo
       
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
