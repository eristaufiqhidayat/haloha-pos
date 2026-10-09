<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HalohaMenuProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->call(HalohaMenuCategorySeeder::class);

            foreach (HalohaMenuCatalog::categories() as $category) {
                $categoryId = Category::where('name', $category['name'])->firstOrFail()->id;

                foreach ($category['products'] as $product) {
                    // A rerun must not reset operational values or administrator edits.
                    Product::firstOrCreate(['sku' => $product['sku']], [
                        'category_id' => $categoryId,
                        'name' => $product['name'],
                        'selling_price' => $product['selling_price'],
                        'cost_price' => 0,
                        'minimum_stock' => 0,
                        'is_active' => true,
                    ]);
                }
            }
        });
    }
}
