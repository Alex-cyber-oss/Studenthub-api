<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskReminderController;
use App\Http\Controllers\ResourceController;

// Route de test pour vérifier que la liaison avec la base de données fonctionne
Route::get('/test', function () {
    $connection = config('database.default');
    $dbConfig = config("database.connections.{$connection}");

    try {
        DB::connection()->getPdo();

        return response()->json([
            'message' => 'API StudentHub fonctionne correctement !',
            'db_connected' => true,
            'db_connection' => $connection,
            'db_host' => $dbConfig['host'] ?? null,
            'db_database' => $dbConfig['database'] ?? null,
            'db_username' => $dbConfig['username'] ?? null,
            'status' => 'OK',
            'timestamp' => now(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'La connexion à la base de données a échoué.',
            'db_connected' => false,
            'db_connection' => $connection,
            'db_host' => $dbConfig['host'] ?? null,
            'db_database' => $dbConfig['database'] ?? null,
            'db_username' => $dbConfig['username'] ?? null,
            'error' => $e->getMessage(),
            'status' => 'ERROR',
            'timestamp' => now(),
        ], 500);
    }
});

// Route pour vérifier les tables de la base de données
Route::get('/db-check', function () {
    try {
        DB::connection()->getPdo();

        $tables = DB::select("
            select tablename
            from pg_tables
            where schemaname = 'public'
        ");

        return response()->json([
            'db_connected' => true,
            'tables' => array_map(fn($row) => $row->tablename, $tables),
            'tables_count' => count($tables),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'db_connected' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
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
    
    // Routes pour rappels (reminders)
    Route::get('/reminders/pending', [TaskReminderController::class, 'pending']);
    Route::get('/reminders/sync', [TaskReminderController::class, 'sync']);
    Route::get('/reminders/stats', [TaskReminderController::class, 'stats']);
    Route::get('/reminders', [TaskReminderController::class, 'index']);
    Route::patch('/reminders/{id}/sent', [TaskReminderController::class, 'markAsSent']);
    
    // Routes pour ressources
    Route::get('/courses/{course}/resources', [ResourceController::class, 'index']);
    Route::post('/courses/{course}/resources', [ResourceController::class, 'store']);
    Route::delete('/resources/{id}', [ResourceController::class, 'destroy']);
    Route::get('/resources/{id}/download', [ResourceController::class, 'download']);
});

