<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ReportService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function cashier(): User
    {
        return User::where('email', 'kasir@haloha.test')->firstOrFail();
    }

    private function pending(array $extra = []): Sale
    {
        return app(SaleService::class)->savePending($this->cashier(), array_replace([
            'checkout_token' => (string) Str::uuid(),
            'items' => [['product_id' => Product::where('sku', 'HL-001')->firstOrFail()->id, 'quantity' => 2, 'notes' => 'Tidak pedas']],
            'guest_name' => 'Andi', 'table_number' => '01', 'takeaway' => false,
            'discount_enabled' => false, 'tax_enabled' => false,
        ], $extra));
    }

    private function split(int $total): array
    {
        $part = intdiv($total, 4);
        return ['payments' => [
            ['method' => 'qris', 'amount' => $part, 'confirmed' => 1],
            ['method' => 'transfer', 'amount' => $part, 'confirmed' => 1],
            ['method' => 'debit', 'amount' => $part, 'confirmed' => 1],
            ['method' => 'cash', 'amount' => $total - 3 * $part, 'paid_amount' => $total - 3 * $part + 1000],
        ]];
    }

    public function test_pending_does_not_change_stock_or_paid_reports_and_payment_is_idempotent(): void
    {
        $p = Product::where('sku', 'HL-001')->firstOrFail();
        $stock = $p->stock;
        $before = app(ReportService::class)->sales(now()->toDateString())['revenue'];
        $sale = $this->pending();
        $this->assertSame($stock, $p->fresh()->stock);
        $this->assertSame($before, app(ReportService::class)->sales(now()->toDateString())['revenue']);
        $this->actingAs($this->cashier())->post(route('orders.pay', $sale), $this->split($sale->total))->assertRedirect(route('sales.show', $sale));
        $this->post(route('orders.pay', $sale), $this->split($sale->total))->assertRedirect();
        $this->assertSame($stock - 2, $p->fresh()->stock);
        $this->assertSame(1, StockMovement::where('sale_id', $sale->id)->count());
        $this->assertSame(4, $sale->payments()->count());
        $this->assertSame($sale->total, (int) $sale->payments()->sum('amount'));
        $this->assertSame(1000, $sale->fresh()->change_amount);
        $this->assertSame('Tidak pedas', $sale->items->first()->notes);
        $this->get(route('sales.show', $sale))->assertOk()->assertSee('Transfer')->assertSee('Debit')->assertSee('Tidak pedas')->assertSee('haloha-receipt-logo.png')->assertDontSee('Pajak 0%');
        $this->get(route('orders.kitchen', $sale))->assertOk()->assertSee('Tidak pedas')->assertSee('haloha-logo.jpeg')->assertDontSee('haloha-receipt-logo.png')->assertDontSee('Rp ');
        $payments = app(ReportService::class)->sales(now()->toDateString())['payments'];
        $this->assertSame(9000, (int) $payments['transfer']);
        $this->assertSame(9000, (int) $payments['debit']);
    }

    public function test_bad_allocations_confirmations_and_cash_are_rejected_atomically(): void
    {
        $sale = $this->pending();
        $this->actingAs($this->cashier());
        foreach (['over', 'under', 'duplicate', 'unconfirmed', 'cash'] as $case) {
            $data = $this->split($sale->total);
            if ($case === 'over') $data['payments'][0]['amount']++;
            if ($case === 'under') $data['payments'][0]['amount']--;
            if ($case === 'duplicate') $data['payments'][1]['method'] = 'qris';
            if ($case === 'unconfirmed') $data['payments'][1]['confirmed'] = 0;
            if ($case === 'cash') $data['payments'][3]['paid_amount'] = 1;
            $this->postJson(route('orders.pay', $sale), $data)->assertUnprocessable();
            $this->assertSame('pending', $sale->fresh()->status);
            $this->assertSame(0, $sale->payments()->count());
        }
    }

    public function test_tax_discount_flags_and_item_specific_notes(): void
    {
        $p = Product::where('sku', 'HL-001')->firstOrFail();
        $sale = $this->pending(['items' => [['product_id' => $p->id, 'quantity' => 1, 'notes' => 'Pedas'], ['product_id' => $p->id, 'quantity' => 1, 'notes' => 'Tidak pedas']], 'discount_enabled' => true, 'discount' => 1000, 'tax_enabled' => true, 'tax_rate' => 10]);
        $this->assertCount(2, $sale->items);
        $this->assertSame(3500, $sale->tax_amount);
        $this->assertSame(38500, $sale->total);
        $data = ['payments' => [['method' => 'debit', 'amount' => $sale->total, 'confirmed' => 1]]];
        $this->actingAs($this->cashier())->post(route('orders.pay', $sale), $data)->assertRedirect();
        $this->get(route('sales.show', $sale))->assertSee('Pajak 10%')->assertSee('Diskon');
        $this->putJson(route('orders.update', $sale), ['checkout_token' => $sale->checkout_token, 'table_number' => '01', 'items' => [['product_id' => $p->id, 'quantity' => 1]]])->assertUnprocessable();
    }

    public function test_catalog_and_sidebar_permissions_are_per_user_and_cannot_be_bypassed(): void
    {
        $admin = User::where('email', 'admin@haloha.test')->firstOrFail();
        $cashier = $this->cashier();
        $p = Product::where('sku', 'HL-001')->firstOrFail();
        $other = Product::where('sku', 'HL-002')->firstOrFail();
        $this->actingAs($admin)->put(route('users.access.update', $cashier), ['inherit_permissions' => 0, 'permission_codes' => ['pos.create'], 'product_ids' => [$p->id]])->assertRedirect();
        $cashier = $cashier->fresh();
        $this->assertTrue($cashier->hasPermission('pos.view'));
        $this->assertFalse($cashier->hasPermission('inventory.view'));
        $this->actingAs($cashier)->get('/pos')->assertOk()->assertSee($p->name)->assertDontSee($other->sku);
        $this->get('/reports/inventory')->assertForbidden();
        $this->get(route('users.access', $admin))->assertForbidden();
        $this->postJson('/pos', ['action' => 'pending', 'checkout_token' => (string) Str::uuid(), 'table_number' => '01', 'items' => [['product_id' => $other->id, 'quantity' => 1]]])->assertUnprocessable();
        $this->assertDatabaseCount('sales', 2);
    }

    public function test_kitchen_tickets_split_between_dapur_and_dapur_2(): void
    {
        $food = Product::where('sku', 'HL-001')->firstOrFail();
        $tea = Product::create(['category_id' => $food->category_id, 'sku' => 'TT-001', 'name' => 'Thai Tea Large (22 oz)', 'selling_price' => 10000, 'cost_price' => 4000, 'minimum_stock' => 0, 'is_active' => true]);
        $tea->forceFill(['stock' => 50])->save();
        $sale = $this->pending(['items' => [['product_id' => $food->id, 'quantity' => 2, 'notes' => 'Tidak pedas'], ['product_id' => $tea->id, 'quantity' => 1, 'notes' => 'Less ice']]]);
        $this->actingAs($this->cashier());
        $this->get(route('orders.index'))->assertSee(route('orders.kitchen', [$sale, 'dapur']))->assertSee(route('orders.kitchen', [$sale, 'dapur2']))->assertSee('Cetak dapur 2');
        $this->get(route('orders.kitchen', $sale))->assertOk()->assertSee('PESANAN DAPUR')->assertSee($food->name)->assertSee('Thai Tea Large')->assertDontSee('Rp ');
        $this->get(route('orders.kitchen', [$sale, 'dapur2']))->assertOk()->assertSee('PESANAN DAPUR 2')->assertSee('Thai Tea Large')->assertSee('Less ice')->assertDontSee($food->name)->assertDontSee('Tidak pedas')->assertDontSee('Rp ');
        $this->get(route('orders.kitchen', [$sale, 'dapur3']))->assertNotFound();

        $plain = $this->pending();
        $this->get(route('orders.index'))->assertSee(route('orders.kitchen', [$plain, 'dapur']))->assertDontSee(route('orders.kitchen', [$plain, 'dapur2']));
        $this->get(route('orders.kitchen', [$plain, 'dapur2']))->assertOk()->assertSee('Tidak ada menu untuk Dapur 2')->assertDontSee('window.print()', false);
    }

    public function test_guest_table_takeaway_ownership_and_pending_views(): void
    {
        $sale = $this->pending(['takeaway' => true, 'table_number' => '99']);
        $this->assertNull($sale->table_number);
        $this->actingAs($this->cashier());
        foreach ([route('orders.index'), route('orders.edit', $sale), route('orders.payment', $sale)] as $url) $this->get($url)->assertOk();
        $this->get(route('orders.kitchen', $sale))->assertSee('TAKE AWAY')->assertSee('Andi');
        $other = User::create(['name' => 'Other', 'email' => 'other2@haloha.test', 'password' => 'TestPass123!', 'role_id' => $this->cashier()->role_id, 'is_active' => true]);
        $this->actingAs($other)->post(route('orders.pay', $sale), $this->split($sale->total))->assertForbidden();
        $this->get(route('orders.edit', $sale))->assertForbidden();
        $this->get(route('orders.kitchen', $sale))->assertForbidden();
    }
    public function test_admin_order_edits_keep_original_cashier_catalog_and_identity(): void
    {
        $sale = $this->pending();
        $cashier = $this->cashier();
        $p = Product::where('sku', 'HL-001')->firstOrFail();
        $other = Product::where('sku', 'HL-002')->firstOrFail();
        $cashier->update(['product_ids' => [$p->id]]);
        $admin = User::where('email', 'admin@haloha.test')->firstOrFail();
        $this->actingAs($admin)->get(route('orders.edit', $sale))->assertOk()->assertDontSee($other->sku);
        $data = ['checkout_token' => $sale->checkout_token, 'table_number' => '02', 'items' => [['product_id' => $other->id, 'quantity' => 1]]];
        $this->putJson(route('orders.update', $sale), $data)->assertUnprocessable();
        $data['items'][0]['product_id'] = $p->id;
        $this->put(route('orders.update', $sale), $data)->assertRedirect();
        $this->assertSame($cashier->id, $sale->fresh()->user_id);
        $this->assertSame($cashier->name, $sale->fresh()->cashier_name);
    }

}
