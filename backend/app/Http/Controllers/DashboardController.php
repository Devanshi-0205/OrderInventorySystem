<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Return summary statistics for the dashboard.
     */
    public function index(): JsonResponse
    {
        $totalProducts    = Product::count();
        $totalOrders      = Order::count();
        $pendingOrders    = Order::where('status', 'pending')->count();
        $lowStockProducts = Product::where('stock_quantity', '<=', 10)->count();

        return response()->json([
            'data' => [
                'total_products'     => $totalProducts,
                'total_orders'       => $totalOrders,
                'pending_orders'     => $pendingOrders,
                'low_stock_products' => $lowStockProducts,
            ],
        ]);
    }
}
