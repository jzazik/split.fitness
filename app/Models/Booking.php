<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'workout_id',
        'athlete_id',
        'slots_count',
        'slot_price',
        'total_amount',
        'status',
        'payment_status',
        'booked_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => 'string',
            'payment_status' => 'string',
            'slot_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * Get the workout that the booking belongs to.
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * Get the athlete (user) that owns the booking.
     */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
