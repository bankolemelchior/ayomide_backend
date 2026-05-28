<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RealizationController;
use Illuminate\Support\Facades\Route;

// --- ROUTE DE TEST ---
Route::get('/test', function () {
    return response()->json(['message' => 'Le backend Ayomide fonctionne parfaitement !']);
});

// --- AUTHENTIFICATION ---
Route::post('/login', [AuthController::class, 'login']);

// --- ROUTES PUBLIQUES (Galerie pour les visiteurs) ---
Route::get('/realizations', [RealizationController::class, 'index']);
Route::get('/realizations/{realization}', [RealizationController::class, 'show']);

// --- ROUTES PROTÉGÉES (Espace Admin) ---
Route::middleware('auth:sanctum')->group(function () {
    // Profil & Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // CRUD des Réalisations sécurisé
    Route::post('/realizations', [RealizationController::class, 'store']);
    Route::delete('/realizations/{realization}', [RealizationController::class, 'destroy']);
    
    // Note technique : En PHP/Laravel, les requêtes PUT/PATCH ne supportent pas nativement l'upload de fichiers (multipart/form-data). Nous utiliserons une requête POST (ou un spoofing de méthode `_method=PUT` côté frontend).
    Route::post('/realizations/{realization}', [RealizationController::class, 'update']);
});