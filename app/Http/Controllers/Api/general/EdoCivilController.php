<?php

namespace App\Http\Controllers\Api\general; 

use Illuminate\Http\Request;

class EdoCivilController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $edocivil = EdoCivil::all();

        $data = [
            'edocivil' => $edocivil,
            'status' => 200
        ];

        return response()->json($data, 200);
        
        public function store(Request $request)
           {

            $validator = Validator::make($request->all(), [
                'descripcion' => 'required|max:255'
            ]);

            if ($validator->fails()) {
                $data = [
                    'message' => 'Error en la validación de los datos',
                    'errors' => $validator->errors(),
                    'status' => 400
                ];
                return response()->json($data, 400);
            }
    
            $maxIdEdoCivil = EdoCivil::max('idEdoCivil');
            $newIdEdoCivil = $maxIdEdoCivil ? $maxIdEdoCivil + 1 : 1;
            $edocivil = EdoCivil::create([
                'idEdoCivil' => $newIdEdoCivil,
                'descripcion' => $request->descripcion
            ]);
    
    
           }

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
