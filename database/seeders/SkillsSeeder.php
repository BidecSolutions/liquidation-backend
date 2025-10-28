<?php

namespace Database\Seeders;

use App\Models\Code;
use Illuminate\Database\Seeder;

class SkillsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            'PHP',
            'JavaScript',
            'Python',
            'Java',
            'C#',
            'Ruby',
            'HTML/CSS',
            'SQL',
            'NoSQL',
            'AWS',
            'Docker',
            'Kubernetes',
            'Machine Learning',
            'Data Analysis',
            'Project Management',
            'Agile Methodologies',
            'UI/UX Design',
            'Mobile Development',
            'DevOps',
            'Cybersecurity',
        ];

        foreach ($skills as $skill) {
            $newSkill = Code::updateOrCreate(
                ['key' => 'skills', 'value' => $skill],
                ['key' => 'skills', 'value' => $skill, 'status' => 1],
            );
            $this->command->info("Seeded Skill: {$newSkill->value}");
        }
    }
}
