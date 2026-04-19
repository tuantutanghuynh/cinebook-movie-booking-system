<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\LoginController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/movies', [MovieController::class, 'homepage']);

Route::post('/login', [LoginController::class, 'apiLogin']);
Route::post('/logout', [LoginController::class, 'apiLogout'])->middleware('auth:sanctum');
