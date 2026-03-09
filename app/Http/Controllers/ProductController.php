<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController
{
    
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::active()
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return ProductResource::collection($products)
            ->additional(['success' => true]);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new ProductResource($product),
        ]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $request->validate([
            'threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        $threshold = $request->filled('threshold')
            ? $request->integer('threshold')
            : config('inventory.low_stock_threshold', 5);

        $products = Product::active()
            ->lowStock($threshold)
            ->orderBy('stock_quantity')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success'   => true,
            'threshold' => $threshold,
            'count'     => $products->count(),
            'data'      => ProductResource::collection($products),
        ]);
    }

    public function store(Request $request): JsonResponse
{
    $request->validate([
        'name'           => ['required', 'string', 'max:255'],
        'sku'            => ['required', 'string', 'unique:products,sku'],
        'price'          => ['required', 'numeric', 'min:0'],
        'stock_quantity' => ['required', 'integer', 'min:0'],
        'description'    => ['nullable', 'string'],
        'is_active'      => ['nullable', 'boolean'],
    ]);

    $product = Product::create($request->only([
        'name', 'sku', 'price', 'stock_quantity', 'description', 'is_active'
    ]));

    return response()->json([
        'success' => true,
        'message' => 'Product created successfully.',
        'data'    => new ProductResource($product),
    ], 201);
}
}