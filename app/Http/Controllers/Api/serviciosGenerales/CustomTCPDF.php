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
    private $titleB;
    private $aquaMark;  
    protected $extgstates = [];

    // Constructor para recibir las rutas de las imágenes
    public function __construct($orientation = 'L', $unit = 'mm', $size = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false)
    {
        parent::__construct($orientation, $unit, $size, $unicode, $encoding, $diskcache);
        //$this->AddFont('TitilliumWeb-Regular', '', 'TitilliumWeb-Regular.php'); // Regular
        //$this->AddFont('TitilliumWeb-Bold', 'B', 'TitilliumWeb-Bold.php');   // Bold
        //$this->AddFont('TitilliumWeb-Italic', 'I', 'TitilliumWeb-Italic.php'); // Italic
    }   

    // Método para establecer las rutas de las imágenes
    public function setImagePaths($encPath, $piePath,$orientation,$aquaMark = false)
    {
        $this->imagePathEnc = $encPath;
        $this->imagePathPie = $piePath;
        $this->orientation = $orientation;
        $this->aquaMark = $aquaMark;
    }

    // Sobrecargar el método Header() para agregar la imagen de encabezado
    public function Header()
    {
        if($this->aquaMark){    
            $this->SetFont('helvetica', 'B', 120);
            $this->SetTextColor(200, 200, 200);
            $this->StartTransform();   
            $this->Rotate(45, 105, 150);     
            $this->Text(50, 100, 'COPIA');               
            $this->SetExtGState(0);
        }

       if(!$this->aquaMark)
        $this->Image($this->imagePathEnc, 15, 0, 180, 0, '', '', '', false, 300);  // Colocar imagen de encabezado     
       // Título del reporte
       //$this->SetFont('TitilliumWeb-Bold', '', 14);   
       $this->MultiCell(0, 30,"\n\n\n\n". $this->titleB, 0, 'R', 0, 1, '', '', false);   
       if($this->headers!=null){        
        $html = '<br><br><table border="0" cellpadding="0">';   
        //$this->SetFont('TitilliumWeb-Bold', '', 12);  // Fuente en negrita para los encabezados
                $html .= '<tr>';   
                foreach ($this->headers as $index => $header)
                    $html .= '<th width="' . $this->columnWidths[$index] . '">' . htmlspecialchars($header) . '</th>';
                $html .= '</tr>';                
            $html .= '</table>';     
            $this->writeHTML($html, true, false, true, false, ''); 
        }       
    }

    // Sobrecargar el método Footer() para agregar el pie de página y el número de página
    public function Footer()
    {
        $this->SetFont('helvetica', '', 8);
        if(!$this->aquaMark){       
            if ($this->imagePathPie && $this->CurOrientation === "P") 
                $this->Image($this->imagePathPie, 50, 287, 180, 0, '', '', '', false, 300);  // Imagen pie de página
            else $this->Image($this->imagePathPie, 120, 200, 180, 0, '', '', '', false, 300);  // Imagen en otra posición
        } 

        $this->SetY(-15);      
        $this->SetX(10);
        date_default_timezone_set('America/Mexico_City');    
        $this->Cell(90, 10, date('d/m/Y H:i:s'), 0, 0, 'L');           
        $this->SetX(180);  
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R'); 
    }  

    public function getPages()
    {
        return $this->getNumPages();   
    }

    public function setHeaders($headers,$columnWidths,$titleB) {
        $this->headers = $headers;
        $this->columnWidths= $columnWidths;
        $this->titleB=$titleB;     
    }
}
