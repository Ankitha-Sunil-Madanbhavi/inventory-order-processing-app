<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'price',
        'stock_quantity',
        'description',
        'is_active',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_active'      => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query, ?int $threshold = null)
    {
        $threshold ??= config('inventory.low_stock_threshold', 5);
        return $query->where('stock_quantity', '<', $threshold);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function hasStock(int $quantity): bool
    {
        return $this->stock_quantity >= $quantity;
    }
}