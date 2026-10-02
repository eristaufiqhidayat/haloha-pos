<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function receive(Product $product, int $quantity, ?User $user, string $note = ''): StockMovement
    {
        if ($quantity < 1 || $quantity > 1000000) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah harus antara 1 dan 1.000.000.']);
        }

        return DB::transaction(function () use ($product, $quantity, $user, $note) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            if (! $locked->is_active) {
                throw ValidationException::withMessages(['product_id' => 'Produk sudah diarsipkan.']);
            }
            if ($locked->stock + $quantity > 2000000000) {
                throw ValidationException::withMessages(['quantity' => 'Stok melebihi batas.']);
            }
            $locked->stock += $quantity;
            $locked->save();

            return StockMovement::create(['product_id' => $locked->id, 'user_id' => $user?->id, 'type' => 'incoming', 'quantity' => $quantity, 'stock_after' => $locked->stock, 'note' => $note, 'occurred_at' => now()]);
        }, 3);
    }
}
