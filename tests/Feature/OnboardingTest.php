<?php

namespace Tests\Feature;

use App\Models\AthleteProfile;
use App\Models\City;
use App\Models\CoachProfile;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_without_profile_is_redirected_to_onboarding(): void
    {
        $user = User::factory()->create([
            'role' => 'coach',
            'first_name' => 'Test',
            'last_name' => 'Coach',
        ]);

        $response = $this->actingAs($user)->get(route('coach.dashboard'));

        $response->assertRedirect(route('onboarding.show'));
    }

    public function test_coach_with_incomplete_profile_is_redirected_to_onboarding(): void
    {
        $user = User::factory()->create([
            'role' => 'coach',
            'first_name' => 'Test',
            'last_name' => 'Coach',
        ]);

        $profile = CoachProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => null,
        ]);

        $response = $this->actingAs($user)->get(route('coach.dashboard'));

        $response->assertRedirect(route('onboarding.show'));
    }

    public function test_coach_with_complete_profile_can_access_dashboard(): void
    {
        $city = City::factory()->create();
        $sport = Sport::factory()->create();

        $user = User::factory()->create([
            'role' => 'coach',
            'first_name' => 'Test',
            'last_name' => 'Coach',
            'city_id' => $city->id,
        ]);

        $profile = CoachProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Experienced coach with 10 years of experience.',
        ]);

        $profile->sports()->attach($sport->id);

        $response = $this->actingAs($user)->get(route('coach.dashboard'));

        $response->assertOk();
    }

    public function test_athlete_without_first_name_is_redirected_to_onboarding(): void
    {
        $user = User::factory()->create([
            'role' => 'athlete',
            'first_name' => '',
            'last_name' => 'Athlete',
        ]);

        AthleteProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('athlete.bookings'));

        $response->assertRedirect(route('onboarding.show'));
    }

    public function test_athlete_with_complete_profile_can_access_bookings(): void
    {
        $user = User::factory()->create([
            'role' => 'athlete',
            'first_name' => 'Test',
            'last_name' => 'Athlete',
        ]);

        AthleteProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('athlete.bookings'));

        $response->assertOk();
    }

    public function test_coach_can_complete_onboarding(): void
    {
        $city = City::factory()->create();
        $sport = Sport::factory()->create();

        $user = User::factory()->create([
            'role' => 'coach',
            'first_name' => 'Old',
            'last_name' => 'Name',
        ]);

        CoachProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('onboarding.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'Smith',
            'bio' => 'Experienced coach with 10 years of experience in basketball and fitness.',
            'city_id' => $city->id,
            'sports' => [$sport->id],
            'experience_years' => 10,
        ]);

        $response->assertRedirect(route('coach.dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'city_id' => $city->id,
        ]);

        $this->assertDatabaseHas('coach_profiles', [
            'user_id' => $user->id,
            'bio' => 'Experienced coach with 10 years of experience in basketball and fitness.',
            'experience_years' => 10,
        ]);

        $this->assertDatabaseHas('coach_sports', [
            'coach_profile_id' => $user->coachProfile->id,
            'sport_id' => $sport->id,
        ]);
    }

    public function test_athlete_can_complete_onboarding(): void
    {
        $city = City::factory()->create();

        $user = User::factory()->create([
            'role' => 'athlete',
            'first_name' => 'Old',
            'last_name' => 'Name',
        ]);

        AthleteProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('onboarding.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '+79991234567',
            'city_id' => $city->id,
            'emergency_contact' => 'John Smith +79991234568',
        ]);

        $response->assertRedirect(route('athlete.bookings'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '+79991234567',
            'city_id' => $city->id,
        ]);

        $this->assertDatabaseHas('athlete_profiles', [
            'user_id' => $user->id,
            'emergency_contact' => 'John Smith +79991234568',
        ]);
    }

    public function test_onboarding_validates_coach_required_fields(): void
    {
        $user = User::factory()->create([
            'role' => 'coach',
        ]);

        CoachProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('onboarding.store'), [
            'first_name' => '',
            'last_name' => '',
            'bio' => '',
            'city_id' => null,
            'sports' => [],
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'bio', 'city_id', 'sports']);
    }

    public function test_onboarding_validates_athlete_required_fields(): void
    {
        $user = User::factory()->create([
            'role' => 'athlete',
        ]);

        AthleteProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('onboarding.store'), [
            'first_name' => '',
            'last_name' => '',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    public function test_onboarding_route_is_excluded_from_middleware(): void
    {
        $user = User::factory()->create([
            'role' => 'coach',
            'first_name' => '',
        ]);

        CoachProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('onboarding.show'));

        $response->assertOk();
    }

    public function test_profile_routes_are_excluded_from_middleware(): void
    {
        $user = User::factory()->create([
            'role' => 'coach',
            'first_name' => '',
        ]);

        CoachProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('coach.profile'));

        $response->assertOk();
    }
}
