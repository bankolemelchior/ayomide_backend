<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EstimateController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\API\ImageController;

// ─── Routes publiques ───────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// Produits : lecture publique
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// Devis : soumission publique
Route::post('/estimates', [EstimateController::class, 'store']);

// ─── Routes protégées (admin) ────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Utilisateurs : administration protégée
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Produits : écriture protégée
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Devis : consultation sécurisée
    Route::get('/estimates', [EstimateController::class, 'index']);
    Route::patch('/estimates/{id}/status', [EstimateController::class, 'updateStatus']);
    Route::delete('/estimates/{id}', [EstimateController::class, 'destroy']);

    // Routes images
    Route::post('/upload/image', [ImageController::class, 'upload']);
    Route::post('/upload/images', [ImageController::class, 'uploadMultiple']);
    Route::delete('/upload/image', [ImageController::class, 'delete']);
});

