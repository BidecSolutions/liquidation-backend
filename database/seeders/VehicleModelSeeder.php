<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VehicleModelSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch all makes that don’t yet have models
        $makes = DB::table('vehicle_data')
            ->whereNull('model')
            ->whereNull('year')
            ->pluck('make');

        $inserted = 0;
        $failed = 0;
        $total = count($makes);

        $this->command->info("🚀 Starting model seeding for {$total} makes...");

        foreach ($makes as $index => $make) {
            $url = "https://vpic.nhtsa.dot.gov/api/vehicles/getmodelsformake/" . urlencode($make) . "?format=json";

            try {
                $response = Http::timeout(10)->retry(3, 500)->get($url);

                if ($response->failed()) {
                    $this->command->warn("⚠️ Skipping make: {$make} (API failed)");
                    $failed++;
                    continue;
                }

                $models = $response->json()['Results'] ?? [];

                if (empty($models)) {
                    $this->command->warn("⚠️ No models found for {$make}");
                    continue;
                }

                foreach ($models as $modelItem) {
                    $modelName = trim($modelItem['Model_Name'] ?? '');

                    if (!$modelName) {
                        continue;
                    }

                    DB::table('vehicle_data')->updateOrInsert(
                        ['make' => $make, 'model' => $modelName, 'year' => null],
                        [
                            'make_slug'  => Str::slug($make),
                            'model_slug' => Str::slug($modelName),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );

                    $inserted++;
                }

                $this->command->info("✅ Synced " . count($models) . " models for {$make}");

                // Throttle to avoid hammering API (pause every 20 requests)
                if ($index % 20 === 0 && $index > 0) {
                    sleep(2);
                }

            } catch (\Exception $e) {
                $this->command->error("❌ Exception for {$make}: " . $e->getMessage());
                $failed++;
                continue;
            }
        }

        $this->command->info("🎉 Finished seeding. Inserted {$inserted} models. Failed {$failed} makes.");
    }
}
