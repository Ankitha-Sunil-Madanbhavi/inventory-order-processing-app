<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_paginated_orders_for_a_user(): void
    {
        Order::factory(5)->forUser(1)->create();
        Order::factory(2)->forUser(2)->create(); // different user, must not appear

        $response = $this->getJson('/api/users/1/orders?per_page=3');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonCount(3, 'data')
                 ->assertJsonPath('meta.total', 5)
                 ->assertJsonPath('meta.last_page', 2);
    }

    public function test_includes_nested_items_and_product_details(): void
    {
        $product = Product::factory()->create(['name' => 'Test Widget']);
        $order   = Order::factory()->forUser(42)->create();

        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 2,
            'unit_price' => 9.99,
        ]);

        $this->getJson('/api/users/42/orders')
             ->assertStatus(200)
             ->assertJsonPath('data.0.items.0.product.name', 'Test Widget')
             ->assertJsonPath('data.0.items.0.line_total', 19.98);
    }

    public function test_returns_orders_newest_first(): void
    {
        $o1 = Order::factory()->forUser(1)->create(['created_at' => now()->subDays(2)]);
        $o2 = Order::factory()->forUser(1)->create(['created_at' => now()->subDay()]);
        $o3 = Order::factory()->forUser(1)->create(['created_at' => now()]);

        $response = $this->getJson('/api/users/1/orders');
        $ids      = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([$o3->id, $o2->id, $o1->id], $ids);
    }

    public function test_filters_by_status(): void
    {
        Order::factory()->forUser(1)->create(['status' => 'confirmed']);
        Order::factory(2)->forUser(1)->cancelled()->create();

        $response = $this->getJson('/api/users/1/orders?status=cancelled');

        $this->assertSame(2, $response->json('meta.total'));
    }

    public function test_returns_empty_list_for_user_with_no_orders(): void
    {
        $this->getJson('/api/users/9999/orders')
             ->assertStatus(200)
             ->assertJsonPath('meta.total', 0)
             ->assertJsonCount(0, 'data');
    }

    public function test_rejects_invalid_status_filter(): void
    {
        $this->getJson('/api/users/1/orders?status=bogus')
             ->assertStatus(422);
    }
}