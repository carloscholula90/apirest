<?php

namespace App\Http\Controllers\Api\admisiones; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\admisiones\Aspirante;
    
class AspiranteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $aspirantes = Aspirante::all();
        return $this->returnData('aspirantes',$aspirantes,200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request) 
    {           
        $validator = Validator::make($request->all(), [
                            'uid' => 'required|numeric|max:11',
                            'idPeriodo' => 'required|numeric|max:11',
                            'idCarrera' => 'required|numeric|max:11',
                            'adeudoAsignaturas' => 'required|numeric|max:11',
                            'idNivel' => 'required|numeric|max:11',
                            'idMedio' => 'required|numeric|max:11',
                            'publica' => 'required|numeric|max:1',
                            'paisCursoGradoAnterior' => 'required|numeric|max:11',
                            'estadoCursoGradoAnterior' => 'required|numeric|max:11',
                            'uidEmpleado' => 'required|numeric|max:11',
                            'mesReprobada' => 'required|numeric|max:11',
                            'observaciones' => 'required|max:255'

        ]);

        if ($validator->fails()) 
        return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Aspirante::where('uid', $request->uid)  
                                 ->max('secuencia');  

        $newId = $maxId ? $maxId + 1 : 1; 
        $aspirante = Aspirante::create([
                            'secuencia' => $newId,
                            'uid' => $request->uid,
                            'idPeriodo' => $request->idPeriodo,
                            'idCarrera' => $request->idCarrera,
                            'adeudoAsignaturas' => $request->adeudoAsignaturas,
                            'idNivel' => $request->idNivel,
                            'idMedio' => $request->idMedio,
                            'publica' => $request->publica,
                            'paisCursoGradoAnterior' => $request->paisCursoGradoAnterior,
                            'estadoCursoGradoAnterior' => $request->estadoCursoGradoAnterior,
                            'uidEmpleado' =>$request->uidEmpleado,
                            'mesReprobada' => $request->mesReprobada,
                            'observaciones' => $request->observaciones
        ]);

        if (!$aspirante) 
            return $this->returnEstatus('Error al crear el asentamiento',500,null); 
        return $this->returnData('aspirante',$aspirante,201);   
    }     

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

}
