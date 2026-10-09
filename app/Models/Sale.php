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
}
