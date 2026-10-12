<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sold_at' => 'datetime', 'subtotal' => 'integer', 'discount' => 'integer', 'total' => 'integer', 'total_cost' => 'integer', 'paid_amount' => 'integer', 'change_amount' => 'integer', 'tax_amount' => 'integer', 'tax_rate' => 'integer', 'takeaway' => 'boolean', 'discount_enabled' => 'boolean', 'tax_enabled' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function bills()
    {
        return $this->hasMany(SaleBill::class)->orderBy('number');
    }

    public function isSplit(): bool
    {
        return $this->split_mode !== null;
    }

    public function hasPaidBill(): bool
    {
        return $this->bills()->where('status', 'paid')->exists();
    }

    public static function kitchenStation(string $key): ?array
    {
        $station = config("pos.kitchen_stations.$key");
        return $station ? $station + ['key' => $key] : null;
    }

    public function kitchenItems(array $station)
    {
        $keywords = array_map('mb_strtolower', $station['keywords'] ?? []);
        return $this->items->filter(fn ($i) => $keywords === [] || collect($keywords)->contains(fn ($k) => str_contains(mb_strtolower($i->product_name), $k)))
            // Split per item memecah baris menu antar tagihan; dapur tetap melihat satu baris per menu + catatan.
            ->groupBy(fn ($i) => $i->product_id.'|'.$i->notes)->map(fn ($rows) => tap(clone $rows->first(), fn ($i) => $i->quantity = $rows->sum('quantity')))->values();
    }
}
