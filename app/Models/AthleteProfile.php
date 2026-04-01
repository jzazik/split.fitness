<?php

namespace App\Models;

use Database\Factories\AthleteProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AthleteProfile extends Model
{
    /** @use HasFactory<AthleteProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'emergency_contact',
    ];

    protected function casts(): array
    {
        return [
            'emergency_contact' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
