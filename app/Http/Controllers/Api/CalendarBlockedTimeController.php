<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListBlockedTimesRequest;
use App\Http\Requests\StoreBlockedTimeRequest;
use App\Http\Requests\UpdateBlockedTimeRequest;
use App\Http\Resources\BlockedTimeResource;
use App\Models\BlockedTime;
use App\Models\Calendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class CalendarBlockedTimeController extends Controller
{
    public function index(ListBlockedTimesRequest $request, Calendar $calendar): JsonResponse
    {
        $filters = $request->validated();
        $blockedTimes = $calendar->blockedTimes()
            ->when(
                isset($filters['starts_from']),
                fn ($query) => $query->where('starts_at', '>=', CarbonImmutable::parse($filters['starts_from'])->utc()),
            )
            ->when(
                isset($filters['ends_before']),
                fn ($query) => $query->where('ends_at', '<=', CarbonImmutable::parse($filters['ends_before'])->utc()),
            )
            ->orderBy('starts_at')
            ->paginate($filters['per_page'] ?? 25);

        return response()->json([
            'data' => $blockedTimes->getCollection()->map(
                fn (BlockedTime $blockedTime): array => (new BlockedTimeResource($blockedTime))->resolve($request),
            )->values(),
            'meta' => [
                'current_page' => $blockedTimes->currentPage(),
                'per_page' => $blockedTimes->perPage(),
                'total' => $blockedTimes->total(),
            ],
        ]);
    }

    public function store(StoreBlockedTimeRequest $request, Calendar $calendar): JsonResponse
    {
        $blockedTime = $calendar->blockedTimes()->create($request->validated());

        return (new BlockedTimeResource($blockedTime))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Calendar $calendar, BlockedTime $blockedTime): BlockedTimeResource
    {
        return new BlockedTimeResource($blockedTime);
    }

    public function update(UpdateBlockedTimeRequest $request, Calendar $calendar, BlockedTime $blockedTime): BlockedTimeResource
    {
        $blockedTime->update($request->validated());

        return new BlockedTimeResource($blockedTime);
    }

    public function destroy(Calendar $calendar, BlockedTime $blockedTime): JsonResponse
    {
        $blockedTime->delete();

        return response()->json(status: 204);
    }
}
