<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Create a new Order
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'invoice_number' => $data['invoice_number'],
                'date_time' => $data['date_time'],
                'customer_name' => $data['customer_name'],
                'total_amount' => 0,
                'status' => $data['status'] ?? 'Pending',
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                $stock = ! empty($item['stock_id']) ? \App\Models\Stock::lockForUpdate()->findOrFail($item['stock_id'])
                                                  : \App\Models\Stock::where('product_id', $productId)->lockForUpdate()->firstOrFail();

                $this->stockService->changeStock($stock->id, $productId, -$qty, 'Order-create');

                OrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'stock_id' => $stock->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $qty * $unitPrice,
                ]);

                $total += $qty * $unitPrice;
            }

            $order->update(['total_amount' => $total]);

            return $order->load('products.product');
        });
    }

    /**
     * Update an existing Order
     */
    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $orderFields = array_intersect_key($data, array_flip(['invoice_number', 'date_time', 'customer_name', 'status']));
            if (! empty($orderFields)) {
                $order->update($orderFields);
            }
            $items = $data['items'] ?? null;
            if (empty($items) || ! is_array($items)) {
                return $order->load('products.product');
            }
            $existing = $order->products()->get()->keyBy('id');
            $incomingIds = [];
            $total = 0;

            foreach ($items as $item) {
                $opId = $item['order_product_id'] ?? null;
                $productId = (int) $item['product_id'];
                $newQty = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                if ($opId && $existing->has($opId)) {
                    $op = $existing->get($opId);
                    $oldQty = (int) $op->quantity;
                    $delta = $newQty - $oldQty;

                    if ($delta !== 0) {
                        $stock = $op->stock_id
                            ? \App\Models\Stock::lockForUpdate()->find($op->stock_id)
                            : \App\Models\Stock::where('product_id', $productId)->lockForUpdate()->firstOrFail();

                        $this->stockService->changeStock($stock->id, $productId, -$delta, 'Order-update');
                    }

                    $op->update([
                        'quantity' => $newQty,
                        'unit_price' => $unitPrice,
                        'total_price' => $newQty * $unitPrice,
                    ]);

                    $incomingIds[] = $op->id;
                    $total += $newQty * $unitPrice;
                } else {
                    $stock = ! empty($item['stock_id'])
                        ? \App\Models\Stock::lockForUpdate()->findOrFail($item['stock_id'])
                        : \App\Models\Stock::where('product_id', $productId)->lockForUpdate()->firstOrFail();

                    $this->stockService->changeStock($stock->id, $productId, -$newQty, 'Order-update');

                    $newOp = OrderProduct::create([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'stock_id' => $stock->id,
                        'quantity' => $newQty,
                        'unit_price' => $unitPrice,
                        'total_price' => $newQty * $unitPrice,
                    ]);

                    $incomingIds[] = $newOp->id;
                    $total += $newQty * $unitPrice;
                }
            }

            // Remove products not in new items
            $toRemove = $existing->keys()->diff($incomingIds);
            foreach ($toRemove as $removeId) {
                $op = $existing->get($removeId);
                $stock = $op->stock_id
                    ? \App\Models\Stock::lockForUpdate()->find($op->stock_id)
                    : \App\Models\Stock::where('product_id', $op->product_id)->lockForUpdate()->firstOrFail();

                $this->stockService->changeStock($stock->id, $op->product_id, $op->quantity, 'Order-update');
                $op->delete();
            }

            $order->update(['total_amount' => $total]);

            return $order->load('products.product');
        });
    }

    /**
     * Delete an Order and restore stock
     */
    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->products as $op) {
                $stock = $op->stock_id ? \App\Models\Stock::lockForUpdate()->find($op->stock_id)
                                        : \App\Models\Stock::where('product_id', $op->product_id)->lockForUpdate()->firstOrFail();
                $this->stockService->changeStock($stock->id, $op->product_id, $op->quantity, 'Order-delete');
            }
            $order->delete();
        });
    }
}
