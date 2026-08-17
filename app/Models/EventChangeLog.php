<?php

namespace App\Models;

use Database\Factories\EventChangeLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $event_id
 * @property int $revision
 * @property array<string, mixed> $snapshot
 */
#[Fillable(['event_id', 'revision', 'snapshot', 'archived_at'])]
class EventChangeLog extends Model
{
    /** @use HasFactory<EventChangeLogFactory> */
    use HasFactory, HasUuids;

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $table = 'event_change_log';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'archived_at' => 'immutable_datetime',
        ];
    }
}
