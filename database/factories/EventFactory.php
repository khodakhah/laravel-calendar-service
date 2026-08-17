<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
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
            'title' => fake()->sentence(3),
            'status' => 'pending',
            'revision' => 1,
            'type' => 'single',
            'timezone' => 'Europe/Berlin',
            'starts_at' => now()->addDay()->startOfHour(),
            'ends_at' => now()->addDay()->addHour()->startOfHour(),
            'all_day' => false,
            'blocks_availability' => true,
            'duration_minutes' => 60,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'sources' => [],
            'assignees' => [],
            'metadata' => [],
        ];
    }
}
