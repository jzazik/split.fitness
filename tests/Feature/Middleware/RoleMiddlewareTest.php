<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth', 'role:athlete'])->get('/test-athlete-route', function () {
            return response()->json(['message' => 'athlete route']);
        });

        Route::middleware(['auth', 'role:coach'])->get('/test-coach-route', function () {
            return response()->json(['message' => 'coach route']);
        });

        Route::middleware(['auth', 'role:admin'])->get('/test-admin-route', function () {
            return response()->json(['message' => 'admin route']);
        });
    }

    public function test_athlete_can_access_athlete_route(): void
    {
        $athlete = User::factory()->create(['role' => 'athlete']);

        $response = $this->actingAs($athlete)->get('/test-athlete-route');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'athlete route']);
    }

    public function test_coach_can_access_coach_route(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);

        $response = $this->actingAs($coach)->get('/test-coach-route');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'coach route']);
    }

    public function test_admin_can_access_admin_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/test-admin-route');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'admin route']);
    }

    public function test_athlete_cannot_access_coach_route(): void
    {
        $athlete = User::factory()->create(['role' => 'athlete']);

        $response = $this->actingAs($athlete)->get('/test-coach-route');

        $response->assertStatus(403);
    }

    public function test_coach_cannot_access_athlete_route(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);

        $response = $this->actingAs($coach)->get('/test-athlete-route');

        $response->assertStatus(403);
    }

    public function test_athlete_cannot_access_admin_route(): void
    {
        $athlete = User::factory()->create(['role' => 'athlete']);

        $response = $this->actingAs($athlete)->get('/test-admin-route');

        $response->assertStatus(403);
    }

    public function test_coach_cannot_access_admin_route(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);

        $response = $this->actingAs($coach)->get('/test-admin-route');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $response = $this->get('/test-athlete-route');

        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_access_is_logged(): void
    {
        Log::spy();

        $athlete = User::factory()->create(['role' => 'athlete']);

        $this->actingAs($athlete)->get('/test-coach-route');

        Log::shouldHaveReceived('warning')
            ->once()
            ->with(
                'Unauthorized role access attempt',
                \Mockery::on(function ($context) use ($athlete) {
                    return $context['user_id'] === $athlete->id
                        && $context['user_role'] === 'athlete'
                        && $context['required_role'] === 'coach'
                        && str_contains($context['url'], '/test-coach-route');
                })
            );
    }
}
