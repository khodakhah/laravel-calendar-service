<?php

namespace App\Actions;

use App\Models\Calendar;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ReplaceCalendar
{
    /**
     * @param  array{
     *     name: string,
     *     owner_id: string,
     *     type: string,
     *     timezone: string,
     *     working_hours: array<int, array{day_of_week: string, start_time: string, end_time: string}>,
     *     blocked_times?: array<int, array{starts_at: string, ends_at: string, reason: string, notes?: ?string}>
     * }  $data
     */
    public function execute(Calendar $calendar, array $data): Calendar
    {
        return DB::transaction(function () use ($calendar, $data): Calendar {
            $calendar = Calendar::query()
                ->lockForUpdate()
                ->findOrFail($calendar->getKey());

            $calendar->update(Arr::except($data, ['working_hours', 'blocked_times']));
            $calendar->workingHours()->delete();
            $calendar->workingHours()->createMany($data['working_hours']);

            if (array_key_exists('blocked_times', $data)) {
                $calendar->blockedTimes()->delete();
                $calendar->blockedTimes()->createMany($data['blocked_times']);
            }

            return $calendar->load(['workingHours', 'blockedTimes']);
        });
    }
}
