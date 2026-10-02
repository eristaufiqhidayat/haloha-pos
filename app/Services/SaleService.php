<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
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
            // Serialize checkout requests for the same cashier, then lock products in ID order.
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = Sale::where('checkout_token', $data['checkout_token'])->first();
            if ($existing) {
                abort_unless($existing->user_id === $user->id, 403);

                return $existing;
            }
            $quantities = [];
            foreach ($data['items'] as $row) {
                $id = (int) $row['product_id'];
                $quantities[$id] = ($quantities[$id] ?? 0) + (int) $row['quantity'];
            }
            ksort($quantities);
            $products = Product::query()->whereIn('id', array_keys($quantities))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $subtotal = 0;
            $cost = 0;
            foreach ($quantities as $id => $qty) {
                $p = $products->get($id);
                if (! $p || ! $p->is_active || $qty < 1 || $qty > 10000 || $p->stock < $qty) {
                    throw ValidationException::withMessages(['items' => 'Produk tidak tersedia atau stok tidak mencukupi.']);
                }
                $subtotal += $p->selling_price * $qty;
                $cost += $p->cost_price * $qty;
            }
            $discount = (int) ($data['discount'] ?? 0);
            if ($discount < 0 || $discount > $subtotal) {
                throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh melebihi subtotal.']);
            }
            $total = $subtotal - $discount;
            $method = $data['payment_method'];
            $paid = $method === 'cash' ? (int) ($data['paid_amount'] ?? 0) : $total;
            if ($paid < $total) {
                throw ValidationException::withMessages(['paid_amount' => 'Uang diterima belum mencukupi.']);
            }
            $sale = Sale::create(['invoice_number' => 'INV-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8)), 'checkout_token' => $data['checkout_token'], 'user_id' => $user->id, 'sold_at' => now(), 'subtotal' => $subtotal, 'discount' => $discount, 'total' => $total, 'total_cost' => $cost, 'payment_method' => $method, 'paid_amount' => $paid, 'change_amount' => $paid - $total]);
            foreach ($quantities as $id => $qty) {
                $p = $products[$id];
                $sale->items()->create(['product_id' => $id, 'product_name' => $p->name, 'sku' => $p->sku, 'quantity' => $qty, 'unit_price' => $p->selling_price, 'unit_cost' => $p->cost_price, 'subtotal' => $p->selling_price * $qty, 'total_cost' => $p->cost_price * $qty]);
                $p->stock -= $qty;
                $p->save();
                StockMovement::create(['product_id' => $id, 'user_id' => $user->id, 'sale_id' => $sale->id, 'type' => 'sale', 'quantity' => -$qty, 'stock_after' => $p->stock, 'note' => $sale->invoice_number, 'occurred_at' => $sale->sold_at]);
            }

            return $sale->load('items');
        }, 3);
    }
}
