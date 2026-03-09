<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancels_pending_order_and_restores_stock(): void
    {
        $product = Product::factory()->withStock(5)->create();
        $order   = Order::factory()->create(['status' => 'pending']);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 3,
            'unit_price' => $product->price,
        ]);

        $this->postJson("/api/orders/{$order->id}/cancel")
             ->assertStatus(200)
             ->assertJsonPath('success', true)
             ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(8, $product->fresh()->stock_quantity);
    }

    public function test_cancels_confirmed_order(): void
    {
        $order = Order::factory()->confirmed()->create();

        $this->postJson("/api/orders/{$order->id}/cancel")
             ->assertStatus(200)
             ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_rejects_cancellation_of_already_cancelled_order(): void
    {
        $order = Order::factory()->cancelled()->create();

        $this->postJson("/api/orders/{$order->id}/cancel")
             ->assertStatus(422)
             ->assertJsonPath('success', false);
    }

    public function test_rejects_cancellation_of_dispatched_order(): void
    {
        $order = Order::factory()->dispatched()->create();

        $this->postJson("/api/orders/{$order->id}/cancel")
             ->assertStatus(422);
    }

    public function test_restores_stock_for_all_items_in_multi_item_order(): void
    {
        $p1    = Product::factory()->withStock(5)->create();
        $p2    = Product::factory()->withStock(5)->create();
        $order = Order::factory()->create(['status' => 'pending']);

        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $p1->id,
            'quantity'   => 2,
            'unit_price' => $p1->price,
        ]);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $p2->id,
            'quantity'   => 3,
            'unit_price' => $p2->price,
        ]);

        $this->postJson("/api/orders/{$order->id}/cancel")
             ->assertStatus(200);

        $this->assertSame(7, $p1->fresh()->stock_quantity);
        $this->assertSame(8, $p2->fresh()->stock_quantity);
    }

    public function test_returns_404_for_nonexistent_order(): void
    {
        $this->postJson('/api/orders/99999/cancel')
             ->assertStatus(404);
    }
}