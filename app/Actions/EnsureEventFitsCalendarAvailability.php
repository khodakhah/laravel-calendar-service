<?php

namespace App\Actions;

use App\Models\Calendar;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class EnsureEventFitsCalendarAvailability
{
    public function execute(Calendar $calendar, CarbonImmutable $startsAt, CarbonImmutable $endsAt): void
    {
        $localStartsAt = $startsAt->setTimezone($calendar->timezone);
        $localEndsAt = $endsAt->setTimezone($calendar->timezone);

        if ($localStartsAt->toDateString() !== $localEndsAt->toDateString()) {
            $this->reject();
        }

        $workingHour = $calendar->workingHours()
            ->where('day_of_week', strtolower($localStartsAt->dayName))
            ->first();

        if ($workingHour === null) {
            $this->reject();
        }

        $workingStartsAt = CarbonImmutable::parse(
            $localStartsAt->toDateString().' '.$workingHour->start_time,
            $calendar->timezone,
        );
        $workingEndsAt = CarbonImmutable::parse(
            $localStartsAt->toDateString().' '.$workingHour->end_time,
            $calendar->timezone,
        );

        if ($localStartsAt->lessThan($workingStartsAt) || $localEndsAt->greaterThan($workingEndsAt)) {
            $this->reject();
        }

        if ($calendar->blockedTimes()
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists()) {
            $this->reject();
        }
    }

    private function reject(): never
    {
        throw ValidationException::withMessages([
            'starts_at' => 'The calendar is unavailable for the selected time.',
        ]);
    }
}
