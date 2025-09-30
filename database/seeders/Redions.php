<?php

namespace Database\Seeders;

use App\Models\Governorates;
use App\Models\Regions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Redions extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $json = file_get_contents(base_path('saudi-arabia-regions-simple.json'));
        $data = json_decode($json, true);
        print($data['regions'][0]['label']);
        foreach ($data['regions'] as $regions) {
            // print($regions['label']);
            echo "Main Key 1: " . $regions['label'] . "<br>";
            $regionsModel = Regions::create([
                'name' => $regions['label'],
            ]);
            if (isset($regions['governorates']) && is_array($regions['governorates'])) {
                foreach ($regions['governorates'] as $governorates) {
                    echo "governorates Key 1: " . $governorates['label'] . "<br>";
                    $governorates = Governorates::create([
                        'region_id' => $regionsModel->id,
                        'name' => $governorates['label'],
                    ]);
                }
            }
        }
        // return ;
    }
}
