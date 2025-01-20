<?php  
  
namespace App\Http\Controllers\Api\serviciosGenerales;
use TCPDF; // Si extiende TCPDF, asegúrate de incluir esta línea.   
   
class CustomTCPDF extends TCPDF
{
    private $imagePathEnc;
    private $imagePathPie;
    private $orientation;
    private $headers;
    private $columnWidths;
    public $title;

    // Constructor para recibir las rutas de las imágenes
    public function __construct($orientation = 'L', $unit = 'mm', $size = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false)
    {
        parent::__construct($orientation, $unit, $size, $unicode, $encoding, $diskcache);
        //$this->AddFont('TitilliumWeb-Regular', '', 'TitilliumWeb-Regular.php'); // Regular
        //$this->AddFont('TitilliumWeb-Bold', 'B', 'TitilliumWeb-Bold.php');   // Bold
        //$this->AddFont('TitilliumWeb-Italic', 'I', 'TitilliumWeb-Italic.php'); // Italic
    }   

    // Método para establecer las rutas de las imágenes
    public function setImagePaths($encPath, $piePath,$orientation)
    {
        $this->imagePathEnc = $encPath;
        $this->imagePathPie = $piePath;
    }

    // Sobrecargar el método Header() para agregar la imagen de encabezado
    public function Header()
    {
       $this->Image($this->imagePathEnc, 15, 0, 180, 0, '', '', '', false, 300);  // Colocar imagen de encabezado     
       // Título del reporte
       //$this->SetFont('TitilliumWeb-Bold', '', 14); 
       $this->MultiCell(0, 30,"\n\n\n\n". $this->title, 0, 'R', 0, 1, '', '', false);   
       if($this->headers!=null){
        
        $html = '<br><br><table border="0" cellpadding="0">';   
        //$this->SetFont('TitilliumWeb-Bold', '', 12);  // Fuente en negrita para los encabezados
        if ($this->headers) {
                $html .= '<tr>';   
                foreach ($this->headers as $index => $header)
                    $html .= '<th width="' . $this->columnWidths[$index] . '">' . htmlspecialchars($header) . '</th>';
                $html .= '</tr>';
        }
        $html .= '</table>';     
        $this->writeHTML($html, true, false, true, false, ''); 
    }         
    }

    // Sobrecargar el método Footer() para agregar el pie de página y el número de página
    public function Footer()
    {
        if ($this->imagePathPie && $this->CurOrientation === "P") 
            $this->Image($this->imagePathPie, 5, 286, 180, 0, '', '', '', false, 300);  // Imagen pie de página
         else $this->Image($this->imagePathPie, 120, 200, 180, 0, '', '', '', false, 300);  // Imagen en otra posición
       
        $this->SetY(-15);
        //$this->SetFont('TitilliumWeb-Regular', '', 10);
        $this->SetX(10);
        $this->Cell(90, 10, date('d/m/Y H:i:s'), 0, 0, 'L');            
        $this->SetX(180);  
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
    }  

    public function getPages()
    {
        return $this->getNumPages();   
    }

    public function setHeaders($headers,$columnWidths,$title) {
        $this->headers = $headers;
        $this->columnWidths= $columnWidths;
        $this->title=$title;     
    }
}
