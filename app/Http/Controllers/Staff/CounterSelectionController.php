<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CounterSelectionController extends Controller
{
    /**
     * Let staff choose which counter they're working at.
     */
    public function edit(Request $request): Response
    {
        $services = Service::query()
            ->where('is_active', true)
            ->with(['counters' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        return Inertia::render('Staff/SelectCounter', [
            'services' => $services
                ->filter(fn (Service $service) => $service->counters->isNotEmpty())
                ->values()
                ->map(fn (Service $service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'counters' => $service->counters->map->only(['id', 'name']),
                ]),
            'currentCounterId' => $request->session()->get('staff_counter_id'),
        ]);
    }

    /**
     * Remember the chosen counter for the rest of this login session.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'counter_id' => ['required', 'integer', Rule::exists('counters', 'id')->where('is_active', true)],
        ]);

        $request->session()->put('staff_counter_id', $validated['counter_id']);

        return to_route('dashboard');
    }
}
