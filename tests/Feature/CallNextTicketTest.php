<?php

namespace Tests\Feature;

use App\Actions\CallNextTicket;
use App\Actions\IssueTicket;
use App\Enums\TicketStatus;
use App\Models\Counter;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallNextTicketTest extends TestCase
{
    use RefreshDatabase;

    private CallNextTicket $callNextTicket;

    private Service $service;

    private Counter $counter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->callNextTicket = new CallNextTicket;
        $this->service = Service::factory()->create();
        $this->counter = Counter::factory()->for($this->service)->create();
    }

    private function issue(Service $service, string $sessionId): Ticket
    {
        return (new IssueTicket)->handle($service, $sessionId);
    }

    public function test_calls_the_oldest_waiting_ticket_to_the_counter(): void
    {
        $first = $this->issue($this->service, 'session-a');
        $this->issue($this->service, 'session-b');

        $called = $this->callNextTicket->handle($this->counter);

        $this->assertTrue($called->is($first));
        $first->refresh();
        $this->assertSame(TicketStatus::Serving, $first->status);
        $this->assertSame($this->counter->id, $first->counter_id);
        $this->assertNotNull($first->called_at);
    }

    public function test_returns_null_when_no_tickets_were_issued_today(): void
    {
        $this->assertNull($this->callNextTicket->handle($this->counter));
    }

    public function test_returns_null_when_everyone_has_been_served(): void
    {
        $ticket = $this->issue($this->service, 'session-a');
        $ticket->update(['status' => TicketStatus::Done]);

        $this->assertNull($this->callNextTicket->handle($this->counter));
    }

    public function test_does_not_call_a_second_ticket_while_the_counter_is_still_serving(): void
    {
        $first = $this->issue($this->service, 'session-a');
        $second = $this->issue($this->service, 'session-b');

        $this->callNextTicket->handle($this->counter);
        $calledAgain = $this->callNextTicket->handle($this->counter);

        $this->assertTrue($calledAgain->is($first));
        $this->assertSame(TicketStatus::Waiting, $second->refresh()->status);
    }

    public function test_two_counters_get_different_tickets(): void
    {
        $otherCounter = Counter::factory()->for($this->service)->create();
        $this->issue($this->service, 'session-a');
        $this->issue($this->service, 'session-b');

        $forFirstCounter = $this->callNextTicket->handle($this->counter);
        $forOtherCounter = $this->callNextTicket->handle($otherCounter);

        $this->assertFalse($forFirstCounter->is($forOtherCounter));
    }

    public function test_only_calls_tickets_for_the_counters_own_service(): void
    {
        $this->issue(Service::factory()->create(), 'session-a');

        $this->assertNull($this->callNextTicket->handle($this->counter));
    }

    public function test_does_not_call_tickets_left_over_from_yesterday(): void
    {
        $this->issue($this->service, 'session-a');

        $this->travel(1)->day();

        $this->assertNull($this->callNextTicket->handle($this->counter));
    }
}
