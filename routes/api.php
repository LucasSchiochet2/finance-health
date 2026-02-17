<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Routes (Public)

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        // Auth Routes (Protected)

    });
        Route::get('/cards/{user}', [CardController::class, 'index']);
        Route::post('/cards/{user}', [CardController::class, 'store']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::get('/bills/{user}', [BillController::class, 'index']);
        Route::post('/bills/{user}', [BillController::class, 'store']);
        Route::get('/bills/{user}/notify', [BillController::class, 'notify']);
        Route::get('/bills/{user}/category/{categoryId}', [BillController::class, 'showByCategory']);
        Route::get('/bills/{user}/{id}', [BillController::class, 'show']);
        Route::put('/bills/{user}/{id}', [BillController::class, 'update']);
        Route::delete('/bills/{user}/{id}', [BillController::class, 'destroy']);
                Route::post('/exercises/{user}', [ExerciseController::class, 'store']);
        Route::delete('/exercises/{user}/{id}', [ExerciseController::class, 'destroy']);
        Route::post('/exercises/{user}/{id}/logs', [ExerciseController::class, 'addLog']);

        Route::get('/workouts/{user}', [WorkoutController::class, 'index']);
        Route::post('/workouts/{user}', [WorkoutController::class, 'store']);
        Route::get('/workouts/{user}/{id}', [WorkoutController::class, 'show']);
