<?php  
  
namespace App\Http\Controllers\Api\serviciosGenerales;
use TCPDF; // Si extiende TCPDF, asegúrate de incluir esta línea.   
   
class CustomTCPDF extends TCPDF
{
    private $imagePathEnc;
    private $imagePathPie;
    

    // Constructor para recibir las rutas de las imágenes
    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false)
    {
        parent::__construct($orientation, $unit, $size, $unicode, $encoding, $diskcache);
        $this->AddFont('TitilliumWeb-Regular', '', 'TitilliumWeb-Regular.php'); // Regular
        $this->AddFont('TitilliumWeb-Bold', 'B', 'TitilliumWeb-Bold.php');   // Bold
        $this->AddFont('TitilliumWeb-Italic', 'I', 'TitilliumWeb-Italic.php'); // Italic
    }   

    // Método para establecer las rutas de las imágenes
    public function setImagePaths($encPath, $piePath)
    {
        $this->imagePathEnc = $encPath;
        $this->imagePathPie = $piePath;
    }

    // Sobrecargar el método Header() para agregar la imagen de encabezado
    public function Header()
    {
        if ($this->imagePathEnc) {
            $this->Image($this->imagePathEnc, 15, 0, 180, 0, '', '', '', false, 300);  // Colocar imagen de encabezado
        }
    }

    // Sobrecargar el método Footer() para agregar el pie de página y el número de página
    public function Footer()
    {
        if ($this->imagePathPie)     
            $this->Image($this->imagePathPie, 5, 286, 180, 0, '', '', '', false, 300);  // Colocar imagen del pie de página
        
        $this->SetY(-15);  // Colocar el pie de página a 15mm desde el borde inferior
        $this->SetFont('TitilliumWeb-Regular', '', 10);
       // Posicionamos el cursor en el borde izquierdo de la página
        $this->SetX(10);  // Mover el cursor 10 unidades desde la izquierda
        $this->Cell(90, 10, date('d/m/Y H:i:s'), 0, 0, 'L');  // Fecha y hora a la izquierda

        // Mover el cursor al centro (por ejemplo, 10 unidades después del borde izquierdo y luego centramos)
        $this->SetX(90); // Mover el cursor un poco más
        $this->Cell(90, 10, 'SIAWEB', 0, 0, 'C'); // SIAWEB centrado

        // Mover el cursor al final de la página para la parte derecha      
        $this->SetX(180);  // Mover a la posición final (derecha)
        $this->Cell(0, 10, 'Página ' . $this->getPage() . ' de ' . $this->getPages(), 0, 0, 'R');  // Número de página a la derecha   
    }
  
    public function getPages()
    {
        // Define aquí lo que necesitas. Por ejemplo:
        return $this->getNumPages();   
    }
}
