<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'calendar_id' => $this->calendar_id,
            'sources' => $this->sources ?? [],
            'parent_event_id' => $this->when($this->parent_event_id !== null, $this->parent_event_id),
            'series_master_event_id' => $this->when($this->series_master_event_id !== null, $this->series_master_event_id),
            'event_number' => $this->when($this->event_number !== null, $this->event_number),
            'title' => $this->title,
            'description' => $this->when($this->description !== null, $this->description),
            'status' => $this->status,
            'revision' => $this->revision,
            'type' => $this->type,
            'timezone' => $this->timezone,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'all_day' => $this->all_day,
            'blocks_availability' => $this->blocks_availability,
            'duration_minutes' => $this->duration_minutes,
            'buffer_before_minutes' => $this->buffer_before_minutes,
            'buffer_after_minutes' => $this->buffer_after_minutes,
            'assignees' => $this->assignees ?? [],
            'created_by' => $this->created_by,
            'updated_by' => $this->when($this->updated_by !== null, $this->updated_by),
            'updated_reason' => $this->when($this->updated_reason !== null, $this->updated_reason),
            'location' => $this->when($this->location !== null, $this->location),
            'recurrence' => $this->when($this->recurrence !== null, $this->recurrence),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'notes' => $this->when($this->notes !== null, $this->notes),
            'metadata' => $this->metadata ?? [],
        ];
    }
}
