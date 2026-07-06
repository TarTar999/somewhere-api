<?php

namespace Database\Seeders;

use App\Models\LieuDit;
use Illuminate\Database\Seeder;

class LieuxDitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lieuxDits = $this->getLieuxDits();

        foreach ($lieuxDits as $data) {
            LieuDit::updateOrCreate(
                [
                    'name_normalized' => LieuDit::normalize($data['name']),
                    'city' => $data['city'] ?? null,
                ],
                [
                    'name' => $data['name'],
                    'region' => $data['region'] ?? null,
                    'is_verified' => true,
                    'is_system' => true,
                    'usage_count' => 0,
                ]
            );
        }

        $this->command->info('Imported ' . count($lieuxDits) . ' lieux-dits.');
    }

    /**
     * Get the list of lieux-dits to import
     * Format: ['name' => 'Nom', 'city' => 'Ville', 'region' => 'Region']
     */
    protected function getLieuxDits(): array
    {
        return [
            // TODO: Add lieux-dits data here
            // Example format:
            // ['name' => 'Bonamoussadi', 'city' => 'Douala', 'region' => 'Littoral'],
            // ['name' => 'Akwa', 'city' => 'Douala', 'region' => 'Littoral'],
        ];
    }
}
