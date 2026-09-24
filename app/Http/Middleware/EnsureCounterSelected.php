<?php

namespace App\Http\Middleware;

use App\Models\Counter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCounterSelected
{
    /**
     * Send staff to pick a counter if they haven't chosen one this session (or theirs, or its
     * service, was deactivated). The chosen counter is made available as the "counter" request attribute.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $counter = Counter::query()
            ->with('service')
            ->whereKey($request->session()->get('staff_counter_id'))
            ->where('is_active', true)
            ->whereRelation('service', 'is_active', true)
            ->first();

        if (! $counter) {
            $request->session()->forget('staff_counter_id');

            return to_route('counter.edit');
        }

        $request->attributes->set('counter', $counter);

        return $next($request);
    }
}
