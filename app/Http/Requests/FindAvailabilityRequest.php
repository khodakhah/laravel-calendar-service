<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FindAvailabilityRequest extends FormRequest
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
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'slot_interval_minutes' => ['sometimes', 'integer', 'min:1'],
            'timezone' => ['sometimes', 'timezone'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (strtotime((string) $this->input('starts_at')) >= strtotime((string) $this->input('ends_at'))) {
                    $validator->errors()->add('ends_at', 'The end time must be after the start time.');
                }
            },
        ];
    }
}
