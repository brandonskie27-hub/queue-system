<?php

namespace App\Actions;

use App\Enums\TicketStatus;
use App\Events\TicketCalled;
use App\Models\Counter;
use App\Models\QueueCounter;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class CallNextTicket
{
    /**
     * Call the oldest waiting ticket for the counter's service to that counter.
     *
     * Returns the ticket now being served at the counter, or null if nobody is waiting.
     * If the counter is already serving someone, that ticket is returned unchanged, so a
     * double-click can't pull two people to one window.
     */
    public function handle(Counter $counter): ?Ticket
    {
        return DB::transaction(function () use ($counter) {
            // Lock the same per-service, per-day row that IssueTicket locks. While we hold it, no
            // other counter can call a ticket for this service, so two counters never get the same
            // one. (The usual tool, SKIP LOCKED, needs MariaDB 10.6+ and XAMPP ships 10.4.)
            $sequence = QueueCounter::query()
                ->where('service_id', $counter->service_id)
                ->where('date', today()->toDateString())
                ->lockForUpdate()
                ->first();

            // No sequence row means no tickets have been issued today.
            if (! $sequence) {
                return null;
            }

            $current = Ticket::query()
                ->whereBelongsTo($counter)
                ->today()
                ->where('status', TicketStatus::Serving)
                ->first();

            if ($current) {
                return $current;
            }

            $next = Ticket::query()
                ->where('service_id', $counter->service_id)
                ->today()
                ->where('status', TicketStatus::Waiting)
                ->orderBy('number')
                ->first();

            if (! $next) {
                return null;
            }

            $next->update([
                'status' => TicketStatus::Serving,
                'counter_id' => $counter->id,
                'called_at' => now(),
            ]);

            TicketCalled::dispatch($next->setRelation('counter', $counter));

            return $next;
        }, attempts: 3);
    }
}
