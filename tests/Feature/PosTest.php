<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReportService;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin()
    {
        return User::where('email', 'admin@haloha.test')->firstOrFail();
    }

    private function cashier()
    {
        return User::where('email', 'kasir@haloha.test')->firstOrFail();
    }

    private function payload(array $extra = [])
    {
        return array_replace(['checkout_token' => (string) Str::uuid(), 'items' => [['product_id' => Product::where('sku', 'HL-001')->firstOrFail()->id, 'quantity' => 2]], 'discount' => 1000, 'payment_method' => 'cash', 'paid_amount' => 40000, 'confirmed' => 1], $extra);
    }

    public function test_guest_redirects_and_login_works(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->post('/login', ['email' => $this->admin()->email, 'password' => 'Haloha123!'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->admin());
    }

    public function test_cashier_cannot_manage_users_or_receive_stock(): void
    {
        $this->actingAs($this->cashier())->get('/users')->assertForbidden();
        $this->post('/stock', ['product_id' => 1, 'quantity' => 2])->assertForbidden();
        $this->get('/reports/sales')->assertForbidden();
        $this->get('/pos')->assertOk();
    }

    public function test_disabled_user_is_logged_out(): void
    {
        $u = $this->cashier();
        $u->update(['is_active' => false]);
        $this->actingAs($u)->get('/pos')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_checkout_snapshot_and_idempotency(): void
    {
        $p = Product::where('sku', 'HL-001')->firstOrFail();
        $stock = $p->stock;
        $data = $this->payload();
        $service = app(SaleService::class);
        $sale = $service->checkout($this->cashier(), $data);
        $again = $service->checkout($this->cashier(), $data);
        $this->assertSame($sale->id, $again->id);
        $this->assertSame(35000, $sale->total);
        $this->assertSame(18000, $sale->total_cost);
        $this->assertSame(5000, $sale->change_amount);
        $this->assertSame($stock - 2, $p->fresh()->stock);
        $p->update(['selling_price' => 99999]);
        $this->assertEquals(18000, $sale->items->first()->unit_price);
        $this->assertDatabaseCount('sales', 3);
    }

    public function test_insufficient_stock_rolls_back_all_lines(): void
    {
        $p = Product::where('sku', 'HL-001')->firstOrFail();
        $before = $p->stock;
        $count = Sale::count();
        try {
            app(SaleService::class)->checkout($this->cashier(), $this->payload(['items' => [['product_id' => $p->id, 'quantity' => 1], ['product_id' => Product::where('sku', 'HL-009')->first()->id, 'quantity' => 999]]]));
            $this->fail('Expected validation');
        } catch (ValidationException $e) {
            $this->assertSame($before, $p->fresh()->stock);
            $this->assertSame($count, Sale::count());
        }
    }

    public function test_discount_and_underpayment_are_rejected(): void
    {
        foreach ([['discount' => 999999], ['paid_amount' => 1]] as $extra) {
            try {
                app(SaleService::class)->checkout($this->cashier(), $this->payload($extra));
                $this->fail('Expected validation');
            } catch (ValidationException $e) {
                $this->assertDatabaseCount('sales', 2);
            }
        }
    }

    public function test_request_ignores_client_prices(): void
    {
        $data = $this->payload();
        $data['items'][0]['unit_price'] = 1;
        $this->actingAs($this->cashier())->post('/pos', $data)->assertRedirect();
        $this->assertSame(35000, Sale::where('checkout_token', $data['checkout_token'])->firstOrFail()->total);
    }

    public function test_duplicate_product_rows_are_merged(): void
    {
        $p = Product::where('sku', 'HL-001')->firstOrFail();
        $s = app(SaleService::class)->checkout($this->cashier(), $this->payload(['items' => [['product_id' => $p->id, 'quantity' => 1], ['product_id' => $p->id, 'quantity' => 1]]]));
        $this->assertCount(1, $s->items);
        $this->assertEquals(2, $s->items->first()->quantity);
    }

    public function test_stock_reports_balance_across_days(): void
    {
        $service = app(ReportService::class);
        $report = $service->sales(now()->toDateString());
        $this->assertSame(93000, $report['revenue']);
        $this->assertSame(45000, $report['profit']);
        foreach ($service->inventory(now()->toDateString()) as $row) {
            $this->assertSame($row['product']->stock, $row['closing']);
            $this->assertSame($row['opening'] + $row['incoming'] - $row['sold'], $row['closing']);
        }$p = Product::first();
        $stock = $p->stock;
        app(StockService::class)->receive($p, 7, $this->admin(), 'Tes');
        $this->assertSame($stock + 7, $p->fresh()->stock);
    }

    public function test_admin_views_render(): void
    {
        $this->actingAs($this->admin());
        foreach (['/', '/dashboard', '/pos', '/products', '/products/create', '/products/1/edit', '/categories', '/stock', '/users', '/users/create', '/users/1/edit', '/roles', '/roles/create', '/permissions', '/reports/sales', '/reports/inventory', '/sales/1'] as $uri) {
            $r = $this->get($uri);
            if ($uri === '/') {
                $r->assertRedirect();
            } else {
                $r->assertOk();
            }
        }
    }

    public function test_user_and_role_crud_and_protected_administrator(): void
    {
        $this->actingAs($this->admin())->post('/roles', ['name' => 'Supervisor', 'description' => 'Shift', 'is_active' => 1])->assertRedirect();
        $role = Role::where('name', 'Supervisor')->firstOrFail();
        $this->put('/permissions/'.$role->id, ['permissions' => ['sales.export']])->assertRedirect();
        $this->assertTrue($role->fresh()->permissions->contains('code', 'sales.view'));
        $this->post('/users', ['name' => 'Supervisor 1', 'email' => 'spv@haloha.test', 'password' => 'TestPass123!', 'password_confirmation' => 'TestPass123!', 'role_id' => $role->id, 'is_active' => 1])->assertRedirect();
        $u = User::where('email', 'spv@haloha.test')->firstOrFail();
        $this->assertTrue($u->hasPermission('sales.export'));
        $this->delete('/roles/'.$role->id)->assertSessionHasErrors('role');
        $this->put('/permissions/'.$this->admin()->role_id, ['permissions' => []])->assertForbidden();
        $this->put('/users/'.$this->admin()->id, ['name' => 'Admin', 'email' => $this->admin()->email, 'role_id' => $role->id, 'is_active' => 0])->assertSessionHasErrors('role_id');
    }

    public function test_products_crud_and_stock_ledger(): void
    {
        $this->actingAs($this->admin());
        $cat = Category::first();
        $this->post('/products', ['name' => 'Tes', 'sku' => 'TEST-01', 'category_id' => $cat->id, 'cost_price' => 1000, 'selling_price' => 2000, 'minimum_stock' => 2, 'is_active' => 1])->assertRedirect();
        $p = Product::where('sku', 'TEST-01')->firstOrFail();
        $this->post('/stock', ['product_id' => $p->id, 'quantity' => 10, 'note' => 'Awal'])->assertRedirect();
        $this->assertSame(10, $p->fresh()->stock);
        $this->delete('/products/'.$p->id)->assertRedirect();
        $this->assertFalse($p->fresh()->is_active);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $p->id, 'quantity' => 10]);
    }

    public function test_receipt_ownership(): void
    {
        $sale = Sale::first();
        $other = User::create(['name' => 'Kasir 2', 'email' => 'other@haloha.test', 'password' => 'TestPass123!', 'role_id' => $this->cashier()->role_id, 'is_active' => true]);
        $this->actingAs($other)->get('/sales/'.$sale->id)->assertForbidden();
        $this->actingAs($this->cashier())->get('/sales/'.$sale->id)->assertOk();
    }

    public function test_export_permission_date_validation_and_csv_safety(): void
    {
        $this->actingAs($this->cashier())->get('/reports/inventory/export')->assertForbidden();
        $this->actingAs($this->admin())->get('/reports/sales?date=bad')->assertSessionHasErrors('date');
        $p = Product::first();
        $p->update(['name' => '=HYPERLINK("bad")']);
        $response = $this->get('/reports/inventory/export')->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
    }

    public function test_seed_is_repeatable_without_stock_changes(): void
    {
        $stocks = Product::pluck('stock','id')->all();
        $this->seed();
        $this->assertSame($stocks,Product::pluck('stock','id')->all());
        $this->assertDatabaseCount('sales',2);
    }
}
