<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Configuration CORS pour permettre les requêtes depuis React
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Pour les requêtes API, retourner des réponses JSON au lieu de rediriger
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });
        
        // Gérer les erreurs d'authentification pour les API
        $exceptions->render(function (AuthenticationException $e, $request) {
            // Toujours retourner JSON pour les routes API
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Non authentifié.',
                    'error' => 'Vous devez être connecté pour accéder à cette ressource.'
                ], 401);
            }
            
            // Pour les autres requêtes qui attendent JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Non authentifié.',
                    'error' => 'Vous devez être connecté pour accéder à cette ressource.'
                ], 401);
            }
        });
    })->create();
