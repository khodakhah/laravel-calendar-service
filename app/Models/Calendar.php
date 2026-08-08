<?php

namespace App\Models;

use Database\Factories\CalendarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string $owner_id
 * @property string $type
 * @property string $timezone
 * @property-read Collection<int, BlockedTime> $blockedTimes
 * @property-read Collection<int, WorkingHour> $workingHours
 */
#[Fillable(['name', 'owner_id', 'type', 'timezone'])]
class Calendar extends Model
{
    /** @use HasFactory<CalendarFactory> */
    use HasFactory, HasUuids;

    /**
     * @return HasMany<WorkingHour, $this>
     */
    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class)->orderBy('day_of_week');
    }

    /**
     * @return HasMany<BlockedTime, $this>
     */
    public function blockedTimes(): HasMany
    {
        return $this->hasMany(BlockedTime::class);
    }
}
