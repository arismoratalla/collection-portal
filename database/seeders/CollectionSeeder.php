<?php

namespace Database\Seeders;

use App\Models\Collection as CollectionModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $collections = [
            ['name' => 'Fish', 'slug' => 'fish'],
            ['name' => 'Mollusk', 'slug' => 'mollusk'],
            ['name' => 'Non-Mollusk', 'slug' => 'non-mollusk'],
            ['name' => 'Herpetology', 'slug' => 'herps'],
            ['name' => 'Mammals', 'slug' => 'mammals'],
            ['name' => 'Birds', 'slug' => 'birds'],
        ];

        foreach ($collections as $collection) {
            CollectionModel::query()->updateOrCreate(
                ['slug' => $collection['slug']],
                [
                    'name' => $collection['name'],
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
