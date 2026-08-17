<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $calendar_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property bool $blocks_availability
 * @property int $revision
 * @property int $duration_minutes
 */
#[Fillable([
    'calendar_id',
    'parent_event_id',
    'series_master_event_id',
    'event_number',
    'title',
    'description',
    'status',
    'type',
    'timezone',
    'starts_at',
    'ends_at',
    'all_day',
    'blocks_availability',
    'duration_minutes',
    'buffer_before_minutes',
    'buffer_after_minutes',
    'sources',
    'assignees',
    'created_by',
    'updated_by',
    'updated_reason',
    'location',
    'recurrence',
    'notes',
    'metadata',
    'revision',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUuids;

    /**
     * @var array<string, bool>
     */
    protected $attributes = [
        'blocks_availability' => true,
    ];

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    /**
     * @return HasMany<EventChangeLog, $this>
     */
    public function changeLogs(): HasMany
    {
        return $this->hasMany(EventChangeLog::class);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeBlocksAvailability(Builder $query): Builder
    {
        return $query->where('blocks_availability', true);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeOverlapping(Builder $query, CarbonImmutable $startsAt, CarbonImmutable $endsAt): Builder
    {
        return $query
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'all_day' => 'boolean',
            'blocks_availability' => 'boolean',
            'sources' => 'array',
            'assignees' => 'array',
            'created_by' => 'array',
            'updated_by' => 'array',
            'location' => 'array',
            'recurrence' => 'array',
            'metadata' => 'array',
        ];
    }
}
