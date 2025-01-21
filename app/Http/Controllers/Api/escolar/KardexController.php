<?php
namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
  
class KardexController extends Controller
{

    public function generaReporte($id,$idNivel,$idCarrera)
     {
       
        $results = DB::table('ciclos as cl')
                        ->join('calificaciones as ca', 'ca.indexCiclo', '=', 'cl.indexCiclo')
                        ->join('grupos as g', 'g.grupoSec', '=', 'ca.gruposec')
                        ->join('asignatura as a', 'a.idAsignatura', '=', 'g.idAsignatura')
                        ->join('persona as p', 'p.uid', '=', 'cl.uid')
                        ->leftJoin('alumno', 'alumno.uid', '=', 'p.uid')
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
                                    'p.UID as estudiante',
                                    'p.nombre',
                                    'p.primerApellido as apellidopat',
                                    'p.segundoApellido as apellidomat',
                                    'g.idAsignatura',
                                    'a.descripcion as asignatura',
                                    'per.idPeriodo',  
                                    'a.creditos',
                                    'ca.cf as calificacion',
                                    'e.descripcion as tipo',
                                    DB::raw('CONLETRA(ca.cf) as califConLetra')
                        )
                        ->where('cl.uid', $id)
                        ->where('alumno.idNivel', $idNivel)
                        ->where('alumno.idCarrera', $idCarrera)
                        ->orderBy('per.idPeriodo')
                        ->get();

        // Si no hay personas, devolver un mensaje de error
        if ($results->isEmpty())
            return $this->returnEstatus('No existen datos para generar el kardex',404,null);
        
        $headers = ['Clave', 'Asignatura','Créditos','Calif','Letra','Tipo','Periodo'];
        $columnWidths = [40,120,50,50,80,80,80];   
        $keys = ['idAsignatura','asignatura','creditos','calificacion','califConLetra','tipo','periodo',];
       
        $resultsArray = $results->map(function ($item) {
            return (array) $item; // Convertir cada stdClass a un arreglo
        })->toArray();       
    
        return $this->generateReport($resultsArray,$columnWidths,$keys , 'KARDEX SIMPLE', $headers,'P','letter',
        'rptKardex_'.$id.'_'.mt_rand(100, 999).'.pdf');
      
    }

    public function generateReport(array $data, array $columnWidths = null, array $keys = null, string $title = 'Kardex simple', array $headers = null, string $orientation = 'L', string $size = 'letter',string $nameReport=null)
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
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Nombre:</b> '.$generalesRow['nombre'].' '.$generalesRow['apellidopat'].$generalesRow['apellidomat'].'</td></tr>';
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
            $actualPeriodo = isset($row['idPeriodo']) ? $row['idPeriodo'] : 0;  // Acceder directamente a 'periodo'

            // Si no es la última fila de los datos, obtiene el 'periodo' de la siguiente fila
            if ($index2 + 1 < count($data)) {
                $nextRow = $data[$index2 + 1];
                $period = isset($nextRow['idPeriodo']) ? $nextRow['idPeriodo'] : 0; 
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

            foreach ($keys as $index => $key) {
                $value = isset($row[$key]) ? $row[$key] : '';     
                $html2 .= '<td width="' . $columnWidths[$index] . '">' . htmlspecialchars((string)$value) . '</td>';
                if ($key == 'calificacion') {
                    if ((float)$value >= 7)  
                        $matAprobadas++; 
                    else $matReprobadas++; 
                    $promedio += (float)$value; // Suma la calificación al promedio (si es necesario)
                }
            }
        $html2 .= '</tr>';
        }
        //detalle
        $promedioFinal = round($promedio/count($data), 2);
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $html2 .= '<tr><td colspan="7"><hr style="border: 1px dotted black; background-size: 20px 10px;"></td></tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Materias cursadas:</b> '.count($data).'</td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Materias aprobadas:</b> '.$matAprobadas.'</td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Materias reprobadas:</b> '.$matReprobadas.'</td></tr>';
        $html2 .= '<tr><td colspan="7" style="font-size: 10px;"><b>Promedio:</b> '.$promedioFinal.'</td></tr>';
       
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';
        
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
