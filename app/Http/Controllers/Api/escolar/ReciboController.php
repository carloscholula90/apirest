<?php

namespace App\Http\Controllers\Api\escolar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PDF;
use Illuminate\Support\Facades\Storage;

class ReciboController extends Controller
{
    public function generarYGuardarPDF()
    {
        $datos = [
            'uid' => '123456',
            'nombre' => 'Juan Pérez',
            'carrera' => 'Licenciatura en Sistemas',
            'telefono' => '2222233444',
            'email' => 'juan.perez@email.com',
        ];

        // Genera el nombre del archivo
        $nameReport = 'recibo_' . time() . '.pdf';

        // Crea el PDF desde la vista
        $pdf = PDF::loadView('recibo', $datos)->setPaper('letter', 'portrait');

        // Guarda el archivo en storage/app/public
        Storage::disk('public')->put($nameReport, $pdf->output());

        // Retorna la URL pública
        return response()->json([
            'status' => 200,
            'message' => asset('storage/' . $nameReport) // URL accesible públicamente
        ]);
    }
}