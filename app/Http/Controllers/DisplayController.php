<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Service;
use App\Models\Ticket;
use Inertia\Inertia;
use Inertia\Response;

class DisplayController extends Controller
{
    /**
     * How many upcoming numbers to show under each service.
     */
    private const NEXT_UP_COUNT = 5;

    /**
     * The public "Now Serving" board for the waiting-area screen.
     */
    public function __invoke(): Response
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $tickets = Ticket::query()
            ->with('counter')
            ->whereIn('service_id', $services->keys())
            ->today()
            ->active()
            ->orderBy('number')
            ->get()
            ->each(fn (Ticket $ticket) => $ticket->setRelation('service', $services[$ticket->service_id]));

        $lastCalled = $tickets
            ->where('status', TicketStatus::Serving)
            ->sortByDesc('called_at')
            ->first();

        return Inertia::render('Display', [
            'services' => $services->values()->map(function (Service $service) use ($tickets) {
                $forService = $tickets->where('service_id', $service->id);
                $waiting = $forService->where('status', TicketStatus::Waiting);

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'serving' => $forService
                        ->where('status', TicketStatus::Serving)
                        ->sortBy(fn (Ticket $ticket) => $ticket->counter->name)
                        ->values()
                        ->map(fn (Ticket $ticket) => ['code' => $ticket->code, 'counter' => $ticket->counter->name]),
                    'nextUp' => $waiting->take(self::NEXT_UP_COUNT)->values()->map->code,
                    'waitingCount' => $waiting->count(),
                ];
            }),
            'lastCalled' => $lastCalled ? [
                'code' => $lastCalled->code,
                'counter' => $lastCalled->counter->name,
                'service' => $lastCalled->service->name,
            ] : null,
        ]);
    }
}
