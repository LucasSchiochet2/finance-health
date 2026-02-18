<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Routes (Public)

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/monthly-spend/{user}', [ReportController::class, 'getMonthlySpend']);
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

        Route::get('/exercises/{user}', [ExerciseController::class, 'index']);
        Route::get('/exercises/{user}/{id}', [ExerciseController::class, 'show']);
        Route::post('/exercises/{user}', [ExerciseController::class, 'store']);
        Route::delete('/exercises/{user}/{id}', [ExerciseController::class, 'destroy']);
        Route::get('/exercises/{user}/{id}/logs', [ExerciseController::class, 'getLogs']);
        Route::post('/exercises/{user}/{id}/logs', [ExerciseController::class, 'addLog']);


        Route::get('/workouts/{user}', [WorkoutController::class, 'index']);
        Route::post('/workouts/{user}', [WorkoutController::class, 'store']);
        Route::get('/workouts/{user}/{id}', [WorkoutController::class, 'show']);
        Route::put('/workouts/{user}/{id}', [WorkoutController::class, 'update']);
        Route::delete('/workouts/{user}/{workoutId}/exercises/{exerciseId}', [WorkoutController::class, 'removeExercise']);
