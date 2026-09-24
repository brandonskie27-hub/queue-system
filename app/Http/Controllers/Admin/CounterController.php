<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CounterController extends Controller
{
    public function store(Request $request, Service $service): RedirectResponse
    {
        $service->counters()->create($this->validated($request, $service));

        return to_route('admin.services.index');
    }

    public function update(Request $request, Counter $counter): RedirectResponse
    {
        $counter->update($this->validated($request, $counter->service, $counter));

        return to_route('admin.services.index');
    }

    /**
     * @return array{name: string, is_active: bool}
     */
    private function validated(Request $request, Service $service, ?Counter $counter = null): array
    {
        $validated = $request->validate([
            // Counter names only need to be unique within their own service.
            'name' => ['required', 'string', 'max:100', Rule::unique('counters')->where('service_id', $service->id)->ignore($counter)],
        ]);

        // An unticked checkbox sends nothing, so a missing value means "inactive".
        return [...$validated, 'is_active' => $request->boolean('is_active')];
    }
}
