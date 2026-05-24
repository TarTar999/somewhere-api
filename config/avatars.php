<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available Lottie Avatars
    |--------------------------------------------------------------------------
    |
    | List of available Lottie avatar identifiers. The actual Lottie JSON files
    | are stored on the frontend. This list is used for validation and reference.
    |
    */
    'avatars' => [
        'avatar_1' => ['name' => 'Default', 'color' => '#3B82F6'],
        'avatar_2' => ['name' => 'Happy', 'color' => '#10B981'],
        'avatar_3' => ['name' => 'Cool', 'color' => '#8B5CF6'],
        'avatar_4' => ['name' => 'Ninja', 'color' => '#1F2937'],
        'avatar_5' => ['name' => 'Robot', 'color' => '#6B7280'],
        'avatar_6' => ['name' => 'Cat', 'color' => '#F59E0B'],
        'avatar_7' => ['name' => 'Dog', 'color' => '#92400E'],
        'avatar_8' => ['name' => 'Astronaut', 'color' => '#1E40AF'],
        'avatar_9' => ['name' => 'Pirate', 'color' => '#DC2626'],
        'avatar_10' => ['name' => 'Chef', 'color' => '#FBBF24'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Animations
    |--------------------------------------------------------------------------
    |
    | List of animation identifiers that can be randomly selected when
    | displaying an avatar on the home page. These correspond to Lottie
    | animation files on the frontend.
    |
    */
    'animations' => [
        'wave',           // Hand wave greeting
        'bounce',         // Bouncing effect
        'spin',           // 360 degree spin
        'jump',           // Jump up and down
        'dance',          // Dance move
        'nod',            // Head nod
        'wink',           // Wink animation
        'thumbs_up',      // Thumbs up gesture
        'heart',          // Heart eyes / love
        'celebrate',      // Celebration / confetti
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Avatar
    |--------------------------------------------------------------------------
    |
    | The default avatar identifier assigned to new users.
    |
    */
    'default' => 'avatar_1',
];
