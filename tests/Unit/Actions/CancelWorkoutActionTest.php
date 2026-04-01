<?php

namespace Tests\Unit\Actions;

use App\Actions\Workout\CancelWorkoutAction;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CancelWorkoutActionTest extends TestCase
{
    use RefreshDatabase;

    private CancelWorkoutAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new CancelWorkoutAction;
    }

    public function test_successfully_cancels_workout(): void
    {
        $coach = User::factory()->coach()->create();

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'published',
            'slots_booked' => 0,
        ]);

        $this->action->execute($workout);

        $workout->refresh();
        $this->assertEquals('cancelled', $workout->status);
        $this->assertNotNull($workout->cancelled_at);
    }

    public function test_throws_exception_when_workout_already_cancelled(): void
    {
        $coach = User::factory()->coach()->create();

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'cancelled',
            'cancelled_at' => now()->subHour(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Тренировка уже отменена');

        $this->action->execute($workout);
    }

    public function test_can_cancel_draft_workout(): void
    {
        $coach = User::factory()->coach()->create();

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'draft',
        ]);

        $this->action->execute($workout);

        $workout->refresh();
        $this->assertEquals('cancelled', $workout->status);
    }

    public function test_can_cancel_published_workout_without_bookings(): void
    {
        $coach = User::factory()->coach()->create();

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'published',
            'slots_booked' => 0,
        ]);

        $this->action->execute($workout);

        $workout->refresh();
        $this->assertEquals('cancelled', $workout->status);
    }

    public function test_sets_cancelled_at_timestamp(): void
    {
        $coach = User::factory()->coach()->create();

        $workout = Workout::factory()->create([
            'coach_id' => $coach->id,
            'status' => 'published',
            'cancelled_at' => null,
        ]);

        $beforeCancel = now()->subSecond();
        $this->action->execute($workout);
        $afterCancel = now()->addSecond();

        $workout->refresh();
        $this->assertNotNull($workout->cancelled_at);
        $this->assertTrue(
            $workout->cancelled_at->greaterThanOrEqualTo($beforeCancel) &&
            $workout->cancelled_at->lessThanOrEqualTo($afterCancel),
            "Cancelled at ({$workout->cancelled_at}) should be between {$beforeCancel} and {$afterCancel}"
        );
    }
}
