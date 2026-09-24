<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_id', 'date', 'number', 'session_id', 'status', 'counter_id', 'called_at'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'status' => TicketStatus::class,
            'called_at' => 'datetime',
        ];
    }

    /**
     * The number shown to people, e.g. "A-042". Needs the service relation.
     *
     * @return Attribute<string, never>
     */
    protected function code(): Attribute
    {
        return Attribute::get(fn () => sprintf('%s-%03d', $this->service->prefix, $this->number));
    }

    /**
     * Tickets issued today; numbering restarts every day.
     */
    #[Scope]
    protected function today(Builder $query): void
    {
        $query->where('date', today()->toDateString());
    }

    /**
     * Tickets still in the queue: waiting to be called, or currently at a counter.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', [TicketStatus::Waiting, TicketStatus::Serving]);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<Counter, $this>
     */
    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }
}
