<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;

class StockController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Stock::all());
    }
}
