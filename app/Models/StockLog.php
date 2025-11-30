<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Stock;
use App\Models\Product;

class StockLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'product_id',
        'type',
        'previous_qty',
        'change_qty',
        'current_qty',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
