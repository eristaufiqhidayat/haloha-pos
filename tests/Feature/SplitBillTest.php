<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ReportService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SplitBillTest extends TestCase
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

    private function product(string $sku): Product
    {
        return Product::where('sku', $sku)->firstOrFail();
    }

    /** 3× HL-001 (18.000), 2× HL-002 (22.000), 1× HL-004 (16.000); diskon 10.000, pajak 10% => total 114.400. */
    private function order(): Sale
    {
        return app(SaleService::class)->savePending($this->cashier(), [
            'checkout_token' => (string) Str::uuid(),
            'items' => [
                ['product_id' => $this->product('HL-001')->id, 'quantity' => 3, 'notes' => 'Less sugar'],
                ['product_id' => $this->product('HL-002')->id, 'quantity' => 2],
                ['product_id' => $this->product('HL-004')->id, 'quantity' => 1],
            ],
            'guest_name' => 'Rombongan', 'table_number' => '07', 'takeaway' => false,
            'discount_enabled' => true, 'discount' => 10000, 'tax_enabled' => true, 'tax_rate' => 10,
        ]);
    }

    private function key(Sale $sale, string $sku): string
    {
        return $sale->items()->where('sku', $sku)->firstOrFail()->splitKey();
    }

    private function itemSplit(Sale $sale): array
    {
        return ['mode' => 'item', 'count' => 3, 'labels' => [1 => 'Andi', 2 => '', 3 => 'Budi'], 'alloc' => [
            $this->key($sale, 'HL-001') => [1 => 1, 2 => 2],
            $this->key($sale, 'HL-002') => [1 => 1, 3 => 1],
            $this->key($sale, 'HL-004') => [3 => 1],
        ]];
    }

    private function cash(int $amount): array
    {
        return ['payments' => [['method' => 'cash', 'amount' => $amount, 'paid_amount' => $amount + 500]]];
    }

    public function test_item_split_allocates_discount_and_tax_and_each_bill_is_paid_and_printed_separately(): void
    {
        $sale = $this->order();
        $this->assertSame(114400, $sale->total);
        $stock = $this->product('HL-001')->stock;
        $revenue = app(ReportService::class)->sales(now()->toDateString())['revenue'];
        $this->actingAs($this->cashier());

        $this->get(route('orders.split', $sale))->assertOk()->assertSee('Simpan pembagian');
        $this->post(route('orders.split.store', $sale), $this->itemSplit($sale))->assertRedirect(route('orders.bills', $sale));
        $bills = $sale->fresh()->bills;
        $this->assertSame([40000, 36000, 38000], $bills->pluck('subtotal')->all());
        $this->assertSame(10000, $bills->sum('discount'));
        $this->assertSame(10400, $bills->sum('tax_amount'));
        $this->assertSame(114400, $bills->sum('total'));
        $this->assertSame(['Andi', null, 'Budi'], $bills->pluck('label')->all());
        $this->assertSame(6, (int) $sale->items()->sum('quantity'));
        $this->get(route('orders.index'))->assertOk()->assertSee('Split 3 tagihan')->assertSee('Lihat tagihan');
        $this->get(route('orders.payment', $sale))->assertRedirect(route('orders.bills', $sale));
        $this->get(route('orders.kitchen', $sale))->assertOk()->assertSee('3 × Kopi Susu');

        $b2 = $bills[1];
        $this->get(route('orders.bills.payment', [$sale, 2]))->assertOk()->assertSee('Tagihan 2')->assertSee(number_format($b2->total, 0, ',', '.'));
        $this->post(route('orders.bills.pay', [$sale, 2]), $this->cash($b2->total))->assertRedirect(route('sales.bill', [$sale, 2]));
        $this->post(route('orders.bills.pay', [$sale, 2]), $this->cash($b2->total))->assertRedirect();
        // Stok dipotong sekali saat tagihan pertama lunas; laporan belum berubah.
        $this->assertSame($stock - 3, $this->product('HL-001')->stock);
        $this->assertSame(3, StockMovement::where('sale_id', $sale->id)->count());
        $this->assertSame('pending', $sale->fresh()->status);
        $this->assertSame($revenue, app(ReportService::class)->sales(now()->toDateString())['revenue']);
        $this->get(route('sales.bill', [$sale, 2]))->assertOk()->assertSee('STRUK TAGIHAN 2 DARI 3')->assertSee($sale->invoice_number.'-T2')->assertSee('2 × 18.000')->assertDontSee('Croissant');
        $this->get(route('sales.bill', [$sale, 1]))->assertNotFound();

        // Pesanan terkunci setelah ada tagihan lunas.
        $this->post(route('orders.split.store', $sale), $this->itemSplit($sale))->assertSessionHasErrors('order');
        $this->delete(route('orders.split.destroy', $sale))->assertSessionHasErrors('order');
        $this->get(route('orders.edit', $sale))->assertRedirect(route('orders.bills', $sale));
        $this->post(route('orders.pay', $sale), $this->cash($sale->total))->assertSessionHasErrors('order');

        $this->post(route('orders.bills.pay', [$sale, 1]), ['payments' => [
            ['method' => 'qris', 'amount' => 20000, 'confirmed' => 1], ['method' => 'cash', 'amount' => $bills[0]->total - 20000, 'paid_amount' => $bills[0]->total - 20000],
        ]])->assertRedirect();
        $this->post(route('orders.bills.pay', [$sale, 3]), $this->cash($bills[2]->total))->assertRedirect();
        $sale->refresh();
        $this->assertSame('paid', $sale->status);
        $this->assertSame('split', $sale->payment_method);
        $this->assertSame(114400, (int) $sale->payments()->sum('amount'));
        $this->assertSame(1000, $sale->change_amount);
        $this->assertSame($stock - 3, $this->product('HL-001')->stock);
        $this->assertSame(3, StockMovement::where('sale_id', $sale->id)->count());
        $report = app(ReportService::class)->sales(now()->toDateString());
        $this->assertSame($revenue + 114400, $report['revenue']);
        $this->assertGreaterThanOrEqual(20000, (int) $report['payments']['qris']);
        $this->get(route('sales.show', $sale))->assertOk()->assertSee('Dibayar dalam')->assertSee('T3 Tunai');
        $this->get(route('orders.bills', $sale))->assertOk()->assertSee('Lunas semua');
    }

    public function test_equal_split_supports_fifteen_bills_and_rejects_sixteen(): void
    {
        $sale = $this->order();
        $this->actingAs($this->cashier());
        $this->post(route('orders.split.store', $sale), ['mode' => 'equal', 'count' => 16])->assertSessionHasErrors('count');
        $this->post(route('orders.split.store', $sale), ['mode' => 'equal', 'count' => 1])->assertSessionHasErrors('count');
        $this->assertNull($sale->fresh()->split_mode);
        $this->post(route('orders.split.store', $sale), ['mode' => 'equal', 'count' => 15])->assertRedirect(route('orders.bills', $sale));
        $bills = $sale->fresh()->bills;
        $this->assertCount(15, $bills);
        $this->assertSame(114400, $bills->sum('total'));
        $this->assertLessThanOrEqual(3, $bills->max('total') - $bills->min('total'));
        $this->assertSame($bills->max('total'), $bills->first()->total);
        foreach ($bills as $b) {
            $this->post(route('orders.bills.pay', [$sale, $b->number]), $this->cash($b->total))->assertRedirect();
        }
        $this->assertSame('paid', $sale->fresh()->status);
        $this->assertSame('cash', $sale->fresh()->payment_method);
        $this->get(route('sales.bill', [$sale, 15]))->assertOk()->assertSee('Bagian 15 dari 15')->assertSee('Croissant');
    }

    public function test_incomplete_or_empty_allocations_are_rejected_without_changes(): void
    {
        $sale = $this->order();
        $this->actingAs($this->cashier());
        $data = $this->itemSplit($sale);
        $data['alloc'][$this->key($sale, 'HL-001')] = [1 => 1, 2 => 1];
        $this->post(route('orders.split.store', $sale), $data)->assertSessionHasErrors('alloc');
        $data = $this->itemSplit($sale);
        $data['count'] = 4;
        $this->post(route('orders.split.store', $sale), $data)->assertSessionHasErrors('alloc');
        $data = $this->itemSplit($sale);
        $data['alloc']['bukan-item'] = [1 => 1];
        $this->post(route('orders.split.store', $sale), $data)->assertSessionHasErrors('alloc');
        $this->assertNull($sale->fresh()->split_mode);
        $this->assertSame(0, $sale->bills()->count());
        $this->assertSame(3, $sale->items()->count());
    }

    public function test_resplit_unsplit_and_edit_reset_an_unpaid_split(): void
    {
        $sale = $this->order();
        $this->actingAs($this->cashier());
        $this->post(route('orders.split.store', $sale), $this->itemSplit($sale))->assertRedirect();
        $this->assertSame(5, $sale->items()->count());
        $this->get(route('orders.split', $sale))->assertOk()->assertSee('Batalkan split');
        $this->post(route('orders.split.store', $sale), ['mode' => 'equal', 'count' => 2])->assertRedirect();
        $this->assertSame(3, $sale->items()->count());
        $this->assertSame(2, $sale->bills()->count());
        $this->delete(route('orders.split.destroy', $sale))->assertRedirect(route('orders.index'));
        $sale->refresh();
        $this->assertNull($sale->split_mode);
        $this->assertSame(0, $sale->bills()->count());
        $this->assertSame([3, 2, 1], $sale->items()->orderBy('id')->pluck('quantity')->all());
        $this->assertSame('Less sugar', $sale->items()->where('sku', 'HL-001')->value('notes'));

        $this->post(route('orders.split.store', $sale), $this->itemSplit($sale))->assertRedirect();
        $this->get(route('orders.edit', $sale))->assertOk()->assertSee('menghapus pembagian');
        app(SaleService::class)->savePending($this->cashier(), [
            'checkout_token' => $sale->checkout_token, 'items' => [['product_id' => $this->product('HL-003')->id, 'quantity' => 1]], 'table_number' => '07',
        ], $sale);
        $this->assertNull($sale->fresh()->split_mode);
        $this->assertSame(0, $sale->bills()->count());
        $this->post(route('orders.pay', $sale), $this->cash(12000))->assertRedirect(route('sales.show', $sale));
    }

    public function test_allocate_always_sums_to_total(): void
    {
        $this->assertSame([34, 33, 33], SaleService::allocate(100, [1, 1, 1]));
        $this->assertSame([0, 0], SaleService::allocate(0, [5, 5]));
        $this->assertSame([3509, 3158, 3333], SaleService::allocate(10000, [40000, 36000, 38000]));
        foreach ([[7, [1, 2, 3, 4]], [114400, array_fill(0, 15, 1)], [99, [0, 10, 0]]] as [$total, $w]) {
            $this->assertSame($total, array_sum(SaleService::allocate($total, $w)));
        }
    }
}
