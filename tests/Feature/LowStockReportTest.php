<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_products_below_default_threshold(): void
    {
        Product::factory()->withStock(2)->create();
        Product::factory()->withStock(4)->create();
        Product::factory()->withStock(5)->create(); // at threshold, NOT below
        Product::factory()->withStock(20)->create();

        $this->getJson('/api/products/low-stock')
             ->assertStatus(200)
             ->assertJsonPath('threshold', 5)
             ->assertJsonPath('count', 2);
    }

    public function test_respects_custom_threshold_via_query_param(): void
    {
        Product::factory()->withStock(3)->create();
        Product::factory()->withStock(7)->create();
        Product::factory()->withStock(15)->create();

        $this->getJson('/api/products/low-stock?threshold=10')
             ->assertStatus(200)
             ->assertJsonPath('threshold', 10)
             ->assertJsonPath('count', 2);
    }

    public function test_excludes_inactive_products(): void
    {
        Product::factory()->withStock(1)->inactive()->create();
        Product::factory()->withStock(2)->create();

        $this->getJson('/api/products/low-stock')
             ->assertJsonPath('count', 1);
    }

    public function test_returns_empty_when_all_products_are_well_stocked(): void
    {
        Product::factory(3)->withStock(100)->create();

        $this->getJson('/api/products/low-stock')
             ->assertStatus(200)
             ->assertJsonPath('count', 0)
             ->assertJsonCount(0, 'data');
    }

    public function test_rejects_negative_threshold(): void
    {
        $this->getJson('/api/products/low-stock?threshold=-1')
             ->assertStatus(422);
    }
}