<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;

class ReportService
{
    public function range(string $date): array
    {
        $start = CarbonImmutable::parse($date, config('app.timezone'))->startOfDay();

        return [$start, $start->addDay()];
    }

    /**
     * Rentang waktu laporan untuk periode harian, mingguan (ISO), bulanan, atau tahunan.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string, 3: string} [mulai, selesai (eksklusif), label, kunci nama file]
     */
    public function period(string $period, string $value): array
    {
        $tz = config('app.timezone');

        switch ($period) {
            case 'week':
                [$year, $week] = array_map('intval', explode('-W', $value));
                $start = CarbonImmutable::now($tz)->setISODate($year, $week)->startOfDay();
                $end = $start->addWeek();
                $last = $end->subDay();
                $range = $start->month === $last->month ? $start->day : $start->locale('id')->translatedFormat($start->year === $last->year ? 'j F' : 'j F Y');

                return [$start, $end, 'Minggu ke-'.$start->isoWeek.': '.$range.' – '.$last->locale('id')->translatedFormat('j F Y'), $start->isoWeekYear.'-W'.str_pad((string) $start->isoWeek, 2, '0', STR_PAD_LEFT)];
            case 'month':
                $start = CarbonImmutable::createFromFormat('!Y-m', $value, $tz);

                return [$start, $start->addMonth(), $start->locale('id')->translatedFormat('F Y'), $start->format('Y-m')];
            case 'year':
                $start = CarbonImmutable::create((int) $value, 1, 1, 0, 0, 0, $tz);

                return [$start, $start->addYear(), 'Tahun '.$start->year, (string) $start->year];
            default:
                [$start, $end] = $this->range($value);

                return [$start, $end, $start->locale('id')->translatedFormat('l, j F Y'), $start->format('Y-m-d')];
        }
    }

    public function sales(string $date): array
    {
        return $this->salesBetween(...$this->range($date));
    }

    public function salesBetween(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = Sale::with(['user', 'items', 'payments'])->where('status', 'paid')->where('sold_at', '>=', $start)->where('sold_at', '<', $end)->orderByDesc('sold_at')->get();
        $revenue = (int) $rows->sum('total');
        $cost = (int) $rows->sum('total_cost');

        return compact('rows', 'revenue', 'cost') + ['profit' => $revenue - $cost - (int) $rows->sum('tax_amount'), 'discount' => (int) $rows->sum('discount'), 'quantity' => (int) $rows->sum(fn ($s) => $s->items->sum('quantity')), 'payments' => $rows->flatMap->payments->groupBy('method')->map->sum('amount')];
    }

    /** Baris item terjual (transaksi lunas) dalam rentang, digabung dengan produk & kategorinya saat ini. */
    private function soldItems(CarbonImmutable $start, CarbonImmutable $end)
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('sales.status', 'paid')->where('sales.sold_at', '>=', $start)->where('sales.sold_at', '<', $end);
    }

    /** Omzet (harga item sebelum diskon & pajak transaksi), HPP, dan laba kotor per kategori. */
    public function byCategory(CarbonImmutable $start, CarbonImmutable $end)
    {
        return $this->soldItems($start, $end)
            ->selectRaw('categories.id AS category_id, MAX(categories.name) AS category, COUNT(DISTINCT sale_items.product_id) AS items, SUM(sale_items.quantity) AS quantity, SUM(sale_items.subtotal) AS revenue, SUM(sale_items.total_cost) AS cost')
            ->groupBy('categories.id')->get()
            ->map(fn ($r) => ['category_id' => $r->category_id, 'category' => $r->category ?? 'Tanpa kategori', 'items' => (int) $r->items, 'quantity' => (int) $r->quantity, 'revenue' => (int) $r->revenue, 'cost' => (int) $r->cost, 'profit' => (int) $r->revenue - (int) $r->cost])
            ->sortByDesc('revenue')->values();
    }

    /** Omzet, HPP, dan laba kotor per produk; $sort: qty|revenue|profit. */
    public function byItem(CarbonImmutable $start, CarbonImmutable $end, ?int $categoryId = null, string $sort = 'qty')
    {
        $rows = $this->soldItems($start, $end)
            ->when($categoryId, fn ($q) => $q->where('products.category_id', $categoryId))
            ->selectRaw('sale_items.product_id, MAX(sale_items.sku) AS sku, MAX(sale_items.product_name) AS name, MAX(categories.name) AS category, SUM(sale_items.quantity) AS quantity, SUM(sale_items.subtotal) AS revenue, SUM(sale_items.total_cost) AS cost')
            ->groupBy('sale_items.product_id')->get()
            ->map(fn ($r) => ['product_id' => (int) $r->product_id, 'sku' => $r->sku, 'name' => $r->name, 'category' => $r->category ?? 'Tanpa kategori', 'quantity' => (int) $r->quantity, 'revenue' => (int) $r->revenue, 'cost' => (int) $r->cost, 'profit' => (int) $r->revenue - (int) $r->cost]);

        return $rows->sortBy([[['revenue' => 'revenue', 'profit' => 'profit'][$sort] ?? 'quantity', 'desc'], ['name', 'asc']])->values();
    }

    public function inventory(string $date)
    {
        [$start,$end] = $this->range($date);
        $opening = StockMovement::selectRaw('product_id, SUM(quantity) AS balance')->where('occurred_at', '<', $start)->groupBy('product_id')->pluck('balance', 'product_id');
        $daily = StockMovement::selectRaw('product_id, SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) AS incoming, SUM(CASE WHEN quantity < 0 THEN -quantity ELSE 0 END) AS outgoing')->where('occurred_at', '>=', $start)->where('occurred_at', '<', $end)->groupBy('product_id')->get()->keyBy('product_id');

        return Product::with('category')->orderBy('name')->get()->map(function ($p) use ($opening, $daily) {
            $open = (int) ($opening[$p->id] ?? 0);
            $in = (int) ($daily->get($p->id)?->incoming ?? 0);
            $out = (int) ($daily->get($p->id)?->outgoing ?? 0);

            return ['product' => $p, 'opening' => $open, 'incoming' => $in, 'sold' => $out, 'closing' => $open + $in - $out];
        });
    }
}
