<?php

use App\Http\Controllers\Api\CalendarAvailabilityController;
use App\Http\Controllers\Api\CalendarBlockedTimeController;
use App\Http\Controllers\Api\CalendarController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', function (): JsonResponse {
    return response()->json([
        'status' => 'ok',
    ]);
})->name('api.health');

Route::apiResource('calendars', CalendarController::class);

Route::prefix('calendars/{calendar}')->scopeBindings()->group(function (): void {
    Route::get('availability', CalendarAvailabilityController::class);
    Route::apiResource('blocked-times', CalendarBlockedTimeController::class)
        ->parameters(['blocked-times' => 'blockedTime']);
});
