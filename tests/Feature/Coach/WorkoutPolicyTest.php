<?php

namespace Tests\Feature\Coach;

use App\Models\City;
use App\Models\CoachProfile;
use App\Models\Sport;
use App\Models\User;
use App\Models\Workout;
use App\Policies\WorkoutPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutPolicyTest extends TestCase
{
    use RefreshDatabase;

    private WorkoutPolicy $policy;

    private User $approvedCoach;

    private User $pendingCoach;

    private User $rejectedCoach;

    private City $city;

    private Sport $sport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new WorkoutPolicy;
        $this->city = City::factory()->create();
        $this->sport = Sport::factory()->create(['is_active' => true]);

        // Create approved coach
        $this->approvedCoach = User::factory()->coach()->create([
            'city_id' => $this->city->id,
        ]);

        CoachProfile::factory()->create([
            'user_id' => $this->approvedCoach->id,
            'moderation_status' => 'approved',
        ]);

        // Create pending coach
        $this->pendingCoach = User::factory()->coach()->create([
            'city_id' => $this->city->id,
        ]);

        CoachProfile::factory()->create([
            'user_id' => $this->pendingCoach->id,
            'moderation_status' => 'pending',
        ]);

        // Create rejected coach
        $this->rejectedCoach = User::factory()->coach()->create([
            'city_id' => $this->city->id,
        ]);

        CoachProfile::factory()->create([
            'user_id' => $this->rejectedCoach->id,
            'moderation_status' => 'rejected',
        ]);
    }

    public function test_approved_coach_can_publish_own_workout(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->approvedCoach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        $canPublish = $this->policy->publish($this->approvedCoach, $workout);

        $this->assertTrue($canPublish);
    }

    public function test_pending_coach_cannot_publish_workout(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->pendingCoach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        $canPublish = $this->policy->publish($this->pendingCoach, $workout);

        $this->assertFalse($canPublish);
    }

    public function test_rejected_coach_cannot_publish_workout(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->rejectedCoach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        $canPublish = $this->policy->publish($this->rejectedCoach, $workout);

        $this->assertFalse($canPublish);
    }

    public function test_coach_cannot_publish_another_coach_workout(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->approvedCoach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        // Another approved coach trying to publish the first coach's workout
        $otherCoach = User::factory()->coach()->create([
            'city_id' => $this->city->id,
        ]);

        CoachProfile::factory()->create([
            'user_id' => $otherCoach->id,
            'moderation_status' => 'approved',
        ]);

        $canPublish = $this->policy->publish($otherCoach, $workout);

        $this->assertFalse($canPublish);
    }

    public function test_coach_can_update_own_workout(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->approvedCoach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        $canUpdate = $this->policy->update($this->approvedCoach, $workout);

        $this->assertTrue($canUpdate);
    }

    public function test_coach_cannot_update_another_coach_workout(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->approvedCoach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        $canUpdate = $this->policy->update($this->pendingCoach, $workout);

        $this->assertFalse($canUpdate);
    }
}
