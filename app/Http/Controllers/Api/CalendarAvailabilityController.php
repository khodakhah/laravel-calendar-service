<?php

namespace App\Http\Controllers\Api;

use App\Actions\GenerateAvailabilitySlots;
use App\Http\Controllers\Controller;
use App\Http\Requests\FindAvailabilityRequest;
use App\Models\Calendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class CalendarAvailabilityController extends Controller
{
    public function __invoke(
        FindAvailabilityRequest $request,
        Calendar $calendar,
        GenerateAvailabilitySlots $generateAvailabilitySlots,
    ): JsonResponse {
        $data = $request->validated();
        $timezone = $data['timezone'] ?? $calendar->timezone;
        $startsAt = CarbonImmutable::parse($data['starts_at'])->setTimezone($timezone);
        $endsAt = CarbonImmutable::parse($data['ends_at'])->setTimezone($timezone);
        $durationMinutes = $data['duration_minutes'];
        $slotIntervalMinutes = $data['slot_interval_minutes'] ?? 15;

        return response()->json([
            'data' => $generateAvailabilitySlots->execute(
                $calendar->load('workingHours'),
                $startsAt,
                $endsAt,
                $durationMinutes,
                $slotIntervalMinutes,
            ),
            'meta' => [
                'calendar_id' => $calendar->id,
                'timezone' => $timezone,
                'starts_at' => $startsAt->toIso8601String(),
                'ends_at' => $endsAt->toIso8601String(),
                'duration_minutes' => $durationMinutes,
                'slot_interval_minutes' => $slotIntervalMinutes,
            ],
        ]);
    }
}
