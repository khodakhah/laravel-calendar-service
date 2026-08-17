<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventChangeLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventChangeLog>
 */
class EventChangeLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => fake()->uuid(),
            'revision' => 1,
            'snapshot' => Event::factory()->make()->attributesToArray(),
            'archived_at' => now(),
        ];
    }
}
