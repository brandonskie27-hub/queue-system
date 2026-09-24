<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Counter;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_page_lists_only_active_services(): void
    {
        $open = Service::factory()->create(['name' => 'Registrar']);
        Service::factory()->inactive()->create();
        Ticket::factory()->count(2)->for($open)->create();

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Queue/Index')
                ->has('services', 1)
                ->where('services.0.name', 'Registrar')
                ->where('services.0.waitingCount', 2)
                ->where('services.0.heldTicket', null)
            );
    }

    public function test_student_can_take_a_ticket_and_see_it(): void
    {
        $service = Service::factory()->create(['prefix' => 'A']);

        $response = $this->post(route('tickets.store', $service));

        $ticket = Ticket::sole();
        $response->assertRedirect(route('tickets.show', $ticket));

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Queue/Ticket')
                ->where('ticket.code', 'A-001')
                ->where('ticket.status', 'waiting')
                ->where('peopleAhead', 0)
            );
    }

    public function test_taking_a_ticket_twice_returns_the_same_ticket(): void
    {
        $service = Service::factory()->create();

        $this->post(route('tickets.store', $service));
        $this->post(route('tickets.store', $service));

        $this->assertSame(1, Ticket::count());
    }

    public function test_queue_page_shows_the_ticket_a_student_already_holds(): void
    {
        $service = Service::factory()->create(['prefix' => 'A']);
        $this->post(route('tickets.store', $service));

        $this->get(route('tickets.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('services.0.heldTicket.code', 'A-001')
            );
    }

    public function test_inactive_service_does_not_issue_tickets(): void
    {
        $service = Service::factory()->inactive()->create();

        $this->post(route('tickets.store', $service))->assertNotFound();

        $this->assertSame(0, Ticket::count());
    }

    public function test_student_cannot_view_someone_elses_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->get(route('tickets.show', $ticket))->assertNotFound();
    }

    public function test_ticket_page_shows_people_ahead_and_who_is_being_served(): void
    {
        $service = Service::factory()->create(['prefix' => 'A']);
        $counter = Counter::factory()->for($service)->create(['name' => 'Window 1']);
        Ticket::factory()->for($service)->serving($counter)->create(['number' => 1]);
        Ticket::factory()->for($service)->create(['number' => 2]);
        Ticket::factory()->for($service)->create(['number' => 3, 'status' => TicketStatus::Skipped]);
        $mine = Ticket::factory()->for($service)->create(['number' => 4, 'session_id' => 'my-session']);

        $this->withSession(['queue_session_id' => 'my-session'])
            ->get(route('tickets.show', $mine))
            ->assertInertia(fn (Assert $page) => $page
                ->where('ticket.code', 'A-004')
                ->where('peopleAhead', 1)
                ->has('nowServing', 1)
                ->where('nowServing.0.code', 'A-001')
                ->where('nowServing.0.counter', 'Window 1')
            );
    }
}
