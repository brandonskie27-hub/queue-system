<?php

namespace App\Http\Controllers;

use App\Actions\IssueTicket;
use App\Enums\TicketStatus;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    /**
     * List the services a student can queue for, with any ticket they already hold.
     */
    public function index(Request $request): Response
    {
        $heldTickets = Ticket::query()
            ->with('service')
            ->where('session_id', $this->queueSessionId($request))
            ->today()
            ->active()
            ->get()
            ->keyBy('service_id');

        $services = Service::query()
            ->where('is_active', true)
            ->withCount(['tickets as waiting_count' => fn ($query) => $query->today()->where('status', TicketStatus::Waiting)])
            ->orderBy('name')
            ->get();

        return Inertia::render('Queue/Index', [
            'services' => $services->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'prefix' => $service->prefix,
                'waitingCount' => $service->waiting_count,
                'heldTicket' => $heldTickets->has($service->id)
                    ? ['id' => $heldTickets[$service->id]->id, 'code' => $heldTickets[$service->id]->code]
                    : null,
            ]),
        ]);
    }

    /**
     * Take a ticket for a service (or get back the one this browser already holds).
     */
    public function store(Request $request, Service $service, IssueTicket $issueTicket): RedirectResponse
    {
        abort_unless($service->is_active, 404);

        $ticket = $issueTicket->handle($service, $this->queueSessionId($request));

        return to_route('tickets.show', $ticket);
    }

    /**
     * Show a student their ticket and where they are in the queue.
     */
    public function show(Request $request, Ticket $ticket): Response
    {
        // Only the browser that took the ticket can view it.
        abort_unless($ticket->session_id === $this->queueSessionId($request), 404);

        $ticket->load(['service', 'counter']);

        $peopleAhead = $ticket->status === TicketStatus::Waiting
            ? Ticket::query()
                ->whereBelongsTo($ticket->service)
                ->where('date', $ticket->date)
                ->where('status', TicketStatus::Waiting)
                ->where('number', '<', $ticket->number)
                ->count()
            : 0;

        $nowServing = Ticket::query()
            ->with('counter')
            ->whereBelongsTo($ticket->service)
            ->today()
            ->where('status', TicketStatus::Serving)
            ->orderByDesc('called_at')
            ->get()
            ->each->setRelation('service', $ticket->service);

        return Inertia::render('Queue/Ticket', [
            'ticket' => [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'status' => $ticket->status,
                'service' => $ticket->service->name,
                'counter' => $ticket->counter?->name,
            ],
            'peopleAhead' => $peopleAhead,
            'nowServing' => $nowServing->map(fn (Ticket $serving) => [
                'code' => $serving->code,
                'counter' => $serving->counter?->name,
            ]),
        ]);
    }

    /**
     * The anonymous student's identity, kept in their session. It's a separate ID rather
     * than the session ID itself, because Laravel changes that whenever a session is regenerated.
     */
    private function queueSessionId(Request $request): string
    {
        return $request->session()->remember('queue_session_id', fn () => (string) Str::uuid());
    }
}
