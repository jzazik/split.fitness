<?php

namespace Tests\Feature;

use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_register_avatar_collection(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->getMediaCollection('avatar'));
    }

    public function test_user_can_upload_avatar(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500)->size(1024); // 1MB

        $media = $user->addMedia($file)->toMediaCollection('avatar');

        $this->assertNotNull($media);
        $this->assertEquals('avatar', $media->collection_name);
        $this->assertTrue($user->hasMedia('avatar'));
    }

    public function test_avatar_collection_is_single_file(): void
    {
        $user = User::factory()->create();

        $file1 = UploadedFile::fake()->image('avatar1.jpg');
        $file2 = UploadedFile::fake()->image('avatar2.jpg');

        $user->addMedia($file1)->toMediaCollection('avatar');
        $user->addMedia($file2)->toMediaCollection('avatar');

        $this->assertEquals(1, $user->getMedia('avatar')->count());
    }

    public function test_avatar_accepts_valid_image_types(): void
    {
        $user = User::factory()->create();

        $validTypes = ['jpg', 'png', 'gif', 'webp'];

        foreach ($validTypes as $type) {
            $file = UploadedFile::fake()->image("avatar.{$type}");
            $media = $user->addMedia($file)->toMediaCollection('avatar');
            $this->assertNotNull($media);
        }
    }

    public function test_coach_profile_can_register_diplomas_collection(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $coach->id]);

        $this->assertNotNull($profile->getMediaCollection('diplomas'));
    }

    public function test_coach_profile_can_register_certificates_collection(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $coach->id]);

        $this->assertNotNull($profile->getMediaCollection('certificates'));
    }

    public function test_coach_profile_can_upload_multiple_diplomas(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $coach->id]);

        $file1 = UploadedFile::fake()->image('diploma1.jpg');
        $file2 = UploadedFile::fake()->create('diploma2.pdf', 1024, 'application/pdf');

        $profile->addMedia($file1)->toMediaCollection('diplomas');
        $profile->addMedia($file2)->toMediaCollection('diplomas');

        $this->assertEquals(2, $profile->getMedia('diplomas')->count());
    }

    public function test_coach_profile_can_upload_multiple_certificates(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $coach->id]);

        $file1 = UploadedFile::fake()->image('cert1.jpg');
        $file2 = UploadedFile::fake()->create('cert2.pdf', 1024, 'application/pdf');

        $profile->addMedia($file1)->toMediaCollection('certificates');
        $profile->addMedia($file2)->toMediaCollection('certificates');

        $this->assertEquals(2, $profile->getMedia('certificates')->count());
    }

    public function test_diplomas_accept_valid_file_types(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $coach->id]);

        $validTypes = [
            'jpg' => ['method' => 'image', 'mime' => null],
            'png' => ['method' => 'image', 'mime' => null],
            'gif' => ['method' => 'image', 'mime' => null],
            'webp' => ['method' => 'image', 'mime' => null],
            'pdf' => ['method' => 'create', 'mime' => 'application/pdf'],
        ];

        foreach ($validTypes as $ext => $config) {
            $file = $config['method'] === 'image'
                ? UploadedFile::fake()->image("diploma.{$ext}")
                : UploadedFile::fake()->create("diploma.{$ext}", 1024, $config['mime']);

            $media = $profile->addMedia($file)->toMediaCollection('diplomas');
            $this->assertNotNull($media);
        }
    }

    public function test_certificates_accept_valid_file_types(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $coach->id]);

        $validTypes = [
            'jpg' => ['method' => 'image', 'mime' => null],
            'png' => ['method' => 'image', 'mime' => null],
            'pdf' => ['method' => 'create', 'mime' => 'application/pdf'],
        ];

        foreach ($validTypes as $ext => $config) {
            $file = $config['method'] === 'image'
                ? UploadedFile::fake()->image("cert.{$ext}")
                : UploadedFile::fake()->create("cert.{$ext}", 1024, $config['mime']);

            $media = $profile->addMedia($file)->toMediaCollection('certificates');
            $this->assertNotNull($media);
        }
    }

    public function test_media_can_be_deleted(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.jpg');
        $media = $user->addMedia($file)->toMediaCollection('avatar');

        $this->assertTrue($user->hasMedia('avatar'));

        $media->delete();
        $user->refresh();

        $this->assertFalse($user->hasMedia('avatar'));
    }
}
