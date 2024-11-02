<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentoController extends Controller
{
    public function index()
    {
        $documento = Documento::all();
        return $this->returnData('Documentos',$documento,200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Documento::max('idDocumento');
        $newId = $maxId ? $maxId+ 1 : 1;

        $documento = Documento::create([
            'idDocumento' => $newId,
            'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$documento) 
            return $this->returnEstatus('Error al crear la escolaridad',500,null); 
        $documento= Documento::findOrFail($newId);        
        return $this->returnData('Documentos',$documento,200);
    }

    public function show($id)
    {
        $documento = Documento::find($id);

        if (!$documento) 
            return $this->returnEstatus('Documento no encontrado',404,null); 
        return $this->returnData('Documentos',$documento,200);
    }

    public function destroy($id)
    {
        $documento = Documento::find($id);
        if (!$documento)
            return $this->returnEstatus('Documento no encontrado',404,null); 
        
        $documento->delete();
        return $this->returnEstatus('Documento eliminado',200,null); 
    }

    public function update(Request $request, $id)
    {
        $documento = Documento::find($id);

        if (!$documento)  
            return $this->returnEstatus('Documento no encontrado',404,null);

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $documento->idDocumento = $id;
        $documento->descripcion = strtoupper(trim($request->descripcion));

        $documento->save();

        return $this->returnEstatus('Documento actualizado',200,null); 

    }
}
