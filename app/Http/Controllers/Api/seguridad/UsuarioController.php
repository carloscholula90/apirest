<?php
namespace App\Http\Controllers\Api\general; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\general\Usuario;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller{
    
    public function index() {  
        $usuarios = Usuario::all();
        return $this->returnData('usuarios',$usuarios,200);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
                            'uid' => 'required|numeric|max:255',
                            'secuencia' => 'required|numeric|max:255',
                            'contrasena' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $usuario = Usuario::create([
                                'uid' => $request->uid,
                                'secuencia' => $request->secuencia,
                                'contrasena' => $request->contrasena
        ]);

        if (!$usuario) 
             return $this->returnEstatus('Error al crear el usuario',200,null); 
        return $this->returnEstatus('Usuario gnerado',200,null); 
    }

    public function show($id, $pasw = null) {         
        
        if($pasw==null)     
            $usuario = Usuario::where('uid',$id)->get();        
        else $usuario = Usuario::where('uid',$id)->where('pasw',$pasw)->get();

        if (!$usuario)
            return $this->returnEstatus('Usuario no encontrado',400,null); 
        return $this->returnData('Usuario',$usuario,200);    
    }

   
    public function update(Request $request, $id){
        $usuario = Usuario::find($id);
        
        if (!$usuario) 
            return $this->returnEstatus('Usuario no encontrado',400,null);

        $validator = Validator::make($request->all(), [
                        'secuencia' => 'required|numeric|max:255',
                        'contrasena' => 'required|max:255'
        ]);

        if ($validator->fails())
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $usuario->uid = $request->uid;
        $usuario->secuencia = $request->secuencia;
        $usuario->contrasena = $request->contrasena;
        $usuario->save();
        return $this->returnEstatus('Usuario actualizado',200,null); 

    }
}
