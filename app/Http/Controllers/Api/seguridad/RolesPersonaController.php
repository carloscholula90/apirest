<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use App\Models\seguridad\RolesPersona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;


class RolesPersonaController extends Controller
{

    public function index()
    {
        $rolespersona = RolesPersona::all();

        return $this->returnData('aceptaAvisos',$rolespersona,200);

    }

    public function create(Request $request)
    {
        $rolesPersona = RolesPersona::where('idRol',$request->idRol)
                                   ->where('uid',$request->uid)
                                   ->where('secuencia',$request->secuencia)
                                   ->get();

        if ($rolesPersona->isEmpty())
            return $this->returnEstatus('0',200,NULL);
        else
            return $this->returnEstatus('1',200,NULL);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
