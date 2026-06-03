<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;

Route::post('/auth/check-account',[AuthController::class,'checkAccount']);
Route::post('/auth/login',[AuthController::class,'login']);

// Rutas protegidas con middleware de autenticación y sesión segura
Route::middleware(['auth:sanctum', 'secure.session', 'validate.session'])->group(function(){
    Route::post('/auth/logout',[AuthController::class,'logout']);
    Route::get('/dashboard/summary',[DashboardController::class,'summary']);
    Route::apiResource('products', ProductController::class);
    Route::post('/requests',[RequestController::class,'store']);
    Route::get('/requests/history',[RequestController::class,'history']);
    Route::get('/requests/{id}',[RequestController::class,'show']);
    Route::put('/requests/{id}',[RequestController::class,'update']);
    Route::post('/requests/{id}/return',[RequestController::class,'markReturn']);
    Route::delete('/requests/{id}',[RequestController::class,'destroy']);
    Route::get('/movements',[MovementController::class,'index']);
    Route::apiResource('users', UserController::class)->only(['index','store','update']);
    Route::post('/users/{id}/toggle',[UserController::class,'toggle']);
    Route::get('/categories', function(){ return \App\Models\Category::all(); });
    Route::get('/reports/movements',[ReportController::class,'movementsPdf']);
    Route::get('/reports/requests',[ReportController::class,'requestsPdf']);
});

