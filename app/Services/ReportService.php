<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;

class ReportService
{
    public function range(string $date): array
    {
        $start = CarbonImmutable::parse($date, config('app.timezone'))->startOfDay();

        return [$start, $start->addDay()];
    }

    public function sales(string $date): array
    {
        [$start,$end] = $this->range($date);
        $rows = Sale::with(['user', 'items'])->where('sold_at', '>=', $start)->where('sold_at', '<', $end)->orderByDesc('sold_at')->get();
        $revenue = (int) $rows->sum('total');
        $cost = (int) $rows->sum('total_cost');

        return compact('rows', 'revenue', 'cost') + ['profit' => $revenue - $cost, 'discount' => (int) $rows->sum('discount'), 'quantity' => (int) $rows->sum(fn ($s) => $s->items->sum('quantity')), 'payments' => $rows->groupBy('payment_method')->map->sum('total')];
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
