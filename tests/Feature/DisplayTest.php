<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Counter;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_is_public_and_shows_an_empty_board(): void
    {
        Service::factory()->create(['name' => 'Registrar']);

        $this->get(route('display'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Display')
                ->has('services', 1)
                ->where('services.0.name', 'Registrar')
                ->where('services.0.serving', [])
                ->where('services.0.nextUp', [])
                ->where('lastCalled', null)
            );
    }

    public function test_display_shows_who_is_being_served_and_who_is_next(): void
    {
        $service = Service::factory()->create(['prefix' => 'A']);
        $window1 = Counter::factory()->for($service)->create(['name' => 'Window 1']);
        $window2 = Counter::factory()->for($service)->create(['name' => 'Window 2']);
        Ticket::factory()->for($service)->serving($window2)->create(['number' => 1]);
        Ticket::factory()->for($service)->serving($window1)->create(['number' => 2]);
        Ticket::factory()->for($service)->create(['number' => 3, 'status' => TicketStatus::Done]);
        Ticket::factory()->for($service)->create(['number' => 5]);
        Ticket::factory()->for($service)->create(['number' => 4]);

        $this->get(route('display'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('services.0.serving', [
                    ['code' => 'A-002', 'counter' => 'Window 1'],
                    ['code' => 'A-001', 'counter' => 'Window 2'],
                ])
                ->where('services.0.nextUp', ['A-004', 'A-005'])
                ->where('services.0.waitingCount', 2)
            );
    }

    public function test_next_up_is_limited_to_five_numbers(): void
    {
        $service = Service::factory()->create();
        foreach (range(1, 7) as $number) {
            Ticket::factory()->for($service)->create(['number' => $number]);
        }

        $this->get(route('display'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('services.0.nextUp', 5)
                ->where('services.0.waitingCount', 7)
            );
    }

    public function test_banner_shows_the_most_recently_called_ticket(): void
    {
        $registrar = Service::factory()->create(['name' => 'Registrar', 'prefix' => 'A']);
        $cashier = Service::factory()->create(['name' => 'Cashier', 'prefix' => 'B']);
        Ticket::factory()->for($registrar)->serving(Counter::factory()->for($registrar)->create())
            ->create(['number' => 1, 'called_at' => now()->subMinutes(5)]);
        Ticket::factory()->for($cashier)->serving(Counter::factory()->for($cashier)->create(['name' => 'Window 3']))
            ->create(['number' => 1, 'called_at' => now()->subMinute()]);

        $this->get(route('display'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('lastCalled', ['code' => 'B-001', 'counter' => 'Window 3', 'service' => 'Cashier'])
            );
    }

    public function test_display_ignores_inactive_services_and_other_days(): void
    {
        $service = Service::factory()->create();
        Service::factory()->inactive()->create();
        Ticket::factory()->for($service)->create(['date' => today()->subDay()->toDateString()]);

        $this->get(route('display'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('services', 1)
                ->where('services.0.waitingCount', 0)
            );
    }
}
