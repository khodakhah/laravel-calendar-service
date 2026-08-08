<?php

namespace Tests\Feature;

use App\Models\BlockedTime;
use App\Models\Calendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_crud_and_filters_follow_the_api_contract(): void
    {
        $payload = $this->calendarPayload();

        $this->postJson('/api/calendars', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', $payload['name'])
            ->assertJsonPath('data.working_hours.0.day_of_week', 'monday');

        $calendar = Calendar::firstOrFail();
        $existingBlockedTime = $calendar->blockedTimes()->create([
            'starts_at' => '2026-08-10T10:00:00+02:00',
            'ends_at' => '2026-08-10T11:00:00+02:00',
            'reason' => 'Maintenance',
        ]);

        $this->getJson('/api/calendars?owner_id='.$payload['owner_id'].'&include=working_hours')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $calendar->id)
            ->assertJsonPath('data.0.working_hours.0.start_time', '09:00');

        $replacement = [
            'name' => 'Updated Calendar',
            'owner_id' => fake()->uuid(),
            'type' => 'shared',
            'timezone' => 'America/New_York',
        ];

        $this->putJson("/api/calendars/{$calendar->id}", [
            ...$replacement,
            'working_hours' => [
                ['day_of_week' => 'tuesday', 'start_time' => '08:00', 'end_time' => '16:00'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Calendar')
            ->assertJsonPath('data.working_hours.0.day_of_week', 'tuesday')
            ->assertJsonPath('data.blocked_times.0.id', $existingBlockedTime->id);

        $this->putJson("/api/calendars/{$calendar->id}", [
            ...$replacement,
            'blocked_times' => [
                [
                    'starts_at' => '2026-08-11T10:00:00+02:00',
                    'ends_at' => '2026-08-11T11:00:00+02:00',
                    'reason' => 'Vacation',
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonCount(0, 'data.working_hours')
            ->assertJsonPath('data.blocked_times.0.reason', 'Vacation');

        $this->putJson("/api/calendars/{$calendar->id}", ['name' => 'Partial Calendar'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['owner_id', 'type', 'timezone']);

        $this->assertSame($replacement, $calendar->fresh()->only(array_keys($replacement)));
        $this->assertModelMissing($existingBlockedTime);
        $this->assertCount(0, $calendar->fresh()->workingHours);
        $this->assertSame('Vacation', $calendar->fresh()->blockedTimes->sole()->reason);

        $this->getJson("/api/calendars/{$calendar->id}/working-hours")
            ->assertNotFound();

        $this->deleteJson("/api/calendars/{$calendar->id}")
            ->assertNoContent();

        $this->assertModelMissing($calendar);
    }

    public function test_blocked_times_can_be_managed_per_calendar(): void
    {
        $calendar = Calendar::factory()->create();

        $blockedTimePayload = [
            'starts_at' => '2026-08-10T10:00:00+02:00',
            'ends_at' => '2026-08-10T11:00:00+02:00',
            'reason' => 'Maintenance',
            'notes' => 'Room unavailable.',
        ];

        $this->postJson("/api/calendars/{$calendar->id}/blocked-times", $blockedTimePayload)
            ->assertCreated()
            ->assertJsonPath('data.reason', 'Maintenance');

        $blockedTime = BlockedTime::firstOrFail();

        $this->getJson("/api/calendars/{$calendar->id}/blocked-times?starts_from=2026-08-10T00:00:00%2B02:00")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $blockedTime->id);

        $replacement = [
            'starts_at' => '2026-08-11T10:00:00+02:00',
            'ends_at' => '2026-08-11T11:00:00+02:00',
            'reason' => 'Vacation',
        ];

        $this->putJson("/api/calendars/{$calendar->id}/blocked-times/{$blockedTime->id}", $replacement)
            ->assertOk()
            ->assertJsonPath('data.reason', 'Vacation');

        $this->assertSame('Vacation', $blockedTime->fresh()->reason);
        $this->assertNull($blockedTime->fresh()->notes);

        $this->putJson("/api/calendars/{$calendar->id}/blocked-times/{$blockedTime->id}", ['reason' => 'Maintenance'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);

        $this->assertSame('Vacation', $blockedTime->fresh()->reason);

        $this->deleteJson("/api/calendars/{$calendar->id}/blocked-times/{$blockedTime->id}")
            ->assertNoContent();

        $this->assertModelMissing($blockedTime);
    }

    public function test_availability_excludes_blocked_periods_and_rejects_invalid_time_ranges(): void
    {
        $calendar = Calendar::factory()->create(['timezone' => 'Europe/Berlin']);
        $calendar->workingHours()->create([
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);
        $calendar->blockedTimes()->create([
            'starts_at' => '2026-08-10T10:00:00+02:00',
            'ends_at' => '2026-08-10T11:00:00+02:00',
            'reason' => 'Maintenance',
        ]);

        $this->getJson("/api/calendars/{$calendar->id}/availability?starts_at=2026-08-10T09:00:00%2B02:00&ends_at=2026-08-10T12:00:00%2B02:00&duration_minutes=60&slot_interval_minutes=60")
            ->assertOk()
            ->assertJsonPath('meta.calendar_id', $calendar->id)
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['starts_at' => '2026-08-10T10:00:00+02:00']);

        $this->postJson('/api/calendars', [
            ...$this->calendarPayload(),
            'working_hours' => [
                ['day_of_week' => 'monday', 'start_time' => '17:00', 'end_time' => '09:00'],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('working_hours.0.end_time');

        $this->getJson("/api/calendars/{$calendar->id}/availability?starts_at=2026-08-10T12:00:00%2B02:00&ends_at=2026-08-10T09:00:00%2B02:00&duration_minutes=60")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_at');
    }

    /**
     * @return array{name: string, owner_id: string, type: string, timezone: string, working_hours: array<int, array{day_of_week: string, start_time: string, end_time: string}>}
     */
    private function calendarPayload(): array
    {
        return [
            'name' => 'Engineering Calendar',
            'owner_id' => fake()->uuid(),
            'type' => 'work',
            'timezone' => 'Europe/Berlin',
            'working_hours' => [
                ['day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '17:00'],
            ],
        ];
    }
}
