<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkPlannerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source' => 'nullable|string|max:64',
            'items' => 'required|array|min:1|max:100',
            'items.*.date' => 'required|date',
            'items.*.name' => 'required|string|max:255',
            'items.*.notes' => 'nullable|string|max:1000',
            'items.*.total' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one row before saving.',
            'items.*.date.required' => 'Each row needs a date.',
            'items.*.name.required' => 'Each row needs a name.',
        ];
    }
}
