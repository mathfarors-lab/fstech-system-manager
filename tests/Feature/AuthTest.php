<?php

namespace Tests\Feature;

use App\Models\System;
use App\Models\User;
use App\Models\Version;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminUserSeeder::class);
    }

    public function test_login_page_is_public(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in');
    }

    public function test_welcome_page_is_public_for_guests(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('FS Tech')
            ->assertSee('Explore projects');
    }

    public function test_welcome_page_lists_active_projects_only(): void
    {
        $active = System::create([
            'name' => 'Orbit Portal',
            'slug' => 'orbit-portal',
            'description' => 'A public project for the portfolio.',
            'stack' => 'laravel',
            'status' => 'active',
            'environment' => 'production',
        ]);

        Version::create([
            'system_id' => $active->id,
            'version' => '1.2.3',
            'release_date' => now(),
        ]);

        System::create([
            'name' => 'Hidden Archive',
            'slug' => 'hidden-archive',
            'description' => 'This project should stay private.',
            'stack' => 'php',
            'status' => 'archived',
            'environment' => 'production',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Orbit Portal')
            ->assertSee('v1.2.3')
            ->assertDontSee('Hidden Archive')
            ->assertDontSee('This project should stay private.');
    }

    public function test_user_can_login(): void
    {
        $this->post('/login', [
            'email' => 'admin@fstech.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->post('/login', [
            'email' => 'admin@fstech.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::where('email', 'admin@fstech.com')->first();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_from_welcome_to_dashboard(): void
    {
        $user = User::where('email', 'admin@fstech.com')->first();

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Dashboard');
    }
}
