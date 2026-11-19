<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminUserSeeder::class);
    }

    public function admin(): User
    {
        return User::where('email', 'admin@fstech.com')->first();
    }

    public function developer(): User
    {
        return User::where('email', 'developer@fstech.com')->first();
    }

    public function viewer(): User
    {
        return User::where('email', 'viewer@fstech.com')->first();
    }

    public function test_viewer_cannot_create_systems(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('systems.create'))
            ->assertForbidden();
    }

    public function test_viewer_cannot_store_systems(): void
    {
        $this->actingAs($this->viewer())
            ->post(route('systems.store'), [
                'name' => 'Test System',
                'stack' => 'laravel',
                'environment' => 'development',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('systems', ['name' => 'Test System']);
    }

    public function test_viewer_can_view_systems(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('systems.index'))
            ->assertOk();
    }

    public function test_developer_can_store_systems(): void
    {
        $this->actingAs($this->developer())
            ->post(route('systems.store'), [
                'name' => 'Test System',
                'stack' => 'laravel',
                'environment' => 'development',
                'status' => 'active',
                'expense_currency' => 'USD',
                'expenses' => [['category' => 'hosting', 'amount' => 25]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('systems', ['name' => 'Test System']);
    }

    public function test_developer_cannot_access_user_management(): void
    {
        $this->actingAs($this->developer())
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_user_management(): void
    {
        $this->actingAs($this->admin())
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users.store'), [
                'name' => 'New Dev',
                'email' => 'newdev@fstech.com',
                'password' => 'secure-password-123',
                'role' => 'developer',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'newdev@fstech.com', 'role' => 'developer']);
    }
}
