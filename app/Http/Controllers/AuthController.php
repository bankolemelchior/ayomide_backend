<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authentifier l'utilisateur via session (cookie-based).
     */
    public function login(Request $request)
    {
        // 1. Validation des champs d'entrée
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Tentative de connexion via le guard standard (web)
        // Le paramètre 'remember' permet de garder la session active plus longtemps
        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        // 3. Régénérer l'ID de session pour des raisons de sécurité (prévention contre la fixation de session)
        $request->session()->regenerate();

        // 4. Retourner l'utilisateur connecté
        return response()->json([
            'message' => 'Connexion réussie',
            'user' => Auth::user(),
        ]);
    }

    /**
     * Déconnecter l'utilisateur (destruction de la session et du cookie).
     */
    public function logout(Request $request)
    {
        // 1. Déconnexion de l'utilisateur
        Auth::guard('web')->logout();

        // 2. Invalidation complète de la session en cours
        $request->session()->invalidate();

        // 3. Régénération du token CSRF pour la prochaine requête de login
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Obtenir les informations de l'utilisateur actuellement connecté.
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}
