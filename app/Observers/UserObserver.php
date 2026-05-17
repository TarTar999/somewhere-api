<?php

namespace App\Observers;

use App\Models\Collection;
use App\Models\User;
use App\Models\UserSettings;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Create default settings
        UserSettings::create([
            'user_id' => $user->id,
            'language' => 'fr',
            'unit' => 'metric',
            'notifications' => 'enabled',
            'map_type' => 'GoogleMap',
        ]);
        // Create favorites collection
        Collection::create([
            'owner_id' => $user->id,
            'name' => 'Favoris',
            'slug' => 'favorites-' . $user->id,
            'description' => 'Vos adresses favorites',
            'type' => 'system',
            'icon' => 'heart',
            'color' => '#EF4444',
        ]);
    }
}
