<?php

namespace App\Http\Controllers\Api\escolar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;  
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Illuminate\Support\Facades\Log;  
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF; 
use Illuminate\Support\Str;

class FichasController extends Controller{


    public function generarYGuardarPDFAlumno($idPeriodo, $idNivel,$idCarrera,$uid){
        $this->generarYGuardarPDF($idPeriodo, $idNivel,$idCarrera,$uid);
    } 

    public function generarYGuardarPDF($idPeriodo, $idNivel,$idCarrera,$uid=0){

    $size = 'letter';
    $nameReport = 'fichaPago_' . mt_rand(100, 999) . '.pdf';
    DB::statement("SET lc_time_names = 'es_ES'");
    
    $datos = DB::select("SELECT
                            CONS.nombre,
                            CONS.matricula,
                            CONS.servicios,
                            VENC.fchVencimiento,
                            CONS.total,
                            DATE_FORMAT(VENC.fchVencimiento, '%Y-%m-%d') as fechaVencimiento,
                            Algoritmo45Fun(CONCAT(LPAD(CONS.matricula, 7, '0'),
                            LPAD(
                                CASE 
                                    WHEN FIND_IN_SET(idServicioColegiatura, CONS.serviciosClv) > 0 THEN idServicioColegiatura
                                    WHEN FIND_IN_SET(idServicioReinscripcion, CONS.serviciosClv) > 0 THEN idServicioReinscripcion
                                    WHEN FIND_IN_SET(idServicioTraspasoSaldos1, CONS.serviciosClv) > 0 THEN idServicioTraspasoSaldos1
                                    ELSE idServicioInscripcion
                                END,
                            3, '0')),
                            DATE_FORMAT(VENC.fchVencimiento, '%Y-%m-%d'), CONS.total) AS lineaPago
                            FROM (
                            SELECT
                                edo.uid,
                                al.matricula,
                                edo.parcialidad,
                                edo.secuencia,
                                GROUP_CONCAT(DISTINCT s.descripcion ORDER BY s.descripcion SEPARATOR ' + ')
                                 AS servicios,
                                GROUP_CONCAT(DISTINCT s.idServicio ORDER BY s.idServicio SEPARATOR ' , ')
                                 AS serviciosClv,
                                SUM(CASE WHEN edo.tipomovto = 'C' THEN edo.importe ELSE -edo.importe END) AS total,
                                CONCAT(persona.primerApellido, ' ', persona.segundoApellido, ' ', persona.nombre) AS nombre,
                                MAX(colegiatura.idServicioColegiatura) AS idServicioColegiatura,
                                MAX(reinscripcion.idServicioReinscripcion) AS idServicioReinscripcion,
                                MAX(inscripcion.idServicioInscripcion) AS idServicioInscripcion,
                                MAX(saldo.idServicioTraspasoSaldos1) AS idServicioTraspasoSaldos1
                            FROM edocta AS edo
                            INNER JOIN alumno AS al ON al.uid = edo.uid
                            INNER JOIN servicio AS s ON s.idServicio = edo.idServicio
                            INNER JOIN persona ON persona.uid = al.uid
                            LEFT JOIN configuracionTesoreria AS inscripcion
                                ON inscripcion.idNivel = al.idNivel
                            AND inscripcion.idServicioInscripcion = s.idServicio
                            LEFT JOIN configuracionTesoreria AS reinscripcion
                                ON reinscripcion.idNivel = al.idNivel
                            AND reinscripcion.idServicioReinscripcion = s.idServicio
                            LEFT JOIN configuracionTesoreria AS colegiatura
                                ON colegiatura.idNivel = al.idNivel
                            AND colegiatura.idServicioColegiatura = s.idServicio
                            LEFT JOIN configuracionTesoreria AS saldo
                                ON saldo.idNivel = al.idNivel
                            AND saldo.idServicioTraspasoSaldos1 = s.idServicio
                            WHERE
                                edo.idPeriodo =".$idPeriodo.
                               " AND al.idCarrera = ".$idCarrera.
                               " AND al.idNivel =".$idNivel.
                               ($uid>0?" AND al.uid=".$uid:"").
                               " AND (
                                    colegiatura.idServicioColegiatura IS NOT NULL
                                    OR reinscripcion.idServicioReinscripcion IS NOT NULL
                                    OR inscripcion.idServicioInscripcion IS NOT NULL
                                    OR saldo.idServicioTraspasoSaldos1 IS NOT NULL
                                )
                            GROUP BY
                                edo.parcialidad,
                                edo.uid,
                                persona.primerApellido,
                                persona.segundoApellido,
                                persona.nombre,
                                al.matricula,
                                edo.secuencia
                            ) AS CONS
                            LEFT JOIN (
                            SELECT
                                edo.uid AS idAlumno,
                                edo.secuencia AS seq,
                                edo.parcialidad AS parcialidaF,
                                edo.fechaVencimiento AS fchVencimiento
                            FROM edocta AS edo
                            INNER JOIN alumno AS al ON al.uid = edo.uid
                            INNER JOIN servicio AS s ON s.idServicio = edo.idServicio
                            LEFT JOIN configuracionTesoreria AS inscripcion
                                ON inscripcion.idNivel = al.idNivel
                            AND inscripcion.idServicioInscripcion = s.idServicio
                            LEFT JOIN configuracionTesoreria AS reinscripcion
                                ON reinscripcion.idNivel = al.idNivel
                            AND reinscripcion.idServicioReinscripcion = s.idServicio
                            LEFT JOIN configuracionTesoreria AS colegiatura
                                ON colegiatura.idNivel = al.idNivel
                            AND colegiatura.idServicioColegiatura = s.idServicio
                            LEFT JOIN configuracionTesoreria AS saldo
                                ON saldo.idNivel = al.idNivel
                            AND saldo.idServicioTraspasoSaldos1 = s.idServicio
                            WHERE
                                edo.idPeriodo = ".$idPeriodo.
                               " AND al.idCarrera = ".$idCarrera.
                                " AND al.idNivel =".$idNivel.
                                ($uid>0?" AND al.uid=".$uid:"").
                               " AND edo.tipomovto = 'C'
                                AND (
                                colegiatura.idServicioColegiatura IS NOT NULL
                                OR reinscripcion.idServicioReinscripcion IS NOT NULL
                                OR inscripcion.idServicioInscripcion IS NOT NULL
                                OR saldo.idServicioTraspasoSaldos1 IS NOT NULL
                                )
                            GROUP BY
                                edo.uid, edo.secuencia, edo.parcialidad, edo.fechaVencimiento,
                                colegiatura.idServicioColegiatura, reinscripcion.idServicioReinscripcion,
                                inscripcion.idServicioInscripcion,saldo.idServicioTraspasoSaldos1
                            ) AS VENC
                            ON VENC.idAlumno = CONS.uid
                            AND VENC.parcialidaF = CONS.parcialidad
                            AND VENC.seq = CONS.secuencia
                            ORDER BY CONS.matricula, VENC.parcialidaF
                        ");

    $resultados = collect($datos);

    if ($resultados->isEmpty()) {
        return response()->json(['message' => 'Sin resultados'], 404);
    }

    $imagePathEnc = public_path('images/encPag.png');
    $imagePathPie = public_path('images/piePag.png');
    // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)   
    $pdf = new CustomTCPDF('------', PDF_UNIT, $size, true, 'UTF-8', false);
        
    // Configurar los encabezados, las rutas de las imágenes y otros parámetros
    $pdf->setImagePaths($imagePathEnc, $imagePathPie,'---',false);
    $pdf->SetFont('helvetica', '', 14);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SIAWEB');
    $pdf->SetMargins(15, 30, 15);  
    $pdf->SetAutoPageBreak(TRUE, 25);
    $pdf->AddPage();

    // Generar la tabla HTML para los datos
    $html = '<table>';    
    $html .= '<tr><td colspan="2" style="font-size: 12px;"><b>BBVA BANCOMER</b></td></tr>';
    $html .= '<tr><td colspan="2" style="font-size: 12px;"><b>CONVENIO CIE:0779857 A NOMBRE DE UNIVERSIDAD ALVA EDISON</b></td></tr>';
    $html .= '<br>';
   
    $name=''; 
    foreach ($resultados as $fila) {
        if($name==''||$name!=$fila->nombre){
            if($name!=''){
                  $html .= '<br><br><tr><td colspan="2" style="font-size: 10px;">La Universidad Alva Edison en apoyo a la situación
                    económica, mantendrá la beca de 50%, por lo que el costo de la colegiatura es de $1,200.00
                    con fecha límite de pago los días 10 de cada mes. En caso contrario se aplicará un recargo del 20%
                </td></tr><br><tr>
                <td colspan="2" style="font-size: 10px;">NOTA: Las referencias son instransferibles e indivuales.</td></tr>';

                // Cierra la tabla actual y escribe en el PDF
             $html .= '</table>';
             $pdf->writeHTML($html, true, false, true, false, '');
            
             // 🔹 Nuevo salto de página para el siguiente alumno
             $pdf->AddPage(); 
             $html = '<table>'; 
             
             $html .= '<tr><td colspan="2" style="font-size: 12px;"><b>BBVA BANCOMER</b></td></tr>';
             $html .= '<tr><td colspan="2" style="font-size: 12px;"><b>CONVENIO CIE:0779857 A NOMBRE DE UNIVERSIDAD ALVA EDISON</b></td></tr>';
             $html .= '<br>';
            }
            $html .= '<tr>
                <td style="width: 150px; font-size: 8pt;">NOMBRE:</td>
                <td style="font-size: 12pt;">' . $fila->nombre . '</td>
                </tr>';
            $html .= '<tr>
                <td style="width: 150px; font-size: 8pt;">MATRICULA:</td>
                <td style="font-size: 12pt;">' .$fila->matricula. '</td>
                </tr><br>';
        }
        $name=$fila->nombre;
        Carbon::setLocale('es'); // Establece el idioma a español
        $fecha = Carbon::parse($fila->fechaVencimiento)->translatedFormat('d/F/Y');
        $mes = Carbon::parse($fila->fechaVencimiento)->translatedFormat('F Y');
        $total = number_format($fila->total, 2, '.', ',');
        if (Str::contains($fila->servicios, 'COLEGIATURA'))
            $html .= '<tr>
                <td style="width: 150px; font-size: 8pt;">'.mb_strtoupper($mes,'UTF-8').'</td>
                <td style="font-size: 12pt;">' .$fila->lineaPago. '</td>
                </tr>';
        else
            $html .='<tr>
                <td style="width: 150px; font-size: 8t;">'.$fila->servicios.' '.mb_strtoupper($mes,'UTF-8').'</td>
                <td style="font-size: 12pt;">' .$fila->lineaPago. '</td>
                </tr>';        
        
        $html .= '<tr>
                <td style="width: 150px; font-size: 12pt;"><b>$'.$total.'</b></td>
                <td style="width: 300px; font-size: 12pt;">FECHA LIMITE DE PAGO '.mb_strtoupper($fecha, 'UTF-8'). '</td>
                </tr><br>';
    }  

    $html .= '<br><br><tr><td colspan="2" style="font-size: 10px;">La Universidad Alva Edison en apoyo a la situación
                    económica, mantendrá la beca de 50%, por lo que el costo de la colegiatura es de $1,200.00
                    con fecha límite de pago los días 10 de cada mes. En caso contrario se aplicará un recargo del 20%
                </td></tr><br><tr>
                <td colspan="2" style="font-size: 10px;">NOTA: Las referencias son instransferibles e indivuales.</td></tr>';
  
    $html .= '</table>';     
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $nameReport = 'rptFichas.pdf';
    $filePath = storage_path('app/public/'.$nameReport);  // Ruta donde se guardará el archivo
       
    $pdf->Output($filePath, 'F');  // 'F' para guardar el archivo en el servidor
    
    // Ahora puedes verificar si el archivo se ha guardado correctamente en la ruta especificada.
        if (file_exists($filePath)) {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/'.$nameReport // Puedes devolver la ruta para fines de depuración
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte'
            ]);
        }
}

}
