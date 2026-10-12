<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'paid_amount' => 'integer', 'change_amount' => 'integer'];
    }

    public function bill()
    {
        return $this->belongsTo(SaleBill::class, 'sale_bill_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
