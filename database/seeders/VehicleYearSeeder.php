<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VehicleYearSeeder extends Seeder
{
    public function run(): void
    {
        $makes = DB::table('vehicle_data')
            ->whereNotNull('model')
            ->whereNull('year')
            ->pluck('make')
            ->unique();

        $currentYear = now()->year;
        $startYear = 2000;

        $inserted = 0;

        foreach ($makes as $make) {
            for ($year = $startYear; $year <= $currentYear; $year++) {
                $url = "https://www.carqueryapi.com/api/0.3/?cmd=getModels&make=" . urlencode($make) . "&year={$year}";
                $response = Http::timeout(60)->get($url);

                if ($response->failed()) {
                    $this->command->warn("⚠️ Failed for {$make} - {$year}");
                    continue;
                }

                $models = $response->json()['Models'] ?? [];

                foreach ($models as $modelItem) {
                    $modelName = trim($modelItem['model_name'] ?? $modelItem['model'] ?? '');

                    if (!$modelName) {
                        continue;
                    }

                    DB::table('vehicle_data')->updateOrInsert(
                        ['make' => $make, 'model' => $modelName, 'year' => $year],
                        [
                            'make_slug'  => Str::slug($make),
                            'model_slug' => Str::slug($modelName),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );

                    $inserted++;
                }

                $this->command->info("✅ Synced {$make} for year {$year}");
            }
        }

        $this->command->info("🎉 Seeded {$inserted} year-specific models.");
    }
}
