<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Counter;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
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
            'number' => fake()->unique()->numberBetween(1, 999),
            'session_id' => (string) Str::uuid(),
            'status' => TicketStatus::Waiting,
        ];
    }

    /**
     * Indicate that the ticket has been called to a counter.
     */
    public function serving(?Counter $counter = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Serving,
            'counter_id' => $counter ?? Counter::factory()->state(['service_id' => $attributes['service_id']]),
            'called_at' => now(),
        ]);
    }

    /**
     * Indicate that the ticket has been served.
     */
    public function done(): static
    {
        return $this->serving()->state(fn (array $attributes) => [
            'status' => TicketStatus::Done,
        ]);
    }
}
