<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'country_code',
        'lat',
        'lng',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
