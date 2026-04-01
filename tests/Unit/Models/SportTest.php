<?php

namespace Tests\Unit\Models;

use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sport_has_fillable_attributes(): void
    {
        $sport = new Sport;
        $fillable = $sport->getFillable();

        $this->assertContains('slug', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('icon', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    public function test_sport_can_be_created(): void
    {
        $sport = Sport::create([
            'slug' => 'running',
            'name' => 'Бег',
            'icon' => null,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('sports', [
            'slug' => 'running',
            'name' => 'Бег',
            'is_active' => true,
        ]);
    }

    public function test_sport_is_active_is_cast_to_boolean(): void
    {
        $sport = Sport::create([
            'slug' => 'running',
            'name' => 'Бег',
            'is_active' => true,
        ]);

        $this->assertIsBool($sport->is_active);
        $this->assertTrue($sport->is_active);
    }

    public function test_sport_is_active_defaults_to_true(): void
    {
        $sport = Sport::create([
            'slug' => 'yoga',
            'name' => 'Йога',
        ]);

        $sport->refresh();
        $this->assertTrue($sport->is_active);
    }
}
