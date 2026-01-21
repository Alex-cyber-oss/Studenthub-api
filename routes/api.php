<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ResourceController;

// Route de test pour vérifier que l'API fonctionne
Route::get('/test', function () {
    return response()->json([
        'message' => 'API StudentHub fonctionne correctement !',
        'status' => 'OK',
        'timestamp' => now()
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::put('/user', [AuthController::class, 'update'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Routes spéciales AVANT apiResource (sinon elles sont interceptées par {id})
    Route::get('/courses/stats', [CourseController::class, 'stats']);
    Route::get('/courses/shared/{filiere}/{annee?}', [CourseController::class, 'shared']);
    Route::post('/courses/{id}/duplicate', [CourseController::class, 'duplicate']);
    
    // Routes standards avec apiResource
    Route::apiResource('courses', CourseController::class);
    
    // Routes spéciales pour tâches AVANT apiResource
    Route::get('/tasks/upcoming', [TaskController::class, 'upcoming']);
    Route::get('/tasks/calendar', [TaskController::class, 'calendar']);
    
    // Routes standards avec apiResource
    Route::apiResource('tasks', TaskController::class);
    Route::patch('/tasks/{id}/toggle', [TaskController::class, 'toggleComplete']);
    
    // Routes pour ressources
    Route::get('/courses/{course}/resources', [ResourceController::class, 'index']);
    Route::post('/courses/{course}/resources', [ResourceController::class, 'store']);
    Route::delete('/resources/{id}', [ResourceController::class, 'destroy']);
    Route::get('/resources/{id}/download', [ResourceController::class, 'download']);
});

