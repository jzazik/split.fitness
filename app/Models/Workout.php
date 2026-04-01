<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'coach_id',
        'sport_id',
        'city_id',
        'title',
        'description',
        'location_name',
        'address',
        'lat',
        'lng',
        'starts_at',
        'duration_minutes',
        'total_price',
        'slot_price',
        'slots_total',
        'slots_booked',
        'status',
        'published_at',
        'cancelled_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'status_for_unique_check',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'published_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => 'string',
            'lat' => 'decimal:8',
            'lng' => 'decimal:8',
            'total_price' => 'decimal:2',
            'slot_price' => 'decimal:2',
        ];
    }

    /**
     * Get the coach (user) that owns the workout.
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    /**
     * Get the sport that the workout belongs to.
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Get the city that the workout belongs to.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the bookings for the workout.
     * Note: Booking model will be added in Sprint 4
     */
    // public function bookings(): HasMany
    // {
    //     return $this->hasMany(Booking::class);
    // }

    /**
     * Check if the workout is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if the workout is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
