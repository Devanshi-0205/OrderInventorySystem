<?php

/*
|--------------------------------------------------------------------------
| Product Validation Tests
|--------------------------------------------------------------------------
|
| Covers:
|  • Valid product creation → 201
|  • Duplicate SKU → 422
|  • Missing required fields → 422
|  • Negative price / stock → 422
|  • SKU max-length (100 chars) enforcement
|  • Successful update and delete (admin only)
|  • 404 for non-existent product
|
*/

use App\Models\Product;

// ---------------------------------------------------------------------------
// Creating products
// ---------------------------------------------------------------------------

describe('Product Creation Validation', function () {

    beforeEach(function () {
        $this->admin = adminUser();
    });

    it('creates a product with valid data and returns 201', function () {
        $response = $this->actingAs($this->admin)->postJson('/api/products', [
            'name'           => 'Widget Pro',
            'sku'            => 'WIDGET-PRO-001',
            'description'    => 'A professional-grade widget.',
            'price'          => 49.99,
            'stock_quantity' => 100,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'sku', 'price', 'stock_quantity', 'stock_status'],
                'message',
            ])
            ->assertJsonPath('message', 'Product created successfully.');

        $this->assertDatabaseHas('products', ['sku' => 'WIDGET-PRO-001', 'name' => 'Widget Pro']);
    });

    it('rejects a duplicate SKU with 422 and a validation error on sku', function () {
        Product::factory()->create(['sku' => 'DUP-SKU-001']);

        $response = $this->actingAs($this->admin)->postJson('/api/products', [
            'name'           => 'Another Widget',
            'sku'            => 'DUP-SKU-001',
            'price'          => 10.00,
            'stock_quantity' => 50,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    });

    it('requires name, sku, price, and stock_quantity (all missing → 422)', function () {
        $response = $this->actingAs($this->admin)->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'sku', 'price', 'stock_quantity']);
    });

    it('rejects a negative price with 422', function () {
        $response = $this->actingAs($this->admin)->postJson('/api/products', [
            'name'           => 'Bad Price Product',
            'sku'            => 'BAD-PRICE-001',
            'price'          => -0.01,
            'stock_quantity' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    });

    it('accepts price of zero (free product)', function () {
        $response = $this->actingAs($this->admin)->postJson('/api/products', [
            'name'           => 'Free Item',
            'sku'            => 'FREE-001',
            'price'          => 0.00,
            'stock_quantity' => 5,
        ]);

        $response->assertStatus(201);
    });

    it('rejects a negative stock_quantity with 422', function () {
        $response = $this->actingAs($this->admin)->postJson('/api/products', [
            'name'           => 'Negative Stock',
            'sku'            => 'NEG-STK-001',
            'price'          => 9.99,
            'stock_quantity' => -1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stock_quantity']);
    });

    it('accepts stock_quantity of zero (out-of-stock on creation)', function () {
        $response = $this->actingAs($this->admin)->postJson('/api/products', [
            'name'           => 'No Stock Product',
            'sku'            => 'ZERO-STK-001',
            'price'          => 5.00,
            'stock_quantity' => 0,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.stock_status', 'out_of_stock');
    });

    it('enforces SKU maximum length of 100 characters → 422', function () {
        $response = $this->actingAs($this->admin)->postJson('/api/products', [
            'name'           => 'Long SKU Product',
            'sku'            => str_repeat('X', 101),   // 101 chars — exceeds limit
            'price'          => 10.00,
            'stock_quantity' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    });

    it('accepts SKU of exactly 100 characters → 201', function () {
        $response = $this->actingAs($this->admin)->postJson('/api/products', [
            'name'           => 'Max SKU Product',
            'sku'            => str_repeat('A', 100),   // exactly 100 chars
            'price'          => 10.00,
            'stock_quantity' => 10,
        ]);

        $response->assertStatus(201);
    });
});

// ---------------------------------------------------------------------------
// Reading products
// ---------------------------------------------------------------------------

describe('Product Retrieval', function () {

    it('lists products for any authenticated user', function () {
        Product::factory()->count(3)->create();
        $user = staffUser();

        $this->actingAs($user)->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'total', 'per_page']);
    });

    it('returns a single product with stock_status', function () {
        $product = Product::factory()->create(['stock_quantity' => 15]);
        $user    = staffUser();

        $this->actingAs($user)->getJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.stock_status', 'in_stock');
    });

    it('returns 404 for a non-existent product', function () {
        $user = staffUser();

        $this->actingAs($user)->getJson('/api/products/99999')
            ->assertStatus(404);
    });
});

// ---------------------------------------------------------------------------
// Updating and deleting products (admin)
// ---------------------------------------------------------------------------

describe('Product Update and Delete', function () {

    it('allows admin to update a product and returns updated data', function () {
        $product = Product::factory()->create(['price' => 10.00]);
        $admin   = adminUser();

        $response = $this->actingAs($admin)->putJson("/api/products/{$product->id}", [
            'name'           => 'Updated Name',
            'sku'            => $product->sku,
            'description'    => 'New description.',
            'price'          => 99.99,
            'stock_quantity' => 250,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('message', 'Product updated successfully.');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 99.99]);
    });

    it('allows admin to delete a product and removes it from the database', function () {
        $product = Product::factory()->create();
        $admin   = adminUser();

        $this->actingAs($admin)->deleteJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    });
});
