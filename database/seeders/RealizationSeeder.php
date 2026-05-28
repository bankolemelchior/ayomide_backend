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
        // Chemin vers le fichier JSON dans le dossier frontend
        $jsonPath = base_path('../ayomide_front/app/data/realisations.json');

        if (!File::exists($jsonPath)) {
            $this->command->warn("Le fichier JSON des réalisations n'a pas été trouvé à l'emplacement : {$jsonPath}");
            return;
        }

        $json = File::get($jsonPath);
        $realisations = json_decode($json, true);

        if (is_array($realisations)) {
            foreach ($realisations as $item) {
                Realization::updateOrCreate(
                    [
                        'title' => $item['title'],
                        'category' => $item['category'],
                        'location' => $item['location'] ?? null,
                    ],
                    [
                        'id' => $item['id'], // Conserver l'ID d'origine
                        'image' => $item['image'] ?? null,
                        'description' => $item['description'] ?? null,
                        'date' => (string) ($item['date'] ?? ''),
                        'client' => $item['client'] ?? null,
                    ]
                );
            }
            $this->command->info("Réalisations importées avec succès depuis le JSON frontend (" . count($realisations) . " réalisations).");
        } else {
            $this->command->error("Le format du fichier JSON des réalisations est invalide.");
        }
    }
}
