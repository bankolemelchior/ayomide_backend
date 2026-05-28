<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authentifie l'utilisateur et génère un token Sanctum.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ["Les identifiants de connexion fournis sont incorrects."],
            ]);
        }

        // Révoquer les anciens tokens pour n'en garder qu'un seul actif (optionnel, mais plus sûr pour un admin)
        $user->tokens()->delete();

        // Générer le jeton personnel
        $token = $user->createToken('admin-api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'admin', // Valeur par défaut
            ]
        ]);
    }

    /**
     * Déconnecte l'utilisateur en révoquant son jeton actuel.
     */
    public function logout(Request $request)
    {
        // Révoquer le token qui a servi à s'authentifier
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }
}
