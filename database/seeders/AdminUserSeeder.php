<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the administrator account on first deploy.
 *
 * This seeder runs from start.sh on every container boot, so it must never
 * overwrite data the operator has changed: an existing account keeps its name
 * and password, and only has its administrator flag restored. Set
 * LUMORA_ADMIN_RESET_PASSWORD=true once to force the configured password back.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrNew([
            'email' => (string) config('lumora.admin.email', 'admin@lumora.com'),
        ]);

        if (! $admin->exists) {
            $admin->fill([
                'name' => (string) config('lumora.admin.name', 'Ava Lumora'),
                'password' => Hash::make((string) config('lumora.admin.password', 'password')),
                'email_verified_at' => now(),
            ]);
        } elseif (config('lumora.admin.reset_password')) {
            $admin->password = Hash::make((string) config('lumora.admin.password', 'password'));
        }

        $admin->is_admin = true;
        $admin->email_verified_at ??= now();
        $admin->save();
    }
}
