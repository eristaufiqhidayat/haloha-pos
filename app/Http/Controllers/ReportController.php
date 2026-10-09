<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function date(Request $r): string
    {
        $r->validate(['date' => 'nullable|date_format:Y-m-d']);

        return $r->input('date') ?: now()->toDateString();
    }

    public function sales(Request $r, ReportService $service)
    {
        $date = $this->date($r);
        $report = $service->sales($date);

        return view('reports.sales', compact('date', 'report'));
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
        $date = $this->date($r);
        $report = $service->sales($date);

        return $this->csv('penjualan-'.$date.'.csv', ['Invoice', 'Waktu', 'Kasir', 'Pembayaran', 'Subtotal', 'Diskon', 'Pajak', 'QRIS', 'Transfer', 'Debit', 'Tunai', 'Omzet', 'HPP', 'Laba kotor'], $report['rows']->map(fn ($s) => [$s->invoice_number, $s->sold_at->format('Y-m-d H:i:s'), $s->cashier_name ?? $s->user->name, $s->payment_method, $s->subtotal, $s->discount, $s->tax_amount, $s->payments->where('method', 'qris')->sum('amount'), $s->payments->where('method', 'transfer')->sum('amount'), $s->payments->where('method', 'debit')->sum('amount'), $s->payments->where('method', 'cash')->sum('amount'), $s->total, $s->total_cost, $s->total - $s->total_cost - $s->tax_amount]));
    }

    public function exportInventory(Request $r, ReportService $service)
    {
        $date = $this->date($r);

        return $this->csv('stok-'.$date.'.csv', ['Tanggal', 'SKU', 'Produk', 'Stok awal', 'Masuk', 'Terjual', 'Stok akhir'], $service->inventory($date)->map(fn ($x) => [$date, $x['product']->sku, $x['product']->name, $x['opening'], $x['incoming'], $x['sold'], $x['closing']]));
    }
}
