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
    
    $datos = DB::table(DB::raw("(
            SELECT 
                cta.uid,
                al.matricula,
                cta.parcialidad,
                cta.secuencia,
                GROUP_CONCAT(DISTINCT CONCAT(
                    s.descripcion, ' ',
                    CASE WHEN s.descripcion LIKE '%INSCRIP%' THEN ''
                        ELSE CASE CONVERT(SUBSTRING(cta.referencia, 4), UNSIGNED)
                        WHEN 1 THEN 'ENERO'
                        WHEN 2 THEN 'FEBRERO'
                        WHEN 3 THEN 'MARZO'
                        WHEN 4 THEN 'ABRIL'
                        WHEN 5 THEN 'MAYO'
                        WHEN 6 THEN 'JUNIO'
                        WHEN 7 THEN 'JULIO'
                        WHEN 8 THEN 'AGOSTO'
                        WHEN 9 THEN 'SEPTIEMBRE'
                        WHEN 10 THEN 'OCTUBRE'
                        WHEN 11 THEN 'NOVIEMBRE'
                        WHEN 12 THEN 'DICIEMBRE'
                        ELSE ''
                    END END
                ) ORDER BY s.descripcion SEPARATOR ' + ') AS servicios,
                GROUP_CONCAT(DISTINCT s.idServicio ORDER BY s.idServicio SEPARATOR '') AS serviciosClv,
                SUM(
                    CASE 
                        WHEN cta.tipomovto = 'C' THEN cta.importe
                        WHEN cta.tipomovto = 'A' THEN -cta.importe
                        ELSE 0
                    END
                ) AS total,
                CONCAT(
                    persona.primerApellido, ' ',
                    persona.segundoApellido, ' ',
                    persona.nombre
                ) AS nombre,
                MAX( CASE WHEN cta.tipomovto = 'C' THEN cta.fechaVencimiento END) AS fechaVencimiento
            FROM configuracionTesoreria ct
            INNER JOIN alumno al ON ct.idNivel = al.idNivel
            INNER JOIN persona ON persona.uid = al.uid
            INNER JOIN periodo per ON per.idNivel = al.idNivel AND per.activo = 1
            INNER JOIN nivel niv ON niv.idNivel = al.idNivel
            INNER JOIN servicio s ON s.tipoEdoCta = 1
            INNER JOIN edocta cta ON cta.idServicio = s.idServicio
                AND cta.uid = al.uid
                AND cta.secuencia = al.secuencia
                AND cta.idPeriodo = per.idPeriodo
            WHERE cta.idPeriodo =".$idPeriodo.
                 " AND al.idCarrera =".$idCarrera.
                 " AND al.idNivel =".$idNivel.
            ($uid>0?" AND al.uid=".$uid:"").
            " GROUP BY
                cta.uid,
                al.matricula,
                cta.parcialidad,
                cta.secuencia,
                persona.primerApellido,
                persona.segundoApellido,
                persona.nombre
        ) AS CONS"))
        ->selectRaw("
            CONS.nombre,
            CONS.matricula,
            CONS.servicios,
            CONS.total,
            DATE_FORMAT(fechaVencimiento, '%Y-%m-%d') AS fechaVencimiento,
            Algoritmo45Fun(
                CONCAT(
                    LPAD(CONS.matricula, 7, '0'),
                    LPAD(CONS.serviciosClv, 3, '0')
                ),
                DATE_FORMAT(fechaVencimiento, '%Y-%m-%d'),
                CONS.total
            ) AS lineaPago
        ")
        ->having('total', '>', 0)
        ->orderBy('matricula','asc')
        ->orderBy('fechaVencimiento','asc')
        ->get();


    $resultados = collect($datos);

    if ($resultados->isEmpty()) {
        return response()->json(['message' => 'Sin resultados'], 404);
    }

    $imagePathEnc = public_path('images/encPag.png');
    $imagePathPie = public_path('images/piePag.png');
    // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)   
    $pdf = new CustomTCPDF('P', PDF_UNIT, $size, true, 'UTF-8', false);   
        
    // Configurar los encabezados, las rutas de las imágenes y otros parámetros
    $pdf->setImagePaths($imagePathEnc, $imagePathPie,'P',false);
    $pdf->SetFont('helvetica', '', 14);
    $pdf->setPagoUnico(true);
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
    $numero = random_int(1, 100);
    $nameReport = 'rptFichas'.$numero.'.pdf';
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
