<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    // Upload d'une image
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        $file = $request->file('image');
        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('carrelage', $filename, 'public');

        return response()->json([
            'url' => Storage::url($path),
            'path' => $path,
            'filename' => $filename
        ]);
    }

    // Upload multiple
    public function uploadMultiple(Request $request)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $file) {
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('carrelage', $filename, 'public');

            $uploadedImages[] = [
                'url' => Storage::url($path),
                'path' => $path,
                'filename' => $filename
            ];
        }

        return response()->json($uploadedImages);
    }

    // Supprimer une image
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        if (Storage::disk('public')->delete($request->path)) {
            return response()->json(['message' => 'Image supprimée']);
        }

        return response()->json(['message' => 'Erreur lors de la suppression'], 400);
    }
}
