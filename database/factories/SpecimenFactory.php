<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\Specimen;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Specimen>
 */
class SpecimenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'occurrence_id' => (string) Str::uuid(),
            'catalog_number' => fake()->unique()->bothify('CAT-####'),
            'scientific_name' => fake()->optional()->words(2, true),
            'basis_of_record' => fake()->optional()->randomElement([
                'PreservedSpecimen',
                'HumanObservation',
                'FossilSpecimen',
            ]),
            'type_status' => fake()->optional()->randomElement([
                'holotype',
                'paratype',
                'lectotype',
            ]),
            'individual_count' => fake()->optional()->numberBetween(1, 20),
            'source_modified_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
