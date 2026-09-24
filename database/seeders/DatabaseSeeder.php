<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'staff@example.com'],
            ['name' => 'Staff User', 'password' => 'password', 'email_verified_at' => now()],
        );

        $services = [
            ['name' => 'Registrar', 'prefix' => 'A', 'counters' => ['Window 1', 'Window 2']],
            ['name' => 'Cashier', 'prefix' => 'B', 'counters' => ['Window 3']],
        ];

        foreach ($services as $data) {
            $service = Service::firstOrCreate(
                ['prefix' => $data['prefix']],
                ['name' => $data['name']],
            );

            foreach ($data['counters'] as $counterName) {
                $service->counters()->firstOrCreate(['name' => $counterName]);
            }
        }
    }
}
