<?php
namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF;
use Illuminate\Support\Facades\DB;
  
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
                                    'carrera.descripcion as carrera',
                                    'per.descripcion as periodo',
                                    'p.UID as estudiante',
                                    'p.nombre',
                                    'p.primerApellido as apellidopat',
                                    'p.segundoApellido as apellidomat',
                                    'g.idAsignatura',
                                    'a.descripcion as asignatura',
                                    'a.creditos',
                                    'ca.cf as calificacion',
                                    'e.descripcion as tipo',
                                    DB::raw('CONLETRA(ca.cf) as califConLetra',
                                    'ROW_NUMBER() OVER (PARTITION BY cl.idPeriodo ORDER BY cl.idPeriodo) AS rownum')
                        )
                        ->where('cl.uid', $id)
                        ->where('alumno.idNivel', $idNivel)
                        ->where('alumno.idCarrera', $idCarrera)
                        ->orderBy('per.idPeriodo')
                        ->get();

        // Si no hay personas, devolver un mensaje de error
        if ($results->isEmpty())
            return $this->returnEstatus('No existen datos para generar el kardex',404,null);
        
        $headers = ['Clave', 'Asignatura','Calif','Calificación con letra','Tipo','Periodo','Créditos'];
        $columnWidths = [80,150,80,200,80,150,80];   
        $keys = ['idAsignatura','asignatura','calificacion','califConLetra','tipo','periodo','creditos','rownum'];
       
        $resultsArray = $results->map(function ($item) {
            return (array) $item; // Convertir cada stdClass a un arreglo
        })->toArray();       
    
        return $this->generateReport($resultsArray,$columnWidths,$keys , 'UNIVERSIDAD ALVA EDISON', $headers,'L','letter',
        'rptKardex'.mt_rand(1, 100).'.pdf');
      
    }

    public function generateReport(array $data, array $columnWidths = null, array $keys = null, string $title = 'Kardex simple', array $headers = null, string $orientation = 'L', string $size = 'letter',string $nameReport=null)
    {
        // Rutas de las imágenes para el encabezado y pie
        $imagePathEnc = public_path('images/encPag.png');
        $imagePathPie = public_path('images/piePag.png');

        // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)
        $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
        
        // Configurar los encabezados, las rutas de las imágenes y otros parámetros
        $pdf->setHeaders($headers, $columnWidths, $title);
        $pdf->setImagePaths($imagePathEnc, $imagePathPie, $orientation);
        
        // Configurar las fuentes
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');
        
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(15, 55, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();

        // Establecer fuente para el cuerpo del documento
        $pdf->SetFont('helvetica', '', 8);
        $html2 = "<p>Nivel:</p>";
        $html2 = "<br><p>Carrera:</p>";
        $html2 = "<br><p>Matrícula:</p>";
        $html2 = "<br><p>Nombre:</p>";
        $html2 = "<br><p>Plan:</p><br><hr>";

        // Generar la tabla HTML para los datos
        $html2 = '<table border="0" cellpadding="1">';
        foreach ($data as $row) {
            $html2 .= '<tr>';
            foreach ($keys as $index => $key) {
                $value = isset($row[$key]) ? $row[$key] : '';     
                if($value==1 && $key=='rownum')  
                     $html2 .= '<br>';    
                else $html2 .= '<td width="' . $columnWidths[$index] . '">' . htmlspecialchars((string)$value) . '</td>';
            }
            $html2 .= '</tr>';
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
