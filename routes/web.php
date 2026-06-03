<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| rutas son cargadas por RouteServiceProvider y asignadas al grupo web.
|
*/

// Ruta para el frontend SPA
Route::get('/', function () {
    return file_get_contents(public_path('frontend/index.html'));
});

// Ruta catch-all para el frontend (para que funcione client-side routing)
Route::get('/{path}', function () {
    return file_get_contents(public_path('frontend/index.html'));
})->where('path', '^(?!api).*$');


