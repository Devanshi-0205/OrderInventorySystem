<?php

/*
|--------------------------------------------------------------------------
| PEST v5 — Global Test Configuration
|--------------------------------------------------------------------------
|
| Apply TestCase + RefreshDatabase to every test inside tests/Feature/.
| Helper functions defined here are available in all feature tests without
| any explicit import.
|
*/

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(
    Tests\TestCase::class,
    RefreshDatabase::class,
)->in('Feature');

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------

/**
 * Create and return a persisted admin user.
 *
 * @param  array<string, mixed>  $attrs  Extra attributes to merge.
 */
function adminUser(array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => 'admin'], $attrs));
}

/**
 * Create and return a persisted staff user.
 *
 * @param  array<string, mixed>  $attrs  Extra attributes to merge.
 */
function staffUser(array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => 'staff'], $attrs));
}
