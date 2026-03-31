<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'role' => 'athlete',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_register_as_athlete(): void
    {
        $response = $this->post('/register', [
            'role' => 'athlete',
            'first_name' => 'Test',
            'last_name' => 'Athlete',
            'email' => 'athlete@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'athlete@example.com',
            'role' => 'athlete',
            'first_name' => 'Test',
            'last_name' => 'Athlete',
        ]);
    }

    public function test_users_can_register_as_coach(): void
    {
        $response = $this->post('/register', [
            'role' => 'coach',
            'first_name' => 'Test',
            'last_name' => 'Coach',
            'email' => 'coach@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'coach@example.com',
            'role' => 'coach',
            'first_name' => 'Test',
            'last_name' => 'Coach',
        ]);
    }

    public function test_role_is_required(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_first_name_is_required(): void
    {
        $response = $this->post('/register', [
            'role' => 'athlete',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('first_name');
    }

    public function test_last_name_is_required(): void
    {
        $response = $this->post('/register', [
            'role' => 'athlete',
            'first_name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('last_name');
    }
}
