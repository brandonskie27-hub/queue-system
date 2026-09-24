<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    /**
     * List every service with its counters, including inactive ones.
     */
    public function index(): Response
    {
        $services = Service::query()
            ->with(['counters' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Services', [
            'services' => $services->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'prefix' => $service->prefix,
                'isActive' => $service->is_active,
                'counters' => $service->counters->map(fn (Counter $counter) => [
                    'id' => $counter->id,
                    'name' => $counter->name,
                    'isActive' => $counter->is_active,
                ]),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Service::create($this->validated($request));

        return to_route('admin.services.index');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validated($request, $service));

        return to_route('admin.services.index');
    }

    /**
     * @return array{name: string, prefix: string, is_active: bool}
     */
    private function validated(Request $request, ?Service $service = null): array
    {
        $request->merge(['prefix' => strtoupper(trim((string) $request->input('prefix')))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'prefix' => ['required', 'regex:/^[A-Z]{1,5}$/', Rule::unique('services', 'prefix')->ignore($service)],
        ], [
            'prefix.regex' => 'The prefix must be 1 to 5 letters.',
        ]);

        // An unticked checkbox sends nothing, so a missing value means "inactive".
        return [...$validated, 'is_active' => $request->boolean('is_active')];
    }
}
