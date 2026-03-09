<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListOrdersRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->placeOrder(
            userId: $request->integer('user_id'),
            items:  $request->input('items'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data'    => new OrderResource($order),
        ], 201);
    }

    public function cancel(Order $order): JsonResponse
    {
        $order = $this->orderService->cancelOrder($order);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
            'data'    => new OrderResource($order),
        ]);
    }

    public function index(ListOrdersRequest $request, int $userId): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->forUser($userId)
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status'))
            )
            ->with([
                'items',
                'items.product' => fn ($q) => $q->withTrashed(),
            ])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return OrderResource::collection($orders)
            ->additional(['success' => true]);
    }
}