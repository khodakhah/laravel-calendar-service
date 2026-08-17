<?php

namespace App\Actions;

use App\Models\Calendar;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReplaceEvent
{
    public function __construct(
        private ArchiveEventVersion $archiveEventVersion,
        private EnsureEventFitsCalendarAvailability $ensureEventFitsCalendarAvailability,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Event $event, array $data): Event
    {
        return DB::transaction(function () use ($event, $data): Event {
            $event = Event::query()
                ->lockForUpdate()
                ->findOrFail($event->getKey());

            $startsAt = CarbonImmutable::parse($data['starts_at'] ?? $event->starts_at)->utc();
            $endsAt = CarbonImmutable::parse($data['ends_at'] ?? $event->ends_at)->utc();
            $blocksAvailability = $data['blocks_availability'] ?? $event->blocks_availability;

            $calendar = Calendar::query()
                ->lockForUpdate()
                ->findOrFail($event->calendar_id);

            $this->ensureEventFitsCalendarAvailability->execute($calendar, $startsAt, $endsAt);

            if ($blocksAvailability && Event::query()
                ->where('calendar_id', $event->calendar_id)
                ->whereKeyNot($event->getKey())
                ->blocksAvailability()
                ->overlapping($startsAt, $endsAt)
                ->exists()) {
                throw ValidationException::withMessages([
                    'starts_at' => 'The calendar is unavailable for the selected time.',
                ]);
            }

            $this->archiveEventVersion->execute($event);

            $event->update([
                ...$data,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_minutes' => (int) $startsAt->diffInMinutes($endsAt),
                'revision' => $event->revision + 1,
            ]);

            return $event->fresh();
        });
    }
}
