<?php

namespace Tests\Feature\Models;

use App\Models\AthleteProfile;
use App\Models\CoachProfile;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_profile_belongs_to_user(): void
    {
        $user = User::factory()->create(['role' => 'coach']);
        $coachProfile = CoachProfile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'moderation_status' => 'pending',
        ]);

        $this->assertInstanceOf(User::class, $coachProfile->user);
        $this->assertEquals($user->id, $coachProfile->user->id);
    }

    public function test_user_has_one_coach_profile(): void
    {
        $user = User::factory()->create(['role' => 'coach']);
        $coachProfile = CoachProfile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'moderation_status' => 'pending',
        ]);

        $this->assertInstanceOf(CoachProfile::class, $user->coachProfile);
        $this->assertEquals($coachProfile->id, $user->coachProfile->id);
    }

    public function test_athlete_profile_belongs_to_user(): void
    {
        $user = User::factory()->create(['role' => 'athlete']);
        $athleteProfile = AthleteProfile::create([
            'user_id' => $user->id,
            'emergency_contact' => '+79991234567',
        ]);

        $this->assertInstanceOf(User::class, $athleteProfile->user);
        $this->assertEquals($user->id, $athleteProfile->user->id);
    }

    public function test_user_has_one_athlete_profile(): void
    {
        $user = User::factory()->create(['role' => 'athlete']);
        $athleteProfile = AthleteProfile::create([
            'user_id' => $user->id,
            'emergency_contact' => '+79991234567',
        ]);

        $this->assertInstanceOf(AthleteProfile::class, $user->athleteProfile);
        $this->assertEquals($athleteProfile->id, $user->athleteProfile->id);
    }

    public function test_coach_profile_belongs_to_many_sports(): void
    {
        $user = User::factory()->create(['role' => 'coach']);
        $coachProfile = CoachProfile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'moderation_status' => 'pending',
        ]);

        $sport1 = Sport::create(['slug' => 'football', 'name' => 'Футбол', 'is_active' => true]);
        $sport2 = Sport::create(['slug' => 'tennis', 'name' => 'Теннис', 'is_active' => true]);

        $coachProfile->sports()->attach([$sport1->id, $sport2->id]);

        $this->assertCount(2, $coachProfile->sports);
        $this->assertTrue($coachProfile->sports->contains($sport1));
        $this->assertTrue($coachProfile->sports->contains($sport2));
    }

    public function test_sport_belongs_to_many_coach_profiles(): void
    {
        $sport = Sport::create(['slug' => 'basketball', 'name' => 'Баскетбол', 'is_active' => true]);

        $user1 = User::factory()->create(['role' => 'coach']);
        $coachProfile1 = CoachProfile::create([
            'user_id' => $user1->id,
            'bio' => 'Coach 1',
            'moderation_status' => 'pending',
        ]);

        $user2 = User::factory()->create(['role' => 'coach']);
        $coachProfile2 = CoachProfile::create([
            'user_id' => $user2->id,
            'bio' => 'Coach 2',
            'moderation_status' => 'pending',
        ]);

        $sport->coachProfiles()->attach([$coachProfile1->id, $coachProfile2->id]);

        $this->assertCount(2, $sport->coachProfiles);
        $this->assertTrue($sport->coachProfiles->contains($coachProfile1));
        $this->assertTrue($sport->coachProfiles->contains($coachProfile2));
    }

    public function test_user_is_coach_accessor(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);

        $this->assertTrue($coach->isCoach());
        $this->assertFalse($athlete->isCoach());
    }

    public function test_user_is_athlete_accessor(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);

        $this->assertFalse($coach->isAthlete());
        $this->assertTrue($athlete->isAthlete());
    }

    public function test_coach_profile_casts_are_correct(): void
    {
        $user = User::factory()->create(['role' => 'coach']);
        $coachProfile = CoachProfile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'experience_years' => 5,
            'rating_avg' => 4.75,
            'rating_count' => 20,
            'moderation_status' => 'approved',
            'is_public' => true,
        ]);

        $this->assertIsInt($coachProfile->experience_years);
        $this->assertIsInt($coachProfile->rating_count);
        $this->assertIsString($coachProfile->rating_avg);
        $this->assertEquals('4.75', $coachProfile->rating_avg);
        $this->assertIsString($coachProfile->moderation_status);
        $this->assertIsBool($coachProfile->is_public);
    }

    public function test_coach_profile_has_default_values(): void
    {
        $user = User::factory()->create(['role' => 'coach']);
        $coachProfile = CoachProfile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
        ]);

        $coachProfile->refresh();

        $this->assertEquals('0.00', $coachProfile->rating_avg);
        $this->assertEquals(0, $coachProfile->rating_count);
        $this->assertEquals('pending', $coachProfile->moderation_status);
        $this->assertFalse($coachProfile->is_public);
    }
}
