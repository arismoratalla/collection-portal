<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $first = fake()->firstName();
        $middle = fake()->optional()->firstName();
        $last = fake()->lastName();

        return [
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'full_name' => trim(implode(' ', array_filter([$first, $middle, $last]))),
            'organization' => fake()->optional()->company(),
            'orcid' => fake()->optional()->numerify('0000-0000-0000-0000'),
        ];
    }
}
