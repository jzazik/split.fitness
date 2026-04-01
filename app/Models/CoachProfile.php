<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CoachProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'experience_years',
        'rating_avg',
        'rating_count',
        'moderation_status',
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
}
