<?php

namespace Tests\Feature;

use App\Actions\IssueTicket;
use App\Enums\TicketStatus;
use App\Models\Counter;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Service $service;

    private Counter $counter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create();
        $this->service = Service::factory()->create(['name' => 'Registrar', 'prefix' => 'A']);
        $this->counter = Counter::factory()->for($this->service)->create(['name' => 'Window 1']);
    }

    /**
     * Act as the staff user with a counter already chosen for this session.
     */
    private function atCounter(?Counter $counter = null): static
    {
        return $this->actingAs($this->staff)
            ->withSession(['staff_counter_id' => ($counter ?? $this->counter)->id]);
    }

    private function issue(string $sessionId): Ticket
    {
        return (new IssueTicket)->handle($this->service, $sessionId);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_staff_must_choose_a_counter_first(): void
    {
        $this->actingAs($this->staff)
            ->get(route('dashboard'))
            ->assertRedirect(route('counter.edit'));
    }

    public function test_counter_page_lists_active_counters(): void
    {
        Counter::factory()->for($this->service)->create(['is_active' => false]);

        $this->actingAs($this->staff)
            ->get(route('counter.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/SelectCounter')
                ->has('services', 1)
                ->has('services.0.counters', 1)
                ->where('services.0.counters.0.name', 'Window 1')
            );
    }

    public function test_staff_can_choose_a_counter(): void
    {
        $this->actingAs($this->staff)
            ->put(route('counter.update'), ['counter_id' => $this->counter->id])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('staff_counter_id', $this->counter->id);
    }

    public function test_staff_cannot_choose_an_inactive_counter(): void
    {
        $closed = Counter::factory()->for($this->service)->create(['is_active' => false]);

        $this->actingAs($this->staff)
            ->put(route('counter.update'), ['counter_id' => $closed->id])
            ->assertSessionHasErrors('counter_id');
    }

    public function test_staff_are_asked_again_if_their_counter_is_deactivated(): void
    {
        $this->counter->update(['is_active' => false]);

        $this->atCounter()
            ->get(route('dashboard'))
            ->assertRedirect(route('counter.edit'))
            ->assertSessionMissing('staff_counter_id');
    }

    public function test_dashboard_shows_the_waiting_queue(): void
    {
        $this->issue('session-a');
        $this->issue('session-b');

        $this->atCounter()
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('counter.name', 'Window 1')
                ->where('current', null)
                ->has('waiting', 2)
                ->where('waiting.0.code', 'A-001')
                ->where('waiting.1.code', 'A-002')
            );
    }

    public function test_call_next_brings_the_oldest_ticket_to_this_counter(): void
    {
        $ticket = $this->issue('session-a');

        $this->atCounter()
            ->post(route('dashboard.call-next'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(TicketStatus::Serving, $ticket->refresh()->status);
        $this->assertSame($this->counter->id, $ticket->counter_id);

        $this->atCounter()
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('current.code', 'A-001')
                ->has('waiting', 0)
            );
    }

    public function test_staff_can_mark_their_ticket_done(): void
    {
        $this->issue('session-a');
        $this->atCounter()->post(route('dashboard.call-next'));
        $ticket = Ticket::sole();

        $this->atCounter()
            ->post(route('dashboard.tickets.done', $ticket))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(TicketStatus::Done, $ticket->refresh()->status);
    }

    public function test_staff_can_skip_a_no_show(): void
    {
        $this->issue('session-a');
        $this->atCounter()->post(route('dashboard.call-next'));
        $ticket = Ticket::sole();

        $this->atCounter()->post(route('dashboard.tickets.skip', $ticket));

        $this->assertSame(TicketStatus::Skipped, $ticket->refresh()->status);
    }

    public function test_finishing_a_ticket_twice_does_not_change_it_again(): void
    {
        $this->issue('session-a');
        $this->atCounter()->post(route('dashboard.call-next'));
        $ticket = Ticket::sole();

        $this->atCounter()->post(route('dashboard.tickets.done', $ticket));
        $this->atCounter()
            ->post(route('dashboard.tickets.skip', $ticket))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(TicketStatus::Done, $ticket->refresh()->status);
    }

    public function test_staff_cannot_finish_a_ticket_at_another_counter(): void
    {
        $otherCounter = Counter::factory()->for($this->service)->create();
        $this->issue('session-a');
        $this->atCounter($otherCounter)->post(route('dashboard.call-next'));
        $ticket = Ticket::sole();

        $this->atCounter()
            ->post(route('dashboard.tickets.done', $ticket))
            ->assertForbidden();

        $this->assertSame(TicketStatus::Serving, $ticket->refresh()->status);
    }
}
