<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@haloha.test')->firstOrFail();
        $rows = [['Kopi Susu', 'Minuman', 18000, 9000, 40], ['Matcha Latte', 'Minuman', 22000, 11000, 25], ['Es Teh Lemon', 'Minuman', 12000, 5000, 35], ['Croissant', 'Makanan', 16000, 8000, 20], ['Roti Cokelat', 'Makanan', 12000, 6000, 30], ['Sandwich', 'Makanan', 25000, 13000, 18], ['Air Mineral', 'Minuman', 6000, 3000, 50], ['Cookies', 'Makanan', 15000, 7000, 8], ['Donat', 'Makanan', 10000, 4500, 5]];
        foreach ($rows as $i => [$name,$category,$price,$cost,$stock]) {
            DB::transaction(function () use ($i, $name, $category, $price, $cost, $stock, $admin) {
                $cat = Category::firstOrCreate(['name' => $category]);
                $p = Product::firstOrCreate(['sku' => 'HL-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT)], ['name' => $name, 'category_id' => $cat->id, 'selling_price' => $price, 'cost_price' => $cost, 'minimum_stock' => 8, 'is_active' => true]);
                if ($p->wasRecentlyCreated) {
                    $p->stock = $stock;
                    $p->save();
                    StockMovement::create(['product_id' => $p->id, 'user_id' => $admin->id, 'type' => 'opening', 'quantity' => $stock, 'stock_after' => $stock, 'note' => 'Stok awal seeder', 'occurred_at' => now()->subDay()->startOfDay()]);
                }
            });
        }
    }
}
