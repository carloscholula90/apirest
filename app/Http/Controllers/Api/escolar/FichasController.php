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
    
    /*
     * Calcular primero los saldos del periodo solicitado. Cuando se genera
     * la ficha de un alumno, el UID se filtra dentro de la subconsulta para
     * evitar agrupar toda la tabla edocta.
     */
    $saldosPendientes = DB::table('edocta')
        ->select([
            'uid',
            'secuencia',
            'idPeriodo',
            'idServicio',
            'referencia',
            'parcialidad',
            DB::raw("SUM(
                CASE
                    WHEN tipomovto = 'C' THEN importe
                    WHEN tipomovto = 'A' THEN -importe
                    ELSE 0
                END
            ) AS saldo"),
            DB::raw("MAX(
                CASE
                    WHEN tipomovto = 'C'
                    THEN COALESCE(fechaVencimiento, FechaPago)
                    ELSE NULL
                END
            ) AS fechaVencimiento"),
        ])
        ->where('idPeriodo', (int) $idPeriodo)
        ->when((int) $uid > 0, function ($query) use ($uid) {
            $query->where('uid', (int) $uid);
        })
        ->groupBy([
            'uid',
            'secuencia',
            'idPeriodo',
            'idServicio',
            'referencia',
            'parcialidad',
        ])
        ->havingRaw("SUM(
            CASE
                WHEN tipomovto = 'C' THEN importe
                WHEN tipomovto = 'A' THEN -importe
                ELSE 0
            END
        ) > 0");

    $datos = DB::query()
        ->fromSub($saldosPendientes, 'cta')
        ->join('alumno as al', function ($join) {
            $join->on('al.uid', '=', 'cta.uid')
                ->on('al.secuencia', '=', 'cta.secuencia');
        })
        ->join('persona', 'persona.uid', '=', 'al.uid')
        ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
        ->where('al.idCarrera', (int) $idCarrera)
        ->where('al.idNivel', (int) $idNivel)
        ->where('s.mostrarFichaPago', 1)
        ->selectRaw("
            CONCAT(
                persona.primerApellido, ' ',
                persona.segundoApellido, ' ',
                persona.nombre
            ) AS nombre,
            cta.uid,
            al.matricula,
            CONCAT(
                s.descripcion, ' ',
                CASE
                    WHEN s.descripcion LIKE '%INSCRIP%' THEN ''
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
                    END
                END
            ) AS servicios,
            cta.saldo AS total,
            DATE_FORMAT(cta.fechaVencimiento, '%Y-%m-%d') AS fechaVencimiento,
            Algoritmo45Fun(
                CONCAT(
                    LPAD(al.matricula, 7, '0'),
                    LPAD(cta.idServicio, 3, '0')
                ),
                DATE_FORMAT(cta.fechaVencimiento, '%Y-%m-%d'),
                cta.saldo
            ) AS lineaPago
        ")
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
                <td style="width: 150px; font-size: 8pt;">UID:</td>
                <td style="font-size: 12pt;">' . $fila->uid . '</td>
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
                    económica, mantendrá la beca de 50%, por lo que el costo de la colegiatura es de $'.$total.'
                    con fecha límite de pago los días 10 de cada mes. En caso contrario se aplicará un recargo del 20%
                </td></tr><br><tr>
                <td colspan="2" style="font-size: 10px;">NOTA: Las referencias son intransferibles e individuales .</td></tr>';
  
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

public function generarFichaEspecial($matricula,$importe,$fechaVencimiento){

    $size = 'letter';
    $nameReport = 'fichaPago_' . mt_rand(100, 999) . '.pdf';
    DB::statement("SET lc_time_names = 'es_ES'");
    
 $datos = DB::table('alumno as a')
    ->join('persona as p', 'a.uid', '=', 'p.uid')
    ->selectRaw("
        CONCAT(p.primerApellido, ' ', p.segundoApellido, ' ', p.nombre) AS nombre,
        a.matricula, a.uid,
        ? as total,
        DATE_FORMAT(?, '%Y-%m-%d') AS fechaVencimiento,
        Algoritmo45Fun(
            CONCAT(
                LPAD(a.matricula, 7, '0'),
                '002'
            ),
            DATE_FORMAT(?, '%Y-%m-%d'),
            ?
        ) AS lineaPago
    ", [
        $importe,
        $fechaVencimiento,
        $fechaVencimiento,
        $importe
    ])
    ->where('a.matricula', $matricula)
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
    $html .= '<tr><td colspan="2" style="font-size: 14px;"><b>FICHA ESPECIAL</b></td></tr>';
    $html .= '<tr><td colspan="2" style="font-size: 14px;"></td></tr>';    
    $html .= '<tr><td colspan="2" style="font-size: 12px;"><b>BBVA BANCOMER</b></td></tr>';
    $html .= '<tr><td colspan="2" style="font-size: 12px;"><b>CONVENIO CIE:0779857 A NOMBRE DE UNIVERSIDAD ALVA EDISON</b></td></tr>';
    $html .= '<br>';
   
    $name=''; 
    foreach ($resultados as $fila) {
        if($name==''||$name!=$fila->nombre){
            if($name!=''){
                  $html .= '<br><br><tr><td colspan="2" style="font-size: 10px;">La Universidad Alva Edison en apoyo a la situación
                    económica, mantendrá la beca de 50%, por lo que el costo de la colegiatura es de $'.$total.'
                    con fecha límite de pago los días 10 de cada mes. En caso contrario se aplicará un recargo del 20%
                </td></tr><br><tr>
                <td colspan="2" style="font-size: 10px;">NOTA: Las referencias son intransferibles e individuales .</td></tr>';

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
                <td style="width: 150px; font-size: 8pt;">UID:</td>
                <td style="font-size: 12pt;">' . $fila->uid . '</td>
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
        $html .= '<tr>
                <td style="width: 150px; font-size: 8pt;"></td>
                <td style="font-size: 12pt;">' .$fila->lineaPago. '</td>
                </tr>';
        $html .= '<tr>
                <td style="width: 150px; font-size: 12pt;"><b>$'.$total.'</b></td>
                <td style="width: 300px; font-size: 12pt;">FECHA LIMITE DE PAGO '.mb_strtoupper($fecha, 'UTF-8'). '</td>
                </tr><br>';
    }  

    $html .= '<br><tr>
                <td colspan="2" style="font-size: 10px;">NOTA: Las referencias son intransferibles e individuales .</td></tr>';
  
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
