<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Calendar;
use App\Models\Event;
use App\Models\EventChangeLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = ApiKey::create([
            'name' => 'Event API test',
            'token_hash' => Hash::make($secret = str_repeat('a', 43)),
        ]);

        $this->withToken("lcs_{$apiKey->id}.{$secret}");
    }

    public function test_event_updates_and_deletion_archive_the_previous_latest_revision(): void
    {
        $calendar = $this->availableCalendar();
        $payload = [
            'calendar_id' => $calendar->id,
            'title' => 'Initial event',
            'timezone' => 'Europe/Berlin',
            'starts_at' => '2026-08-24T09:00:00+02:00',
            'ends_at' => '2026-08-24T10:30:00+02:00',
            'metadata' => ['priority' => 'high'],
        ];

        $this->postJson('/api/events', $payload)
            ->assertCreated()
            ->assertJsonPath('data.revision', 1)
            ->assertJsonPath('data.duration_minutes', 90)
            ->assertJsonPath('data.blocks_availability', true);

        $event = Event::firstOrFail();

        $this->getJson("/api/events?calendar_id={$calendar->id}&metadata%5Bpriority%5D=high")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $event->id);

        $this->patchJson("/api/events/{$event->id}", [
            'title' => 'Rescheduled event',
            'updated_reason' => 'Customer requested a later time.',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Rescheduled event')
            ->assertJsonPath('data.revision', 2);

        $this->assertDatabaseHas('event_change_log', [
            'event_id' => $event->id,
            'revision' => 1,
        ]);
        $this->assertSame('Initial event', EventChangeLog::firstOrFail()->snapshot['title']);

        $this->patchJson("/api/events/{$event->id}", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.revision', 3);

        $this->deleteJson("/api/events/{$event->id}")
            ->assertNoContent();

        $this->assertModelMissing($event);
        $this->assertSame(3, EventChangeLog::query()->where('event_id', $event->id)->count());
        $this->assertSame(
            'confirmed',
            EventChangeLog::query()->where('event_id', $event->id)->where('revision', 3)->sole()->snapshot['status'],
        );

        $this->getJson("/api/events/{$event->id}?revision=2")
            ->assertOk()
            ->assertJsonPath('data.title', 'Rescheduled event')
            ->assertJsonPath('data.revision', 2);
    }

    public function test_event_requires_an_end_after_its_start(): void
    {
        $calendar = Calendar::factory()->create();

        $this->postJson('/api/events', [
            'calendar_id' => $calendar->id,
            'title' => 'Invalid event',
            'timezone' => 'Europe/Berlin',
            'starts_at' => '2026-08-24T10:00:00+02:00',
            'ends_at' => '2026-08-24T09:00:00+02:00',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_busy_events_cannot_overlap_but_non_busy_events_can(): void
    {
        $calendar = $this->availableCalendar();
        $busyEvent = [
            'calendar_id' => $calendar->id,
            'title' => 'Busy event',
            'timezone' => 'Europe/Berlin',
            'starts_at' => '2026-08-24T09:00:00+02:00',
            'ends_at' => '2026-08-24T10:00:00+02:00',
        ];

        $this->postJson('/api/events', $busyEvent)->assertCreated();

        $this->postJson('/api/events', [
            ...$busyEvent,
            'title' => 'Conflicting busy event',
            'starts_at' => '2026-08-24T09:30:00+02:00',
            'ends_at' => '2026-08-24T10:30:00+02:00',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('starts_at');

        $this->postJson('/api/events', [
            ...$busyEvent,
            'title' => 'Overlapping non-busy event',
            'blocks_availability' => false,
        ])
            ->assertCreated()
            ->assertJsonPath('data.blocks_availability', false);

        $nonBlockingEvent = Event::query()->where('blocks_availability', false)->sole();

        $this->patchJson("/api/events/{$nonBlockingEvent->id}", ['blocks_availability' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('starts_at');

        $this->assertFalse($nonBlockingEvent->fresh()->blocks_availability);
    }

    public function test_events_must_fit_within_working_hours_and_avoid_blocked_times(): void
    {
        $calendar = $this->availableCalendar();
        $calendar->blockedTimes()->create([
            'starts_at' => '2026-08-24T10:00:00+02:00',
            'ends_at' => '2026-08-24T11:00:00+02:00',
            'reason' => 'Maintenance',
        ]);
        $payload = [
            'calendar_id' => $calendar->id,
            'title' => 'Event',
            'timezone' => 'Europe/Berlin',
        ];

        $this->postJson('/api/events', [
            ...$payload,
            'starts_at' => '2026-08-24T08:30:00+02:00',
            'ends_at' => '2026-08-24T09:30:00+02:00',
            'blocks_availability' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('starts_at');

        $this->postJson('/api/events', [
            ...$payload,
            'starts_at' => '2026-08-24T09:30:00+02:00',
            'ends_at' => '2026-08-24T10:30:00+02:00',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('starts_at');

        $event = $calendar->events()->create([
            'title' => 'In-hours event',
            'timezone' => 'Europe/Berlin',
            'starts_at' => '2026-08-24T07:00:00Z',
            'ends_at' => '2026-08-24T08:00:00Z',
            'duration_minutes' => 60,
        ]);

        $this->patchJson("/api/events/{$event->id}", [
            'starts_at' => '2026-08-24T16:30:00+02:00',
            'ends_at' => '2026-08-24T17:30:00+02:00',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('starts_at');
    }

    private function availableCalendar(): Calendar
    {
        $calendar = Calendar::factory()->create(['timezone' => 'Europe/Berlin']);
        $calendar->workingHours()->create([
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        return $calendar;
    }
}
