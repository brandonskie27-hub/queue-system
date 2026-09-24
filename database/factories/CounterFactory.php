<?php

namespace Database\Factories;

use App\Models\Counter;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Counter>
 */
class CounterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'name' => 'Window '.fake()->unique()->numberBetween(1, 999),
            'is_active' => true,
        ];
    }
}
