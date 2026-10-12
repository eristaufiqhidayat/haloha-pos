<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['takeaway' => 'boolean'];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /** Kunci baris menu yang tetap sama walau baris dipecah/digabung antar tagihan. */
    public function splitKey(): string
    {
        return md5($this->product_id.'|'.$this->unit_price.'|'.$this->notes.'|'.(int) $this->takeaway);
    }

    public function bill()
    {
        return $this->belongsTo(SaleBill::class, 'sale_bill_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
