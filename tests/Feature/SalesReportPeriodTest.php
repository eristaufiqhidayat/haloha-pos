<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReportService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesReportPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@haloha.test')->firstOrFail();
    }

    private function sell(string $soldAt, array $items): Sale
    {
        $lines = collect($items)->map(fn ($qty, $sku) => ['product_id' => Product::where('sku', $sku)->firstOrFail()->id, 'quantity' => $qty])->values()->all();
        $sale = app(SaleService::class)->checkout($this->admin(), ['checkout_token' => (string) Str::uuid(), 'items' => $lines, 'discount' => 0, 'payment_method' => 'cash', 'paid_amount' => 10000000, 'confirmed' => 1]);
        $sale->update(['sold_at' => $soldAt]);

        return $sale;
    }

    public function test_period_ranges_and_labels(): void
    {
        $service = app(ReportService::class);
        [$start, $end, $label, $key] = $service->period('week', '2025-W02');
        $this->assertSame('2025-01-06', $start->toDateString());
        $this->assertSame('2025-01-13', $end->toDateString());
        $this->assertSame('Minggu ke-2: 6 – 12 Januari 2025', $label);
        $this->assertSame('2025-W02', $key);

        [, , $label] = $service->period('week', '2025-W01');
        $this->assertSame('Minggu ke-1: 30 Desember 2024 – 5 Januari 2025', $label);

        [$start, $end, $label] = $service->period('month', '2025-02');
        $this->assertSame(['2025-02-01', '2025-03-01', 'Februari 2025'], [$start->toDateString(), $end->toDateString(), $label]);

        [$start, $end, $label] = $service->period('year', '2025');
        $this->assertSame(['2025-01-01', '2026-01-01', 'Tahun 2025'], [$start->toDateString(), $end->toDateString(), $label]);

        [, , $label] = $service->period('day', '2025-01-06');
        $this->assertSame('Senin, 6 Januari 2025', $label);
    }

    public function test_category_and_item_totals_follow_the_period(): void
    {
        $a = Product::where('sku', 'HL-001')->firstOrFail();
        $b = Product::where('sku', 'HL-002')->firstOrFail();
        $this->sell('2025-01-06 10:00:00', ['HL-001' => 2, 'HL-002' => 1]);
        $this->sell('2025-01-12 22:00:00', ['HL-001' => 1]);
        $this->sell('2025-02-03 09:00:00', ['HL-002' => 4]);

        $service = app(ReportService::class);
        [$start, $end] = $service->period('week', '2025-W02');
        $items = $service->byItem($start, $end);
        $this->assertSame([$a->id, $b->id], $items->pluck('product_id')->all());
        $this->assertSame(3, $items[0]['quantity']);
        $this->assertSame(3 * $a->selling_price, $items[0]['revenue']);
        $this->assertSame(3 * ($a->selling_price - $a->cost_price), $items[0]['profit']);

        [$start, $end] = $service->period('year', '2025');
        $byQty = $service->byItem($start, $end);
        $this->assertSame([5, 3], $byQty->pluck('quantity')->all());
        $this->assertSame([$a->category->name], $service->byItem($start, $end, $a->category_id)->pluck('category')->unique()->values()->all());

        $categories = $service->byCategory($start, $end);
        $this->assertSame(8, $categories->sum('quantity'));
        $this->assertSame(3 * $a->selling_price + 5 * $b->selling_price, $categories->sum('revenue'));
        $this->assertContains($a->category->name, $categories->pluck('category'));

        [$start, $end] = $service->period('day', '2025-01-07');
        $this->assertTrue($service->byCategory($start, $end)->isEmpty());
    }

    public function test_pages_and_csv_exports_for_each_view_and_period(): void
    {
        $a = Product::where('sku', 'HL-001')->firstOrFail();
        $this->sell('2025-03-10 12:00:00', ['HL-001' => 2]);
        $this->actingAs($this->admin());

        $this->get('/reports/sales?period=month&month=2025-03')->assertOk()->assertSee('Maret 2025')->assertSee($a->category->name)->assertSee('Per Item');
        $this->get('/reports/sales?period=week&week=2025-W11&view=item&sort=revenue')->assertOk()->assertSee($a->name)->assertSee('Minggu ke-11');
        $this->get('/reports/sales?period=year&year=2025&view=transactions')->assertOk()->assertSee('10/03/2025');
        $this->get('/reports/sales?period=day&date=2025-03-10&view=item&category='.$a->category_id)->assertOk()->assertSee($a->sku);
        $this->get('/reports/sales')->assertOk();

        $csv = $this->get('/reports/sales/export?period=month&month=2025-03&view=category')->assertOk();
        $this->assertStringContainsString('penjualan-kategori-2025-03.csv', $csv->headers->get('content-disposition'));
        $this->assertStringContainsString($a->category->name, $csv->streamedContent());

        $csv = $this->get('/reports/sales/export?period=week&week=2025-W11&view=item')->assertOk();
        $this->assertStringContainsString('penjualan-item-2025-W11.csv', $csv->headers->get('content-disposition'));
        $this->assertStringContainsString($a->sku, $csv->streamedContent());

        $csv = $this->get('/reports/sales/export?period=year&year=2025&view=transactions')->assertOk();
        $this->assertStringContainsString('penjualan-2025.csv', $csv->headers->get('content-disposition'));

        $this->get('/reports/sales?period=week&week=2025-W60')->assertSessionHasErrors('week');
        $this->get('/reports/sales?period=quarter')->assertSessionHasErrors('period');
    }
}
