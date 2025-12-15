<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    // List Orders
    public function index()
    {
        $orders = Order::with('products.product')->withCount('products')->latest()->get();

        return response()->json($orders);
    }

    public function create()
    {
        //
    }

    // Create Order
    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->createOrder($request->validated());

        return response()->json($order, 201);
    }

    // Show Order
    public function show(Order $order)
    {
        return response()->json($order->load('products.product'));
    }

    public function edit(string $id)
    {
        //
    }

    // Update Order
    public function update(Request $request, Order $order)
    {
        // You can create UpdateOrderRequest if needed
        $order = $this->orderService->updateOrder($order, $request->all());
        return response()->json($order);
        
    }
    // Update Status
    public function updateStatus(Request $request, Order $order)
    {
        $order->update(['status' => $request->status]);

        return response()->json($order);
    }

    // Delete Order
    public function destroy(Order $order)
    {
        $this->orderService->deleteOrder($order);

        return response()->json(['message' => 'Order deleted and stock restored.']);
    }
}
