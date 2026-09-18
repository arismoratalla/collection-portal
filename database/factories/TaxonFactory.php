<?php

namespace Database\Factories;

use App\Models\Taxon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Taxon>
 */
class TaxonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scientificName = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'scientific_name' => $scientificName,
            'canonical_name' => fake()->optional()->words(2, true),
            'authorship' => fake()->optional()->name(),
            'rank' => fake()->randomElement(['species', 'genus', 'family']),
            'kingdom' => 'Animalia',
            'phylum' => fake()->optional()->word(),
            'class_name' => fake()->optional()->word(),
            'order_name' => fake()->optional()->word(),
            'family' => fake()->optional()->word(),
            'genus' => fake()->optional()->word(),
            'specific_epithet' => fake()->optional()->word(),
            'infraspecific_epithet' => fake()->optional()->word(),
            'vernacular_name' => fake()->optional()->words(2, true),
            'source' => fake()->optional()->randomElement(['catalog', 'specify', 'gbif']),
            'source_identifier' => fake()->optional()->uuid(),
            'is_accepted' => true,
        ];
    }
}
