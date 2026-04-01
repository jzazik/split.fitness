<?php

namespace Tests\Feature\Athlete;

use App\Models\AthleteProfile;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_athlete_can_view_profile_edit_page(): void
    {
        $athlete = User::factory()->athlete()->create();
        $athleteProfile = AthleteProfile::factory()->create(['user_id' => $athlete->id]);

        $response = $this
            ->actingAs($athlete)
            ->get(route('athlete.profile'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Athlete/Profile/Edit')
            ->has('user')
            ->has('cities')
        );
    }

    public function test_athlete_can_update_profile_with_valid_data(): void
    {
        $athlete = User::factory()->athlete()->create();
        $athleteProfile = AthleteProfile::factory()->create(['user_id' => $athlete->id]);
        $city = City::factory()->create();

        $response = $this
            ->actingAs($athlete)
            ->patch(route('athlete.profile.update'), [
                'first_name' => 'Анна',
                'last_name' => 'Иванова',
                'phone' => '+7 (999) 123-45-67',
                'city_id' => $city->id,
                'emergency_contact' => 'Мама, +7 (999) 765-43-21',
            ]);

        $response->assertRedirect(route('athlete.profile'));
        $response->assertSessionHas('success');

        $athlete->refresh();
        $this->assertEquals('Анна', $athlete->first_name);
        $this->assertEquals('Иванова', $athlete->last_name);
        $this->assertEquals('+7 (999) 123-45-67', $athlete->phone);
        $this->assertEquals($city->id, $athlete->city_id);

        $athleteProfile->refresh();
        $this->assertEquals('Мама, +7 (999) 765-43-21', $athleteProfile->emergency_contact);
    }

    public function test_profile_update_requires_first_name(): void
    {
        $athlete = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete->id]);

        $response = $this
            ->actingAs($athlete)
            ->patch(route('athlete.profile.update'), [
                'first_name' => '',
                'last_name' => 'Иванова',
            ]);

        $response->assertSessionHasErrors('first_name');
    }

    public function test_profile_update_requires_last_name(): void
    {
        $athlete = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete->id]);

        $response = $this
            ->actingAs($athlete)
            ->patch(route('athlete.profile.update'), [
                'first_name' => 'Анна',
                'last_name' => '',
            ]);

        $response->assertSessionHasErrors('last_name');
    }

    public function test_profile_update_validates_phone_uniqueness(): void
    {
        $athlete1 = User::factory()->athlete()->create(['phone' => '+7 (999) 111-11-11']);
        $athlete2 = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete2->id]);

        $response = $this
            ->actingAs($athlete2)
            ->patch(route('athlete.profile.update'), [
                'first_name' => 'Анна',
                'last_name' => 'Иванова',
                'phone' => '+7 (999) 111-11-11',
            ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_profile_update_allows_keeping_same_phone(): void
    {
        $athlete = User::factory()->athlete()->create(['phone' => '+7 (999) 111-11-11']);
        $athleteProfile = AthleteProfile::factory()->create(['user_id' => $athlete->id]);

        $response = $this
            ->actingAs($athlete)
            ->patch(route('athlete.profile.update'), [
                'first_name' => 'Анна',
                'last_name' => 'Иванова',
                'phone' => '+7 (999) 111-11-11',
            ]);

        $response->assertRedirect(route('athlete.profile'));
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_profile_update_validates_city_exists(): void
    {
        $athlete = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete->id]);

        $response = $this
            ->actingAs($athlete)
            ->patch(route('athlete.profile.update'), [
                'first_name' => 'Анна',
                'last_name' => 'Иванова',
                'city_id' => 99999,
            ]);

        $response->assertSessionHasErrors('city_id');
    }

    public function test_profile_update_allows_nullable_fields(): void
    {
        $athlete = User::factory()->athlete()->create();
        $athleteProfile = AthleteProfile::factory()->create(['user_id' => $athlete->id]);

        $response = $this
            ->actingAs($athlete)
            ->patch(route('athlete.profile.update'), [
                'first_name' => 'Анна',
                'last_name' => 'Иванова',
                'phone' => null,
                'city_id' => null,
                'emergency_contact' => null,
            ]);

        $response->assertRedirect(route('athlete.profile'));
        $response->assertSessionDoesntHaveErrors();

        $athlete->refresh();
        $this->assertNull($athlete->phone);
        $this->assertNull($athlete->city_id);

        $athleteProfile->refresh();
        $this->assertNull($athleteProfile->emergency_contact);
    }

    public function test_athlete_can_upload_avatar(): void
    {
        $this->markTestIncomplete('Avatar upload test will be completed in Task 10 (MediaUploadTest)');

        $athlete = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete->id]);
        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        $response = $this
            ->actingAs($athlete)
            ->post(route('athlete.profile.uploadAvatar'), [
                'avatar' => $file,
            ]);

        $response->assertRedirect(route('athlete.profile'));
        $response->assertSessionHas('success');

        $athlete->refresh();
        $this->assertEquals(1, $athlete->getMedia('avatar')->count());
    }

    public function test_avatar_upload_validates_file_type(): void
    {
        $athlete = User::factory()->athlete()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this
            ->actingAs($athlete)
            ->post(route('athlete.profile.uploadAvatar'), [
                'avatar' => $file,
            ]);

        $response->assertSessionHasErrors('avatar');
    }

    public function test_avatar_upload_validates_file_size(): void
    {
        $athlete = User::factory()->athlete()->create();
        $file = UploadedFile::fake()->image('avatar.jpg')->size(6000);

        $response = $this
            ->actingAs($athlete)
            ->post(route('athlete.profile.uploadAvatar'), [
                'avatar' => $file,
            ]);

        $response->assertSessionHasErrors('avatar');
    }

    public function test_athlete_can_delete_avatar(): void
    {
        $athlete = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete->id]);

        $file = UploadedFile::fake()->image('avatar.jpg');
        $athlete->addMedia($file)->toMediaCollection('avatar');

        $this->assertEquals(1, $athlete->getMedia('avatar')->count());

        $response = $this
            ->actingAs($athlete)
            ->delete(route('athlete.profile.deleteAvatar'));

        $response->assertRedirect(route('athlete.profile'));
        $response->assertSessionHas('success');

        $athlete->refresh();
        $this->assertEquals(0, $athlete->getMedia('avatar')->count());
    }

    public function test_non_athlete_cannot_access_athlete_profile(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this
            ->actingAs($coach)
            ->get(route('athlete.profile'));

        $response->assertForbidden();
    }
}
