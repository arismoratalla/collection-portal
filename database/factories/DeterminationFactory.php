<?php

namespace Database\Factories;

use App\Models\Determination;
use App\Models\Specimen;
use App\Models\Taxon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Determination>
 */
class DeterminationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'specimen_id' => Specimen::factory(),
            'taxon_id' => Taxon::factory(),
            'determiner_name' => fake()->optional()->name(),
            'determined_at' => fake()->optional()->date(),
            'remarks' => fake()->optional()->sentence(),
            'is_current' => false,
        ];
    }
}
