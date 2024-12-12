<?php
namespace App\Http\Controllers\Api\general;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\general\Salud;
use Illuminate\Support\Facades\Validator;
     
class SaludController extends Controller
{
    public function index(){                        
        $salud = Salud::all();
        return $this->returnData('salud',$salud,200);
    }     

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                                        'uid' => 'required|numeric',
                                        'enfermedad' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
                           
            $maxId = Salud::where('uid', $request->uid)  
                                  ->max('secuencia');
  
            $newId = $maxId ? $maxId + 1 : 1; 
            try {
                $salud = Salud::create([
                                'secuencia' => $newId,
                                'uid' => $request->uid, 
                                'enfermedad' => trim($request->enfermedad),
                                'medico' => trim($request->medico),
                                'telefono' => trim($request->telefono)     
                ]);  
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('Error al crear el registro',400,null);                
            return $this->returnEstatus('Error al crear el registro',400,null);
        }

        if (!$salud) 
            return $this->returnEstatus('Error al crear el registro',500,null); 
        return $this->returnData('salud',$salud,201);   
    }

    public function show($uid){
        try {
            $salud = Salud::select('uid','secuencia','enfermedad','medico','telefono')
                            ->where('uid',$uid)
                            ->get();      
            if (!$salud) 
                return $this->returnData('salud',$salud,200);     
            else return $this->returnEstatus('Registro no encontrado',404,null); 
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Salud no encontrado',404,null); 
        }  
    }
    
    public function destroy($uid,$secuencia){
        $salud = Salud::where('uid', $uid)
                        ->where('secuencia',$secuencia); 
        $salud->delete();

        if (!$salud) 
            return $this->returnEstatus('Salud no encontrado',404,null);  
        return $this->returnEstatus('Salud eliminado',200,null); 
    }
}