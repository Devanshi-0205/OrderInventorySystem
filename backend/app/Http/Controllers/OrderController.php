<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * List orders with optional status filter.
     * Admins see all orders; staff see only their own.
     * GET /api/orders?status=pending|confirmed|cancelled
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Order::with(['user:id,name,email', 'items.product:id,name,sku']);

        if ($user->role === 'staff') {
            $query->where('user_id', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $orders = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($orders);
    }

    /**
     * Create an order with atomic stock deduction.
     * POST /api/orders
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $items  = $request->validated()['items'];

        $order = DB::transaction(function () use ($userId, $items) {
            $totalAmount = 0;
            $orderItems  = [];

            foreach ($items as $item) {
                // Lock the row to prevent concurrent over-selling
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock_quantity < $item['quantity']) {
                    throw new \App\Exceptions\InsufficientStockException(
                        "Insufficient stock for product \"{$product->name}\". "
                        . "Available: {$product->stock_quantity}, Requested: {$item['quantity']}."
                    );
                }

                $product->decrement('stock_quantity', $item['quantity']);

                $lineTotal    = $product->price * $item['quantity'];
                $totalAmount += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                ];
            }

            $order = Order::create([
                'user_id'      => $userId,
                'status'       => 'pending',
                'total_amount' => $totalAmount,
            ]);

            $order->items()->createMany($orderItems);

            return $order->load(['items.product:id,name,sku']);
        });

        return response()->json([
            'data'    => $order,
            'message' => 'Order placed successfully.',
        ], 201);
    }

    /**
     * Get a single order with its items.
     * GET /api/orders/{id}
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Staff can only view their own orders
        if ($user->role === 'staff' && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $order->load(['user:id,name,email', 'items.product:id,name,sku']);

        return response()->json(['data' => $order]);
    }

    /**
     * Update order status (admin only).
     * PATCH /api/orders/{id}/status
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $order->update(['status' => $request->validated()['status']]);

        return response()->json([
            'data'    => $order->fresh(['items.product:id,name,sku']),
            'message' => 'Order status updated.',
        ]);
    }
}
