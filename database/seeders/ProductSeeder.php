<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Chemin vers le fichier JSON dans le dossier frontend
        $jsonPath = base_path('../ayomide_front/app/data/products.json');

        if (!File::exists($jsonPath)) {
            $this->command->warn("Le fichier JSON des produits n'a pas été trouvé à l'emplacement : {$jsonPath}");
            return;
        }

        $json = File::get($jsonPath);
        $products = json_decode($json, true);

        if (is_array($products)) {
            foreach ($products as $item) {
                Product::updateOrCreate(
                    ['slug' => $item['slug']], // Clé unique d'identification
                    [
                        'id' => $item['id'], // Conserver l'ID d'origine du JSON si nécessaire
                        'name' => $item['name'],
                        'price_m2' => $item['price_m2'],
                        'dimension' => $item['dimension'],
                        'type' => $item['type'],
                        'finition' => $item['finition'],
                        'thickness' => $item['thickness'] ?? null,
                        'usage' => $item['usage'] ?? null,
                        'epaisseur' => $item['epaisseur'] ?? null,
                        'images' => $item['images'] ?? [],
                        'popular' => $item['popular'] ?? false,
                    ]
                );
            }
            $this->command->info("Produits importés avec succès depuis le JSON frontend (" . count($products) . " produits).");
        } else {
            $this->command->error("Le format du fichier JSON des produits est invalide.");
        }
    }
}
