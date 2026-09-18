<?php

namespace Database\Factories;

use App\Models\Preparation;
use App\Models\Specimen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Preparation>
 */
class PreparationFactory extends Factory
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
            'preparation_type' => fake()->randomElement(['tissue', 'skeleton', 'fluid-preserved']),
            'count' => fake()->optional()->numberBetween(1, 10),
            'storage_medium' => fake()->optional()->randomElement(['ethanol', 'dry', 'frozen']),
            'storage_location' => fake()->optional()->bothify('Cabinet ##-##'),
            'source_value' => fake()->optional()->randomElement(['3 EtOH70', 'dry', 'EtOH70']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
