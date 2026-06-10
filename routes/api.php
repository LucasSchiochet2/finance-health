<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\DietMealController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Routes (Public)

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        
    });

    Route::post('/whatsapp/webhook', [WhatsAppController::class, 'handle']);
    //--------- User Routes ---------
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::get('/monthly-spend/{user}', [ReportController::class, 'getMonthlySpend']);
        Route::put('/user/{user}', [AuthController::class, 'update']);

        //--------- Investment Routes ---------
        Route::get('/investments/{user}/summary', [InvestmentController::class, 'summary']);
        Route::get('/investments/{user}/goal', [InvestmentController::class, 'goal']);
        Route::put('/investments/{user}/goal', [InvestmentController::class, 'updateGoal']);
        Route::get('/investments/{user}', [InvestmentController::class, 'index']);
        Route::post('/investments/{user}', [InvestmentController::class, 'store']);
        Route::get('/investments/{user}/{id}', [InvestmentController::class, 'show']);
        Route::put('/investments/{user}/{id}', [InvestmentController::class, 'update']);
        Route::delete('/investments/{user}/{id}', [InvestmentController::class, 'destroy']);

        //--------- Bill Routes ---------
        Route::get('/bills/{user}', [BillController::class, 'index']);
        Route::post('/bills/{user}', [BillController::class, 'store']);
        Route::get('/bills/{user}/notify', [BillController::class, 'notify']);
        Route::get('/bills/{user}/spending-by-category', [BillController::class, 'spendingByCategory']);
        Route::get('/bills/{user}/category/{categoryId}', [BillController::class, 'showByCategory']);
        Route::get('/bills/{user}/{id}', [BillController::class, 'show']);
        Route::put('/bills/{user}/{id}', [BillController::class, 'update']);
        Route::delete('/bills/{user}/{id}', [BillController::class, 'destroy']);

        //--------- Card Routes ---------
        Route::get('/cards/{user}', [CardController::class, 'index']);
        Route::post('/cards/{user}', [CardController::class, 'store']);
        Route::put('/cards/{user}/{id}', [CardController::class, 'update']);

        //--------- Diet Routes ---------
        Route::get('/diet/{user}/charts', [DietMealController::class, 'charts']);
        Route::get('/diet/{user}', [DietMealController::class, 'index']);
        Route::post('/diet/{user}', [DietMealController::class, 'store']);
        Route::get('/diet/{user}/{id}', [DietMealController::class, 'show']);
        Route::put('/diet/{user}/{id}', [DietMealController::class, 'update']);
        Route::delete('/diet/{user}/{id}', [DietMealController::class, 'destroy']);

        //--------- Exercise Routes ---------
        Route::get('/exercises/{user}', [ExerciseController::class, 'index']);
        Route::get('/exercises/{user}/{id}', [ExerciseController::class, 'show']);
        Route::post('/exercises/{user}', [ExerciseController::class, 'store']);
        Route::delete('/exercises/{user}/{id}', [ExerciseController::class, 'destroy']);
        Route::get('/exercises/{user}/{id}/logs', [ExerciseController::class, 'getLogs']);
        Route::post('/exercises/{user}/{id}/logs', [ExerciseController::class, 'addLog']);

        //--------- Workout Routes ---------
        Route::get('/workouts/{user}', [WorkoutController::class, 'index']);
        Route::post('/workouts/{user}', [WorkoutController::class, 'store']);
        Route::get('/workouts/{user}/{id}', [WorkoutController::class, 'show']);
        Route::put('/workouts/{user}/{id}', [WorkoutController::class, 'update']);
        Route::delete('/workouts/{user}/{workoutId}/exercises/{exerciseId}', [WorkoutController::class, 'removeExercise']);
