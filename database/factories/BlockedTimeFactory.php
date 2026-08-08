<?php

namespace Database\Factories;

use App\Models\BlockedTime;
use App\Models\Calendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedTime>
 */
class BlockedTimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'calendar_id' => Calendar::factory(),
            'starts_at' => now()->addDay()->startOfHour(),
            'ends_at' => now()->addDay()->addHour()->startOfHour(),
            'reason' => fake()->words(3, true),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
