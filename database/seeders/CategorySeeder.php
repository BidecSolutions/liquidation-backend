<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        // 1. Fetch API response
        $response = Http::get('https://api.trademe.co.nz/v1/Categories.json');

        if ($response->failed()) {
            $this->command->error('Failed to fetch categories API');
            return;
        }

        $data = $response->json();
        // 2. Save Root category itself
            $rootCategory = Category::create([
                'parent_id'   => null,
                'name'        => $data['Name'] ?? 'Root',
                'slug'        => !empty($data['Path']) ? trim($data['Path'], '/') : Str::slug($data['Name'] ?? 'Root'),
                'status'      => 1,
                'meta_title'  => $data['Name'] ?? 'Root',
            ]);
        // 2. Start recursive import from "Root"
        if (isset($data['Subcategories'])) {
            foreach ($data['Subcategories'] as $subcategory) {
                $this->importCategory($subcategory, $rootCategory->id);
            }
        }
    }

    private function importCategory(array $data, $parentId = null)
    {
        // 3. Create category in DB
        $category = Category::create([
            'parent_id'   => $parentId,
            'name'        => $data['Name'] ?? '',
            'slug'        => !empty($data['Path']) ? trim($data['Path'], '/') : Str::slug($data['Name'] ?? ''),
            'status'      => 1,
            'meta_title'  => $data['Name'] ?? '',
        ]);

        // 4. Recurse if there are children
        if (!empty($data['Subcategories'])) {
            foreach ($data['Subcategories'] as $child) {
                $this->importCategory($child, $category->id);
            }
        }
    }
}
