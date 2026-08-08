<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCalendarRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', 'uuid'],
            'type' => ['required', 'in:work,personal,resource_booking,shared,availability,external_sync'],
            'timezone' => ['required', 'timezone'],
            'working_hours' => ['sometimes', 'array'],
            'working_hours.*' => ['array:day_of_week,start_time,end_time'],
            'working_hours.*.day_of_week' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday', 'distinct'],
            'working_hours.*.start_time' => ['required', 'date_format:H:i'],
            'working_hours.*.end_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return $this->workingHourValidation();
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    protected function workingHourValidation(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->input('working_hours', []) as $index => $workingHour) {
                    if (($workingHour['start_time'] ?? '') >= ($workingHour['end_time'] ?? '')) {
                        $validator->errors()->add("working_hours.{$index}.end_time", 'The end time must be after the start time.');
                    }
                }
            },
        ];
    }
}
