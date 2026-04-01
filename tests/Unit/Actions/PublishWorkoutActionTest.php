<?php

namespace Tests\Unit\Actions;

use App\Actions\Workout\PublishWorkoutAction;
use App\Models\CoachProfile;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PublishWorkoutActionTest extends TestCase
{
    use RefreshDatabase;

    private PublishWorkoutAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new PublishWorkoutAction;
    }

    public function test_successfully_publishes_draft_workout(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'moderation_status' => 'approved',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'draft',
            'starts_at' => now()->addDay(),
        ]);

        $this->action->execute($workout);

        $workout->refresh();
        $this->assertEquals('published', $workout->status);
        $this->assertNotNull($workout->published_at);
    }

    public function test_throws_exception_when_workout_already_published(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'moderation_status' => 'approved',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'published',
            'published_at' => now()->subHour(),
            'starts_at' => now()->addDay(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Можно публиковать только черновики тренировок');

        $this->action->execute($workout);
    }

    public function test_throws_exception_when_workout_is_cancelled(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'moderation_status' => 'approved',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'cancelled',
            'cancelled_at' => now()->subHour(),
            'starts_at' => now()->addDay(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Можно публиковать только черновики тренировок');

        $this->action->execute($workout);
    }

    public function test_throws_exception_when_workout_is_completed(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'moderation_status' => 'approved',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'completed',
            'starts_at' => now()->subDay(), // In the past
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Можно публиковать только черновики тренировок');

        $this->action->execute($workout);
    }

    public function test_throws_exception_when_coach_has_no_profile(): void
    {
        $coach = User::factory()->coach()->create();

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'draft',
            'starts_at' => now()->addDay(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Профиль тренера должен быть одобрен');

        $this->action->execute($workout);
    }

    public function test_throws_exception_when_coach_is_pending(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'moderation_status' => 'pending',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'draft',
            'starts_at' => now()->addDay(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Профиль тренера должен быть одобрен');

        $this->action->execute($workout);
    }

    public function test_throws_exception_when_coach_is_rejected(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'moderation_status' => 'rejected',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'draft',
            'starts_at' => now()->addDay(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Профиль тренера должен быть одобрен');

        $this->action->execute($workout);
    }

    public function test_throws_exception_when_starts_at_is_in_past(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'moderation_status' => 'approved',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'draft',
            'starts_at' => now()->addMinutes(30), // Less than 1 hour
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Тренировку можно публиковать только если она начинается минимум через 1 час');

        $this->action->execute($workout);
    }

    public function test_sets_published_at_timestamp(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'moderation_status' => 'approved',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'draft',
            'starts_at' => now()->addDay(),
            'published_at' => null,
        ]);

        $beforePublish = now()->subSecond();
        $this->action->execute($workout);
        $afterPublish = now()->addSecond();

        $workout->refresh();
        $this->assertNotNull($workout->published_at);
        $this->assertTrue(
            $workout->published_at->greaterThanOrEqualTo($beforePublish) &&
            $workout->published_at->lessThanOrEqualTo($afterPublish),
            "Published at ({$workout->published_at}) should be between {$beforePublish} and {$afterPublish}"
        );
    }
}
