<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthenticationTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test user can visit login page.
     */
    public function test_can_visit_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertPathIs('/login')
                ->assertSee('Log in to your account');
        });
    }

    /**
     * Test user can login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                ->type('#email', $user->email)
                ->type('#password', 'password')
                ->press('Log in')
                ->assertPathIs('/dashboard')
                ->assertAuthenticated();
        });
    }

    /**
     * Test user cannot login with invalid credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('#email', 'wrong@example.com')
                ->type('#password', 'wrongpassword')
                ->press('Log in')
                ->assertPathIs('/login')
                ->pause(500)
                ->assertSee('credentials')
                ->assertGuest();
        });
    }

    /**
     * Test user can logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertAuthenticated()
                ->click('@user-menu-button')
                ->clickLink('Log out')
                ->assertPathIs('/login')
                ->assertGuest();
        });
    }
}
