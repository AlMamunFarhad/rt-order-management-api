<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('stocks')->paginate(20);

        return response()->json($products);
    }

    public function store(Request $req)
    {
        // Validate Product
        $data = $req->validate([
            'name' => 'required|string',
            'barcode' => 'required|string|unique:products,barcode',
            'description' => 'nullable|string',
            // Stock Fields
            'sku' => 'nullable|string',
            'purchase_price' => 'nullable|numeric',
            'sale_price' => 'nullable|numeric',
            'quantity' => 'nullable|numeric',
            'received_at' => 'required|date',
        ]);

        $data['slug'] = Str::slug($data['name'].'-'.uniqid());
        $product = Product::create($data);
        // Create Stock under Product
        $product->stocks()->create([
            'sku' => $data['sku'],
            'purchase_price' => $data['purchase_price'],
            'sale_price' => $data['sale_price'],
            'quantity' => $data['quantity'],
            'received_at' => $data['received_at'],
        ]);

        // Load stocks to include in response
        $product->load('stocks');

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    public function show(Product $product)
    {
        $product->load('stocks');

        return response()->json($product);
    }

    public function update(Request $req, Product $product)
    {

        // Validate incoming fields
        $data = $req->validate([
            'name' => 'required|string',
            'barcode' => 'required|string|unique:products,barcode,'.$product->id,
            'description' => 'nullable|string',
            // Stock fields (flat)
            'sku' => 'required|string',
            'purchase_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'quantity' => 'required|numeric',
            'received_at' => 'required|date',
            // optional: if frontend sends stock_id to update a specific stock row
            'stock_id' => 'nullable|exists:stocks,id',
        ]);

        DB::beginTransaction();

        try {
            // Update product
            $product->update([
                'name' => $data['name'],
                'barcode' => $data['barcode'],
                'description' => $data['description'] ?? null,
            ]);

            // Determine which stock to update/create
            if (! empty($data['stock_id'])) {
                // update specific stock
                $stock = $product->stocks()->where('id', $data['stock_id'])->first();
                if ($stock) {
                    $stock->update([
                        'sku' => $data['sku'],
                        'purchase_price' => $data['purchase_price'],
                        'sale_price' => $data['sale_price'],
                        'quantity' => $data['quantity'],
                        'received_at' => $data['received_at'],
                    ]);
                } else {
                    // fallback: create new stock linked to product
                    $product->stocks()->create([
                        'sku' => $data['sku'],
                        'purchase_price' => $data['purchase_price'],
                        'sale_price' => $data['sale_price'],
                        'quantity' => $data['quantity'],
                        'received_at' => $data['received_at'],
                    ]);
                }
            } else {
                // If you only ever have one initial stock per product,
                // update first stock or create if not exists.
                $stock = $product->stocks()->first();
                if ($stock) {
                    $stock->update([
                        'sku' => $data['sku'],
                        'purchase_price' => $data['purchase_price'],
                        'sale_price' => $data['sale_price'],
                        'quantity' => $data['quantity'],
                        'received_at' => $data['received_at'],
                    ]);
                } else {
                    $product->stocks()->create([
                        'sku' => $data['sku'],
                        'purchase_price' => $data['purchase_price'],
                        'sale_price' => $data['sale_price'],
                        'quantity' => $data['quantity'],
                        'received_at' => $data['received_at'],
                    ]);
                }
            }

            DB::commit();

            // return updated product with stocks
            return response()->json([
                'message' => 'Product updated',
                'product' => $product->load('stocks'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    public function destroy(Product $product)
    {
        try {
            // Find product
            $product = Product::with('stocks')->find($product->id);

            if (! $product) {
                return response()->json([
                    'message' => 'Product not found',
                ], 404);
            }

            // Delete all stocks first (if needed)
            if ($product->stocks && $product->stocks->count() > 0) {
                foreach ($product->stocks as $stock) {
                    $stock->delete();
                }
            }

            // Now delete product
            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // search API (barcode/sku/name)
    public function search(Request $req)
    {
        $query = $req->query('q');

        $products = Product::with('stocks')
            ->when($query, function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%")
                    ->orWhereHas('stocks', function ($q2) use ($query) {
                        $q2->where('sku', 'like', "%{$query}%");
                    });
            })
            ->get();

        return response()->json([
            'data' => $products,
        ]);
    }
}
