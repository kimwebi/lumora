<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_configured_administrator_with_a_hashed_password(): void
    {
        config([
            'lumora.admin.name' => 'Ops Lead',
            'lumora.admin.email' => 'ops@lumora.com',
            'lumora.admin.password' => 'secret-pass',
        ]);

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', 'ops@lumora.com')->firstOrFail();

        $this->assertSame('Ops Lead', $admin->name);
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('secret-pass', $admin->password));
        $this->assertNotNull($admin->email_verified_at);
    }

    #[Test]
    public function it_resets_the_existing_administrator_password_without_creating_a_duplicate(): void
    {
        config([
            'lumora.admin.name' => 'Ops Lead',
            'lumora.admin.email' => 'admin@lumora.com',
            'lumora.admin.password' => 'first-password',
        ]);
        $this->seed(AdminUserSeeder::class);

        config(['lumora.admin.password' => 'second-password']);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'admin@lumora.com')->count());

        $admin = User::query()->where('email', 'admin@lumora.com')->firstOrFail();
        $this->assertTrue(Hash::check('second-password', $admin->password));
        $this->assertFalse(Hash::check('first-password', $admin->password));
    }
}
