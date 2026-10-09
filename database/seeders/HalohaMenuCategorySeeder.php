<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class HalohaMenuCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (HalohaMenuCatalog::categories() as $category) {
            Category::firstOrCreate(['name' => $category['name']]);
        }
    }
}
