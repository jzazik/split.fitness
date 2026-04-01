<?php

namespace Tests\Feature\Auth;

use App\Models\AthleteProfile;
use App\Models\CoachProfile;
use App\Models\User;
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
        $response->assertRedirect(route('onboarding.show', absolute: false));
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
        $response->assertRedirect(route('onboarding.show', absolute: false));
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
        $response->assertRedirect(route('onboarding.show', absolute: false));
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

    public function test_phone_must_be_unique(): void
    {
        // Create first user with phone directly in database
        User::create([
            'role' => 'athlete',
            'first_name' => 'First',
            'last_name' => 'User',
            'email' => 'first@example.com',
            'phone' => '+7 (999) 123-45-67',
            'password' => bcrypt('password'),
        ]);

        // Attempt to register second user with same phone
        $response = $this->post('/register', [
            'role' => 'coach',
            'first_name' => 'Second',
            'last_name' => 'User',
            'email' => 'second@example.com',
            'phone' => '+7 (999) 123-45-67',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_phone_must_be_valid_format(): void
    {
        $response = $this->post('/register', [
            'role' => 'athlete',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => 'invalid-phone-abc',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_coach_profile_is_created_automatically_on_registration(): void
    {
        $this->post('/register', [
            'role' => 'coach',
            'first_name' => 'Test',
            'last_name' => 'Coach',
            'email' => 'coach@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'coach@example.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('coach_profiles', [
            'user_id' => $user->id,
            'moderation_status' => 'pending',
            'is_public' => false,
        ]);

        $coachProfile = CoachProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($coachProfile);
        $this->assertEquals('pending', $coachProfile->moderation_status);
        $this->assertFalse($coachProfile->is_public);
    }

    public function test_athlete_profile_is_created_automatically_on_registration(): void
    {
        $this->post('/register', [
            'role' => 'athlete',
            'first_name' => 'Test',
            'last_name' => 'Athlete',
            'email' => 'athlete@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'athlete@example.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('athlete_profiles', [
            'user_id' => $user->id,
        ]);

        $athleteProfile = AthleteProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($athleteProfile);
    }
}
