<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\HalohaMenuCategorySeeder;
use Database\Seeders\HalohaMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalohaMenuSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_catalog_can_be_seeded_without_demo_users(): void
    {
        $this->seed(HalohaMenuSeeder::class);
        $this->assertSame(24, Category::count());
        $this->assertSame(161, Product::count());
        $this->assertSame(38, Product::where('sku', 'like', 'HH-%')->count());
        $this->assertSame(123, Product::where('sku', 'like', 'RST-%')->count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(0, (int) Product::sum('stock'));
        $this->assertSame(0, (int) Product::sum('cost_price'));
        $this->assertDatabaseHas('products', ['sku' => 'HH-TEA-001', 'name' => 'Thai Tea Medium (16 oz)', 'selling_price' => 8000]);
        $this->assertDatabaseHas('products', ['sku' => 'HH-TEA-004', 'selling_price' => 12000]);
        $this->assertDatabaseHas('products', ['sku' => 'RST-MIE-014', 'name' => 'Pangsit Chili Oil (4 Pcs)', 'selling_price' => 15000]);
        $this->assertDatabaseHas('products', ['sku' => 'RST-MIE-015', 'name' => 'Pangsit Chili Oil (6 Pcs)', 'selling_price' => 20000]);
        $this->assertDatabaseHas('products', ['sku' => 'RST-CBF-017', 'selling_price' => 60000]);
        $this->assertDatabaseHas('products', ['sku' => 'RST-CBF-019', 'selling_price' => 40000]);
    }

    public function test_rerun_preserves_ids_stock_cost_prices_and_cashier_grants(): void
    {
        $this->seed();
        $cashier = User::where('email', 'kasir@haloha.test')->firstOrFail();
        $demo = Product::where('sku', 'HL-001')->firstOrFail();
        $cashier->update(['product_ids' => [$demo->id]]);
        $demoValues = $demo->getAttributes();
        $movements = StockMovement::count();
        $this->seed(HalohaMenuSeeder::class);
        $product = Product::where('sku', 'HH-TEA-001')->firstOrFail();
        $product->forceFill(['stock' => 42, 'cost_price' => 3500, 'selling_price' => 9000, 'minimum_stock' => 7, 'is_active' => false, 'name' => 'Nama edit admin'])->save();
        $before = $product->getAttributes();
        $ids = Product::orderBy('id')->pluck('id')->all();
        $this->seed(HalohaMenuSeeder::class);
        $this->assertSame(170, Product::count());
        $this->assertSame($ids, Product::orderBy('id')->pluck('id')->all());
        $this->assertSame($before, $product->fresh()->getAttributes());
        $this->assertSame($demoValues, $demo->fresh()->getAttributes());
        $this->assertSame($movements, StockMovement::count());
        $this->assertSame([$demo->id], $cashier->fresh()->product_ids);
        $this->assertFalse($cashier->fresh()->maySellProduct($product->id));
    }

    public function test_category_only_import_is_idempotent_and_does_not_add_products(): void
    {
        $this->seed(HalohaMenuCategorySeeder::class);
        $this->seed(HalohaMenuCategorySeeder::class);
        $this->assertSame(24, Category::count());
        $this->assertSame(0, Product::count());
    }
}
