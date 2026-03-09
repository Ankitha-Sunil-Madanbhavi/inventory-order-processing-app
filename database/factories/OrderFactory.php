<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'      => $this->faker->numberBetween(1, 100),
            'status'       => 'pending',
            'total_amount' => $this->faker->randomFloat(2, 5.00, 500.00),
            'notes'        => $this->faker->optional()->sentence(),
        ];
    }

    public function confirmed(): static  { return $this->state(['status' => 'confirmed']); }
    public function cancelled(): static  { return $this->state(['status' => 'cancelled']); }
    public function dispatched(): static { return $this->state(['status' => 'dispatched']); }
    public function forUser(int $id): static { return $this->state(['user_id' => $id]); }
}