<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VehicleMakeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $makeUrl = "https://vpic.nhtsa.dot.gov/api/vehicles/getallmakes?format=json";
        $makeResponse = Http::get($makeUrl);

        if ($makeResponse->failed()) {
            $this->command->error("❌ Failed to fetch makes from API.");
            return;
        }

        $makes = $makeResponse->json()['Results'] ?? [];

        $totalMakes = count($makes);
        $insertedModels = 0;

        foreach ($makes as $makeItem) {
            $makeName = trim($makeItem['Make_Name']);

            // Save make (without model)
            DB::table('vehicle_data')->updateOrInsert(
                ['make' => $makeName, 'model' => null, 'year' => null],
                [
                    'make_slug' => Str::slug($makeName),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Fetch models for this make
            $modelUrl = "https://vpic.nhtsa.dot.gov/api/vehicles/getmodelsformake/" . urlencode($makeName) . "?format=json";
            $modelResponse = Http::get($modelUrl);

            if ($modelResponse->failed()) {
                $this->command->warn("⚠️ Failed to fetch models for make: {$makeName}");
                continue;
            }

            $models = $modelResponse->json()['Results'] ?? [];

            foreach ($models as $modelItem) {
                $modelName = trim($modelItem['Model_Name']);

                DB::table('vehicle_data')->updateOrInsert(
                    ['make' => $makeName, 'model' => $modelName, 'year' => null],
                    [
                        'make_slug'  => Str::slug($makeName),
                        'model_slug' => Str::slug($modelName),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $insertedModels++;
            }

            $this->command->info("✅ Synced models for make: {$makeName}");
        }

        $this->command->info("🎉 Seeded {$totalMakes} makes and {$insertedModels} models into vehicle_data.");
    }
}
