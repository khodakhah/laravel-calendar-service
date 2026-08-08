<?php

namespace App\Actions;

use App\Models\Calendar;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class GenerateAvailabilitySlots
{
    /**
     * @return array<int, array{starts_at: string, ends_at: string, duration_minutes: int}>
     */
    public function execute(
        Calendar $calendar,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        int $durationMinutes,
        int $slotIntervalMinutes,
    ): array {
        /** @var Collection<string, WorkingHour> $workingHours */
        $workingHours = $calendar->workingHours->keyBy('day_of_week');
        $blockedTimes = $calendar->blockedTimes()
            ->where('starts_at', '<', $endsAt->utc())
            ->where('ends_at', '>', $startsAt->utc())
            ->get();
        $slots = [];

        for ($date = $startsAt->startOfDay(); $date->lessThanOrEqualTo($endsAt->startOfDay()); $date = $date->addDay()) {
            $workingHour = $workingHours->get(strtolower($date->dayName));

            if ($workingHour === null) {
                continue;
            }

            $workStartsAt = CarbonImmutable::parse($date->toDateString().' '.$workingHour->start_time, $date->timezone);
            $workEndsAt = CarbonImmutable::parse($date->toDateString().' '.$workingHour->end_time, $date->timezone);
            $slotStartsAt = $workStartsAt->greaterThan($startsAt) ? $workStartsAt : $startsAt;

            while ($slotStartsAt->addMinutes($durationMinutes)->lessThanOrEqualTo($workEndsAt)
                && $slotStartsAt->addMinutes($durationMinutes)->lessThanOrEqualTo($endsAt)) {
                $slotEndsAt = $slotStartsAt->addMinutes($durationMinutes);
                $overlapsBlockedTime = $blockedTimes->contains(
                    fn ($blockedTime): bool => $blockedTime->starts_at->lessThan($slotEndsAt)
                        && $blockedTime->ends_at->greaterThan($slotStartsAt),
                );

                if (! $overlapsBlockedTime) {
                    $slots[] = [
                        'starts_at' => $slotStartsAt->toIso8601String(),
                        'ends_at' => $slotEndsAt->toIso8601String(),
                        'duration_minutes' => $durationMinutes,
                    ];
                }

                $slotStartsAt = $slotStartsAt->addMinutes($slotIntervalMinutes);
            }
        }

        return $slots;
    }
}
