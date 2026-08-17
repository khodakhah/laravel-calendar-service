<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListEventsRequest extends FormRequest
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
            'calendar_id' => ['sometimes', 'uuid'],
            'status' => ['sometimes', Rule::in(['draft', 'pending', 'tentative', 'approved', 'confirmed', 'processing', 'processed', 'completed', 'cancelled', 'rejected', 'no_show', 'expired', 'deleted', 'archived'])],
            'type' => ['sometimes', Rule::in(['single', 'recurring_master', 'recurring_instance', 'recurring_exception', 'hold', 'availability_block', 'external_sync'])],
            'starts_from' => ['sometimes', 'date'],
            'ends_before' => ['sometimes', 'date'],
            'assignee_id' => ['sometimes', 'uuid'],
            'created_by' => ['sometimes', 'uuid'],
            'updated_by' => ['sometimes', 'uuid'],
            'metadata' => ['sometimes', 'array'],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ((array) $this->input('metadata', []) as $key => $_) {
                    if (! preg_match('/^[A-Za-z0-9_-]+$/', (string) $key)) {
                        $validator->errors()->add('metadata', 'Metadata keys may only contain letters, numbers, hyphens, and underscores.');
                    }
                }
            },
        ];
    }
}
