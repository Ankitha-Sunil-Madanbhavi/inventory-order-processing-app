<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_places_an_order_and_decrements_stock(): void
    {
        $product = Product::factory()->withStock(10)->create(['price' => 25.00]);

        $this->postJson('/api/orders', [
            'user_id' => 1,
            'items'   => [['product_id' => $product->id, 'quantity' => 3]],
        ])
        ->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.total_amount', 75)
        ->assertJsonPath('data.items.0.line_total', 75);

        $this->assertSame(7, $product->fresh()->stock_quantity);
    }

    public function test_rejects_order_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->withStock(2)->create();

        $this->postJson('/api/orders', [
            'user_id' => 1,
            'items'   => [['product_id' => $product->id, 'quantity' => 5]],
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_all_or_nothing_partial_stock_not_reserved(): void
    {
        $p1 = Product::factory()->withStock(10)->create();
        $p2 = Product::factory()->withStock(1)->create();

        $this->postJson('/api/orders', [
            'user_id' => 1,
            'items'   => [
                ['product_id' => $p1->id, 'quantity' => 3],
                ['product_id' => $p2->id, 'quantity' => 5],
            ],
        ])->assertStatus(422);

        $this->assertSame(10, $p1->fresh()->stock_quantity);
        $this->assertSame(1, $p2->fresh()->stock_quantity);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_deduplicates_repeated_product_ids(): void
    {
        $product = Product::factory()->withStock(10)->create(['price' => 10.00]);

        $this->postJson('/api/orders', [
            'user_id' => 1,
            'items'   => [
                ['product_id' => $product->id, 'quantity' => 2],
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.total_amount', 50);

        $this->assertSame(5, $product->fresh()->stock_quantity);
    }

    public function test_stock_never_goes_negative(): void
    {
        $product = Product::factory()->withStock(3)->create();

        foreach (range(1, 3) as $userId) {
            $this->postJson('/api/orders', [
                'user_id' => $userId,
                'items'   => [['product_id' => $product->id, 'quantity' => 1]],
            ])->assertStatus(201);
        }

        $this->postJson('/api/orders', [
            'user_id' => 99,
            'items'   => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        $this->assertSame(0, $product->fresh()->stock_quantity);
    }

    public function test_fails_validation_when_items_array_is_empty(): void
    {
        $this->postJson('/api/orders', ['user_id' => 1, 'items' => []])
             ->assertStatus(422);
    }

    public function test_rejects_inactive_products(): void
    {
        $product = Product::factory()->inactive()->withStock(10)->create();

        $this->postJson('/api/orders', [
            'user_id' => 1,
            'items'   => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_fails_validation_when_user_id_is_missing(): void
    {
        $product = Product::factory()->create();

        $this->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }
}