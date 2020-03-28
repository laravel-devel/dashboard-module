<?php

namespace Modules\DevelDashboard\Tests\Feature;

use Devel\Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Devel\Database\Seeders\DevelDatabaseSeeder;
use Modules\DevelDashboard\Database\Seeders\DevelDashboardDatabaseSeeder;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DevelDatabaseSeeder::class);
        $this->seed(DevelDashboardDatabaseSeeder::class);

        $this->userModel = config('auth.providers.users.model');

        $this->admin = factory($this->userModel)->create();
        $this->user = factory($this->userModel)->create();

        $this->admin->roles()->attach('admin');
        $this->user->roles()->attach('user');
    }

    /** @test */
    public function not_everyone_allowed_to_access_admin_dashboard()
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard.index'))
            ->assertStatus(200);

        $this->actingAs($this->user)
            ->get(route('dashboard.index'))
            ->assertStatus(302)
            ->assertRedirect('/');
    }
}
