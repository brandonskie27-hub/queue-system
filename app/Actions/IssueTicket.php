<?php

namespace App\Actions;

use App\Enums\TicketStatus;
use App\Events\QueueUpdated;
use App\Models\QueueCounter;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class IssueTicket
{
    /**
     * Give a browser session the next ticket for a service, or return the active
     * ticket it already holds for that service today.
     */
    public function handle(Service $service, string $sessionId): Ticket
    {
        $date = today()->toDateString();

        // Make sure today's sequence row exists before we lock it. This runs outside the
        // transaction on purpose: INSERT IGNORE takes a shared lock on an existing row, and
        // two transactions that each hold a shared lock and then ask for FOR UPDATE deadlock.
        QueueCounter::insertOrIgnore([
            'service_id' => $service->id,
            'date' => $date,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::transaction(function () use ($service, $sessionId, $date) {
            // Every request for this service and day waits here until the previous one commits,
            // so no two requests can ever read the same last_number.
            $sequence = QueueCounter::query()
                ->where('service_id', $service->id)
                ->where('date', $date)
                ->lockForUpdate()
                ->firstOrFail();

            // Checked while holding the lock, so a double-tap can't slip two tickets through.
            $existing = Ticket::query()
                ->whereBelongsTo($service)
                ->where('session_id', $sessionId)
                ->today()
                ->active()
                ->first();

            if ($existing) {
                return $existing;
            }

            $sequence->increment('last_number');

            $ticket = Ticket::create([
                'service_id' => $service->id,
                'date' => $date,
                'number' => $sequence->last_number,
                'session_id' => $sessionId,
                'status' => TicketStatus::Waiting,
            ]);

            QueueUpdated::dispatch($service->id);

            return $ticket;
        }, attempts: 3);
    }
}
