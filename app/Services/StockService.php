<?php
namespace App\Services;

use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    public function changeStock(int $stockId, int $productId, int $changeQty, string $type): StockLog
    {
        if (!in_array($type, ['Order-create','Order-update','Order-delete'])) {
            throw new Exception("Invalid log type: {$type}");
        }

        return DB::transaction(function () use ($stockId, $productId, $changeQty, $type) {
            $stock = Stock::lockForUpdate()->find($stockId);
            if (!$stock) throw new Exception("Stock not found: {$stockId}");
            if ((int)$stock->product_id !== $productId) throw new Exception("Stock #{$stockId} does not belong to product #{$productId}");

            $previous = (int) $stock->quantity;
            $current = $previous + $changeQty;
            if ($current < 0) throw new Exception("Insufficient stock");

            $stock->quantity = $current;
            $stock->save();

            return StockLog::create([
                'stock_id' => $stockId,
                'product_id' => $productId,
                'type' => $type,
                'previous_qty' => $previous,
                'change_qty' => $changeQty,
                'current_qty' => $current,
            ]);
        });
    }
}
