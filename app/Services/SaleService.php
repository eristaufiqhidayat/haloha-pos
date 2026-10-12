<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleBill;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function checkout(User $user, array $data): Sale
    {
        return DB::transaction(function () use ($user, $data) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = Sale::where('checkout_token', $data['checkout_token'])->first();
            if ($existing) {
                abort_unless($existing->user_id === $user->id, 403);
                if ($existing->status !== 'paid') {
                    throw ValidationException::withMessages(['checkout_token' => 'Pesanan sudah tersimpan. Bayar dari Pesanan Berjalan.']);
                }
                return $existing->load('items', 'payments');
            }
            [$fields, $items] = $this->snapshot($user, $data);
            $payments = $this->payments($data, $fields['total']);
            $sale = $this->createSale($user, $data, $fields, 'paid');
            $sale->items()->createMany($items);
            $this->settle($sale, $payments, $user);
            return $sale->load('items', 'payments');
        }, 3);
    }

    public function savePending(User $user, array $data, ?Sale $order = null): Sale
    {
        return DB::transaction(function () use ($user, $data, $order) {
            $actor = $user;
            $user = User::whereKey($order?->user_id ?? $user->id)->lockForUpdate()->firstOrFail();
            if ($order) {
                $order = Sale::whereKey($order->id)->lockForUpdate()->firstOrFail();
                abort_unless($order->user_id === $actor->id || $actor->role->is_system, 403);
                if ($order->status !== 'pending') {
                    throw ValidationException::withMessages(['order' => 'Pesanan lunas tidak dapat diubah.']);
                }
                if ($order->hasPaidBill()) {
                    throw ValidationException::withMessages(['order' => 'Sebagian tagihan sudah lunas. Pesanan tidak dapat diubah.']);
                }
            } else {
                $existing = Sale::where('checkout_token', $data['checkout_token'])->first();
                if ($existing) {
                    abort_unless($existing->user_id === $user->id, 403);
                    return $existing;
                }
            }
            [$fields, $items] = $this->snapshot($user, $data);
            if ($order) {
                // Isi pesanan berubah, jadi pembagian tagihan lama tidak berlaku lagi.
                $order->update($fields + ['split_mode' => null]);
                $order->items()->delete();
                $order->bills()->delete();
            } else {
                $order = $this->createSale($user, $data, $fields, 'pending');
            }
            $order->items()->createMany($items);
            return $order->load('items');
        }, 3);
    }

    public function payPending(User $actor, Sale $order, array $data): Sale
    {
        return DB::transaction(function () use ($actor, $order, $data) {
            // Lock owner first in all order flows to keep lock order consistent.
            $owner = User::whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $sale = Sale::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($sale->user_id === $actor->id || $actor->role->is_system, 403);
            if ($sale->status === 'paid') {
                return $sale->load('items', 'payments');
            }
            if ($sale->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Pesanan tidak dapat dibayar.']);
            }
            if ($sale->isSplit()) {
                throw ValidationException::withMessages(['order' => 'Pesanan sudah dibagi. Bayar per tagihan.']);
            }
            $payments = $this->payments($data, $sale->total);
            $this->settle($sale, $payments, $actor, $owner);
            return $sale->load('items', 'payments');
        }, 3);
    }

    /**
     * Bagi pesanan berjalan menjadi 2..15 tagihan. Mode 'item' memindahkan porsi menu ke tagihan
     * ($data['alloc'][SaleItem::splitKey()][nomor tagihan] = jumlah); mode 'equal' membagi total sama rata.
     */
    public function split(User $actor, Sale $order, array $data): Sale
    {
        return DB::transaction(function () use ($actor, $order, $data) {
            User::whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $sale = $this->lockOpenOrder($actor, $order);
            $this->mergeItems($sale);
            $count = (int) $data['count'];
            $labels = $data['labels'] ?? [];
            $items = $sale->items()->orderBy('id')->get();
            $bills = collect(range(1, $count))->map(fn ($n) => $sale->bills()->create([
                'number' => $n, 'label' => trim((string) ($labels[$n] ?? '')) ?: null, 'subtotal' => 0, 'total' => 0,
            ]))->keyBy('number');
            if ($data['mode'] === 'item') {
                $alloc = $data['alloc'] ?? [];
                $subtotals = $units = array_fill(1, $count, 0);
                if (array_diff(array_keys($alloc), $items->map->splitKey()->all())) {
                    throw ValidationException::withMessages(['alloc' => 'Item pembagian tidak sesuai pesanan. Muat ulang halaman.']);
                }
                foreach ($items as $item) {
                    $rows = array_filter(array_map('intval', $alloc[$item->splitKey()] ?? []));
                    if (array_sum($rows) !== $item->quantity || min($rows ?: [-1]) < 0 || array_diff(array_keys($rows), range(1, $count))) {
                        throw ValidationException::withMessages(['alloc' => 'Semua porsi '.$item->product_name.' harus masuk ke tagihan.']);
                    }
                    foreach ($rows as $n => $qty) {
                        $sale->items()->create(['sale_bill_id' => $bills[$n]->id, 'quantity' => $qty, 'subtotal' => $item->unit_price * $qty, 'total_cost' => $item->unit_cost * $qty]
                            + $item->only('product_id', 'product_name', 'sku', 'unit_price', 'unit_cost', 'notes'));
                        $subtotals[$n] += $item->unit_price * $qty;
                        $units[$n] += $qty;
                    }
                    $item->delete();
                }
                if (in_array(0, $units, true)) {
                    throw ValidationException::withMessages(['alloc' => 'Setiap tagihan harus berisi minimal satu porsi.']);
                }
                $weights = array_values($subtotals);
            } else {
                $weights = array_fill(0, $count, 1);
            }
            if ($data['mode'] === 'item') {
                $subtotal = $weights;
                $discount = self::allocate($sale->discount, $subtotal);
                $tax = self::allocate($sale->tax_amount, array_map(fn ($a, $b) => $a - $b, $subtotal, $discount));
                $total = array_map(fn ($s, $d, $t) => $s - $d + $t, $subtotal, $discount, $tax);
            } else {
                // Bagi rata: total yang dibagi rata, selisih pembulatan masuk ke tagihan awal.
                $total = self::allocate($sale->total, $weights);
                $discount = self::allocate($sale->discount, $weights);
                $tax = self::allocate($sale->tax_amount, $weights);
                $subtotal = array_map(fn ($t, $d, $x) => $t + $d - $x, $total, $discount, $tax);
            }
            foreach ($bills->values() as $i => $bill) {
                $bill->update(['subtotal' => $subtotal[$i], 'discount' => $discount[$i], 'tax_amount' => $tax[$i], 'total' => $total[$i]]);
            }
            $sale->update(['split_mode' => $data['mode']]);
            return $sale->load('bills', 'items');
        }, 3);
    }

    public function unsplit(User $actor, Sale $order): Sale
    {
        return DB::transaction(function () use ($actor, $order) {
            User::whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $sale = $this->lockOpenOrder($actor, $order);
            $this->mergeItems($sale);
            return $sale->load('items');
        }, 3);
    }

    public function payBill(User $actor, Sale $order, int $number, array $data): SaleBill
    {
        return DB::transaction(function () use ($actor, $order, $number, $data) {
            $owner = User::whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $sale = Sale::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($sale->user_id === $actor->id || $actor->role->is_system, 403);
            $bill = $sale->bills()->where('number', $number)->lockForUpdate()->firstOrFail();
            if ($bill->isPaid()) {
                return $bill->load('payments');
            }
            if ($sale->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Pesanan tidak dapat dibayar.']);
            }
            $payments = $this->payments($data, $bill->total);
            // Stok dipotong saat tagihan pertama lunas, agar tagihan berikutnya tidak bisa gagal karena stok.
            if (! $sale->hasPaidBill()) {
                $this->deductStock($sale, $actor, $owner);
            }
            $sale->payments()->createMany(array_map(fn ($p) => $p + ['sale_bill_id' => $bill->id, 'bill_number' => $bill->number], $payments));
            $bill->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => $actor->id, 'payment_method' => count($payments) === 1 ? $payments[0]['method'] : 'split',
                'paid_amount' => array_sum(array_column($payments, 'paid_amount')), 'change_amount' => array_sum(array_column($payments, 'change_amount'))]);
            if (! $sale->bills()->where('status', '!=', 'paid')->exists()) {
                $all = $sale->payments()->get();
                $sale->update(['status' => 'paid', 'sold_at' => now(), 'payment_method' => $all->pluck('method')->unique()->count() === 1 ? $all->first()->method : 'split',
                    'paid_amount' => (int) $all->sum('paid_amount'), 'change_amount' => (int) $all->sum('change_amount')]);
            }
            return $bill->load('payments');
        }, 3);
    }

    /** Bagi $total ke bobot dengan metode sisa terbesar; hasil selalu berjumlah tepat $total. */
    public static function allocate(int $total, array $weights): array
    {
        $weights = array_values($weights);
        $sum = array_sum($weights);
        if ($sum <= 0) {
            return array_fill(0, count($weights), 0);
        }
        $base = array_map(fn ($w) => intdiv($total * $w, $sum), $weights);
        $order = array_keys($weights);
        usort($order, fn ($a, $b) => (($total * $weights[$b]) % $sum) <=> (($total * $weights[$a]) % $sum) ?: $a <=> $b);
        for ($i = 0, $rest = $total - array_sum($base); $i < $rest; $i++) {
            $base[$order[$i]]++;
        }
        return $base;
    }

    private function lockOpenOrder(User $actor, Sale $order): Sale
    {
        $sale = Sale::whereKey($order->id)->lockForUpdate()->firstOrFail();
        abort_unless($sale->user_id === $actor->id || $actor->role->is_system, 403);
        if ($sale->status !== 'pending' || $sale->hasPaidBill()) {
            throw ValidationException::withMessages(['order' => 'Pembagian tidak dapat diubah setelah ada tagihan lunas.']);
        }
        return $sale;
    }

    /** Hapus pembagian lama dan satukan kembali baris menu yang terpecah antar tagihan. */
    private function mergeItems(Sale $sale): void
    {
        if (! $sale->isSplit()) {
            return;
        }
        $items = $sale->items()->orderBy('id')->get();
        $sale->items()->delete();
        $sale->bills()->delete();
        foreach ($items->groupBy->splitKey() as $rows) {
            $qty = (int) $rows->sum('quantity');
            $sale->items()->create(['quantity' => $qty, 'subtotal' => (int) $rows->sum('subtotal'), 'total_cost' => (int) $rows->sum('total_cost')]
                + $rows->first()->only('product_id', 'product_name', 'sku', 'unit_price', 'unit_cost', 'notes'));
        }
        $sale->update(['split_mode' => null]);
    }

    private function snapshot(User $user, array $data): array
    {
        $rows = [];
        $quantities = [];
        foreach ($data['items'] as $row) {
            $id = (int) $row['product_id'];
            $qty = (int) $row['quantity'];
            $notes = trim((string) ($row['notes'] ?? ''));
            if (! $user->maySellProduct($id)) {
                throw ValidationException::withMessages(['items' => 'Menu belum diizinkan oleh admin untuk kasir ini.']);
            }
            $quantities[$id] = ($quantities[$id] ?? 0) + $qty;
            $key = $id.'|'.$notes;
            $rows[$key] ??= ['product_id' => $id, 'quantity' => 0, 'notes' => $notes];
            $rows[$key]['quantity'] += $qty;
        }
        ksort($quantities);
        $products = Product::whereIn('id', array_keys($quantities))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($quantities as $id => $qty) {
            $p = $products->get($id);
            if (! $p || ! $p->is_active || $qty < 1 || $qty > 10000 || $p->stock < $qty) {
                throw ValidationException::withMessages(['items' => 'Produk tidak tersedia atau stok tidak mencukupi.']);
            }
        }
        $subtotal = 0;
        $cost = 0;
        $items = [];
        foreach ($rows as $row) {
            $p = $products[$row['product_id']];
            $line = $p->selling_price * $row['quantity'];
            $lineCost = $p->cost_price * $row['quantity'];
            $subtotal += $line;
            $cost += $lineCost;
            $items[] = $row + ['product_name' => $p->name, 'sku' => $p->sku, 'unit_price' => $p->selling_price, 'unit_cost' => $p->cost_price, 'subtotal' => $line, 'total_cost' => $lineCost];
        }
        // Missing flag supports old clients; new forms always submit explicit booleans.
        $discountEnabled = (bool) ($data['discount_enabled'] ?? ((int) ($data['discount'] ?? 0) > 0));
        $discount = $discountEnabled ? (int) ($data['discount'] ?? 0) : 0;
        if ($discount < 0 || $discount > $subtotal) {
            throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh melebihi subtotal.']);
        }
        $taxEnabled = (bool) ($data['tax_enabled'] ?? false);
        $rate = $taxEnabled ? (int) ($data['tax_rate'] ?? 0) : 0;
        $tax = (int) round(($subtotal - $discount) * $rate / 100);
        $takeaway = (bool) ($data['takeaway'] ?? false);
        return [[
            'subtotal' => $subtotal, 'discount' => $discount, 'discount_enabled' => $discountEnabled,
            'tax_enabled' => $taxEnabled, 'tax_rate' => $rate, 'tax_amount' => $tax,
            'total' => $subtotal - $discount + $tax, 'total_cost' => $cost,
            'guest_name' => $data['guest_name'] ?? null,
            'table_number' => $takeaway ? null : ($data['table_number'] ?? null), 'takeaway' => $takeaway,
        ], $items];
    }

    private function createSale(User $user, array $data, array $fields, string $status): Sale
    {
        return Sale::create($fields + [
            'invoice_number' => 'INV-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8)),
            'checkout_token' => $data['checkout_token'], 'user_id' => $user->id, 'cashier_name' => $user->name,
            'sold_at' => now(), 'status' => $status, 'payment_method' => $status === 'pending' ? 'pending' : 'cash',
            'paid_amount' => 0, 'change_amount' => 0,
        ]);
    }

    private function payments(array $data, int $total): array
    {
        $rows = $data['payments'] ?? null;
        if ($rows === null) {
            // Keep the previous single-method endpoint compatible.
            $method = $data['payment_method'];
            $rows = [['method' => $method, 'amount' => $total, 'paid_amount' => $method === 'cash' ? (int) ($data['paid_amount'] ?? 0) : $total, 'confirmed' => $data['confirmed'] ?? true]];
        }
        $seen = [];
        $result = [];
        $sum = 0;
        foreach ($rows as $row) {
            $method = $row['method'];
            $amount = (int) $row['amount'];
            if (! in_array($method, ['cash', 'qris', 'transfer', 'debit'], true) || isset($seen[$method]) || $amount < 0 || ($total > 0 && $amount === 0)) {
                throw ValidationException::withMessages(['payments' => 'Pilih metode unik dengan nominal pembayaran positif.']);
            }
            if ($method !== 'cash' && $amount > 0 && ! in_array($row['confirmed'] ?? false, [true, 1, '1', 'yes', 'on', 'true'], true)) {
                throw ValidationException::withMessages(['payments' => 'Konfirmasi pembayaran '.$method.' diterima.']);
            }
            $paid = $method === 'cash' ? (int) ($row['paid_amount'] ?? 0) : $amount;
            if ($paid < $amount) {
                throw ValidationException::withMessages(['payments' => 'Uang tunai diterima belum mencukupi.']);
            }
            $seen[$method] = true;
            $sum += $amount;
            $result[] = ['method' => $method, 'amount' => $amount, 'paid_amount' => $paid, 'change_amount' => $paid - $amount];
        }
        if (! $result || $sum !== $total) {
            throw ValidationException::withMessages(['payments' => 'Total alokasi pembayaran harus sama dengan tagihan.']);
        }
        return $result;
    }

    private function settle(Sale $sale, array $payments, User $actor, ?User $owner = null): void
    {
        $this->deductStock($sale, $actor, $owner, function () use ($sale, $payments) {
            $sale->payments()->createMany($payments);
            $sale->update(['status' => 'paid', 'sold_at' => now(), 'payment_method' => count($payments) === 1 ? $payments[0]['method'] : 'split', 'paid_amount' => array_sum(array_column($payments, 'paid_amount')), 'change_amount' => array_sum(array_column($payments, 'change_amount'))]);
        });
    }

    private function deductStock(Sale $sale, User $actor, ?User $owner = null, ?callable $beforeDeduct = null): void
    {
        $quantities = $sale->items()->get()->groupBy('product_id')->map->sum('quantity')->sortKeys();
        $products = Product::whereIn('id', $quantities->keys())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($quantities as $id => $qty) {
            $p = $products->get($id);
            if (! $p || ! $p->is_active || $p->stock < $qty || ($owner && ! $owner->maySellProduct((int) $id))) {
                throw ValidationException::withMessages(['items' => 'Stok atau akses menu pesanan telah berubah. Periksa pesanan sebelum membayar.']);
            }
        }
        if ($beforeDeduct) {
            $beforeDeduct();
        }
        foreach ($quantities as $id => $qty) {
            $p = $products[$id];
            $p->stock -= $qty;
            $p->save();
            StockMovement::create(['product_id' => $id, 'user_id' => $actor->id, 'sale_id' => $sale->id, 'type' => 'sale', 'quantity' => -$qty, 'stock_after' => $p->stock, 'note' => $sale->invoice_number, 'occurred_at' => $sale->status === 'paid' ? $sale->sold_at : now()]);
        }
    }
}
