<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminServicesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_staff_who_are_not_admins_cannot_manage_services(): void
    {
        $staff = User::factory()->create();
        $service = Service::factory()->create();

        $this->actingAs($staff)->get(route('admin.services.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.services.store'), ['name' => 'X', 'prefix' => 'X'])->assertForbidden();
        $this->actingAs($staff)->put(route('admin.services.update', $service), ['name' => 'X', 'prefix' => 'X'])->assertForbidden();
    }

    public function test_admin_sees_every_service_and_counter_including_closed_ones(): void
    {
        $service = Service::factory()->inactive()->create(['name' => 'Registrar']);
        Counter::factory()->for($service)->create(['name' => 'Window 1', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.services.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Services')
                ->where('services.0.name', 'Registrar')
                ->where('services.0.isActive', false)
                ->where('services.0.counters.0.name', 'Window 1')
                ->where('services.0.counters.0.isActive', false)
            );
    }

    public function test_admin_can_add_a_service_and_the_prefix_is_uppercased(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.services.store'), ['name' => 'Clinic', 'prefix' => ' c ', 'is_active' => '1'])
            ->assertRedirect(route('admin.services.index'));

        $service = Service::sole();
        $this->assertSame('Clinic', $service->name);
        $this->assertSame('C', $service->prefix);
        $this->assertTrue($service->is_active);
    }

    public function test_service_prefix_must_be_letters_and_unique(): void
    {
        Service::factory()->create(['prefix' => 'A']);

        $this->actingAs($this->admin)
            ->post(route('admin.services.store'), ['name' => 'Clinic', 'prefix' => 'a'])
            ->assertSessionHasErrors('prefix');

        $this->actingAs($this->admin)
            ->post(route('admin.services.store'), ['name' => 'Clinic', 'prefix' => 'C1'])
            ->assertSessionHasErrors(['prefix' => 'The prefix must be 1 to 5 letters.']);
    }

    public function test_admin_can_rename_and_close_a_service(): void
    {
        $service = Service::factory()->create(['name' => 'Registrar', 'prefix' => 'A']);

        // No is_active in the request = the "Open" box was unticked.
        $this->actingAs($this->admin)
            ->put(route('admin.services.update', $service), ['name' => 'Registrar Office', 'prefix' => 'A'])
            ->assertSessionHasNoErrors();

        $service->refresh();
        $this->assertSame('Registrar Office', $service->name);
        $this->assertFalse($service->is_active);
    }

    public function test_admin_can_add_a_counter_to_a_service(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.counters.store', $service), ['name' => 'Window 4', 'is_active' => '1'])
            ->assertRedirect(route('admin.services.index'));

        $counter = $service->counters()->sole();
        $this->assertSame('Window 4', $counter->name);
        $this->assertTrue($counter->is_active);
    }

    public function test_counter_names_are_unique_per_service_only(): void
    {
        [$registrar, $cashier] = Service::factory()->count(2)->create();
        Counter::factory()->for($registrar)->create(['name' => 'Window 1']);

        $this->actingAs($this->admin)
            ->post(route('admin.counters.store', $registrar), ['name' => 'Window 1'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)
            ->post(route('admin.counters.store', $cashier), ['name' => 'Window 1'])
            ->assertSessionHasNoErrors();
    }

    public function test_admin_can_rename_and_close_a_counter(): void
    {
        $counter = Counter::factory()->create(['name' => 'Window 1']);

        $this->actingAs($this->admin)
            ->put(route('admin.counters.update', $counter), ['name' => 'Window 1A'])
            ->assertSessionHasNoErrors();

        $counter->refresh();
        $this->assertSame('Window 1A', $counter->name);
        $this->assertFalse($counter->is_active);
    }

    public function test_closing_a_service_sends_its_staff_back_to_choose_a_counter(): void
    {
        $counter = Counter::factory()->create();
        $counter->service->update(['is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->withSession(['staff_counter_id' => $counter->id])
            ->get(route('dashboard'))
            ->assertRedirect(route('counter.edit'));
    }
}
