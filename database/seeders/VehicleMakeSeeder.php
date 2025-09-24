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
        $json = Storage::get("vehicle/all_makes.json");
        $data = json_decode($json, true);

        $makes = $data['Results'] ?? [];
        $count = 0;

        foreach (array_chunk($makes, 500) as $chunk) {
            $insertData = [];
            foreach ($chunk as $make) {
                $makeName = trim($make['Make_Name']);

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
            DB::table('vehicle_data')->insertOrIgnore($insertData);
        }

        $this->command->info("✅ Inserted {$count} makes from cached JSON.");
    }
}
