<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{

    protected $fillable = [
        'product_id', 'sku', 'purchase_price', 'sale_price', 'quantity', 'received_at'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
