<?php
namespace App\Http\Controllers\Api\seguridad; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\seguridad\PermisoPersona;
use Illuminate\Support\Facades\DB;   

class PermisoPersonaController extends Controller
{  
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
      
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
    public function show($id,$idRol)
    {
          $permisos = DB::table('aplicacionesUsuario')
                        ->where('uid', $id)
                        ->where('idRol', $idRol)
                        ->get();

          if (!$permisos)
            return $this->returnEstatus('Sin aplicaciones en el rol. Favor de validar',400,null); 
          return $this->returnData('Permisos',$permisos,200);  
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
