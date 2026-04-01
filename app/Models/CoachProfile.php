<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CoachProfile extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $fillable = [
        'user_id',
        'bio',
        'experience_years',
        'rating_avg',
        'rating_count',
        'moderation_status',
        'rejection_reason',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
            'moderation_status' => 'string',
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sports(): BelongsToMany
    {
        return $this->belongsToMany(Sport::class, 'coach_sports');
    }

    /**
     * Register media collections for the coach profile.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('diplomas');

        $this->addMediaCollection('certificates');
    }
}
