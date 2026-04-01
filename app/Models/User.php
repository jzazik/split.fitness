<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'email',
        'password',
        'phone',
        'first_name',
        'last_name',
        'middle_name',
        'avatar_path',
        'city_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'full_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'status' => 'string',
        ];
    }

    /**
     * Get the user's full name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => implode(' ', array_filter(
                array_map(
                    fn ($value) => $value !== null ? trim($value) : '',
                    [
                        $this->last_name,
                        $this->first_name,
                        $this->middle_name,
                    ]
                ),
                fn ($value) => ! empty($value)
            ))
        );
    }

    /**
     * Get the city that the user belongs to.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
