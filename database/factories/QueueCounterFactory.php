<?php

namespace Database\Factories;

use App\Models\QueueCounter;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueueCounter>
 */
class QueueCounterFactory extends Factory
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
            'date' => today()->toDateString(),
            'last_number' => 0,
        ];
    }
}
