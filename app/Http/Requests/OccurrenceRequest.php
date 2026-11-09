<?php

namespace App\Http\Requests;

use App\Domains\Housing\Models\Occurrence;
use Illuminate\Validation\Rule;

/**
 * Create or edit a rhythm (occurrence): its name, flags and the transaction
 * rule, validated like the rule preview (ids must belong to the team).
 */
class OccurrenceRequest extends OccurrencePreviewRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in([Occurrence::TYPE_UTILITY, Occurrence::TYPE_RELATIONSHIP])],
            'is_active' => ['sometimes', 'boolean'],
            'notify_on_avg' => ['sometimes', 'boolean'],
            'notify_on_last_count' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'name.required' => __('Give this rhythm a name.'),
            'name.max' => __('The name is too long.'),
            'type.in' => __('Choose a supported rhythm type.'),
        ];
    }
}
