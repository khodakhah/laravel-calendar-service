<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\EventRules;
use App\Models\Calendar;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEventRequest extends FormRequest
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
            'calendar_id' => ['required', 'uuid', Rule::exists(Calendar::class, 'id')],
            'title' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            ...$this->eventRules(false),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['calendar_id', 'starts_at', 'ends_at']) || ! $this->boolean('blocks_availability', true)) {
                    return;
                }

                $startsAt = CarbonImmutable::parse((string) $this->input('starts_at'))->utc();
                $endsAt = CarbonImmutable::parse((string) $this->input('ends_at'))->utc();

                if ($startsAt->greaterThanOrEqualTo($endsAt)) {
                    $validator->errors()->add('ends_at', 'The end time must be after the start time.');

                    return;
                }

                if (Event::query()
                    ->where('calendar_id', $this->input('calendar_id'))
                    ->blocksAvailability()
                    ->overlapping($startsAt, $endsAt)
                    ->exists()) {
                    $validator->errors()->add('starts_at', 'The calendar is unavailable for the selected time.');
                }
            },
        ];
    }
}
