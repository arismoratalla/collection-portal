<?php

namespace Database\Factories;

use App\Models\CollectingEvent;
use App\Models\Locality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectingEvent>
 */
class CollectingEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventDate = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'locality_id' => Locality::factory(),
            'field_number' => fake()->optional()->bothify('FN-####'),
            'event_year' => $eventDate->format('Y'),
            'event_month' => $eventDate->format('n'),
            'event_day' => $eventDate->format('j'),
            'event_date' => $eventDate,
            'event_date_start' => $eventDate,
            'event_date_end' => $eventDate,
            'verbatim_event_date' => fake()->optional()->date('F j, Y'),
            'sampling_protocol' => fake()->optional()->sentence(),
            'habitat' => fake()->optional()->sentence(),
            'field_notes' => fake()->optional()->paragraph(),
        ];
    }
}
