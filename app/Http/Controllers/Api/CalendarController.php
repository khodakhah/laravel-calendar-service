<?php

namespace App\Http\Controllers\Api;

use App\Actions\ReplaceCalendar;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListCalendarsRequest;
use App\Http\Requests\StoreCalendarRequest;
use App\Http\Requests\UpdateCalendarRequest;
use App\Http\Resources\CalendarResource;
use App\Models\Calendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function index(ListCalendarsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $calendars = Calendar::query()
            ->when(isset($filters['owner_id']), fn ($query) => $query->where('owner_id', $filters['owner_id']))
            ->when(isset($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(isset($filters['timezone']), fn ($query) => $query->where('timezone', $filters['timezone']))
            ->when(isset($filters['search']), fn ($query) => $query->where('name', 'like', '%'.$filters['search'].'%'))
            ->when(isset($filters['include']), fn ($query) => $query->with($this->relations($filters['include'])))
            ->latest()
            ->paginate($filters['per_page'] ?? 25);

        return response()->json([
            'data' => $calendars->getCollection()->map(
                fn (Calendar $calendar): array => (new CalendarResource($calendar))->resolve($request),
            )->values(),
            'meta' => [
                'current_page' => $calendars->currentPage(),
                'per_page' => $calendars->perPage(),
                'total' => $calendars->total(),
            ],
        ]);
    }

    public function store(StoreCalendarRequest $request): JsonResponse
    {
        $data = $request->validated();
        $calendar = DB::transaction(function () use ($data): Calendar {
            $calendar = Calendar::create(Arr::except($data, 'working_hours'));
            $calendar->workingHours()->createMany($data['working_hours'] ?? []);

            return $calendar;
        });

        return (new CalendarResource($calendar->load('workingHours')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Calendar $calendar): CalendarResource
    {
        if ($request->filled('include')) {
            $calendar->load($this->relations((string) $request->input('include')));
        }

        return new CalendarResource($calendar);
    }

    public function update(UpdateCalendarRequest $request, Calendar $calendar, ReplaceCalendar $replaceCalendar): CalendarResource
    {
        return new CalendarResource($replaceCalendar->execute($calendar, $request->validated()));
    }

    public function destroy(Calendar $calendar): JsonResponse
    {
        $calendar->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<int, string>
     */
    private function relations(string $includes): array
    {
        return array_values(array_filter(array_map(
            static fn (string $include): ?string => match ($include) {
                'working_hours' => 'workingHours',
                'blocked_times' => 'blockedTimes',
                default => null,
            },
            explode(',', $includes),
        )));
    }
}
