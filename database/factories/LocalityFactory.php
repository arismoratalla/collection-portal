<?php

namespace Database\Factories;

use App\Models\Geography;
use App\Models\Locality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Locality>
 */
class LocalityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'geography_id' => Geography::factory(),
            'verbatim_locality' => fake()->optional()->sentence(),
            'locality' => fake()->optional()->sentence(),
            'decimal_latitude' => fake()->randomFloat(7, -90, 90),
            'decimal_longitude' => fake()->randomFloat(7, -180, 180),
            'coordinate_uncertainty_meters' => fake()->optional()->randomFloat(2, 0, 1000),
            'geodetic_datum' => fake()->optional()->randomElement(['WGS84', 'NAD83']),
            'georeference_sources' => fake()->optional()->sentence(),
            'georeference_remarks' => fake()->optional()->sentence(),
            'water_body' => fake()->optional()->word(),
            'verbatim_depth' => fake()->optional()->randomElement(['3 m', '10-15 m', 'shallow']),
            'footprint_wkt' => fake()->optional()->randomElement([
                'POINT(-77.7654321 35.1234567)',
                'POLYGON((-77.8 35.1,-77.7 35.1,-77.7 35.2,-77.8 35.2,-77.8 35.1))',
            ]),
            'georeferenced_by' => fake()->optional()->name(),
            'georeferenced_date' => fake()->optional()->date(),
            'georeference_protocol' => fake()->optional()->sentence(),
            'coordinates_public' => true,
            'sensitive' => false,
        ];
    }
}
