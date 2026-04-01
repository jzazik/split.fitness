<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->athlete()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('athlete.bookings', absolute: false));
    }

    public function test_athlete_redirects_to_bookings_after_login(): void
    {
        $user = User::factory()->athlete()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('athlete.bookings', absolute: false));
    }

    public function test_coach_redirects_to_dashboard_after_login(): void
    {
        $user = User::factory()->coach()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('coach.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_coach_redirects_to_intended_url_after_login(): void
    {
        $user = User::factory()->coach()->create();

        // Attempt to access a protected page while not logged in
        // This stores the intended URL in the session
        $this->get('/coach/profile');

        // Now login
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // Should redirect to the originally requested URL, not the role home page
        $response->assertRedirect(route('coach.profile', absolute: false));
    }

    public function test_athlete_redirects_to_intended_url_after_login(): void
    {
        $user = User::factory()->athlete()->create();

        // Attempt to access a protected page while not logged in
        $this->get('/athlete/bookings');

        // Now login
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // Should redirect to the originally requested URL
        $response->assertRedirect(route('athlete.bookings', absolute: false));
    }
}
