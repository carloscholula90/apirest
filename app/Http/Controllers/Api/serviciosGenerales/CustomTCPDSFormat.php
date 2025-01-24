<?php  
  
namespace App\Http\Controllers\Api\serviciosGenerales;
use TCPDF; // Si extiende TCPDF, asegúrate de incluir esta línea.   
   
class CustomTCPDSFormat extends TCPDF
{
    
   // Constructor para recibir las rutas de las imágenes
    public function __construct($orientation = 'L', $unit = 'mm', $size = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false)
    {
        parent::__construct($orientation, $unit, $size, $unicode, $encoding, $diskcache);
    }   

    public function Header()
    {             
    }

    // Sobrecargar el método Footer() para agregar el pie de página y el número de página
    public function Footer()
    {
    }  

   
}
