<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\EventRules;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends FormRequest
{
    use EventRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'updated_reason' => ['sometimes', 'nullable', 'string', 'max:500'],
            'timezone' => ['sometimes', 'timezone'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
            ...$this->eventRules(true),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['starts_at', 'ends_at'])) {
                    return;
                }

                /** @var Event $event */
                $event = $this->route('event');
                $startsAt = CarbonImmutable::parse($this->input('starts_at', $event->starts_at))->utc();
                $endsAt = CarbonImmutable::parse($this->input('ends_at', $event->ends_at))->utc();

                if ($startsAt->greaterThanOrEqualTo($endsAt)) {
                    $validator->errors()->add('ends_at', 'The end time must be after the start time.');

                    return;
                }

                if (! $this->boolean('blocks_availability', $event->blocks_availability)) {
                    return;
                }

                if (Event::query()
                    ->where('calendar_id', $event->calendar_id)
                    ->whereKeyNot($event->getKey())
                    ->blocksAvailability()
                    ->overlapping($startsAt, $endsAt)
                    ->exists()) {
                    $validator->errors()->add('starts_at', 'The calendar is unavailable for the selected time.');
                }
            },
        ];
    }
}
