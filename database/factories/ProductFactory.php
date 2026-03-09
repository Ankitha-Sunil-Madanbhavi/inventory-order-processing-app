<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name'           => ucwords($name),
            'sku'            => strtoupper(Str::slug($name, '-')) . '-' . $this->faker->numerify('###'),
            'price'          => $this->faker->randomFloat(2, 1.00, 999.99),
            'stock_quantity' => $this->faker->numberBetween(10, 100),
            'description'    => $this->faker->optional()->sentence(),
            'is_active'      => true,
        ];
    }

    public function withStock(int $qty): static
    {
        return $this->state(['stock_quantity' => $qty]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock_quantity' => 0]);
    }

    public function lowStock(int $max = 4): static
    {
        return $this->state(['stock_quantity' => $this->faker->numberBetween(1, $max)]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}