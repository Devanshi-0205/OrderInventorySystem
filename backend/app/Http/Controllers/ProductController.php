<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * List products with optional search and stock_status filter.
     * GET /api/products?search=&stock_status=in_stock|low_stock|out_of_stock
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($stockStatus = $request->query('stock_status')) {
            match ($stockStatus) {
                'in_stock'     => $query->where('stock_quantity', '>', 10),
                'low_stock'    => $query->whereBetween('stock_quantity', [1, 10]),
                'out_of_stock' => $query->where('stock_quantity', 0),
                default        => null,
            };
        }

        $products = $query->orderBy('name')->paginate(20);

        return response()->json($products);
    }

    /**
     * Create a new product (admin only).
     * POST /api/products
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json([
            'data'    => $product,
            'message' => 'Product created successfully.',
        ], 201);
    }

    /**
     * Get a single product.
     * GET /api/products/{id}
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'data' => array_merge($product->toArray(), [
                'stock_status' => $product->stock_status,
            ]),
        ]);
    }

    /**
     * Update an existing product (admin only).
     * PUT /api/products/{id}
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return response()->json([
            'data'    => array_merge($product->fresh()->toArray(), [
                'stock_status' => $product->fresh()->stock_status,
            ]),
            'message' => 'Product updated successfully.',
        ]);
    }

    /**
     * Delete a product (admin only).
     * DELETE /api/products/{id}
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
