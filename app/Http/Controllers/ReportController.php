<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function date(Request $r): string
    {
        $r->validate(['date' => 'nullable|date_format:Y-m-d']);

        return $r->input('date') ?: now()->toDateString();
    }

    /** Filter laporan penjualan: periode, tab, dan filter/urutan per item. */
    private function salesFilter(Request $r, ReportService $service): array
    {
        $r->validate([
            'period' => 'nullable|in:day,week,month,year',
            'date' => 'nullable|date_format:Y-m-d',
            'week' => ['nullable', 'regex:/^\d{4}-W(0[1-9]|[1-4]\d|5[0-3])$/'],
            'month' => 'nullable|date_format:Y-m',
            'year' => 'nullable|integer|between:2000,2100',
            'view' => 'nullable|in:category,item,transactions',
            'category' => 'nullable|integer',
            'sort' => 'nullable|in:qty,revenue,profit',
        ]);
        $now = now();
        $period = $r->input('period', 'day');
        $value = match ($period) {
            'week' => $r->input('week') ?: $now->isoWeekYear.'-W'.str_pad((string) $now->isoWeek, 2, '0', STR_PAD_LEFT),
            'month' => $r->input('month') ?: $now->format('Y-m'),
            'year' => (string) ($r->input('year') ?: $now->year),
            default => $r->input('date') ?: $now->toDateString(),
        };
        [$start, $end, $label, $key] = $service->period($period, $value);

        return [
            'period' => $period, 'start' => $start, 'end' => $end, 'label' => $label, 'key' => $key,
            'date' => $period === 'day' ? $value : $start->toDateString(),
            'week' => $start->isoWeekYear.'-W'.str_pad((string) $start->isoWeek, 2, '0', STR_PAD_LEFT),
            'month' => $start->format('Y-m'), 'year' => $start->year,
            'view' => $r->input('view', 'category'),
            'category' => $r->filled('category') ? (int) $r->input('category') : null,
            'sort' => $r->input('sort', 'qty'),
        ];
    }

    public function sales(Request $r, ReportService $service)
    {
        $filter = $this->salesFilter($r, $service);
        $date = $filter['date'];
        $report = $service->salesBetween($filter['start'], $filter['end']);
        $categories = $filter['view'] === 'category' ? $service->byCategory($filter['start'], $filter['end']) : collect();
        $items = $filter['view'] === 'item' ? $service->byItem($filter['start'], $filter['end'], $filter['category'], $filter['sort']) : collect();
        $categoryOptions = Category::orderBy('name')->get(['id', 'name']);

        return view('reports.sales', compact('date', 'report', 'filter', 'categories', 'items', 'categoryOptions'));
    }

    public function inventory(Request $r, ReportService $service)
    {
        $date = $this->date($r);
        $rows = $service->inventory($date);

        return view('reports.inventory', compact('date', 'rows'));
    }

    private function csv(string $filename, array $header, iterable $rows)
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, ',', '"', '');
            foreach ($rows as $row) {
                $row = array_map(function ($value) {
                    if (is_string($value) && preg_match('/^[=+@\-\t\r]/u', $value)) {
                        return "'".$value;
                    }

                    return $value;
                }, $row);
                fputcsv($out, $row, ',', '"', '');
            }fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportSales(Request $r, ReportService $service)
    {
        $f = $this->salesFilter($r, $service);
        $periode = $f['label'];

        if ($f['view'] === 'category') {
            return $this->csv('penjualan-kategori-'.$f['key'].'.csv', ['Periode', 'Kategori', 'Jumlah item', 'Qty terjual', 'Omzet', 'HPP', 'Laba kotor'], $service->byCategory($f['start'], $f['end'])->map(fn ($x) => [$periode, $x['category'], $x['items'], $x['quantity'], $x['revenue'], $x['cost'], $x['profit']]));
        }

        if ($f['view'] === 'item') {
            return $this->csv('penjualan-item-'.$f['key'].'.csv', ['Periode', 'SKU', 'Produk', 'Kategori', 'Qty', 'Omzet', 'HPP', 'Laba kotor'], $service->byItem($f['start'], $f['end'], $f['category'], $f['sort'])->map(fn ($x) => [$periode, $x['sku'], $x['name'], $x['category'], $x['quantity'], $x['revenue'], $x['cost'], $x['profit']]));
        }

        $report = $service->salesBetween($f['start'], $f['end']);

        return $this->csv('penjualan-'.$f['key'].'.csv', ['Invoice', 'Waktu', 'Kasir', 'Pembayaran', 'Subtotal', 'Diskon', 'Pajak', 'QRIS', 'Transfer', 'Debit', 'Tunai', 'Omzet', 'HPP', 'Laba kotor'], $report['rows']->map(fn ($s) => [$s->invoice_number, $s->sold_at->format('Y-m-d H:i:s'), $s->cashier_name ?? $s->user->name, $s->payment_method, $s->subtotal, $s->discount, $s->tax_amount, $s->payments->where('method', 'qris')->sum('amount'), $s->payments->where('method', 'transfer')->sum('amount'), $s->payments->where('method', 'debit')->sum('amount'), $s->payments->where('method', 'cash')->sum('amount'), $s->total, $s->total_cost, $s->total - $s->total_cost - $s->tax_amount]));
    }

    public function exportInventory(Request $r, ReportService $service)
    {
        $date = $this->date($r);

        return $this->csv('stok-'.$date.'.csv', ['Tanggal', 'SKU', 'Produk', 'Stok awal', 'Masuk', 'Terjual', 'Stok akhir'], $service->inventory($date)->map(fn ($x) => [$date, $x['product']->sku, $x['product']->name, $x['opening'], $x['incoming'], $x['sold'], $x['closing']]));
    }
}
