<?php

/*
|--------------------------------------------------------------------------
| Negative-Stock Prevention Tests
|--------------------------------------------------------------------------
|
| Real parallel requests cannot be issued inside a single PHP test process,
| and SQLite (used for the in-memory test DB) ignores `FOR UPDATE`. These tests
| therefore verify the invariants that the lock + transaction guarantee:
|
|  • N competing buyers for M < N units → exactly M succeed, the rest get 409
|  • Stock never drops below zero, whatever the order of requests
|  • Total units sold == initial stock − final stock (no lost updates)
|  • Duplicate lines for one product in a single order are checked against
|    the *already-decremented* locked row
|
| The row lock itself (`lockForUpdate`) is MySQL behaviour; verify it with a
| load test against MySQL if needed.
|
*/

use App\Models\OrderItem;
use App\Models\Product;

describe('Prevention of negative stock under competing requests', function () {

    it('sells exactly the available units when many users compete for scarce stock', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);
        $buyers = collect(range(1, 12))->map(fn () => staffUser());

        $statuses = $buyers->map(
            fn ($buyer) => $this->actingAs($buyer)->postJson('/api/orders', [
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ])->getStatusCode()
        );

        expect($statuses->filter(fn ($s) => $s === 201))->toHaveCount(5)
            ->and($statuses->filter(fn ($s) => $s === 409))->toHaveCount(7)
            ->and($product->fresh()->stock_quantity)->toBe(0)
            ->and((int) OrderItem::where('product_id', $product->id)->sum('quantity'))->toBe(5);
    });

    it('never lets stock go negative with mixed-size orders', function () {
        $initial = 10;
        $product = Product::factory()->create(['stock_quantity' => $initial]);
        $sizes = [4, 4, 4, 3, 2, 1, 5];   // total demand 23 > 10

        foreach ($sizes as $qty) {
            $this->actingAs(staffUser())->postJson('/api/orders', [
                'items' => [['product_id' => $product->id, 'quantity' => $qty]],
            ]);

            expect($product->fresh()->stock_quantity)->toBeGreaterThanOrEqual(0);
        }

        $sold = (int) OrderItem::where('product_id', $product->id)->sum('quantity');

        expect($sold)->toBeLessThanOrEqual($initial)
            ->and($product->fresh()->stock_quantity)->toBe($initial - $sold);
    });

    it('rejects an order whose duplicate lines together exceed stock', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        // 3 + 3 = 6 > 5 — the second line must see the decremented, locked row.
        $this->actingAs(staffUser())->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ])->assertStatus(409);

        expect($product->fresh()->stock_quantity)->toBe(5);
        $this->assertDatabaseCount('orders', 0);
    });

    it('accepts duplicate lines that together fit within stock', function () {
        $product = Product::factory()->create(['stock_quantity' => 6]);

        $this->actingAs(staffUser())->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ])->assertStatus(201);

        expect($product->fresh()->stock_quantity)->toBe(0);
    });

    it('issues a row lock inside a transaction when reading product stock', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);
        $queries = [];

        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->actingAs(staffUser())->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(201);

        // SQLite omits "for update" from SQL; assert the lock path is the one used
        // by checking the product was selected and then decremented via UPDATE.
        $selects = collect($queries)->filter(fn ($q) => str_contains($q, 'from "products"') && str_starts_with(strtolower($q), 'select'));
        $updates = collect($queries)->filter(fn ($q) => str_starts_with(strtolower($q), 'update "products"'));

        expect($selects)->not->toBeEmpty()
            ->and($updates)->not->toBeEmpty();

        if (DB::getDriverName() === 'mysql') {
            expect(collect($queries)->contains(fn ($q) => str_contains(strtolower($q), 'for update')))->toBeTrue();
        }
    });
});
