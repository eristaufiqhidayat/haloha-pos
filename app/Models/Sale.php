<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sold_at' => 'datetime', 'subtotal' => 'integer', 'discount' => 'integer', 'total' => 'integer', 'total_cost' => 'integer', 'paid_amount' => 'integer', 'change_amount' => 'integer', 'tax_amount' => 'integer', 'tax_rate' => 'integer', 'takeaway' => 'boolean', 'qris_code' => 'integer', 'qris_claimed_at' => 'datetime', 'confirmed_at' => 'datetime', 'discount_enabled' => 'boolean', 'tax_enabled' => 'boolean'];
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

    public function diningTable()
    {
        return $this->belongsTo(DiningTable::class);
    }

    /** Nominal yang ditransfer tamu lewat QRIS: total + kode unik. */
    public function qrisAmount(): int
    {
        return $this->total + $this->qris_code;
    }

    public function qrisExpiresAt()
    {
        return $this->created_at->copy()->addMinutes((int) config('pos.qr_order.qris_expire_minutes', 30));
    }

    /** Pesanan QR yang menunggu tindakan kasir: bayar di kasir, atau QRIS yang sudah dibayar tamu. */
    public function scopeAwaitingCashier($q)
    {
        return $q->where('status', 'incoming')->where(fn ($q) => $q->where('qr_payment', 'cashier')->orWhereNotNull('qris_claimed_at'));
    }

    /** Batalkan pesanan QRIS yang tidak dibayar tamu sampai batas waktu. */
    public static function expireGuestOrders(): void
    {
        static::where('status', 'incoming')->where('qr_payment', 'qris')->whereNull('qris_claimed_at')
            ->where('created_at', '<', now()->subMinutes((int) config('pos.qr_order.qris_expire_minutes', 30)))
            ->update(['status' => 'cancelled']);
    }

    public function isSplit(): bool
    {
        return $this->split_mode !== null;
    }

    public function hasPaidBill(): bool
    {
        return $this->bills()->where('status', 'paid')->exists();
    }

    /** Jumlah porsi take away; dipakai untuk menandai pesanan campuran. */
    public function takeawayQuantity(): int
    {
        return (int) $this->items->where('takeaway', true)->sum('quantity');
    }

    public function isMixedTakeaway(): bool
    {
        return ! $this->takeaway && $this->takeawayQuantity() > 0;
    }

    /** Label tempat pesanan: "Take away", "Meja 05", atau "Meja 05 · ada take away". */
    public function placeLabel(): string
    {
        return $this->takeaway ? 'Take away' : 'Meja '.($this->table_number ?: '—').($this->isMixedTakeaway() ? ' · ada take away' : '');
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
            ->groupBy(fn ($i) => $i->product_id.'|'.$i->notes.'|'.(int) $i->takeaway)->map(fn ($rows) => tap(clone $rows->first(), fn ($i) => $i->quantity = $rows->sum('quantity')))->values();
    }
}
