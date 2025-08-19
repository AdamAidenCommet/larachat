<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test authenticated user can access dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertPathIs('/dashboard')
                ->assertSee('Dashboard')
                ->assertAuthenticated();
        });
    }

    /**
     * Test unauthenticated user is redirected to login.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dashboard')
                ->assertPathIs('/login')
                ->assertGuest();
        });
    }

    /**
     * Test dashboard navigation elements are present.
     */
    public function test_dashboard_navigation_elements_are_present(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertPresent('.sidebar')
                ->assertPresent('.header')
                ->assertSeeIn('.sidebar', 'Dashboard')
                ->assertSeeIn('.sidebar', 'Claude');
        });
    }
}
