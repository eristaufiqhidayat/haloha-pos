<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['category_id', 'sku', 'name', 'cost_price', 'selling_price', 'minimum_stock', 'is_active'];

    protected function casts(): array
    {
        return ['cost_price' => 'integer', 'selling_price' => 'integer', 'stock' => 'integer', 'minimum_stock' => 'integer', 'is_active' => 'boolean'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
