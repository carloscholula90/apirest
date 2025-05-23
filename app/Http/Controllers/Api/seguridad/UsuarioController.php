<?php
namespace App\Http\Controllers\Api\seguridad;  
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\seguridad\Usuario;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller{
    
    public function index() {     
        $usuarios = Usuario::join('persona as p', 'p.uid', '=', 'usuario.uid')
                            ->select('usuario.uid', 'usuario.contrasena', 'usuario.contrasena', 'p.nombre', 'p.primerApellido', 'p.segundoApellido')
                            ->get();
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

    public function show($id,$pasw) {         
        $query = Usuario::where('uid', $id);
        $query->where('contrasena', $pasw); 
        $usuario = $query->get();

        if ($usuario->isEmpty())
            return $this->returnData('Usuario',0,200);  
        else if($usuario->contrasena = $pasw && $usuario->contrasena=$id) 
                return $this->returnData('Usuario',1,200); 
            else  $this->returnData('Usuario',0,200);                                                                                                    
    }

     public function update(Request $request){   
       $usuario = Usuario::find($request->uid);  
           
        if (!$usuario) 
            return $this->returnEstatus('Usuario no encontrado',400,null);

        $validator = Validator::make($request->all(), [   
                        'contrasena' => 'required|max:255'
        ]);
  
        if ($validator->fails())
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $usuario->uid = $request->uid;   
        $usuario->contrasena = $request->contrasena;
        $usuario->save();
        return $this->returnEstatus('Contraseña actualizada',200,null); 
    }
}
