<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;

#[Signature('staff:create {name : The staff member\'s full name} {email : The email they will log in with} {--admin : Also let them manage services and counters}')]
#[Description('Create a staff account (public registration is turned off)')]
class CreateStaffUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $data = [
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => password(label: 'Password', required: true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // Staff are created by an admin, so their email counts as verified.
        (new User($validator->validated()))
            ->forceFill(['email_verified_at' => now(), 'is_admin' => $this->option('admin')])
            ->save();

        $this->info(($this->option('admin') ? 'Admin' : 'Staff')." account created for {$data['email']}.");

        return self::SUCCESS;
    }
}
