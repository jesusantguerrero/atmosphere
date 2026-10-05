<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReconciliationEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['matched' => ['required', 'boolean']];
    }

    public function messages(): array
    {
        return ['matched.required' => 'Choose whether the movement matches.', 'matched.boolean' => 'The matching state must be true or false.'];
    }
}
