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
        $jsonPath = database_path('seeders/data/products.json');

        if (!File::exists($jsonPath)) {
            $this->command->warn("Le fichier JSON des produits n'a pas été trouvé à l'emplacement : {$jsonPath}");
            return;
        }

        $json = File::get($jsonPath);
        $products = json_decode($json, true);

        if (is_array($products)) {
            foreach ($products as $item) {
                // Convert image paths to use backend storage URLs
                $images = [];
                foreach ($item['images'] ?? [] as $imgPath) {
                    $images[] = $this->convertImagePath($imgPath);
                }
                
                Product::updateOrCreate(
                    ['slug' => $item['slug']],
                    [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'price_m2' => $item['price_m2'],
                        'dimension' => $item['dimension'],
                        'type' => $item['type'],
                        'finition' => $item['finition'],
                        'thickness' => $item['thickness'] ?? null,
                        'usage' => $item['usage'] ?? null,
                        'epaisseur' => $item['epaisseur'] ?? null,
                        'images' => $images,
                        'popular' => $item['popular'] ?? false,
                    ]
                );
            }
            $this->command->info("Produits importés avec succès (" . count($products) . " produits).");
        } else {
            $this->command->error("Le format du fichier JSON des produits est invalide.");
        }
    }
    
    private function convertImagePath(string $path): string
    {
        // Convert frontend /images paths into backend /storage paths
        if (str_starts_with($path, '/images/')) {
            return str_replace('/images/', '/storage/', $path);
        }

        return $path;
    }
}
