<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OccurrencePreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->current_team_id !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'conditions' => ['nullable', 'array:match,description,category_id,payee_id,account_id,groups,exclude'],
            'conditions.match' => ['nullable', Rule::in(['all', 'any'])],
            'conditions.groups' => ['nullable', 'array', 'max:5'],
            'conditions.groups.*' => ['array:match,description,category_id,payee_id,account_id'],
            'conditions.groups.*.match' => ['nullable', Rule::in(['all', 'any'])],
            'conditions.exclude' => ['nullable', 'array:description,category_id,payee_id,account_id'],
        ];
        foreach (['conditions', 'conditions.groups.*', 'conditions.exclude'] as $prefix) {
            $rules["$prefix.description"] = ['nullable', 'array', 'max:10'];
            $rules["$prefix.description.*"] = ['array:operator,value'];
            $rules["$prefix.description.*.operator"] = ['nullable', Rule::in(['contains', 'is'])];
            $rules["$prefix.description.*.value"] = ['nullable', 'string', 'max:1000'];
            foreach (['category_id' => 'categories', 'payee_id' => 'payees', 'account_id' => 'accounts'] as $field => $table) {
                $rules["$prefix.$field"] = ['nullable', 'array', 'max:100'];
                $rules["$prefix.$field.*"] = ['integer', Rule::exists($table, 'id')->where('team_id', $this->user()->current_team_id)];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'conditions.array' => 'Use supported transaction matching conditions.',
            'conditions.*.in' => 'Choose a supported matching option.',
            '*.integer' => 'Choose a valid item.',
            '*.exists' => 'That item does not belong to this team.',
            '*.max' => 'This rule has too many conditions or its text is too long.',
        ];
    }
}
