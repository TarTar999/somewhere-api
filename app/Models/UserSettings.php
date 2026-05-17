<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSettings extends Model
{
    protected $fillable = [
        'user_id',
        'language',
        'unit',
        'notifications',
        'map_type',
        'proof_of_residence',
        'proof_of_residence_date',
        'google_search',
        'is_city_mapper',
        'dark_mode',
    ];

    protected function casts(): array
    {
        return [
            'proof_of_residence_date' => 'datetime',
            'google_search' => 'boolean',
            'is_city_mapper' => 'boolean',
            'dark_mode' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
