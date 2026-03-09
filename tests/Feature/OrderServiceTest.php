<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderCancellationException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_order_and_decrements_stock(): void
    {
        $product = Product::factory()->withStock(10)->create(['price' => '20.00']);

        $order = (new OrderService())->placeOrder(1, [
            ['product_id' => $product->id, 'quantity' => 4],
        ]);

        $this->assertSame(80.0, (float) $order->total_amount);
        $this->assertSame(6, $product->fresh()->stock_quantity);
    }

    public function test_throws_insufficient_stock_exception_without_changing_stock(): void
    {
        $product = Product::factory()->withStock(2)->create();

        $this->expectException(InsufficientStockException::class);

        (new OrderService())->placeOrder(1, [
            ['product_id' => $product->id, 'quantity' => 5],
        ]);

        $this->assertSame(2, $product->fresh()->stock_quantity);
    }

    public function test_reports_all_failing_products_in_exception(): void
    {
        $p1 = Product::factory()->withStock(1)->create();
        $p2 = Product::factory()->withStock(1)->create();

        try {
            (new OrderService())->placeOrder(1, [
                ['product_id' => $p1->id, 'quantity' => 5],
                ['product_id' => $p2->id, 'quantity' => 5],
            ]);
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException $e) {
            $this->assertCount(2, $e->getStockErrors());
        }
    }

    public function test_throws_cancellation_exception_for_non_cancellable_statuses(): void
    {
        foreach (['cancelled', 'dispatched', 'refunded'] as $status) {
            $order = Order::factory()->create(['status' => $status]);

            $this->expectException(OrderCancellationException::class);
            (new OrderService())->cancelOrder($order);
        }
    }

    public function test_restores_stock_on_cancellation(): void
    {
        $product = Product::factory()->withStock(5)->create();
        $order   = Order::factory()->create(['status' => 'pending']);

        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 3,
            'unit_price' => $product->price,
        ]);

        (new OrderService())->cancelOrder($order);

        $this->assertSame(8, $product->fresh()->stock_quantity);
    }
}