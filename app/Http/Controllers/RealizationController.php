<?php

namespace App\Http\Controllers;

use App\Models\Realization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RealizationController extends Controller
{
    /**
     * Afficher la liste de toutes les réalisations.
     */
    public function index()
    {
        // On récupère toutes les réalisations triées par date de création décroissante
        $realizations = Realization::orderBy('created_at', 'desc')->get();
        return response()->json($realizations);
    }

    /**
     * Enregistrer une nouvelle réalisation en base de données.
     */
    public function store(Request $request)
    {
        // 1. Validation des données reçues
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Image de 5Mo max
            'description' => 'nullable|string',
            'date' => 'nullable|string|max:255',
            'client' => 'nullable|string|max:255',
        ]);

        $data = $validated;

        // 2. Gestion de l'upload de l'image
        if ($request->hasFile('image')) {
            // Stocke l'image dans storage/app/public/realisations
            $path = $request->file('image')->store('realisations', 'public');
            // Génère l'URL publique absolue
            $data['image'] = asset('storage/' . $path);
        }

        // 3. Création en base de données
        $realization = Realization::create($data);

        return response()->json([
            'message' => 'Réalisation créée avec succès !',
            'realization' => $realization,
        ], 201);
    }

    /**
     * Afficher les détails d'une réalisation spécifique.
     */
    public function show(Realization $realization)
    {
        return response()->json($realization);
    }

    /**
     * Mettre à jour une réalisation existante.
     */
    public function update(Request $request, Realization $realization)
    {
        // 1. Validation des données
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'image' => 'nullable', // Peut être une nouvelle image (file) ou l'ancienne URL (string)
            'description' => 'nullable|string',
            'date' => 'nullable|string|max:255',
            'client' => 'nullable|string|max:255',
        ]);

        $data = $validated;

        // 2. Si une nouvelle image est envoyée sous forme de fichier
        if ($request->hasFile('image')) {
            // Validation spécifique si c'est un fichier
            $request->validate([
                'image' => 'image|mimes:jpeg,png,jpg,webp|max:5120'
            ]);

            // Enregistrer la nouvelle image sans supprimer l'ancienne du serveur
            $path = $request->file('image')->store('realisations', 'public');
            $data['image'] = asset('storage/' . $path);
        } else {
            // Si aucune nouvelle image n'est envoyée, on conserve l'ancienne
            unset($data['image']);
        }

        // 3. Mise à jour en base de données
        $realization->update($data);

        return response()->json([
            'message' => 'Réalisation mise à jour avec succès !',
            'realization' => $realization,
        ]);
    }

    /**
     * Supprimer une réalisation.
     */
    public function destroy(Realization $realization)
    {
        // 1. Supprimer l'image associée du disque
        if ($realization->image) {
            $path = str_replace(asset('storage/'), '', $realization->image);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        // 2. Supprimer l'enregistrement de la base de données
        $realization->delete();

        return response()->json([
            'message' => 'Réalisation supprimée avec succès !',
        ]);
    }
}
