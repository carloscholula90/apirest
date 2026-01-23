<?php
use App\Http\Controllers\Api\escolar\ImpresionDocumentoController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 

Route::get('/{idServicio}/{matricula}/{folio}', [ImpresionDocumentoController::class, 'generaReporte']);





  