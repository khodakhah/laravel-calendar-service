<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ListCalendarsRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'include' => ['sometimes', 'string'],
            'owner_id' => ['sometimes', 'uuid'],
            'type' => ['sometimes', 'in:work,personal,resource_booking,shared,availability,external_sync'],
            'timezone' => ['sometimes', 'timezone'],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $includes = array_filter(explode(',', (string) $this->input('include', '')));

                if (array_diff($includes, ['working_hours', 'blocked_times']) !== []) {
                    $validator->errors()->add('include', 'The include field contains an unsupported relation.');
                }
            },
        ];
    }
}
