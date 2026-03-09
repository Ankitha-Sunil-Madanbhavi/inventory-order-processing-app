<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderCancellationException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function placeOrder(int $userId, array $items): Order
    {
        return DB::transaction(function () use ($userId, $items) {

            // Aggregate quantities in case the same product_id appears twice
            $quantityMap = [];
            foreach ($items as $item) {
                $id = $item['product_id'];
                $quantityMap[$id] = ($quantityMap[$id] ?? 0) + $item['quantity'];
            }

            // Sort by id before locking — prevents circular-wait deadlocks
            // between concurrent transactions locking the same rows
            $productIds = collect($quantityMap)->keys()->sort()->values();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->where('is_active', true)
                ->lockForUpdate()   // SELECT ... FOR UPDATE
                ->get()
                ->keyBy('id');

            // Validate everything before touching any stock
            $stockErrors = [];

            foreach ($quantityMap as $id => $qty) {
                if (! $products->has($id)) {
                    $stockErrors[] = [
                        'product_id' => $id,
                        'sku'        => null,
                        'name'       => null,
                        'requested'  => $qty,
                        'available'  => 0,
                        'error'      => 'Product not found or inactive.',
                    ];
                    continue;
                }

                $product = $products->get($id);

                if (! $product->hasStock($qty)) {
                    $stockErrors[] = [
                        'product_id' => $id,
                        'sku'        => $product->sku,
                        'name'       => $product->name,
                        'requested'  => $qty,
                        'available'  => $product->stock_quantity,
                        'error'      => 'Insufficient stock.',
                    ];
                }
            }

            if (! empty($stockErrors)) {
                throw new InsufficientStockException($stockErrors);
            }

            // All items valid — create order and decrement stock
            $order = Order::create([
                'user_id'      => $userId,
                'status'       => 'pending',
                'total_amount' => 0,
            ]);

            $total = 0.0;

            foreach ($quantityMap as $productId => $qty) {
                $product = $products->get($productId);

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $productId,
                    'quantity'   => $qty,
                    'unit_price' => $product->price,
                ]);

                $total += (float) $product->price * $qty;

                // decrement() uses a DB expression — safe even if model is stale
                $product->decrement('stock_quantity', $qty);
            }

            $order->update(['total_amount' => round($total, 2)]);

            return $order->load(['items.product']);
        });
    }

    /**
     * Cancel an order and restore stock atomically.
     */
    public function cancelOrder(Order $order): Order
    {
        if (! $order->isCancellable()) {
            throw new OrderCancellationException($order->status);
        }

        return DB::transaction(function () use ($order) {

            $order->load('items');

            // Lock in deterministic order to prevent deadlocks
            $productIds = $order->items
                ->pluck('product_id')
                ->unique()->sort()->values();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($order->items as $item) {
                if ($products->has($item->product_id)) {
                    $products->get($item->product_id)
                             ->increment('stock_quantity', $item->quantity);
                }
            }

            $order->update(['status' => 'cancelled']);

            return $order->load(['items.product']);
        });
    }
}