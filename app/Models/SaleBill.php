<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleBill extends Model
{
    public const MAX = 15;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['number' => 'integer', 'subtotal' => 'integer', 'discount' => 'integer', 'tax_amount' => 'integer', 'total' => 'integer', 'paid_amount' => 'integer', 'change_amount' => 'integer', 'paid_at' => 'datetime'];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function title(): string
    {
        return 'Tagihan '.$this->number.($this->label ? ' · '.$this->label : '');
    }
}
