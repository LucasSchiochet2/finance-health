<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Routes (Public)

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        // Auth Routes (Protected)
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        // Bills Routes
        Route::get('/bills/{user}', [BillController::class, 'index']);
        Route::post('/bills/{user}', [BillController::class, 'store']);
        Route::get('/bills/{user}/category/{categoryId}', [BillController::class, 'showByCategory']);
        Route::get('/bills/{user}/{id}', [BillController::class, 'show']);
        Route::put('/bills/{user}/{id}', [BillController::class, 'update']);
        Route::delete('/bills/{user}/{id}', [BillController::class, 'destroy']);

        // Cards Routes

    });
Route::get('/cards/{user}', [CardController::class, 'index']);
Route::post('/cards/{user}', [CardController::class, 'store']);
