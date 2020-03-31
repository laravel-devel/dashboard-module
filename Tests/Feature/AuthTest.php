<?php

namespace Modules\DevelDashboard\Tests\Feature;

use Devel\Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Devel\Database\Seeders\DevelDatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Modules\DevelDashboard\Database\Seeders\DevelDashboardDatabaseSeeder;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DevelDatabaseSeeder::class);
        $this->seed(DevelDashboardDatabaseSeeder::class);

        $this->userModel = config('auth.providers.users.model');

        $this->password = 'qwerty';
        $this->admin = factory($this->userModel)->create([
            'password' => Hash::make($this->password),
        ]);

        $this->admin->permissions()->attach('admin_dashboard.access');
    }

    /** @test */
    public function users_with_access_can_log_into_dashboard()
    {
        $this->assertGuest();

        $controller = config('develdashboard.auth_controllers_namespace') . '\\LoginController';
        $controller = new $controller;

        $this->postJson(route('dashboard.auth.login.post', [
            $controller->username() => $this->admin->{$controller->username()},
            'password' => $this->password,
        ]))->assertStatus(200);

        $this->assertAuthenticated();
    }

    /** @test */
    public function users_can_log_out()
    {
        $this->actingAs($this->admin);

        $this->postJson(route('dashboard.auth.logout'))->assertStatus(302);

        $this->assertGuest();
    }
}
