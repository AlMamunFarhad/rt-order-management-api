<?php

namespace App\Http\Controllers\Product;

use App\Models\Stock;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Request;

class StockController extends Controller
{
    public function index()
    {
        $stocks = Stock::all();
        return response()->json($stocks);
    }


}
