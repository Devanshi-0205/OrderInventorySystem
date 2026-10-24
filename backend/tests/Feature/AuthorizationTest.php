<?php

/*
|--------------------------------------------------------------------------
| Authentication & Authorization Tests
|--------------------------------------------------------------------------
|
| Covers:
|  • Unauthenticated requests → 401
|  • Staff on admin-only routes → 403 (and no side-effects)
|  • Admin on admin-only routes → allowed
|  • Staff only see / open their own orders; admins see all
|  • Real Sanctum Bearer-token flow (login → use token → logout)
|
*/

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

function validProductPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Auth Test Product',
        'sku' => 'AUTH-TEST-001',
        'price' => 10.00,
        'stock_quantity' => 5,
    ], $overrides);
}

function resetAuthState(): void
{
    app('session')->driver()->flush();
    app('auth')->forgetGuards();
}

function makeOrderFor(User $user): Order
{
    return Order::create([
        'user_id' => $user->id,
        'status' => 'pending',
        'total_amount' => 10.00,
    ]);
}

describe('Unauthenticated access → 401', function () {

    it('rejects protected endpoints without a token', function (string $method, string $uri) {
        $this->json($method, $uri)->assertStatus(401);
    })->with([
        'user' => ['GET', '/api/user'],
        'dashboard' => ['GET', '/api/dashboard'],
        'list products' => ['GET', '/api/products'],
        'create product' => ['POST', '/api/products'],
        'list orders' => ['GET', '/api/orders'],
        'create order' => ['POST', '/api/orders'],
        'logout' => ['POST', '/api/logout'],
    ]);

    it('rejects an invalid bearer token', function () {
        $this->withToken('not-a-real-token')->getJson('/api/products')->assertStatus(401);
    });
});

describe('Staff on admin-only routes → 403', function () {

    beforeEach(function () {
        $this->staff = staffUser();
    });

    it('cannot create a product and nothing is persisted', function () {
        $this->actingAs($this->staff)->postJson('/api/products', validProductPayload())
            ->assertStatus(403);

        $this->assertDatabaseCount('products', 0);
    });

    it('cannot update a product', function () {
        $product = Product::factory()->create(['name' => 'Original']);

        $this->actingAs($this->staff)->putJson("/api/products/{$product->id}", validProductPayload([
            'name' => 'Hacked',
            'sku' => $product->sku,
        ]))->assertStatus(403);

        expect($product->fresh()->name)->toBe('Original');
    });

    it('cannot delete a product', function () {
        $product = Product::factory()->create();

        $this->actingAs($this->staff)->deleteJson("/api/products/{$product->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    });

    it('cannot change an order status', function () {
        $order = makeOrderFor($this->staff);

        $this->actingAs($this->staff)->patchJson("/api/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertStatus(403);

        expect($order->fresh()->status)->toBe('pending');
    });
});

describe('Admin on admin-only routes → allowed', function () {

    it('can create, update and delete products', function () {
        $admin = adminUser();

        $id = $this->actingAs($admin)->postJson('/api/products', validProductPayload())
            ->assertStatus(201)->json('data.id');

        $this->actingAs($admin)->putJson("/api/products/{$id}", validProductPayload(['name' => 'Renamed']))
            ->assertStatus(200);

        $this->actingAs($admin)->deleteJson("/api/products/{$id}")->assertStatus(200);
    });

    it('can update an order status and rejects invalid statuses', function () {
        $admin = adminUser();
        $order = makeOrderFor(staffUser());

        $this->actingAs($admin)->patchJson("/api/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        $this->actingAs($admin)->patchJson("/api/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    });
});

describe('Order visibility by role', function () {

    it('lets staff list only their own orders', function () {
        $staff = staffUser();
        $other = staffUser();
        $mine = makeOrderFor($staff);
        makeOrderFor($other);

        $response = $this->actingAs($staff)->getJson('/api/orders')->assertStatus(200);

        expect(collect($response->json('data'))->pluck('id')->all())->toBe([$mine->id]);
    });

    it('lets admins list every order', function () {
        makeOrderFor(staffUser());
        makeOrderFor(staffUser());

        $this->actingAs(adminUser())->getJson('/api/orders')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it("forbids staff from viewing another user's order (403)", function () {
        $order = makeOrderFor(staffUser());

        $this->actingAs(staffUser())->getJson("/api/orders/{$order->id}")->assertStatus(403);
    });

    it('lets staff view their own order and admins view any order', function () {
        $staff = staffUser();
        $order = makeOrderFor($staff);

        $this->actingAs($staff)->getJson("/api/orders/{$order->id}")
            ->assertStatus(200)->assertJsonPath('data.id', $order->id);

        $this->actingAs(adminUser())->getJson("/api/orders/{$order->id}")
            ->assertStatus(200)->assertJsonPath('data.id', $order->id);
    });
});

describe('Sanctum bearer-token flow', function () {

    it('authenticates via Bearer token, accesses a protected route, then logs out', function () {
        // Create a real persisted Sanctum token directly — no Auth::attempt() so no
        // session side-effects that confuse currentAccessToken() resolution in tests.
        $user       = staffUser();
        $plainToken = $user->createToken('api-token')->plainTextToken;

        // Bearer token gives access to protected routes
        $this->withToken($plainToken)->getJson('/api/user')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $user->id);

        // Logout endpoint deletes the token from the database
        $this->withToken($plainToken)->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Logged out successfully.');

        // Token must no longer exist in the personal_access_tokens table
        expect($user->tokens()->count())->toBe(0);
    });

    it('returns a bearer token on successful login via the login endpoint', function () {
        staffUser(['email' => 'login@example.com', 'password' => 'secret-pass']);

        $response = $this->postJson('/api/login', [
            'email'    => 'login@example.com',
            'password' => 'secret-pass',
        ])->assertStatus(200);

        $token = $response->json('data.token');
        expect($token)->toBeString()->not->toBeEmpty();

        // The token from login also grants access to protected routes
        $this->withToken($token)->getJson('/api/user')->assertStatus(200);
    });

    it('rejects login with wrong credentials', function () {
        staffUser(['email' => 'x@example.com', 'password' => 'right']);

        $this->postJson('/api/login', ['email' => 'x@example.com', 'password' => 'wrong'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Invalid credentials.');
    });
});
