<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginCsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_get_then_post_does_not_419(): void
    {
        $user = User::factory()->create([
            'email' => 'relogin@example.com',
            'password' => 'password',
        ]);

        $this->get('/login')->assertOk();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_logout_then_login_again_does_not_419(): void
    {
        $user = User::factory()->create([
            'email' => 'again@example.com',
            'password' => 'password',
        ]);

        $this->get('/login')->assertOk();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();

        $this->get('/login')->assertOk();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $this->assertNotEquals(419, $response->status());
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }

    public function test_login_with_year_session_lifetime_does_not_419(): void
    {
        config(['session.lifetime' => 525600]);

        $user = User::factory()->create([
            'email' => 'year@example.com',
            'password' => 'password',
        ]);

        $this->get('/login')->assertOk();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }
}
