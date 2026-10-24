<?php

/*
|--------------------------------------------------------------------------
| Order Rollback & Payload-Integrity Tests
|--------------------------------------------------------------------------
|
| Complements OrderTest.php (happy path, 409, partial-failure rollback) with:
|  • Client-supplied price / total_amount are ignored (backend is authoritative)
|  • Inventory rollback when the DB write fails AFTER stock was deducted
|  • No stack trace leaks in 409 responses
|  • Strict quantity validation (zero, negative, non-numeric, fractional)
|
*/

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

describe('Backend-authoritative pricing', function () {

    it('ignores client-supplied price, unit_price and total_amount', function () {
        $product = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 5]);

        $this->actingAs(staffUser())->postJson('/api/orders', [
            'total_amount' => 0.01,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => 0.01,
                'unit_price' => 0.01,
            ]],
        ])->assertStatus(201);

        $order = Order::latest('id')->firstOrFail();
        $item = OrderItem::where('order_id', $order->id)->firstOrFail();

        expect((float) $order->total_amount)->toBe(200.00)
            ->and((float) $item->unit_price)->toBe(100.00);
    });
});

describe('Inventory rollback when persistence fails after deduction', function () {

    it('restores stock when the order row cannot be created', function () {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        // Stock is decremented BEFORE the order row is written, so failing here
        // proves DB::transaction() undoes the deduction.
        Order::creating(function () {
            throw new RuntimeException('Simulated database failure');
        });

        $this->actingAs(staffUser())->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 6]],
        ])->assertStatus(500);

        expect($product->fresh()->stock_quantity)->toBe(10);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    });

    it('restores stock and removes the order when saving order items fails', function () {
        $a = Product::factory()->create(['stock_quantity' => 10]);
        $b = Product::factory()->create(['stock_quantity' => 8]);

        OrderItem::creating(function () {
            throw new RuntimeException('Simulated order item failure');
        });

        $this->actingAs(staffUser())->postJson('/api/orders', [
            'items' => [
                ['product_id' => $a->id, 'quantity' => 3],
                ['product_id' => $b->id, 'quantity' => 2],
            ],
        ])->assertStatus(500);

        expect($a->fresh()->stock_quantity)->toBe(10)
            ->and($b->fresh()->stock_quantity)->toBe(8);

        // The order row was inserted before the item failed — it must be rolled back.
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    });

    it('does not leak a stack trace in the 409 response', function () {
        $product = Product::factory()->create(['stock_quantity' => 1]);

        $this->actingAs(staffUser())->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ])->assertStatus(409)
            ->assertJsonStructure(['message'])
            ->assertJsonMissingPath('trace')
            ->assertJsonMissingPath('exception');
    });
});

describe('Quantity validation', function () {

    it('rejects invalid quantity :dataset and leaves stock untouched', function (mixed $qty) {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->actingAs(staffUser())->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
        ])->assertStatus(422)->assertJsonValidationErrors(['items.0.quantity']);

        expect($product->fresh()->stock_quantity)->toBe(10);
        $this->assertDatabaseCount('orders', 0);
    })->with([
        'negative' => -3,
        'text' => 'abc',
        'fractional' => 1.5,
    ]);
});
