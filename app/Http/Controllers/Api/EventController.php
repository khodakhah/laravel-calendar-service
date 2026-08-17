<?php

namespace App\Http\Controllers\Api;

use App\Actions\DeleteEvent;
use App\Actions\EnsureEventFitsCalendarAvailability;
use App\Actions\ReplaceEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListEventsRequest;
use App\Http\Requests\ShowEventRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Calendar;
use App\Models\Event;
use App\Models\EventChangeLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index(ListEventsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $events = Event::query()
            ->when(isset($filters['calendar_id']), fn ($query) => $query->where('calendar_id', $filters['calendar_id']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(isset($filters['starts_from']), fn ($query) => $query->where('starts_at', '>=', CarbonImmutable::parse($filters['starts_from'])->utc()))
            ->when(isset($filters['ends_before']), fn ($query) => $query->where('ends_at', '<=', CarbonImmutable::parse($filters['ends_before'])->utc()))
            ->when(isset($filters['assignee_id']), fn ($query) => $query->whereJsonContains('assignees', [['id' => $filters['assignee_id']]]))
            ->when(isset($filters['created_by']), fn ($query) => $query->whereJsonContains('created_by->id', $filters['created_by']))
            ->when(isset($filters['updated_by']), fn ($query) => $query->whereJsonContains('updated_by->id', $filters['updated_by']))
            ->when(isset($filters['metadata']), function ($query) use ($filters): void {
                foreach ($filters['metadata'] as $key => $value) {
                    $query->whereJsonContains('metadata->'.$key, $value);
                }
            })
            ->when(isset($filters['search']), fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('event_number', 'like', '%'.$filters['search'].'%')
                    ->orWhere('title', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'%'),
            ))
            ->latest()
            ->paginate($filters['per_page'] ?? 25);

        return response()->json([
            'data' => $events->getCollection()->map(
                fn (Event $event): array => (new EventResource($event))->resolve($request),
            )->values(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    public function store(
        StoreEventRequest $request,
        EnsureEventFitsCalendarAvailability $ensureEventFitsCalendarAvailability,
    ): JsonResponse {
        $data = $request->validated();
        $startsAt = CarbonImmutable::parse($data['starts_at'])->utc();
        $endsAt = CarbonImmutable::parse($data['ends_at'])->utc();
        $event = DB::transaction(function () use ($data, $startsAt, $endsAt, $ensureEventFitsCalendarAvailability): Event {
            $calendar = Calendar::query()
                ->lockForUpdate()
                ->findOrFail($data['calendar_id']);

            $ensureEventFitsCalendarAvailability->execute($calendar, $startsAt, $endsAt);

            if (($data['blocks_availability'] ?? true) && Event::query()
                ->where('calendar_id', $data['calendar_id'])
                ->blocksAvailability()
                ->overlapping($startsAt, $endsAt)
                ->exists()) {
                throw ValidationException::withMessages([
                    'starts_at' => 'The calendar is unavailable for the selected time.',
                ]);
            }

            return Event::create([
                ...$data,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_minutes' => (int) $startsAt->diffInMinutes($endsAt),
                'revision' => 1,
                'status' => $data['status'] ?? 'pending',
                'type' => $data['type'] ?? 'single',
                'all_day' => $data['all_day'] ?? false,
                'blocks_availability' => $data['blocks_availability'] ?? true,
                'buffer_before_minutes' => $data['buffer_before_minutes'] ?? 0,
                'buffer_after_minutes' => $data['buffer_after_minutes'] ?? 0,
                'sources' => $data['sources'] ?? [],
                'assignees' => $data['assignees'] ?? [],
                'metadata' => $data['metadata'] ?? [],
            ]);
        });

        return (new EventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ShowEventRequest $request, string $event): EventResource
    {
        $revision = $request->integer('revision');

        if ($revision === 0) {
            return new EventResource(Event::query()->findOrFail($event));
        }

        $changeLog = EventChangeLog::query()
            ->where('event_id', $event)
            ->where('revision', $revision)
            ->firstOrFail();

        $historicEvent = new Event;
        $historicEvent->forceFill($changeLog->snapshot);
        $historicEvent->exists = true;

        return new EventResource($historicEvent);
    }

    public function update(UpdateEventRequest $request, Event $event, ReplaceEvent $replaceEvent): EventResource
    {
        return new EventResource($replaceEvent->execute($event, $request->validated()));
    }

    public function destroy(Event $event, DeleteEvent $deleteEvent): JsonResponse
    {
        $deleteEvent->execute($event);

        return response()->json(status: 204);
    }
}
