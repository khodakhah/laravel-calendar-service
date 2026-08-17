<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait EventRules
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function eventRules(bool $partial): array
    {
        $presence = $partial ? 'sometimes' : 'nullable';

        return [
            'parent_event_id' => [$presence, 'uuid'],
            'series_master_event_id' => [$presence, 'uuid'],
            'event_number' => [$presence, 'string', 'max:100'],
            'description' => [$presence, 'string'],
            'status' => [$presence, 'string', Rule::in(['draft', 'pending', 'tentative', 'approved', 'confirmed', 'processing', 'processed', 'completed', 'cancelled', 'rejected', 'no_show', 'expired', 'deleted', 'archived'])],
            'type' => [$presence, 'string', Rule::in(['single', 'recurring_master', 'recurring_instance', 'recurring_exception', 'hold', 'availability_block', 'external_sync'])],
            'all_day' => [$presence, 'boolean'],
            'blocks_availability' => ['sometimes', 'boolean'],
            'buffer_before_minutes' => [$presence, 'integer', 'min:0'],
            'buffer_after_minutes' => [$presence, 'integer', 'min:0'],
            'sources' => [$presence, 'array'],
            'sources.*.kind' => ['required_with:sources', 'string', 'max:100'],
            'sources.*.provider' => ['required_with:sources', 'string', 'max:100'],
            'sources.*.id' => ['required_with:sources', 'string', 'max:255'],
            'sources.*.synced_at' => ['sometimes', 'date'],
            'sources.*.metadata' => ['sometimes', 'array'],
            'assignees' => [$presence, 'array'],
            'assignees.*.kind' => ['required_with:assignees', 'string', 'max:100'],
            'assignees.*.id' => ['required_with:assignees', 'uuid'],
            'location' => [$presence, 'array'],
            'location.type' => ['sometimes', Rule::in(['in_person', 'virtual', 'hybrid'])],
            'location.label' => ['sometimes', 'string', 'max:255'],
            'location.address' => ['sometimes', 'string'],
            'location.meeting_url' => ['sometimes', 'url'],
            'recurrence' => [$presence, 'array'],
            'recurrence.frequency' => ['required_with:recurrence', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'recurrence.interval' => ['required_with:recurrence', 'integer', 'min:1'],
            'recurrence.count' => ['sometimes', 'integer', 'min:1'],
            'recurrence.until' => ['sometimes', 'date'],
            'recurrence.by_week_days' => ['sometimes', 'array'],
            'recurrence.by_week_days.*' => [Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'notes' => [$presence, 'string'],
            'metadata' => [$presence, 'array'],
        ];
    }
}
