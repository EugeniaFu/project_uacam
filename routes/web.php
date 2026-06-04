<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Este proyecto usa un frontend estático ubicado en /public/frontend.
| Para que las rutas relativas (js/app.js, css/, assets/) funcionen,
| redirigimos la raíz a /frontend/index.html.
|
*/

Route::redirect('/', '/frontend/index.html');

