<?php

namespace App\Http\Controllers\Api\general; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\general\Usuario;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller{

    public function index() {
        $usuarios = Usuario::all();

        $data = [
            'usuarios' => $usuarios,
            'status' => 200
        ];
        return response()->json($data, 200);
    }

    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'uid' => 'required|numeric|max:255',
            'secuencia' => 'required|numeric|max:255',
            'contrasena' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $usuario = Usuario::create([
            'uid' => $request->uid,
            'secuencia' => $request->secuencia,
            'contrasena' => $request->contrasena
        ]);

        if (!$usuario) {
            $data = [
                'message' => 'Error al crear el usuario',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        $data = [
            'usuario' => $usuario,
            'status' => 201
        ];
        return response()->json($data, 201);
    }

    public function show($id, $pasw) {

        $usuario = Usuario::find($id,$pasw);

        if (!$usuario) {
            $data = [
                'message' => 'Usuario no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'usuario' => $usuario,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function destroy($id) {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            $data = [
                'message' => 'Usuario no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $usuario->delete();

        $data = [
            'message' => 'Usuario eliminado',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $id){
        $usuario = Usuario::find($id);

        if (!$usuario) {
            $data = [
                'message' => 'Usuario no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'uid' => 'required|numeric|max:255',
            'secuencia' => 'required|numeric|max:255',
            'contrasena' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $usuario->uid = $request->uid;
        $usuario->secuencia = $request->secuencia;
        $usuario->contrasena = $request->contrasena;
        $usuario->save();

        $data = [
            'message' => 'Usuario actualizado',
            'usuario' => $usuario,
            'status' => 200
        ];
        return response()->json($data, 200);
    }

    public function updatePartial(Request $request, $id) {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            $data = [
                'message' => 'Usuario no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
           'uid' => 'required|numeric|max:255',
           'secuencia' => 'required|numeric|max:255',
           'contrasena' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        if ($request->has('uid')) {
            $usuario->uid = $request->uid;
        }

        if ($request->has('secuencia')) {
            $usuario->secuencia = $request->secuencia;
        }
        if ($request->has('contrasena')) {
            $usuario->contrasena = $request->contrasena;
        }

        $usuario->save();

        $data = [
            'message' => 'Usuario actualizado',
            'usuario' => $usuario,
            'status' => 200
        ];

        return response()->json($data, 200);
    }
}
