<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Database\Seeder;

class DemoSaleSeeder extends Seeder
{
    public function run(): void
    {
        if (! filter_var(env('POS_SEED_DEMO_SALES', true), FILTER_VALIDATE_BOOL)) {
            return;
        }
        $user = User::where('email', 'kasir@haloha.test')->firstOrFail();
        $service = app(SaleService::class);
        $rows = [['00000000-0000-4000-8000-000000000001', [['HL-001', 2], ['HL-005', 1]], 0, 'cash', 50000], ['00000000-0000-4000-8000-000000000002', [['HL-002', 1], ['HL-006', 1]], 2000, 'qris', 45000]];
        foreach ($rows as [$token,$lines,$discount,$method,$paid]) {
            if (Sale::where('checkout_token', $token)->exists()) {
                continue;
            }$items = array_map(fn ($x) => ['product_id' => Product::where('sku', $x[0])->firstOrFail()->id, 'quantity' => $x[1]], $lines);
            $service->checkout($user, ['checkout_token' => $token, 'items' => $items, 'discount' => $discount, 'payment_method' => $method, 'paid_amount' => $paid]);
        }
    }
}
