<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    // Categories that should go to ROOT (not under Marketplace) + their category_type
    private $rootExceptions = [
        ['name' => 'Trade Me Property', 'path' => '/Trade-Me-Property', 'type' => 'property'],
        ['name' => 'Trade Me Motors',   'path' => '/Trade-Me-Motors',   'type' => 'motors'],
        ['name' => 'Trade Me Jobs',     'path' => '/Trade-Me-Jobs',     'type' => 'jobs'],
        ['name' => 'Services',          'path' => '/Services',          'type' => 'services'],
    ];

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

        // 2. Create Marketplace root
        $marketplace = Category::firstOrCreate(
            ['slug' => 'marketplace'],
            [
                'parent_id'     => null,
                'name'          => 'Market place',
                'status'        => 1,
                'meta_title'    => 'Marketplace',
                'category_type' => 'marketplace',
            ]
        );

        // 3. Loop over subcategories of Root
        if (isset($data['Subcategories'])) {
            foreach ($data['Subcategories'] as $subcategory) {
                $exception = $this->getRootException($subcategory);

                if ($exception) {
                    // Direct root category (with special category_type)
                    $this->importCategory($subcategory, null, $exception['type']);
                } else {
                    // Goes under Marketplace, all should be type marketplace
                    $this->importCategory($subcategory, $marketplace->id, 'marketplace');
                }
            }
        }
    }

    private function importCategory(array $data, $parentId = null, $categoryType = 'marketplace')
    {
        $slug = !empty($data['Path'])
            ? trim($data['Path'], '/')
            : Str::slug($data['Name'] ?? '');

        // 1. Create or fetch existing category
        $category = Category::firstOrCreate(
            ['slug' => $slug],
            [
                'parent_id'     => $parentId,
                'name'          => $data['Name'] ?? '',
                'status'        => 1,
                'meta_title'    => $data['Name'] ?? '',
                'category_type' => $categoryType,
            ]
        );

        // 2. Ensure correct parent & type if already exists
        $updates = [];
        if ($category->parent_id !== $parentId) {
            $updates['parent_id'] = $parentId;
        }
        if ($category->category_type !== $categoryType) {
            $updates['category_type'] = $categoryType;
        }
        if (!empty($updates)) {
            $category->update($updates);
        }

        // 3. Recursively import children (inherit type from parent)
        if (!empty($data['Subcategories'])) {
            foreach ($data['Subcategories'] as $child) {
                $this->importCategory($child, $category->id, $categoryType);
            }
        }

        return $category;
    }

    private function getRootException($category)
    {
        foreach ($this->rootExceptions as $exception) {
            if (
                isset($category['Name'], $category['Path']) &&
                $category['Name'] === $exception['name'] &&
                $category['Path'] === $exception['path']
            ) {
                return $exception;
            }
        }
        return null;
    }
}
