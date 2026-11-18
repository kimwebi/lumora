<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Create or refresh the administrator account from configuration.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => (string) config('lumora.admin.email', 'admin@lumora.com')],
            [
                'name' => (string) config('lumora.admin.name', 'Ava Lumora'),
                'password' => Hash::make((string) config('lumora.admin.password', 'password')),
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
