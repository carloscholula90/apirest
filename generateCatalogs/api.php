<?php
use App\Http\Controllers\Api\{ruta}\{Nombre}Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [{Nombre}Controller::class, 'index']);
Route::get('/{id{Nombre}}', [{Nombre}Controller::class, 'show']);
Route::post('/create', [{Nombre}Controller::class, 'store']);
Route::put('/{id{Nombre}}', [{Nombre}Controller::class, 'update']);
Route::delete('/{id{Nombre}}', [{Nombre}Controller::class, 'destroy']);
