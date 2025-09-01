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
        echo "Starting Category Import...\n";
        // 1. Fetch API response
        $response = Http::get('https://api.trademe.co.nz/v1/Categories.json');

        if ($response->failed()) {
            $this->command->error('Failed to fetch categories API');
            return;
        }

        $data = $response->json();

        // 2. Handle Root category
        $rootSlug = !empty($data['Path']) 
            ? trim($data['Path'], '/') 
            : Str::slug($data['Name'] ?? 'Root');

        $rootCategory = Category::firstOrCreate(
            ['slug' => $rootSlug],
            [
                'parent_id'   => null,
                'name'        => $data['Name'] ?? 'Root',
                'status'      => 1,
                'meta_title'  => $data['Name'] ?? 'Root',
            ]
        );

        // 3. Import subcategories recursively
        if (isset($data['Subcategories'])) {
            foreach ($data['Subcategories'] as $subcategory) {
                $this->importCategory($subcategory, $rootCategory->id);
            }
        }
    }

    private function importCategory(array $data, $parentId = null)
    {
        $slug = !empty($data['Path']) 
            ? trim($data['Path'], '/') 
            : Str::slug($data['Name'] ?? '');

        // 1. Check if category already exists
        $category = Category::where('slug', $slug)->first();

        if ($category) {
            // 2. If parent_id is wrong, update it
            if ($category->parent_id !== $parentId) {
                $category->update(['parent_id' => $parentId]);
            }
        } else {
            // 3. Otherwise create new category
            $category = Category::create([
                'parent_id'   => $parentId,
                'name'        => $data['Name'] ?? '',
                'slug'        => $slug,
                'status'      => 1,
                'meta_title'  => $data['Name'] ?? '',
            ]);
        }

        // 4. Always use this category's ID for children
        if (!empty($data['Subcategories'])) {
            foreach ($data['Subcategories'] as $child) {
                $this->importCategory($child, $category->id);
            }
        }
    }
}
