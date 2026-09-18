<?php

namespace Database\Factories;

use App\Models\Geography;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Geography>
 */
class GeographyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'name' => fake()->unique()->city(),
            'geography_type' => fake()->randomElement(['country', 'state', 'county', 'municipality', 'continent']),
            'iso_code' => fake()->optional()->countryCode(),
        ];
    }
}
