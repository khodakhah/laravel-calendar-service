<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BlockedTimeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $calendar_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property string $reason
 * @property string|null $notes
 */
#[Fillable(['starts_at', 'ends_at', 'reason', 'notes'])]
class BlockedTime extends Model
{
    /** @use HasFactory<BlockedTimeFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Calendar, $this>
     */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    protected function startsAt(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value) => CarbonImmutable::parse($value)->utc(),
        );
    }

    protected function endsAt(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value) => CarbonImmutable::parse($value)->utc(),
        );
    }
}
