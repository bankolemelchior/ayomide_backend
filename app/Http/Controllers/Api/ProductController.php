<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Liste de tous les produits (publique).
     */
    public function index()
    {
        $products = Product::orderBy('created_at', 'desc')->get();
        return response()->json($products);
    }

    /**
     * Afficher un produit par son slug (publique).
     */
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        return response()->json($product);
    }

    /**
     * Créer un nouveau produit (protégé admin).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'price_m2'       => 'required|integer|min:0',
            'promo_price_m2' => 'nullable|integer|min:0',
            'promo_label'    => 'nullable|string|max:100',
            'dimension'      => 'required|string|max:50',
            'type'           => 'required|in:sol,mur',
            'finition'       => 'required|string|max:100',
            'thickness'      => 'nullable|string|max:50',
            'usage'          => 'nullable|string|max:255',
            'epaisseur'      => 'nullable|string|max:50',
            'images'         => 'nullable|array',
            'images.*'       => 'nullable|string',
            'popular'        => 'boolean',
        ]);

        // Générer un slug unique à partir du nom
        $validated['slug'] = Str::slug($validated['name']);

        // S'assurer que le slug est unique
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Product::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter++;
        }

        $validated['images'] = $validated['images'] ?? [];
        $validated['popular'] = $validated['popular'] ?? false;

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    /**
     * Mettre à jour un produit (protégé admin).
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'price_m2'       => 'sometimes|integer|min:0',
            'promo_price_m2' => 'nullable|integer|min:0',
            'promo_label'    => 'nullable|string|max:100',
            'dimension'      => 'sometimes|string|max:50',
            'type'           => 'sometimes|in:sol,mur',
            'finition'       => 'sometimes|string|max:100',
            'thickness'      => 'nullable|string|max:50',
            'usage'          => 'nullable|string|max:255',
            'epaisseur'      => 'nullable|string|max:50',
            'images'         => 'nullable|array',
            'images.*'       => 'nullable|string',
            'popular'        => 'boolean',
        ]);

        // Régénérer le slug si le nom change
        if (isset($validated['name'])) {
            $newSlug = Str::slug($validated['name']);
            if ($newSlug !== $product->slug) {
                $originalSlug = $newSlug;
                $counter = 1;
                while (Product::where('slug', $newSlug)->where('id', '!=', $product->id)->exists()) {
                    $newSlug = $originalSlug . '-' . $counter++;
                }
                $validated['slug'] = $newSlug;
            }
        }

        $product->update($validated);

        return response()->json($product);
    }

    /**
     * Supprimer un produit (protégé admin).
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Produit supprimé avec succès'], 200);
    }
}
