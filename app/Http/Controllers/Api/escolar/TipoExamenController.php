<?php

namespace App\Http\Controllers\escolar;
use Illuminate\Http\Request;

class TipoExamenController extends Controller
{
    public function index()
    {
        $tipoexamen = TipoExamen::all();
        return $this->returnData('Tipo Examen',$tipoexamen,200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = TipoExamen::max('idExamen');
        $newId = $maxId ? $maxId+ 1 : 1;

        $tipoexamen = TipoExamen::create([
            'idExamen' => $newId,
            'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$tipoexamen) 
            return $this->returnEstatus('Error al crear el tipo de examen',500,null); 
        $tipoexamen= TipoExamen::findOrFail($newId);        
        return $this->returnData('Tipo Examen',$tipoexamen,200);
    }

    public function show($id)
    {
        $tipoexamen = TipoExamen::find($id);

        if (!$tipoexamen) 
            return $this->returnEstatus('Tipo de examen no encontrada',404,null); 
        return $this->returnData('Tipo Examen',$tipoexamen,200);
    }

    public function destroy($id)
    {
        $tipoexamen = TipoExamen::find($id);
        if (!$tipoexamen)
            return $this->returnEstatus('Tipo de examen no encontrada',404,null); 
        
        $tipoexamen->delete();
        return $this->returnEstatus('Tipo examen eliminada',200,null); 
    }

    public function update(Request $request, $id)
    {
        $tipoexamen = TipoExamen::find($id);

        if (!$tipoexamen)  
            return $this->returnEstatus('Tipo examen no encontrada',404,null);

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $tipoexamen->idExamen = $id;
        $tipoexamen->descripcion = strtoupper(trim($request->descripcion));

        $tipoexamen->save();

        return $this->returnEstatus('Tipo examen actualizada',200,null); 

    }

    // Función para generar el reporte de personas
    public function generaReporte()
     {
        return $this->imprimeCtl('tipoExamen','tipo de examen');
    } 
    
    public function exportaExcel() {  
        return $this->exportaXLS('tipoExamen','idExamen', ['CLAVE','DESCRIPCIÓN']);     
    }

}
