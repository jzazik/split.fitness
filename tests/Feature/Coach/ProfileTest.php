<?php

namespace Tests\Feature\Coach;

use App\Models\City;
use App\Models\CoachProfile;
use App\Models\Sport;
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

    public function test_coach_can_view_profile_edit_page(): void
    {
        $coach = User::factory()->coach()->create();
        $coachProfile = CoachProfile::factory()->create(['user_id' => $coach->id]);

        $response = $this
            ->actingAs($coach)
            ->get(route('coach.profile'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Coach/Profile/Edit')
            ->has('user')
            ->has('profile')
            ->has('cities')
            ->has('sports')
        );
    }

    public function test_coach_can_update_profile_with_valid_data(): void
    {
        $coach = User::factory()->coach()->create();
        $coachProfile = CoachProfile::factory()->create(['user_id' => $coach->id]);
        $city = City::factory()->create();
        $sports = Sport::factory()->count(3)->create(['is_active' => true]);

        $response = $this
            ->actingAs($coach)
            ->patch(route('coach.profile.update'), [
                'first_name' => 'Иван',
                'last_name' => 'Петров',
                'middle_name' => 'Сергеевич',
                'bio' => 'Опытный тренер по боксу',
                'city_id' => $city->id,
                'sports' => $sports->pluck('id')->toArray(),
                'experience_years' => 5,
            ]);

        $response->assertRedirect(route('coach.profile'));
        $response->assertSessionHas('success');

        $coach->refresh();
        $this->assertEquals('Иван', $coach->first_name);
        $this->assertEquals('Петров', $coach->last_name);
        $this->assertEquals('Сергеевич', $coach->middle_name);
        $this->assertEquals($city->id, $coach->city_id);

        $coachProfile->refresh();
        $this->assertEquals('Опытный тренер по боксу', $coachProfile->bio);
        $this->assertEquals(5, $coachProfile->experience_years);
        $this->assertEquals(3, $coachProfile->sports()->count());
    }

    public function test_profile_update_requires_bio(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create(['user_id' => $coach->id]);
        $city = City::factory()->create();
        $sport = Sport::factory()->create(['is_active' => true]);

        $response = $this
            ->actingAs($coach)
            ->patch(route('coach.profile.update'), [
                'first_name' => 'Иван',
                'last_name' => 'Петров',
                'bio' => '',
                'city_id' => $city->id,
                'sports' => [$sport->id],
            ]);

        $response->assertSessionHasErrors('bio');
    }

    public function test_profile_update_requires_at_least_one_sport(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create(['user_id' => $coach->id]);
        $city = City::factory()->create();

        $response = $this
            ->actingAs($coach)
            ->patch(route('coach.profile.update'), [
                'first_name' => 'Иван',
                'last_name' => 'Петров',
                'bio' => 'Тренер',
                'city_id' => $city->id,
                'sports' => [],
            ]);

        $response->assertSessionHasErrors('sports');
    }

    public function test_profile_update_validates_city_exists(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create(['user_id' => $coach->id]);
        $sport = Sport::factory()->create(['is_active' => true]);

        $response = $this
            ->actingAs($coach)
            ->patch(route('coach.profile.update'), [
                'first_name' => 'Иван',
                'last_name' => 'Петров',
                'bio' => 'Тренер',
                'city_id' => 99999,
                'sports' => [$sport->id],
            ]);

        $response->assertSessionHasErrors('city_id');
    }

    public function test_coach_can_upload_avatar(): void
    {
        $this->markTestIncomplete('Avatar upload test will be completed in Task 10 (MediaUploadTest)');

        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create(['user_id' => $coach->id]);
        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        $response = $this
            ->actingAs($coach)
            ->post(route('coach.profile.uploadAvatar'), [
                'avatar' => $file,
            ]);

        $response->assertRedirect(route('coach.profile'));
    }

    public function test_avatar_upload_validates_file_type(): void
    {
        $coach = User::factory()->coach()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this
            ->actingAs($coach)
            ->post(route('coach.profile.uploadAvatar'), [
                'avatar' => $file,
            ]);

        $response->assertSessionHasErrors('avatar');
    }

    public function test_avatar_upload_validates_file_size(): void
    {
        $coach = User::factory()->coach()->create();
        $file = UploadedFile::fake()->image('avatar.jpg')->size(6000);

        $response = $this
            ->actingAs($coach)
            ->post(route('coach.profile.uploadAvatar'), [
                'avatar' => $file,
            ]);

        $response->assertSessionHasErrors('avatar');
    }

    public function test_coach_can_attach_sports_to_profile(): void
    {
        $coach = User::factory()->coach()->create();
        $coachProfile = CoachProfile::factory()->create(['user_id' => $coach->id]);
        $city = City::factory()->create();
        $sports = Sport::factory()->count(2)->create(['is_active' => true]);

        $response = $this
            ->actingAs($coach)
            ->patch(route('coach.profile.update'), [
                'first_name' => $coach->first_name,
                'last_name' => $coach->last_name,
                'bio' => 'Тренер',
                'city_id' => $city->id,
                'sports' => $sports->pluck('id')->toArray(),
            ]);

        $response->assertRedirect(route('coach.profile'));

        $coachProfile->refresh();
        $this->assertEquals(2, $coachProfile->sports()->count());
        $this->assertTrue($coachProfile->sports->contains($sports[0]));
        $this->assertTrue($coachProfile->sports->contains($sports[1]));
    }

    public function test_non_coach_cannot_access_coach_profile(): void
    {
        $athlete = User::factory()->athlete()->create();

        $response = $this
            ->actingAs($athlete)
            ->get(route('coach.profile'));

        $response->assertForbidden();
    }

    public function test_coach_can_upload_diplomas(): void
    {
        $coach = User::factory()->coach()->create();
        $coachProfile = CoachProfile::factory()->create(['user_id' => $coach->id]);

        $file1 = UploadedFile::fake()->create('diploma1.pdf', 1000);
        $file2 = UploadedFile::fake()->create('diploma2.pdf', 1000);

        $response = $this
            ->actingAs($coach)
            ->post(route('coach.profile.uploadDiploma'), [
                'diplomas' => [$file1, $file2],
            ]);

        $response->assertRedirect(route('coach.profile'));
        $response->assertSessionHas('success');

        $coachProfile->refresh();
        $this->assertEquals(2, $coachProfile->getMedia('diplomas')->count());
    }

    public function test_coach_can_upload_certificates(): void
    {
        $coach = User::factory()->coach()->create();
        $coachProfile = CoachProfile::factory()->create(['user_id' => $coach->id]);

        $file = UploadedFile::fake()->create('certificate.pdf', 1000);

        $response = $this
            ->actingAs($coach)
            ->post(route('coach.profile.uploadCertificate'), [
                'certificates' => [$file],
            ]);

        $response->assertRedirect(route('coach.profile'));
        $response->assertSessionHas('success');

        $this->assertEquals(1, $coachProfile->getMedia('certificates')->count());
    }

    public function test_coach_can_delete_avatar(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create(['user_id' => $coach->id]);

        $file = UploadedFile::fake()->image('avatar.jpg');
        $coach->addMedia($file)->toMediaCollection('avatar');

        $this->assertEquals(1, $coach->getMedia('avatar')->count());

        $response = $this
            ->actingAs($coach)
            ->delete(route('coach.profile.deleteAvatar'));

        $response->assertRedirect(route('coach.profile'));
        $response->assertSessionHas('success');

        $coach->refresh();
        $this->assertEquals(0, $coach->getMedia('avatar')->count());
    }

    public function test_coach_can_delete_diploma(): void
    {
        $coach = User::factory()->coach()->create();
        $coachProfile = CoachProfile::factory()->create(['user_id' => $coach->id]);

        $file = UploadedFile::fake()->image('diploma.jpg');
        $media = $coachProfile->addMedia($file)->toMediaCollection('diplomas');

        $this->assertEquals(1, $coachProfile->getMedia('diplomas')->count());

        $response = $this
            ->actingAs($coach)
            ->delete(route('coach.profile.deleteDiploma', $media->id));

        $response->assertRedirect(route('coach.profile'));
        $response->assertSessionHas('success');

        $coachProfile->refresh();
        $this->assertEquals(0, $coachProfile->getMedia('diplomas')->count());
    }

    public function test_coach_can_delete_certificate(): void
    {
        $coach = User::factory()->coach()->create();
        $coachProfile = CoachProfile::factory()->create(['user_id' => $coach->id]);

        $file = UploadedFile::fake()->create('certificate.pdf', 1000);
        $media = $coachProfile->addMedia($file)->toMediaCollection('certificates');

        $this->assertEquals(1, $coachProfile->getMedia('certificates')->count());

        $response = $this
            ->actingAs($coach)
            ->delete(route('coach.profile.deleteCertificate', $media->id));

        $response->assertRedirect(route('coach.profile'));
        $response->assertSessionHas('success');

        $coachProfile->refresh();
        $this->assertEquals(0, $coachProfile->getMedia('certificates')->count());
    }

    public function test_diploma_upload_validates_file_type(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create(['user_id' => $coach->id]);

        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this
            ->actingAs($coach)
            ->post(route('coach.profile.uploadDiploma'), [
                'diplomas' => [$file],
            ]);

        $response->assertSessionHasErrors('diplomas.0');
    }

    public function test_diploma_upload_validates_file_size(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create(['user_id' => $coach->id]);

        $file = UploadedFile::fake()->image('diploma.jpg')->size(11000);

        $response = $this
            ->actingAs($coach)
            ->post(route('coach.profile.uploadDiploma'), [
                'diplomas' => [$file],
            ]);

        $response->assertSessionHasErrors('diplomas.0');
    }

    public function test_coach_can_resubmit_rejected_profile(): void
    {
        $coach = User::factory()->coach()->create();
        $coachProfile = CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'moderation_status' => 'rejected',
            'rejection_reason' => 'Некорректные данные в профиле',
        ]);

        $response = $this
            ->actingAs($coach)
            ->post(route('coach.profile.resubmit'));

        $response->assertRedirect(route('coach.dashboard'));
        $response->assertSessionHas('success');

        $coachProfile->refresh();
        $this->assertEquals('pending', $coachProfile->moderation_status);
        $this->assertNull($coachProfile->rejection_reason);
    }

    public function test_coach_cannot_resubmit_pending_profile(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'moderation_status' => 'pending',
        ]);

        $response = $this
            ->actingAs($coach)
            ->post(route('coach.profile.resubmit'));

        $response->assertRedirect(route('coach.dashboard'));
        $response->assertSessionHasErrors('profile');
    }

    public function test_coach_cannot_resubmit_approved_profile(): void
    {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'moderation_status' => 'approved',
        ]);

        $response = $this
            ->actingAs($coach)
            ->post(route('coach.profile.resubmit'));

        $response->assertRedirect(route('coach.dashboard'));
        $response->assertSessionHasErrors('profile');
    }

    public function test_moderation_status_shared_with_coach_in_inertia(): void
    {
        $city = City::factory()->create();
        $sport = Sport::factory()->create(['is_active' => true]);
        $coach = User::factory()->coach()->create(['city_id' => $city->id]);
        $coachProfile = CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'bio' => 'Опытный тренер',
            'moderation_status' => 'pending',
        ]);
        $coachProfile->sports()->attach($sport->id);

        $response = $this
            ->actingAs($coach)
            ->get(route('coach.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('auth.coachProfile.moderation_status', 'pending')
        );
    }

    public function test_rejection_reason_shared_with_coach_when_rejected(): void
    {
        $city = City::factory()->create();
        $sport = Sport::factory()->create(['is_active' => true]);
        $coach = User::factory()->coach()->create(['city_id' => $city->id]);
        $coachProfile = CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'bio' => 'Опытный тренер',
            'moderation_status' => 'rejected',
            'rejection_reason' => 'Недостаточно данных',
        ]);
        $coachProfile->sports()->attach($sport->id);

        $response = $this
            ->actingAs($coach)
            ->get(route('coach.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('auth.coachProfile.moderation_status', 'rejected')
            ->where('auth.coachProfile.rejection_reason', 'Недостаточно данных')
        );
    }
}
