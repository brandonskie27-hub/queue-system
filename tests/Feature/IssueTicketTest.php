<?php

namespace Tests\Feature;

use App\Actions\IssueTicket;
use App\Enums\TicketStatus;
use App\Events\QueueUpdated;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class IssueTicketTest extends TestCase
{
    use RefreshDatabase;

    private IssueTicket $issueTicket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->issueTicket = new IssueTicket;
    }

    public function test_first_ticket_of_the_day_is_number_one(): void
    {
        $service = Service::factory()->create();

        $ticket = $this->issueTicket->handle($service, 'session-a');

        $this->assertSame(1, $ticket->number);
        $this->assertSame(TicketStatus::Waiting, $ticket->status);
        $this->assertSame(today()->toDateString(), $ticket->date);
    }

    public function test_each_new_session_gets_the_next_number(): void
    {
        $service = Service::factory()->create();

        $numbers = collect(['session-a', 'session-b', 'session-c'])
            ->map(fn (string $sessionId) => $this->issueTicket->handle($service, $sessionId)->number);

        $this->assertSame([1, 2, 3], $numbers->all());
    }

    public function test_a_session_gets_its_active_ticket_back_instead_of_a_new_one(): void
    {
        $service = Service::factory()->create();

        $first = $this->issueTicket->handle($service, 'session-a');
        $second = $this->issueTicket->handle($service, 'session-a');

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Ticket::count());
    }

    public function test_a_session_being_served_still_gets_its_ticket_back(): void
    {
        $service = Service::factory()->create();
        $ticket = $this->issueTicket->handle($service, 'session-a');
        $ticket->update(['status' => TicketStatus::Serving]);

        $again = $this->issueTicket->handle($service, 'session-a');

        $this->assertTrue($ticket->is($again));
    }

    public function test_a_session_can_take_a_new_ticket_once_its_last_one_is_finished(): void
    {
        $service = Service::factory()->create();
        $first = $this->issueTicket->handle($service, 'session-a');
        $first->update(['status' => TicketStatus::Done]);

        $second = $this->issueTicket->handle($service, 'session-a');

        $this->assertFalse($first->is($second));
        $this->assertSame(2, $second->number);
    }

    public function test_one_session_can_hold_tickets_for_different_services(): void
    {
        [$registrar, $cashier] = Service::factory()->count(2)->create();

        $registrarTicket = $this->issueTicket->handle($registrar, 'session-a');
        $cashierTicket = $this->issueTicket->handle($cashier, 'session-a');

        $this->assertFalse($registrarTicket->is($cashierTicket));
    }

    public function test_each_service_has_its_own_numbering(): void
    {
        [$registrar, $cashier] = Service::factory()->count(2)->create();

        $this->issueTicket->handle($registrar, 'session-a');
        $this->issueTicket->handle($registrar, 'session-b');
        $cashierTicket = $this->issueTicket->handle($cashier, 'session-c');

        $this->assertSame(1, $cashierTicket->number);
    }

    public function test_numbering_restarts_the_next_day(): void
    {
        $service = Service::factory()->create();
        $this->issueTicket->handle($service, 'session-a');
        $this->issueTicket->handle($service, 'session-b');

        $this->travel(1)->day();
        $ticket = $this->issueTicket->handle($service, 'session-c');

        $this->assertSame(1, $ticket->number);
    }

    public function test_a_new_ticket_announces_a_queue_update(): void
    {
        Event::fake();
        $service = Service::factory()->create();

        $this->issueTicket->handle($service, 'session-a');

        Event::assertDispatched(QueueUpdated::class, fn (QueueUpdated $event) => $event->serviceId === $service->id);
    }

    public function test_getting_an_existing_ticket_back_announces_nothing(): void
    {
        $service = Service::factory()->create();
        $this->issueTicket->handle($service, 'session-a');
        Event::fake();

        $this->issueTicket->handle($service, 'session-a');

        Event::assertNotDispatched(QueueUpdated::class);
    }

    public function test_an_unfinished_ticket_from_yesterday_does_not_block_a_new_one(): void
    {
        $service = Service::factory()->create();
        $yesterday = $this->issueTicket->handle($service, 'session-a');

        $this->travel(1)->day();
        $today = $this->issueTicket->handle($service, 'session-a');

        $this->assertFalse($yesterday->is($today));
        $this->assertSame(1, $today->number);
    }
}
