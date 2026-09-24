<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A counter called a ticket. Sent on the service's public queue channel, so student
 * ticket pages, staff dashboards and the display screen can all update.
 */
class TicketCalled implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public int $serviceId;

    public string $code;

    public string $counter;

    public function __construct(Ticket $ticket)
    {
        $this->serviceId = $ticket->service_id;
        $this->code = $ticket->code;
        $this->counter = $ticket->counter->name;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel("queue.{$this->serviceId}")];
    }

    public function broadcastAs(): string
    {
        return 'ticket.called';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return ['code' => $this->code, 'counter' => $this->counter];
    }
}
