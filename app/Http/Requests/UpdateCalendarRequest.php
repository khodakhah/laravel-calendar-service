<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class UpdateCalendarRequest extends StoreCalendarRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'blocked_times' => ['sometimes', 'array'],
            'blocked_times.*' => ['array:starts_at,ends_at,reason,notes'],
            'blocked_times.*.starts_at' => ['required', 'date'],
            'blocked_times.*.ends_at' => ['required', 'date'],
            'blocked_times.*.reason' => ['required', 'string', 'max:255'],
            'blocked_times.*.notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                foreach ($this->input('blocked_times', []) as $index => $blockedTime) {
                    if (strtotime((string) ($blockedTime['starts_at'] ?? '')) >= strtotime((string) ($blockedTime['ends_at'] ?? ''))) {
                        $validator->errors()->add("blocked_times.{$index}.ends_at", 'The end time must be after the start time.');
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['working_hours' => []]);
    }
}
