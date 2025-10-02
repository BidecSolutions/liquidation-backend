<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleMakeSeeder extends Seeder
{
    public function run(): void
    {
        // Step 1: Truncate table
        DB::table('vehicle_data')->truncate();

        // Step 2: Load JSON file
        $json = Storage::get("vehicle/all_makes.json");
        $data = json_decode($json, true);

        $makes = $data['Results'] ?? [];

        $count = 0;

        // Allowed makes list (only these will be inserted)
        $allowedMakes = [
            'Toyota',
            'Hyundai',
            'Kia',
            'Nissan',
            'Mazda',
            'Ford',
            'Isuzu',
            'Suzuki',
            'Changan',
            'Geely',
            'Lexus',
            'Mercedes-Benz',
            'BMW',
            'Land Rover',
            'Infiniti',
        ];

        // Step 3: Loop and insert only matching makes
        foreach ($makes as $chunk) {
            $insertData = [];
            print($chunk);
            foreach ($chunk as $make) {
                $makeName = trim($make['Make_Name']);

                if (in_array($makeName, $allowedMakes, true)) {
                    $insertData[] = [
                        'make'       => $makeName,
                        'make_slug'  => Str::slug($makeName),
                        'model'      => null,
                        'year'       => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $count++;
                }
            }

            if (!empty($insertData)) {
                DB::table('vehicle_data')->insertOrIgnore($insertData);
            }
        }

        $this->command->info("✅ Inserted {$count} filtered makes from cached JSON.");
    }
}
