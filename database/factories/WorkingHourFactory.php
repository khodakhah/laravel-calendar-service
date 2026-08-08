<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\WorkingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkingHour>
 */
class WorkingHourFactory extends Factory
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
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ];
    }
}
