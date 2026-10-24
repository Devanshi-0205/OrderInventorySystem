<?php

/*
|--------------------------------------------------------------------------
| Order Creation & Inventory Tests
|--------------------------------------------------------------------------
|
| Covers:
|  • Valid order creation → 201, stock atomically deducted
|  • Backend-authoritative total calculation (never trusts client price)
|  • unit_price snapshot at order time
|  • Insufficient stock → 409, stock unchanged
|  • Partial-failure rollback: one bad item rolls back ALL deductions
|  • Concurrent-safe: stock cannot go negative via sequential over-orders
|  • Input validation: empty items, zero quantity, non-existent product
|
*/

use App\Models\Order;
use App\Models\Product;

// ---------------------------------------------------------------------------
// Happy-path: valid stock
// ---------------------------------------------------------------------------

describe('Order Creation — Valid Stock', function () {

    beforeEach(function () {
        $this->user    = staffUser();
        $this->product = Product::factory()->create([
            'price'          => 50.00,
            'stock_quantity' => 10,
        ]);
    });

    it('returns 201 and deducts stock atomically for a single item', function () {
        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Order placed successfully.')
            ->assertJsonStructure([
                'data' => ['id', 'status', 'total_amount', 'items'],
            ]);

        expect($this->product->fresh()->stock_quantity)->toBe(7);
    });

    it('calculates total_amount on the backend (2 × 50.00 = 100.00)', function () {
        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(201);
        expect((float) $response->json('data.total_amount'))->toBe(100.00);
    });

    it('snapshots unit_price from the product row at the time of ordering', function () {
        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201);
        expect((float) $response->json('data.items.0.unit_price'))->toBe(50.00);
    });

    it('handles multiple items, deducts all stock, and sums the total correctly', function () {
        $product2 = Product::factory()->create(['price' => 20.00, 'stock_quantity' => 5]);

        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],  // 2 × 50 = 100
                ['product_id' => $product2->id,       'quantity' => 3],  // 3 × 20 = 60
            ],
        ]);

        $response->assertStatus(201);
        expect($this->product->fresh()->stock_quantity)->toBe(8);
        expect($product2->fresh()->stock_quantity)->toBe(2);
        expect((float) $response->json('data.total_amount'))->toBe(160.00);
    });

    it('sets the initial order status to pending', function () {
        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');
    });

    it('allows ordering exactly the available stock quantity', function () {
        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10], // exactly stock_quantity
            ],
        ]);

        $response->assertStatus(201);
        expect($this->product->fresh()->stock_quantity)->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// Insufficient stock → 409
// ---------------------------------------------------------------------------

describe('Order Creation — Insufficient Stock', function () {

    beforeEach(function () {
        $this->user    = staffUser();
        $this->product = Product::factory()->create([
            'price'          => 10.00,
            'stock_quantity' => 5,
        ]);
    });

    it('returns 409 when requested quantity exceeds available stock', function () {
        $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 6],
            ],
        ])->assertStatus(409);
    });

    it('leaves the product stock_quantity unchanged on a 409 response', function () {
        $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 99],
            ],
        ]);

        expect($this->product->fresh()->stock_quantity)->toBe(5);
    });

    it('does not persist an Order record when stock is insufficient', function () {
        $countBefore = Order::count();

        $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 99],
            ],
        ]);

        expect(Order::count())->toBe($countBefore);
    });

    it('returns 409 for an out-of-stock product (stock = 0)', function () {
        $product = Product::factory()->outOfStock()->create();

        $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertStatus(409);
    });
});

// ---------------------------------------------------------------------------
// Transaction rollback on partial failure
// ---------------------------------------------------------------------------

describe('Inventory Rollback on Partial Failure', function () {

    it('rolls back ALL stock deductions when one item in the batch is insufficient', function () {
        $user     = staffUser();
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 2]);

        // product1 would succeed (5 ≤ 10) but product2 fails (5 > 2)
        $response = $this->actingAs($user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 5],
                ['product_id' => $product2->id, 'quantity' => 5],
            ],
        ]);

        $response->assertStatus(409);

        // The transaction must have rolled back — both stocks untouched
        expect($product1->fresh()->stock_quantity)->toBe(10);
        expect($product2->fresh()->stock_quantity)->toBe(2);
    });

    it('does not create an Order record when a multi-item transaction rolls back', function () {
        $user     = staffUser();
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 1]);

        $countBefore = Order::count();

        $this->actingAs($user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 3],
                ['product_id' => $product2->id, 'quantity' => 5],  // will fail
            ],
        ]);

        expect(Order::count())->toBe($countBefore);
    });
});

// ---------------------------------------------------------------------------
// Concurrent stock safety (sequential simulation)
// ---------------------------------------------------------------------------

describe('Concurrent Stock Safety', function () {

    it('prevents a second order when the first already exhausted the stock', function () {
        $user    = staffUser();
        $product = Product::factory()->create(['stock_quantity' => 3]);

        // First order consumes all 3 units — must succeed
        $this->actingAs($user)->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertStatus(201);

        expect($product->fresh()->stock_quantity)->toBe(0);

        // Second order for 1 unit — must be rejected (stock is now 0)
        $this->actingAs($user)->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(409);

        // Stock must remain 0 — never goes negative
        expect($product->fresh()->stock_quantity)->toBe(0);
    });

    it('processes back-to-back orders correctly when stock is sufficient', function () {
        $user    = staffUser();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->actingAs($user)->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 4]],
        ])->assertStatus(201);

        $this->actingAs($user)->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 4]],
        ])->assertStatus(201);

        expect($product->fresh()->stock_quantity)->toBe(2);
    });
});

// ---------------------------------------------------------------------------
// Order input validation
// ---------------------------------------------------------------------------

describe('Order Input Validation', function () {

    it('returns 422 when items array is empty', function () {
        $user = staffUser();

        $this->actingAs($user)->postJson('/api/orders', ['items' => []])
            ->assertStatus(422);
    });

    it('returns 422 when items key is missing', function () {
        $user = staffUser();

        $this->actingAs($user)->postJson('/api/orders', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    });

    it('returns 422 when quantity is zero', function () {
        $user    = staffUser();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->actingAs($user)->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 0]],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    });

    it('returns 422 when referencing a non-existent product', function () {
        $user = staffUser();

        $this->actingAs($user)->postJson('/api/orders', [
            'items' => [['product_id' => 99999, 'quantity' => 1]],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_id']);
    });
});
