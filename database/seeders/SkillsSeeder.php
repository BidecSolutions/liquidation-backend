<?php

namespace Database\Seeders;

use App\Models\Code;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SkillsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/skills.json');
        if (! File::exists($jsonPath)) {
            $this->command->error('json files does not exist');
        }
        $json = File::get($jsonPath);
        $indestries = json_decode($json);

        foreach ($indestries as $indestry => $skillList) {
            foreach ($skillList as $skill) {
                $newSkill = Code::updateOrCreate(
                    ['key' => 'skills', 'value' => $skill],
                    ['status' => 1],
                );
                $this->command->info("Seeded Skill: {$newSkill->value}");
            }
            $this->command->info('🎉 All skills have been seeded successfully!');
        }
    }
}
