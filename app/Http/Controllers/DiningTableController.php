<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\DiningTable;
use App\Support\QrImage;
use App\Support\Qris;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiningTableController extends Controller
{
    public function index()
    {
        $tables = DiningTable::orderByRaw('LENGTH(number), number')->get();
        $payload = AppSetting::get('qris_payload');
        return view('tables.index', ['tables' => $tables, 'qr' => $tables->mapWithKeys(fn ($t) => [$t->id => QrImage::svg($t->orderUrl(), 140)]),
            'qrisMerchant' => $payload ? Qris::merchantName($payload) : null, 'qrisEnabled' => AppSetting::get('qris_enabled') === '1']);
    }

    public function store(Request $r)
    {
        DiningTable::create($this->data($r));
        return back()->with('success', 'Meja ditambahkan.');
    }

    public function update(Request $r, DiningTable $table)
    {
        $table->update($this->data($r, $table));
        return back()->with('success', 'Meja diperbarui.');
    }

    public function regenerate(DiningTable $table)
    {
        $table->regenerateToken();
        return back()->with('success', 'QR Meja '.$table->number.' diganti. Cetak ulang dan ganti kartu QR di meja; QR lama tidak berlaku lagi.');
    }

    public function destroy(DiningTable $table)
    {
        $table->delete();
        return back()->with('success', 'Meja dihapus.');
    }

    public function print(Request $r)
    {
        $tables = DiningTable::where('is_active', true)->when($r->filled('table'), fn ($q) => $q->whereKey($r->integer('table')))->orderByRaw('LENGTH(number), number')->get();
        return view('tables.print', ['tables' => $tables, 'qr' => $tables->mapWithKeys(fn ($t) => [$t->id => QrImage::svg($t->orderUrl(), 220)])]);
    }

    public function updateQris(Request $r)
    {
        $data = $r->validate(['qris_payload' => 'nullable|string|max:512', 'qris_enabled' => 'nullable|boolean']);
        $payload = trim((string) ($data['qris_payload'] ?? '')) ?: AppSetting::get('qris_payload');
        if ($payload && ! Qris::isValid($payload)) {
            return back()->withErrors(['qris_payload' => 'Kode QRIS tidak terbaca atau rusak. Upload foto QRIS resto yang jelas.']);
        }
        if ($r->boolean('qris_enabled') && ! $payload) {
            return back()->withErrors(['qris_payload' => 'Upload foto QRIS resto sebelum mengaktifkan pembayaran QRIS dari HP tamu.']);
        }
        AppSetting::put('qris_payload', $payload);
        AppSetting::put('qris_enabled', $r->boolean('qris_enabled') ? '1' : '0');
        return back()->with('success', 'Pengaturan QRIS disimpan.');
    }

    private function data(Request $r, ?DiningTable $table = null): array
    {
        $data = $r->validate([
            'number' => ['required', 'string', 'max:30', Rule::unique('dining_tables')->ignore($table?->id)],
            'description' => 'nullable|string|max:100', 'is_active' => 'nullable|boolean',
        ]);
        return ['is_active' => $r->boolean('is_active', $table === null)] + $data;
    }
}
