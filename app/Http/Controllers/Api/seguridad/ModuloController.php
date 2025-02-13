<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use App\Models\seguridad\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;

class ModuloController extends Controller
{

    protected $pdfController;

    // Inyección de la clase PdfReportGenerator
    public function __construct(pdfController $pdfController)
    {
        $this->pdfController = $pdfController;
    }
       
    public function index()
    {
        $modulos = Modulo::all();
        return $this->returnData('modulos',$modulos,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                            'descripcion' => 'required|max:255',
                            'icono' => 'required|max:255',
                            'alias' => 'required|max:255'

        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Modulo::max('idModulo');
        $newId = $maxId ? $maxId+ 1 : 1;
        try{
        $modulos = Modulo::create([
                                    'idModulo' => $newId,
                                    'descripcion' => $request->descripcion,
                                    'icono' => $request->icono,
                                    'alias' => $request->alias
        ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El modulo ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el modulo',400,null);
        }

        if (!$modulos) return 
            $this->returnEstatus('Error al crear el módulo',500,null); 
        
        $modulos = Modulo::findOrFail($newId);
        return $this->returnData('modulos',$modulos,200);
    }

    public function show($id)
    {
        $modulos = Modulo::find($id);

        if (!$modulos) 
            return $this->returnEstatus('Modulo no encontrado',404,null); 

        return $this->returnData('modulos',$modulos,200);
    }

    public function destroy($id)
    {
        $modulos = Modulo::find($id);

        if (!$modulos) 
            return $this->returnEstatus('Modulo no encontrado',404,null);         
        
        $modulos->delete();

        return $this->returnEstatus('Módulo eliminado',200,null); 
    }

    public function update(Request $request, $id)
    {
        $modulos = Modulo::find($id);

        if (!$modulos) 
            return $this->returnEstatus('Modulo no encontrado',404,null); 

        $validator = Validator::make($request->all(), [
                                'descripcion' => 'required|max:255',
                                'icono' => 'required|max:255',
                                'alias' => 'required|max:255'
        ]);

        if ($validator->fails())
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $modulos->idModulo = $id;
        $modulos->descripcion = strtoupper(trim($request->descripcion));
        $modulos->icono=$request->icono;
        $modulos->alias=$request->alias;
        $modulos->save();
        
        return $this->returnEstatus('Módulo actualizado',200,null); 

    }

    // Función para generar el reporte de personas
    public function generaReporte()
     {
        $modulos = Modulo::all();    
        // Si no hay personas, devolver un mensaje de error
        if ($modulos->isEmpty())
            return $this->returnEstatus('No se encontraron personas para generar el reporte',404,null);
        
        $headers = ['Clave', 'Descripción','Ícono','Alias'];
        $columnWidths = [80,150,80,80];   
        $keys = ['idModulo','descripcion','icono','alias'];
       
        $modulosArray = $modulos->map(function ($modulos) {
            return $modulos->toArray();
        })->toArray();     
    
        return $this->pdfController->generateReport($modulosArray,$columnWidths,$keys , 'REPORTE DE MÓDULOS', $headers,'L','letter',
        'rptModulos'.mt_rand(1, 100).'.pdf');
      
    } 
    
    public function exportaExcel() {  
        return $this->exportaXLS('modulos','idModulo', ['CLAVE','DESCRIPCIÓN']);     
    }
}
