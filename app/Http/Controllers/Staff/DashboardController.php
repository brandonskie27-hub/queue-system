<?php

namespace App\Http\Controllers\Staff;

use App\Actions\CallNextTicket;
use App\Enums\TicketStatus;
use App\Events\QueueUpdated;
use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the counter's current ticket and the waiting queue for its service.
     */
    public function index(Request $request): Response
    {
        $counter = $this->counter($request);

        $current = Ticket::query()
            ->whereBelongsTo($counter)
            ->today()
            ->where('status', TicketStatus::Serving)
            ->first()
            ?->setRelation('service', $counter->service);

        $waiting = Ticket::query()
            ->whereBelongsTo($counter->service)
            ->today()
            ->where('status', TicketStatus::Waiting)
            ->orderBy('number')
            ->get()
            ->each->setRelation('service', $counter->service);

        return Inertia::render('Dashboard', [
            'counter' => ['id' => $counter->id, 'name' => $counter->name],
            'service' => ['id' => $counter->service_id, 'name' => $counter->service->name],
            'current' => $current ? [
                'id' => $current->id,
                'code' => $current->code,
                'calledAt' => $current->called_at,
            ] : null,
            'waiting' => $waiting->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'takenAt' => $ticket->created_at,
            ]),
        ]);
    }

    /**
     * Call the next waiting ticket to this counter.
     */
    public function callNext(Request $request, CallNextTicket $callNextTicket): RedirectResponse
    {
        $callNextTicket->handle($this->counter($request));

        return to_route('dashboard');
    }

    /**
     * Mark the ticket at this counter as served.
     */
    public function done(Request $request, Ticket $ticket): RedirectResponse
    {
        return $this->finish($request, $ticket, TicketStatus::Done);
    }

    /**
     * Mark the ticket at this counter as skipped (the student didn't show up).
     */
    public function skip(Request $request, Ticket $ticket): RedirectResponse
    {
        return $this->finish($request, $ticket, TicketStatus::Skipped);
    }

    private function finish(Request $request, Ticket $ticket, TicketStatus $status): RedirectResponse
    {
        abort_unless($ticket->counter_id === $this->counter($request)->id, 403);

        // Only a ticket still being served can be finished; a repeated click changes nothing.
        $finished = Ticket::query()
            ->whereKey($ticket->id)
            ->where('status', TicketStatus::Serving)
            ->update(['status' => $status]);

        if ($finished) {
            QueueUpdated::dispatch($ticket->service_id);
        }

        return to_route('dashboard');
    }

    /**
     * The counter this staff member picked, set by the EnsureCounterSelected middleware.
     */
    private function counter(Request $request): Counter
    {
        return $request->attributes->get('counter');
    }
}
