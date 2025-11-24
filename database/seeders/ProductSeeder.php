<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $products = [
            ['name'=>'Apple iPhone 14', 'barcode'=>'111000111','description'=>'iPhone 14'],
            ['name'=>'Samsung Galaxy A53', 'barcode'=>'111000222','description'=>'Galaxy A53'],
            ['name'=>'USB Cable', 'barcode'=>'111000333','description'=>'Type-C cable'],
        ];

        foreach ($products as $p) {
            $prod = Product::create([
                'name'=>$p['name'],
                'barcode'=>$p['barcode'],
                'slug'=>Str::slug($p['name'].'-'.uniqid()),
                'description'=>$p['description'] ?? null
            ]);

            // create some stock batches
            Stock::create([
                'product_id'=>$prod->id,
                'sku'=>'BATCH-'.Str::upper(Str::random(6)),
                'purchase_price'=>500,
                'sale_price'=>700,
                'quantity'=>10,
                'received_at'=>Carbon::now()->subDays(10)
            ]);

            Stock::create([
                'product_id'=>$prod->id,
                'sku'=>'BATCH-'.Str::upper(Str::random(6)),
                'purchase_price'=>520,
                'sale_price'=>720,
                'quantity'=>5,
                'received_at'=>Carbon::now()->subDays(2)
            ]);
        }
    }
}
