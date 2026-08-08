<?php

namespace App\Http\Resources;

use App\Models\Calendar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Calendar */
class CalendarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner_id' => $this->owner_id,
            'type' => $this->type,
            'timezone' => $this->timezone,
            'working_hours' => $this->whenLoaded('workingHours', fn () => $this->workingHours
                ->map(fn ($workingHour): array => [
                    'day_of_week' => $workingHour->day_of_week,
                    'start_time' => substr((string) $workingHour->start_time, 0, 5),
                    'end_time' => substr((string) $workingHour->end_time, 0, 5),
                ])->values()),
            'blocked_times' => $this->whenLoaded('blockedTimes', fn () => BlockedTimeResource::collection($this->blockedTimes)),
        ];
    }
}
