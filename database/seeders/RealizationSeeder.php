<?php

namespace Database\Seeders;

use App\Models\Realization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class RealizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/realisations.json');

        if (!File::exists($jsonPath)) {
            $this->command->warn("Le fichier JSON des réalisations n'a pas été trouvé à l'emplacement : {$jsonPath}");
            return;
        }

        $json = File::get($jsonPath);
        $realisations = json_decode($json, true);

        if (is_array($realisations)) {
            foreach ($realisations as $item) {
                $imagePath = $item['image'] ?? null;
                if ($imagePath) {
                    $imagePath = $this->convertImagePath($imagePath);
                }
                
                Realization::updateOrCreate(
                    [
                        'title' => $item['title'],
                        'category' => $item['category'],
                        'location' => $item['location'] ?? null,
                    ],
                    [
                        'id' => $item['id'],
                        'image' => $imagePath,
                        'description' => $item['description'] ?? null,
                        'date' => (string) ($item['date'] ?? ''),
                        'client' => $item['client'] ?? null,
                    ]
                );
            }
            $this->command->info("Réalisations importées avec succès (" . count($realisations) . " réalisations).");
        } else {
            $this->command->error("Le format du fichier JSON des réalisations est invalide.");
        }
    }
    
    private function convertImagePath(string $path): string
    {
        // Replace /images/realisations/ with /storage/realisations/
        if (str_starts_with($path, '/images/realisations/')) {
            return str_replace('/images/realisations/', '/storage/realisations/', $path);
        }
        
        return $path;
    }
}
